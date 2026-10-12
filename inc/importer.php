<?php
/**
 * দলিল লেখক → CSV ইমপোর্ট
 *
 * Creates (or updates) deed writer profiles from a CSV file.
 * Columns: name, cert, mobile, address, area, whatsapp, facebook, youtube, photo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sro_import_menu() {
	add_submenu_page(
		'edit.php?post_type=deed_writer',
		'CSV থেকে ইমপোর্ট',
		'CSV ইমপোর্ট',
		'manage_options',
		'sro-import',
		'sro_import_page'
	);
}
add_action( 'admin_menu', 'sro_import_menu' );

function sro_import_back( $args = array() ) {
	$url = add_query_arg(
		array_merge( array( 'post_type' => 'deed_writer', 'page' => 'sro-import' ), $args ),
		admin_url( 'edit.php' )
	);
	wp_safe_redirect( $url );
	exit;
}

/**
 * Map a CSV header cell to a field key.
 */
function sro_import_field( $header ) {
	$h   = strtolower( trim( preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header ) ) );
	$map = array(
		'name'    => 'name',    'নাম'      => 'name',
		'cert'    => 'cert',    'সনদ'      => 'cert',    'সনদ নম্বর' => 'cert',
		'mobile'  => 'mobile',  'মোবাইল'   => 'mobile',
		'address' => 'address', 'ঠিকানা'   => 'address',
		'area'    => 'area',    'এলাকা'    => 'area',
		'social'  => 'facebook', 'সোশ্যাল'  => 'facebook',
		'whatsapp' => 'whatsapp', 'হোয়াটসঅ্যাপ' => 'whatsapp',
		'facebook' => 'facebook', 'ফেসবুক' => 'facebook',
		'youtube' => 'youtube', 'ইউটিউব' => 'youtube',
		'photo'   => 'photo',   'ছবি'      => 'photo',
	);
	return isset( $map[ $h ] ) ? $map[ $h ] : '';
}

/**
 * Find an existing profile by certificate number, else by mobile number.
 */
function sro_import_find( $cert, $mobile ) {
	foreach ( array( '_sro_cert' => $cert, '_sro_mobile' => $mobile ) as $key => $value ) {
		if ( '' === $value ) {
			continue;
		}
		$found = get_posts(
			array(
				'post_type'      => 'deed_writer',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => $key,
				'meta_value'     => $value,
			)
		);
		if ( $found ) {
			return (int) $found[0];
		}
	}
	return 0;
}

function sro_import_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_import' );

	if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) {
		sro_import_back( array( 'sro_err' => 'file' ) );
	}
	$name = isset( $_FILES['csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ) : '';
	if ( 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
		sro_import_back( array( 'sro_err' => 'type' ) );
	}
	if ( (int) $_FILES['csv']['size'] > 2 * MB_IN_BYTES ) {
		sro_import_back( array( 'sro_err' => 'size' ) );
	}

	$content = (string) file_get_contents( $_FILES['csv']['tmp_name'] );
	$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );
	$lines   = preg_split( '/\r\n|\r|\n/', $content );
	$lines   = array_values( array_filter( $lines, 'strlen' ) );
	if ( count( $lines ) < 2 ) {
		sro_import_back( array( 'sro_err' => 'empty' ) );
	}

	$columns = array();
	foreach ( str_getcsv( array_shift( $lines ) ) as $i => $cell ) {
		$field = sro_import_field( $cell );
		if ( $field ) {
			$columns[ $i ] = $field;
		}
	}
	if ( ! in_array( 'name', $columns, true ) ) {
		sro_import_back( array( 'sro_err' => 'header' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
	@set_time_limit( 300 );

	$created = 0;
	$updated = 0;
	$skipped = 0;
	$photos  = 0;

	foreach ( $lines as $line ) {
		$row = array(
			'name'    => '',
			'cert'    => '',
			'mobile'  => '',
			'address' => '',
			'area'    => '',
			'whatsapp' => '',
			'facebook' => '',
			'youtube' => '',
			'photo'   => '',
		);
		foreach ( str_getcsv( $line ) as $i => $cell ) {
			if ( isset( $columns[ $i ] ) ) {
				$row[ $columns[ $i ] ] = trim( (string) $cell );
			}
		}
		if ( '' === $row['name'] ) {
			$skipped++;
			continue;
		}

		$post_id = sro_import_find( sanitize_text_field( $row['cert'] ), sanitize_text_field( $row['mobile'] ) );
		if ( $post_id ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => sanitize_text_field( $row['name'] ),
				)
			);
			$updated++;
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'deed_writer',
					'post_status' => 'publish',
					'post_title'  => sanitize_text_field( $row['name'] ),
				)
			);
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$skipped++;
				continue;
			}
			$created++;
		}

		update_post_meta( $post_id, '_sro_cert', sanitize_text_field( $row['cert'] ) );
		update_post_meta( $post_id, '_sro_mobile', sanitize_text_field( $row['mobile'] ) );
		update_post_meta( $post_id, '_sro_address', sanitize_text_field( $row['address'] ) );
		foreach ( array( 'whatsapp', 'facebook', 'youtube' ) as $net ) {
			if ( '' !== $row[ $net ] ) {
				update_post_meta( $post_id, '_sro_' . $net, sanitize_text_field( $row[ $net ] ) );
			}
		}

		if ( '' !== $row['area'] ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $row['area'] ), 'writer_area' );
		}

		if ( '' !== $row['photo'] && ! has_post_thumbnail( $post_id ) && preg_match( '#^https?://#i', $row['photo'] ) ) {
			$att = media_sideload_image( esc_url_raw( $row['photo'] ), $post_id, sanitize_text_field( $row['name'] ), 'id' );
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $post_id, (int) $att );
				$photos++;
			}
		}
	}

	sro_import_back(
		array(
			'sro_created' => $created,
			'sro_updated' => $updated,
			'sro_skipped' => $skipped,
			'sro_photos'  => $photos,
		)
	);
}
add_action( 'admin_post_sro_import', 'sro_import_handle' );

function sro_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$err     = isset( $_GET['sro_err'] ) ? sanitize_key( wp_unslash( $_GET['sro_err'] ) ) : '';
	$done    = isset( $_GET['sro_created'] );
	$created = isset( $_GET['sro_created'] ) ? (int) $_GET['sro_created'] : 0;
	$updated = isset( $_GET['sro_updated'] ) ? (int) $_GET['sro_updated'] : 0;
	$skipped = isset( $_GET['sro_skipped'] ) ? (int) $_GET['sro_skipped'] : 0;
	$photos  = isset( $_GET['sro_photos'] ) ? (int) $_GET['sro_photos'] : 0;
	// phpcs:enable
	$errors = array(
		'file'   => 'ফাইল আপলোড হয়নি।',
		'type'   => 'শুধু .csv ফাইল দিন।',
		'size'   => 'ফাইল ২ MB এর বেশি।',
		'empty'  => 'ফাইলে কোনো সারি নেই।',
		'header' => 'প্রথম সারিতে "name" (নাম) কলাম থাকতে হবে।',
	);
	?>
	<div class="wrap">
		<h1>দলিল লেখক CSV ইমপোর্ট</h1>

		<?php if ( $err && isset( $errors[ $err ] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $errors[ $err ] ); ?></p></div>
		<?php elseif ( $done ) : ?>
			<div class="notice notice-success">
				<p>
					নতুন তৈরি: <strong><?php echo esc_html( $created ); ?></strong>,
					আপডেট: <strong><?php echo esc_html( $updated ); ?></strong>,
					ছবি যুক্ত: <strong><?php echo esc_html( $photos ); ?></strong>,
					বাদ (নাম নেই): <strong><?php echo esc_html( $skipped ); ?></strong>
				</p>
			</div>
		<?php endif; ?>

		<p>প্রথম সারিতে কলামের নাম থাকতে হবে (ফাইল UTF-8 এ সেভ করুন):</p>
		<p><code>name, cert, mobile, address, area, whatsapp, facebook, youtube, photo</code></p>
		<ul style="list-style:disc;margin-left:20px;">
			<li><strong>name</strong>: নাম (বাধ্যতামূলক)</li>
			<li><strong>cert</strong>: সনদ নম্বর, <strong>mobile</strong>: মোবাইল, <strong>address</strong>: ঠিকানা</li>
			<li><strong>area</strong>: এলাকা (না থাকলে নতুন তৈরি হবে)</li>
			<li><strong>whatsapp</strong>: হোয়াটসঅ্যাপ নম্বর বা লিংক, <strong>facebook</strong> ও <strong>youtube</strong>: লিংক (https:// দিয়ে)</li>
			<li><strong>photo</strong>: ছবির লিংক (ঐচ্ছিক)</li>
		</ul>
		<p>একই সনদ নম্বর বা মোবাইল নম্বর থাকলে নতুন না বানিয়ে ওই প্রোফাইল আপডেট হবে, তাই আবার ইমপোর্ট করলে ডুপ্লিকেট হবে না।</p>

		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sro_import">
			<?php wp_nonce_field( 'sro_import' ); ?>
			<p><input type="file" name="csv" accept=".csv,text/csv" required></p>
			<p><button class="button button-primary" type="submit">ইমপোর্ট করুন</button></p>
		</form>
	</div>
	<?php
}
