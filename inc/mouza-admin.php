<?php
/**
 * Dashboard: view, search, correct, add and delete mouza rates; export backup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editable columns of a rate row.
 */
function sro_mouza_columns() {
	return array(
		'district'         => array( 'জেলা', 90 ),
		'upazila'          => array( 'উপজেলা', 90 ),
		'mouza_name'       => array( 'মৌজার নাম', 130 ),
		'mouza_code'       => array( 'মৌজা কোড', 70 ),
		'land_category'    => array( 'জমির শ্রেণি', 130 ),
		'rate'             => array( 'রেট (টাকা)', 90 ),
		'rate_unit'        => array( 'রেটের একক', 100 ),
		'effective_period' => array( 'কার্যকর সময়কাল', 110 ),
		'source_ref'       => array( 'তথ্যের উৎস', 150 ),
		'last_verified'    => array( 'সর্বশেষ যাচাই', 100 ),
	);
}

/**
 * Validate and clean one submitted row. Returns array or WP_Error.
 */
function sro_mouza_clean_row( $src ) {
	$row = array();
	foreach ( array_keys( sro_mouza_columns() ) as $key ) {
		$value = isset( $src[ $key ] ) ? trim( wp_unslash( (string) $src[ $key ] ) ) : '';
		if ( 'rate' === $key ) {
			$value = sro_mouza_digits( str_replace( ',', '', $value ) );
			if ( '' === $value || ! is_numeric( $value ) || (float) $value < 0 ) {
				return new WP_Error( 'rate', 'রেট একটি সঠিক সংখ্যা হতে হবে।' );
			}
			$row[ $key ] = (float) $value;
		} elseif ( 'source_ref' === $key ) {
			$row[ $key ] = sanitize_textarea_field( $value );
		} else {
			$row[ $key ] = sanitize_text_field( $value );
		}
	}
	foreach ( array( 'district' => 'জেলা', 'upazila' => 'উপজেলা', 'mouza_name' => 'মৌজার নাম', 'land_category' => 'জমির শ্রেণি', 'rate_unit' => 'রেটের একক' ) as $k => $label ) {
		if ( '' === $row[ $k ] ) {
			return new WP_Error( $k, $label . ' খালি রাখা যাবে না।' );
		}
	}
	return $row;
}

function sro_mouza_ajax_save() {
	check_ajax_referer( 'sro_mouza_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'অনুমতি নেই।' ), 403 );
	}
	$row = sro_mouza_clean_row( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( is_wp_error( $row ) ) {
		wp_send_json_error( array( 'message' => $row->get_error_message() ) );
	}
	global $wpdb;
	$table = sro_mouza_table();
	$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $id ) {
		$ok = $wpdb->update( $table, $row, array( 'id' => $id ) );
	} else {
		$ok = $wpdb->insert( $table, $row );
		$id = (int) $wpdb->insert_id;
	}
	if ( false === $ok ) {
		wp_send_json_error( array( 'message' => 'সংরক্ষণ করা যায়নি।' ) );
	}
	wp_send_json_success( array( 'id' => $id ) );
}
add_action( 'wp_ajax_sro_mouza_save', 'sro_mouza_ajax_save' );

function sro_mouza_ajax_delete() {
	check_ajax_referer( 'sro_mouza_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'অনুমতি নেই।' ), 403 );
	}
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => 'সারি পাওয়া যায়নি।' ) );
	}
	global $wpdb;
	$wpdb->delete( sro_mouza_table(), array( 'id' => $id ) );
	wp_send_json_success();
}
add_action( 'wp_ajax_sro_mouza_delete', 'sro_mouza_ajax_delete' );

/**
 * Backup: download every rate as CSV (same columns the importer reads).
 */
function sro_mouza_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_mouza_export' );
	global $wpdb;
	$table = sro_mouza_table();
	$rows  = $wpdb->get_results( "SELECT district, upazila, mouza_name, mouza_code, land_category, rate, rate_unit, effective_period, source_ref, last_verified FROM {$table} ORDER BY district, upazila, mouza_name, land_category", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="mouza-rates-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'district', 'upazila', 'mouza_name', 'mouza_code', 'land_category', 'rate', 'rate_unit', 'effective_period', 'source_reference', 'last_verified' ) );
	foreach ( (array) $rows as $r ) {
		fputcsv( $out, array_values( $r ) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_sro_mouza_export', 'sro_mouza_export' );

/**
 * Admin page: list + inline correction.
 */
function sro_mouza_list_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$table   = sro_mouza_table();
	$columns = sro_mouza_columns();
	$per     = 50;

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$upz    = isset( $_GET['upazila'] ) ? sanitize_text_field( wp_unslash( $_GET['upazila'] ) ) : '';
	$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	// phpcs:enable

	$where  = array( '1=1' );
	$params = array();
	if ( '' !== $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$where[]  = '(mouza_name LIKE %s OR land_category LIKE %s OR mouza_code LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}
	if ( '' !== $upz ) {
		$where[]  = 'upazila = %s';
		$params[] = $upz;
	}
	$where_sql = implode( ' AND ', $where );

	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$list_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY district, upazila, mouza_name, land_category LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows     = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $params, array( $per, ( $paged - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$pages    = max( 1, (int) ceil( $total / $per ) );

	$upazilas = $wpdb->get_col( "SELECT DISTINCT upazila FROM {$table} ORDER BY upazila" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$nonce    = wp_create_nonce( 'sro_mouza_admin' );

	$input = function ( $key, $value, $width ) {
		return sprintf(
			'<input type="text" name="%1$s" value="%2$s" style="min-width:%3$dpx;width:100%%" %4$s>',
			esc_attr( $key ),
			esc_attr( $value ),
			(int) $width,
			'rate' === $key ? 'inputmode="decimal"' : ''
		);
	};
	?>
	<div class="wrap sro-mouza-admin" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
		<h1 class="wp-heading-inline">মৌজা রেট: দেখুন ও সংশোধন করুন</h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=sro-mouza-import' ) ); ?>">CSV/JSON ইমপোর্ট</a>
		<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sro_mouza_export' ), 'sro_mouza_export' ) ); ?>">ব্যাকআপ ডাউনলোড (CSV)</a>
		<hr class="wp-header-end">

		<p>কোনো ঘরের মান বদলালে ওই সারির শেষে <strong>"সংরক্ষণ"</strong> বাটন চাপুন। শুধু যাচাইকৃত সরকারি তথ্য দিন। রেটের একক যেমন: <em>প্রতি শতাংশ</em>, <em>প্রতি একর</em>।</p>

		<form method="get" class="sro-filter">
			<input type="hidden" name="page" value="sro-mouza">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="মৌজা, শ্রেণি বা কোড খুঁজুন" style="min-width:260px">
			<select name="upazila">
				<option value="">সব উপজেলা</option>
				<?php foreach ( (array) $upazilas as $u ) : ?>
					<option value="<?php echo esc_attr( $u ); ?>"<?php selected( $upz, $u ); ?>><?php echo esc_html( $u ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="button" type="submit">খুঁজুন</button>
			<?php if ( '' !== $search || '' !== $upz ) : ?>
				<a class="button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sro-mouza' ) ); ?>">ফিল্টার পরিষ্কার</a>
			<?php endif; ?>
			<span class="description">মোট <?php echo esc_html( $total ); ?> টি রেট</span>
		</form>

		<div class="sro-table-wrap">
			<table class="widefat striped sro-rates">
				<thead>
					<tr>
						<?php foreach ( $columns as $c ) : ?><th><?php echo esc_html( $c[0] ); ?></th><?php endforeach; ?>
						<th style="min-width:150px">কাজ</th>
					</tr>
				</thead>
				<tbody>
					<tr class="sro-new" data-id="0">
						<?php foreach ( $columns as $key => $c ) : ?>
							<td>
								<?php
								$default = '';
								if ( 'district' === $key ) {
									$default = 'লক্ষ্মীপুর';
								} elseif ( 'upazila' === $key ) {
									$default = 'রামগঞ্জ';
								}
								echo $input( $key, $default, $c[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</td>
						<?php endforeach; ?>
						<td><button type="button" class="button button-primary sro-save">+ নতুন রেট যোগ করুন</button><span class="sro-msg"></span></td>
					</tr>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="<?php echo esc_attr( count( $columns ) + 1 ); ?>">কোনো রেট পাওয়া যায়নি।<?php echo ( 0 === $total && '' === $search && '' === $upz ) ? ' উপরের সারিতে নতুন রেট যোগ করুন বা CSV ইমপোর্ট করুন।' : ''; ?></td></tr>
					<?php endif; ?>
					<?php foreach ( (array) $rows as $r ) : ?>
						<tr data-id="<?php echo esc_attr( $r->id ); ?>">
							<?php foreach ( $columns as $key => $c ) : ?>
								<td><?php echo $input( $key, $r->$key, $c[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<?php endforeach; ?>
							<td>
								<button type="button" class="button button-primary sro-save">সংরক্ষণ</button>
								<button type="button" class="button-link-delete sro-del">মুছুন</button>
								<span class="sro-msg"></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $pages > 1 ) : ?>
			<div class="tablenav"><div class="tablenav-pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $paged,
							'total'     => $pages,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						)
					)
				);
				?>
			</div></div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Assets for the list page only.
 */
function sro_mouza_admin_assets( $hook ) {
	if ( 'toplevel_page_sro-mouza' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'sro-mouza-admin', get_template_directory_uri() . '/assets/js/mouza-admin.js', array(), SRO_VERSION, true );
	wp_add_inline_style(
		'wp-admin',
		'.sro-table-wrap{overflow-x:auto;margin-top:12px;max-width:100%}
		.sro-rates td{vertical-align:middle;padding:6px}
		.sro-rates input{margin:0}
		.sro-new td{background:#eef6f0}
		.sro-rates tr.is-dirty td{background:#fff8e1}
		.sro-msg{display:block;font-size:12px;margin-top:4px}
		.sro-msg.ok{color:#1a7f37}.sro-msg.err{color:#b32d2e}
		.sro-filter{margin:12px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center}'
	);
}
add_action( 'admin_enqueue_scripts', 'sro_mouza_admin_assets' );
