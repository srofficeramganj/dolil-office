<?php
/**
 * Dashboard: view, search, correct, add, delete and verify mouza rates; export backup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Text columns of a rate row: key => label.
 */
function sro_mouza_text_columns() {
	return array(
		'district'         => 'জেলা',
		'upazila'          => 'উপজেলা',
		'area_type'        => 'এলাকার ধরন',
		'mouza_name'       => 'মৌজার নাম',
		'mouza_code'       => 'জেএল নং',
		'land_category'    => 'জমির শ্রেণি / বাজার',
		'rate'             => 'রেট (টাকা)',
		'rate_unit'        => 'রেটের একক',
		'effective_period' => 'কার্যকর সময়কাল',
		'dag_note'         => 'দাগ নং / টীকা',
		'source_ref'       => 'তথ্যের উৎস',
		'page_ref'         => 'পৃষ্ঠা',
		'last_verified'    => 'যাচাইয়ের তারিখ',
	);
}

/**
 * Validate and clean one submitted row. Returns array or WP_Error.
 */
function sro_mouza_clean_row( $src ) {
	$row = array();
	foreach ( array_keys( sro_mouza_text_columns() ) as $key ) {
		$value = isset( $src[ $key ] ) ? trim( wp_unslash( (string) $src[ $key ] ) ) : '';
		if ( 'rate' === $key ) {
			$value = sro_mouza_digits( str_replace( ',', '', $value ) );
			if ( '' === $value || ! is_numeric( $value ) || (float) $value < 0 ) {
				return new WP_Error( 'rate', 'রেট একটি সঠিক সংখ্যা হতে হবে।' );
			}
			$row[ $key ] = (float) $value;
		} elseif ( in_array( $key, array( 'source_ref', 'dag_note' ), true ) ) {
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
	$row['carried']  = ! empty( $src['carried'] ) && '0' !== (string) $src['carried'] ? 1 : 0;
	$row['verified'] = ! empty( $src['verified'] ) && '0' !== (string) $src['verified'] ? 1 : 0;
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
 * Mark many rows verified (or not) at once.
 */
function sro_mouza_ajax_bulk_verify() {
	check_ajax_referer( 'sro_mouza_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'অনুমতি নেই।' ), 403 );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) ) ) : array();
	$val = ! empty( $_POST['value'] ) && '0' !== $_POST['value'] ? 1 : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! $ids ) {
		wp_send_json_error( array( 'message' => 'কোনো সারি নেই।' ) );
	}
	global $wpdb;
	$table = sro_mouza_table();
	$in    = implode( ',', array_map( 'intval', $ids ) );
	$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET verified = %d WHERE id IN ({$in})", $val ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	wp_send_json_success( array( 'count' => count( $ids ) ) );
}
add_action( 'wp_ajax_sro_mouza_bulk_verify', 'sro_mouza_ajax_bulk_verify' );

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
	$rows  = $wpdb->get_results( "SELECT district, upazila, area_type, mouza_name, mouza_code, land_category, rate, rate_unit, effective_period, carried, dag_note, source_ref, page_ref, last_verified, verified FROM {$table} ORDER BY id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="mouza-rates-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'district', 'upazila', 'area_type', 'mouza_name', 'mouza_code', 'land_category', 'rate', 'rate_unit', 'effective_period', 'carried', 'dag_note', 'source_reference', 'page_ref', 'last_verified', 'verified' ) );
	foreach ( (array) $rows as $r ) {
		fputcsv( $out, array_values( $r ) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_sro_mouza_export', 'sro_mouza_export' );

/**
 * One editable row (also used for the "new rate" row).
 */
function sro_mouza_row_html( $r, $is_new = false ) {
	$val = function ( $k ) use ( $r ) {
		return $r ? (string) $r->$k : '';
	};
	$id       = $r ? (int) $r->id : 0;
	$areas    = sro_mouza_area_types();
	$cur_area = $val( 'area_type' );
	if ( $is_new ) {
		$cur_area = $cur_area ? $cur_area : $areas[1];
	}
	$text = function ( $key, $width, $extra = '' ) use ( $val, $is_new ) {
		$v = $val( $key );
		if ( $is_new && 'district' === $key ) {
			$v = 'লক্ষ্মীপুর';
		}
		if ( $is_new && 'upazila' === $key ) {
			$v = 'রামগঞ্জ';
		}
		if ( $is_new && 'rate_unit' === $key ) {
			$v = 'প্রতি শতাংশ';
		}
		if ( $is_new && 'effective_period' === $key ) {
			$v = '২০২৫-২০২৬ খ্রি.';
		}
		return sprintf( '<input type="text" name="%1$s" value="%2$s" style="min-width:%3$dpx;width:100%%" %4$s>', esc_attr( $key ), esc_attr( $v ), (int) $width, $extra );
	};
	ob_start();
	?>
	<tr class="<?php echo $is_new ? 'sro-new' : ''; ?><?php echo ( ! $is_new && ! (int) $val( 'verified' ) ) ? ' is-unverified' : ''; ?>" data-id="<?php echo esc_attr( $id ); ?>">
		<td>
			<select name="area_type" style="min-width:150px">
				<?php
				$opts = $areas;
				if ( $cur_area && ! in_array( $cur_area, $opts, true ) ) {
					$opts[] = $cur_area;
				}
				foreach ( $opts as $a ) :
					?>
					<option value="<?php echo esc_attr( $a ); ?>"<?php selected( $cur_area, $a ); ?>><?php echo esc_html( $a ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td><?php echo $text( 'mouza_name', 130 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		<td><?php echo $text( 'mouza_code', 60 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		<td><?php echo $text( 'land_category', 130 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		<td><?php echo $text( 'rate', 90, 'inputmode="decimal"' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		<td style="text-align:center"><label title="(*) পূর্ববর্তী বছরের মূল্য বহাল"><input type="checkbox" name="carried" value="1" <?php checked( (int) $val( 'carried' ), 1 ); ?>> *</label></td>
		<td style="text-align:center"><label><input type="checkbox" name="verified" value="1" <?php checked( (int) $val( 'verified' ), 1 ); ?>> যাচাই</label></td>
		<td>
			<details>
				<summary>আরও তথ্য</summary>
				<p><?php echo $text( 'district', 100 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'upazila', 100 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'rate_unit', 100 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'effective_period', 100 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'dag_note', 220 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'source_ref', 220 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'page_ref', 80 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p><?php echo $text( 'last_verified', 100 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<small>জেলা / উপজেলা / একক / সময়কাল / দাগ নং ও টীকা / উৎস / পৃষ্ঠা / যাচাইয়ের তারিখ</small>
			</details>
		</td>
		<td>
			<?php if ( $is_new ) : ?>
				<button type="button" class="button button-primary sro-save">+ যোগ করুন</button>
			<?php else : ?>
				<button type="button" class="button button-primary sro-save">সংরক্ষণ</button>
				<button type="button" class="button-link-delete sro-del">মুছুন</button>
			<?php endif; ?>
			<span class="sro-msg"></span>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}

/**
 * Admin page: list + inline correction.
 */
function sro_mouza_list_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$table = sro_mouza_table();
	$per   = 40;

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$area   = isset( $_GET['area'] ) ? sanitize_text_field( wp_unslash( $_GET['area'] ) ) : '';
	$only   = ! empty( $_GET['unverified'] );
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
	if ( '' !== $area ) {
		$where[]  = 'area_type = %s';
		$params[] = $area;
	}
	if ( $only ) {
		$where[] = 'verified = 0';
	}
	$where_sql = implode( ' AND ', $where );

	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$list_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows     = $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( $params, array( $per, ( $paged - 1 ) * $per ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$pages    = max( 1, (int) ceil( $total / $per ) );

	$all   = sro_mouza_count();
	$bad   = sro_mouza_unverified_count();
	$nonce = wp_create_nonce( 'sro_mouza_admin' );
	$ids   = $rows ? implode( ',', wp_list_pluck( $rows, 'id' ) ) : '';
	?>
	<div class="wrap sro-mouza-admin" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-ids="<?php echo esc_attr( $ids ); ?>">
		<h1 class="wp-heading-inline">মৌজা রেট: দেখুন, সংশোধন ও যাচাই করুন</h1>
		<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=sro-mouza-import' ) ); ?>">CSV/JSON ইমপোর্ট</a>
		<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sro_mouza_export' ), 'sro_mouza_export' ) ); ?>">ব্যাকআপ ডাউনলোড (CSV)</a>
		<hr class="wp-header-end">

		<p class="sro-stats">
			মোট <strong><?php echo esc_html( $all ); ?></strong> টি রেট &middot;
			<span style="color:#1a7f37">যাচাইকৃত <strong><?php echo esc_html( $all - $bad ); ?></strong></span> &middot;
			<span style="color:#b45309">যাচাই বাকি <strong><?php echo esc_html( $bad ); ?></strong></span>
			<?php if ( $bad ) : ?>
				(যাচাই বাকি থাকা পর্যন্ত সাইটে "যাচাই চলছে" নোট দেখাবে)
			<?php endif; ?>
		</p>
		<p>মূল কাগজের সাথে মিলিয়ে ভুল ঠিক করুন, তারপর সারির <strong>"যাচাই" টিক</strong> দিয়ে <strong>"সংরক্ষণ"</strong> চাপুন। একসাথে অনেক সারি ঠিক থাকলে নিচের "এই পেজের সব সারি যাচাইকৃত করুন" বাটন ব্যবহার করুন।</p>

		<form method="get" class="sro-filter">
			<input type="hidden" name="page" value="sro-mouza">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="মৌজা, শ্রেণি বা জেএল নং খুঁজুন" style="min-width:240px">
			<select name="area">
				<option value="">সব এলাকার ধরন</option>
				<?php foreach ( sro_mouza_area_types() as $a ) : ?>
					<option value="<?php echo esc_attr( $a ); ?>"<?php selected( $area, $a ); ?>><?php echo esc_html( $a ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><input type="checkbox" name="unverified" value="1" <?php checked( $only ); ?>> শুধু যাচাই বাকি</label>
			<button class="button" type="submit">খুঁজুন</button>
			<?php if ( '' !== $search || '' !== $area || $only ) : ?>
				<a class="button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sro-mouza' ) ); ?>">ফিল্টার পরিষ্কার</a>
			<?php endif; ?>
			<span class="description">ফলাফল: <?php echo esc_html( $total ); ?> টি</span>
		</form>

		<?php if ( $rows ) : ?>
			<p>
				<button type="button" class="button" id="sro-verify-page">এই পেজের সব সারি যাচাইকৃত করুন</button>
				<span class="sro-msg" id="sro-bulk-msg"></span>
			</p>
		<?php endif; ?>

		<div class="sro-table-wrap">
			<table class="widefat striped sro-rates">
				<thead>
					<tr>
						<th>এলাকার ধরন</th>
						<th>মৌজার নাম</th>
						<th>জেএল</th>
						<th>জমির শ্রেণি / বাজার</th>
						<th>রেট (টাকা)</th>
						<th title="পূর্ববর্তী বছরের মূল্য বহাল">(*)</th>
						<th>যাচাই</th>
						<th>বিস্তারিত</th>
						<th style="min-width:150px">কাজ</th>
					</tr>
				</thead>
				<tbody>
					<?php echo sro_mouza_row_html( null, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="9">কোনো রেট পাওয়া যায়নি।<?php echo ( 0 === $all ) ? ' উপরের সবুজ সারিতে নতুন রেট যোগ করুন বা CSV ইমপোর্ট করুন।' : ''; ?></td></tr>
					<?php endif; ?>
					<?php foreach ( (array) $rows as $r ) : ?>
						<?php echo sro_mouza_row_html( $r ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
		.sro-rates td{vertical-align:top;padding:6px}
		.sro-rates input[type=text],.sro-rates select{margin:0}
		.sro-rates details p{margin:6px 0}
		.sro-rates summary{cursor:pointer;color:#2271b1}
		.sro-new td{background:#eef6f0}
		.sro-rates tr.is-unverified td{border-left:0}
		.sro-rates tr.is-unverified td:first-child{box-shadow:inset 4px 0 0 #f0b849}
		.sro-rates tr.is-dirty td{background:#fff8e1}
		.sro-msg{display:block;font-size:12px;margin-top:4px}
		.sro-msg.ok{color:#1a7f37}.sro-msg.err{color:#b32d2e}
		.sro-filter{margin:12px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center}'
	);
}
add_action( 'admin_enqueue_scripts', 'sro_mouza_admin_assets' );
