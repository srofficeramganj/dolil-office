<?php
/**
 * Appearance → থিম সিঙ্ক
 *
 * Shows the installed commit and the latest GitHub commit, and lets an admin
 * pull the latest theme files from the public GitHub repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SRO_GH_OWNER', 'srofficeramganj' );
define( 'SRO_GH_REPO', 'dolil-office' );
define( 'SRO_GH_BRANCH', 'main' );

/**
 * Commit currently installed on this site (40-char sha or '').
 */
function sro_installed_sha() {
	$git  = get_template_directory() . '/.git';
	$head = $git . '/HEAD';
	if ( is_dir( $git ) && is_readable( $head ) ) {
		$content = trim( (string) file_get_contents( $head ) );
		if ( preg_match( '/^[0-9a-f]{40}$/', $content ) ) {
			return $content;
		}
		if ( preg_match( '/^ref: (refs\/[A-Za-z0-9._\/-]+)$/', $content, $m ) ) {
			$ref = $git . '/' . $m[1];
			if ( is_readable( $ref ) ) {
				$sha = trim( (string) file_get_contents( $ref ) );
				if ( preg_match( '/^[0-9a-f]{40}$/', $sha ) ) {
					return $sha;
				}
			}
		}
	}
	$sha = (string) get_option( 'sro_installed_sha', '' );
	return preg_match( '/^[0-9a-f]{40}$/', $sha ) ? $sha : '';
}

/**
 * Latest commit on the GitHub branch (cached 5 minutes).
 *
 * @return array|WP_Error { sha, message, date }
 */
function sro_latest_commit( $force = false ) {
	if ( ! $force ) {
		$cached = get_transient( 'sro_latest_commit' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}
	$url      = sprintf( 'https://api.github.com/repos/%s/%s/commits/%s', SRO_GH_OWNER, SRO_GH_REPO, SRO_GH_BRANCH );
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'sr-office-ramganj-theme',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $code || ! is_array( $body ) || empty( $body['sha'] ) || ! preg_match( '/^[0-9a-f]{40}$/', $body['sha'] ) ) {
		return new WP_Error( 'sro_github', 'GitHub থেকে তথ্য আনা যায়নি (কোড ' . $code . ')।' );
	}
	$data = array(
		'sha'     => $body['sha'],
		'message' => isset( $body['commit']['message'] ) ? strtok( (string) $body['commit']['message'], "\n" ) : '',
		'date'    => isset( $body['commit']['committer']['date'] ) ? (string) $body['commit']['committer']['date'] : '',
	);
	set_transient( 'sro_latest_commit', $data, 5 * MINUTE_IN_SECONDS );
	return $data;
}

/**
 * Menu: Appearance → থিম সিঙ্ক.
 */
function sro_sync_menu() {
	add_theme_page( 'থিম সিঙ্ক', 'থিম সিঙ্ক', 'manage_options', 'sro-sync', 'sro_sync_page' );
}
add_action( 'admin_menu', 'sro_sync_menu' );

function sro_sync_redirect( $type, $msg ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'      => 'sro-sync',
				'sro_type'  => $type,
				'sro_msg'   => rawurlencode( $msg ),
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}

/**
 * "Check now": clear cache and re-ask GitHub.
 */
function sro_sync_check() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_sync' );
	delete_transient( 'sro_latest_commit' );
	$latest = sro_latest_commit( true );
	if ( is_wp_error( $latest ) ) {
		sro_sync_redirect( 'error', $latest->get_error_message() );
	}
	sro_sync_redirect( 'success', 'সর্বশেষ কমিট চেক করা হয়েছে।' );
}
add_action( 'admin_post_sro_sync_check', 'sro_sync_check' );

/**
 * "Update now": download the latest commit as ZIP and copy it over the theme.
 */
function sro_sync_update() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_sync' );

	require_once ABSPATH . 'wp-admin/includes/file.php';

	$latest = sro_latest_commit( true );
	if ( is_wp_error( $latest ) ) {
		sro_sync_redirect( 'error', $latest->get_error_message() );
	}
	$sha = $latest['sha'];

	$zip_url = sprintf( 'https://github.com/%s/%s/archive/%s.zip', SRO_GH_OWNER, SRO_GH_REPO, $sha );
	$tmp     = download_url( $zip_url, 60 );
	if ( is_wp_error( $tmp ) ) {
		sro_sync_redirect( 'error', 'ডাউনলোড ব্যর্থ: ' . $tmp->get_error_message() );
	}

	if ( ! WP_Filesystem() ) {
		wp_delete_file( $tmp );
		sro_sync_redirect( 'error', 'ফাইল সিস্টেমে লেখার অনুমতি পাওয়া যায়নি।' );
	}
	global $wp_filesystem;

	$work   = trailingslashit( get_temp_dir() ) . 'sro-sync-' . wp_generate_password( 8, false );
	$result = unzip_file( $tmp, $work );
	wp_delete_file( $tmp );
	if ( is_wp_error( $result ) ) {
		sro_sync_redirect( 'error', 'ZIP খোলা যায়নি: ' . $result->get_error_message() );
	}

	$dirs = glob( $work . '/*', GLOB_ONLYDIR );
	$src  = $dirs ? $dirs[0] : '';
	if ( ! $src || ! file_exists( $src . '/style.css' ) || ! file_exists( $src . '/functions.php' ) ) {
		$wp_filesystem->delete( $work, true );
		sro_sync_redirect( 'error', 'ডাউনলোড করা ফাইল সঠিক থিম নয়, আপডেট বাতিল করা হয়েছে।' );
	}

	$copied = copy_dir( $src, untrailingslashit( get_template_directory() ) );
	$wp_filesystem->delete( $work, true );
	if ( is_wp_error( $copied ) ) {
		sro_sync_redirect( 'error', 'ফাইল কপি ব্যর্থ: ' . $copied->get_error_message() );
	}

	update_option( 'sro_installed_sha', $sha, false );
	delete_transient( 'sro_latest_commit' );
	if ( function_exists( 'wp_opcache_invalidate_directory' ) ) {
		wp_opcache_invalidate_directory( get_template_directory() );
	}
	do_action( 'litespeed_purge_all' ); // No-op unless LiteSpeed Cache is active.

	sro_sync_redirect( 'success', 'থিম আপডেট হয়েছে: ' . substr( $sha, 0, 7 ) );
}
add_action( 'admin_post_sro_sync_update', 'sro_sync_update' );

/**
 * The admin page.
 */
function sro_sync_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$installed = sro_installed_sha();
	$latest    = sro_latest_commit();
	$repo_url  = sprintf( 'https://github.com/%s/%s', SRO_GH_OWNER, SRO_GH_REPO );
	$theme     = wp_get_theme();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$type = isset( $_GET['sro_type'] ) ? sanitize_key( wp_unslash( $_GET['sro_type'] ) ) : '';
	$msg  = isset( $_GET['sro_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['sro_msg'] ) ) ) : '';
	// phpcs:enable

	$up_to_date = $installed && ! is_wp_error( $latest ) && $installed === $latest['sha'];
	?>
	<div class="wrap">
		<h1>থিম সিঙ্ক</h1>

		<?php if ( $msg ) : ?>
			<div class="notice notice-<?php echo 'success' === $type ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:760px;">
			<tbody>
				<tr>
					<th style="width:220px;">থিমের ভার্সন</th>
					<td><?php echo esc_html( $theme->get( 'Version' ) ); ?></td>
				</tr>
				<tr>
					<th>এই সাইটে ইনস্টল করা কমিট</th>
					<td>
						<?php if ( $installed ) : ?>
							<a href="<?php echo esc_url( $repo_url . '/commit/' . $installed ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( substr( $installed, 0, 7 ) ); ?></code></a>
						<?php else : ?>
							অজানা (একবার "এখনই আপডেট করুন" চাপলে জানা যাবে)
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>GitHub-এর সর্বশেষ কমিট</th>
					<td>
						<?php if ( is_wp_error( $latest ) ) : ?>
							<span style="color:#b32d2e;"><?php echo esc_html( $latest->get_error_message() ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $repo_url . '/commit/' . $latest['sha'] ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( substr( $latest['sha'], 0, 7 ) ); ?></code></a>
							&mdash; <?php echo esc_html( $latest['message'] ); ?>
							<?php if ( $latest['date'] ) : ?>
								<br><small><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $latest['date'] ) ), 'd/m/Y h:i A' ) ); ?></small>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>অবস্থা</th>
					<td>
						<?php
						if ( is_wp_error( $latest ) ) {
							echo 'যাচাই করা যায়নি';
						} elseif ( $up_to_date ) {
							echo '<strong style="color:#1a7f37;">&#10003; আপ-টু-ডেট</strong>';
						} else {
							echo '<strong style="color:#b45309;">নতুন আপডেট পাওয়া যাচ্ছে</strong>';
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>

		<div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sro_sync_check">
				<?php wp_nonce_field( 'sro_sync' ); ?>
				<button class="button" type="submit">এখন চেক করুন</button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('GitHub থেকে সর্বশেষ থিম ফাইল এনে এই সাইটের থিম আপডেট করা হবে। চালিয়ে যাবেন?');">
				<input type="hidden" name="action" value="sro_sync_update">
				<?php wp_nonce_field( 'sro_sync' ); ?>
				<button class="button button-primary" type="submit"<?php disabled( $up_to_date ); ?>>এখনই আপডেট করুন</button>
			</form>
		</div>

		<p class="description" style="max-width:760px;">
			রিপোজিটরি: <a href="<?php echo esc_url( $repo_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( SRO_GH_OWNER . '/' . SRO_GH_REPO ); ?></a> (branch: <?php echo esc_html( SRO_GH_BRANCH ); ?>)।
			আপডেটে শুধু থিমের ফাইল বদলায়; দলিল লেখক, ছবি ও সেটিংস অপরিবর্তিত থাকে।
		</p>
	</div>
	<?php
}
