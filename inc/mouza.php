<?php
/**
 * Mouza rate data: database table, CSV/JSON importer (admin) and a read-only REST API.
 *
 * Modelled on the Sub-Registrar office's "সম্পত্তির সর্বনিম্ন বাজার মূল্য তালিকা":
 * area type (পৌরসভা এলাকা / পৌরসভা এলাকার বাহিরে / বাজার এলাকা) → mouza (JL no.)
 * → land category → rate per শতাংশ, with "(*) carried over" marks and, for bazar
 * areas, a list of plot (dag) numbers.
 *
 * No rate data ships with the theme. Only data supplied by the office is shown.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SRO_MOUZA_DB_VERSION = '2';

function sro_mouza_table() {
	global $wpdb;
	return $wpdb->prefix . 'sro_mouza_rates';
}

/**
 * Create / upgrade the table.
 */
function sro_mouza_install() {
	if ( get_option( 'sro_mouza_db' ) === SRO_MOUZA_DB_VERSION ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = sro_mouza_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			district varchar(100) NOT NULL DEFAULT '',
			upazila varchar(100) NOT NULL DEFAULT '',
			area_type varchar(80) NOT NULL DEFAULT '',
			mouza_name varchar(150) NOT NULL DEFAULT '',
			mouza_code varchar(50) NOT NULL DEFAULT '',
			land_category varchar(190) NOT NULL DEFAULT '',
			rate decimal(16,2) NOT NULL DEFAULT 0,
			rate_unit varchar(50) NOT NULL DEFAULT '',
			effective_period varchar(100) NOT NULL DEFAULT '',
			carried tinyint(1) NOT NULL DEFAULT 0,
			dag_note text NOT NULL,
			source_ref text NOT NULL,
			page_ref varchar(60) NOT NULL DEFAULT '',
			last_verified varchar(50) NOT NULL DEFAULT '',
			verified tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY loc2 (district, upazila, area_type, mouza_name)
		) {$charset};"
	);
	update_option( 'sro_mouza_db', SRO_MOUZA_DB_VERSION, false );
}
add_action( 'init', 'sro_mouza_install', 97 );

function sro_mouza_count( $where = '' ) {
	global $wpdb;
	$table = sro_mouza_table();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

function sro_mouza_unverified_count() {
	return sro_mouza_count( 'WHERE verified = 0' );
}

/**
 * Bengali/English digits to plain ASCII digits.
 */
function sro_mouza_digits( $value ) {
	return strtr(
		(string) $value,
		array(
			'০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
			'৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
		)
	);
}

/**
 * Area types in the order citizens expect them.
 */
function sro_mouza_area_types() {
	return array( 'পৌরসভা এলাকা', 'পৌরসভা এলাকার বাহিরে', 'বাজার এলাকা' );
}

/* --------------------------------------------------------------------
 * Admin: মৌজা রেট → ইমপোর্ট
 * ------------------------------------------------------------------ */

function sro_mouza_menu() {
	add_menu_page( 'মৌজা রেট', 'মৌজা রেট', 'manage_options', 'sro-mouza', 'sro_mouza_list_page', 'dashicons-location-alt', 7 );
	add_submenu_page( 'sro-mouza', 'সব রেট (সংশোধন)', 'সব রেট (সংশোধন)', 'manage_options', 'sro-mouza', 'sro_mouza_list_page' );
	add_submenu_page( 'sro-mouza', 'ইমপোর্ট', 'ইমপোর্ট', 'manage_options', 'sro-mouza-import', 'sro_mouza_page' );
}
add_action( 'admin_menu', 'sro_mouza_menu' );

function sro_mouza_back( $args = array() ) {
	wp_safe_redirect( add_query_arg( array_merge( array( 'page' => 'sro-mouza-import' ), $args ), admin_url( 'admin.php' ) ) );
	exit;
}

function sro_mouza_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_mouza_import' );

	if ( empty( $_FILES['rates']['tmp_name'] ) || ! is_uploaded_file( $_FILES['rates']['tmp_name'] ) ) {
		sro_mouza_back( array( 'sro_err' => 'file' ) );
	}
	$name = isset( $_FILES['rates']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['rates']['name'] ) ) : '';
	$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'csv', 'json' ), true ) ) {
		sro_mouza_back( array( 'sro_err' => 'type' ) );
	}
	if ( (int) $_FILES['rates']['size'] > 8 * MB_IN_BYTES ) {
		sro_mouza_back( array( 'sro_err' => 'size' ) );
	}

	$content = (string) file_get_contents( $_FILES['rates']['tmp_name'] );
	$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );
	$rows    = array();

	if ( 'json' === $ext ) {
		$decoded = json_decode( $content, true );
		if ( ! is_array( $decoded ) ) {
			sro_mouza_back( array( 'sro_err' => 'json' ) );
		}
		$rows = $decoded;
	} else {
		$lines  = array_values( array_filter( preg_split( '/\r\n|\r|\n/', $content ), 'strlen' ) );
		$header = $lines ? array_map( 'trim', str_getcsv( array_shift( $lines ) ) ) : array();
		foreach ( $lines as $line ) {
			$cells = str_getcsv( $line );
			$row   = array();
			foreach ( $header as $i => $key ) {
				$row[ strtolower( $key ) ] = isset( $cells[ $i ] ) ? trim( $cells[ $i ] ) : '';
			}
			$rows[] = $row;
		}
	}

	global $wpdb;
	$table = sro_mouza_table();
	if ( ! empty( $_POST['replace_all'] ) ) {
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	$added   = 0;
	$skipped = 0;
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			$skipped++;
			continue;
		}
		$row  = array_change_key_case( $row, CASE_LOWER );
		$get  = function ( $k ) use ( $row ) {
			return isset( $row[ $k ] ) ? trim( (string) $row[ $k ] ) : '';
		};
		$rate = sro_mouza_digits( str_replace( ',', '', $get( 'rate' ) ) );

		if ( '' === $get( 'district' ) || '' === $get( 'upazila' ) || '' === $get( 'mouza_name' ) || '' === $get( 'land_category' ) || ! is_numeric( $rate ) || '' === $get( 'rate_unit' ) ) {
			$skipped++;
			continue;
		}
		$carried  = in_array( strtolower( $get( 'carried' ) ), array( '1', 'yes', 'true', '*', 'হ্যাঁ' ), true ) ? 1 : 0;
		$verified = in_array( strtolower( $get( 'verified' ) ), array( '1', 'yes', 'true', 'হ্যাঁ' ), true ) ? 1 : 0;
		$wpdb->insert(
			$table,
			array(
				'district'         => sanitize_text_field( $get( 'district' ) ),
				'upazila'          => sanitize_text_field( $get( 'upazila' ) ),
				'area_type'        => sanitize_text_field( $get( 'area_type' ) ),
				'mouza_name'       => sanitize_text_field( $get( 'mouza_name' ) ),
				'mouza_code'       => sanitize_text_field( $get( 'mouza_code' ) ),
				'land_category'    => sanitize_text_field( $get( 'land_category' ) ),
				'rate'             => (float) $rate,
				'rate_unit'        => sanitize_text_field( $get( 'rate_unit' ) ),
				'effective_period' => sanitize_text_field( $get( 'effective_period' ) ),
				'carried'          => $carried,
				'dag_note'         => sanitize_textarea_field( $get( 'dag_note' ) ),
				'source_ref'       => sanitize_textarea_field( $get( 'source_reference' ) ? $get( 'source_reference' ) : $get( 'source_ref' ) ),
				'page_ref'         => sanitize_text_field( $get( 'page_ref' ) ),
				'last_verified'    => sanitize_text_field( $get( 'last_verified' ) ),
				'verified'         => $verified,
			)
		);
		$added++;
	}
	sro_mouza_back(
		array(
			'sro_added'   => $added,
			'sro_skipped' => $skipped,
		)
	);
}
add_action( 'admin_post_sro_mouza_import', 'sro_mouza_import' );

function sro_mouza_clear() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_mouza_clear' );
	global $wpdb;
	$table = sro_mouza_table();
	$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	sro_mouza_back( array( 'sro_cleared' => 1 ) );
}
add_action( 'admin_post_sro_mouza_clear', 'sro_mouza_clear' );

function sro_mouza_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$table = sro_mouza_table();
	$count = sro_mouza_count();
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$err     = isset( $_GET['sro_err'] ) ? sanitize_key( wp_unslash( $_GET['sro_err'] ) ) : '';
	$added   = isset( $_GET['sro_added'] ) ? (int) $_GET['sro_added'] : null;
	$skipped = isset( $_GET['sro_skipped'] ) ? (int) $_GET['sro_skipped'] : 0;
	$cleared = isset( $_GET['sro_cleared'] );
	// phpcs:enable
	$errors  = array(
		'file' => 'ফাইল আপলোড হয়নি।',
		'type' => 'শুধু .csv বা .json ফাইল দিন।',
		'size' => 'ফাইল ৮ MB এর বেশি।',
		'json' => 'JSON ফাইলটি সঠিক নয়।',
	);
	$summary = $count ? $wpdb->get_results( "SELECT district, upazila, area_type, COUNT(DISTINCT CONCAT_WS('|', mouza_name, mouza_code)) AS mouzas, COUNT(*) AS rows_count, SUM(verified) AS ok FROM {$table} GROUP BY district, upazila, area_type ORDER BY district, upazila, area_type" ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	?>
	<div class="wrap">
		<h1>মৌজা রেট ইমপোর্ট</h1>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=sro-mouza' ) ); ?>">&larr; সব রেট দেখুন ও সংশোধন করুন</a></p>

		<?php if ( $err && isset( $errors[ $err ] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $errors[ $err ] ); ?></p></div>
		<?php elseif ( null !== $added ) : ?>
			<div class="notice notice-success"><p>যোগ হয়েছে: <strong><?php echo esc_html( $added ); ?></strong> সারি, বাদ গেছে (তথ্য অসম্পূর্ণ): <strong><?php echo esc_html( $skipped ); ?></strong>।</p></div>
		<?php elseif ( $cleared ) : ?>
			<div class="notice notice-success"><p>সব মৌজা রেটের তথ্য মুছে ফেলা হয়েছে।</p></div>
		<?php endif; ?>

		<p><strong>বর্তমানে সাইটে মোট <?php echo esc_html( $count ); ?> টি রেটের সারি আছে।</strong>
		<?php if ( ! $count ) : ?>
			সাইটে "মৌজা রেটের তথ্য এখনো সংযুক্ত করা হয়নি" লেখা দেখাচ্ছে।
		<?php endif; ?></p>

		<?php if ( $summary ) : ?>
			<table class="widefat striped" style="max-width:760px;">
				<thead><tr><th>জেলা</th><th>উপজেলা</th><th>এলাকার ধরন</th><th>মৌজা</th><th>রেটের সারি</th><th>যাচাইকৃত</th></tr></thead>
				<tbody>
				<?php foreach ( $summary as $r ) : ?>
					<tr><td><?php echo esc_html( $r->district ); ?></td><td><?php echo esc_html( $r->upazila ); ?></td><td><?php echo esc_html( $r->area_type ); ?></td><td><?php echo esc_html( $r->mouzas ); ?></td><td><?php echo esc_html( $r->rows_count ); ?></td><td><?php echo esc_html( (int) $r->ok ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2 style="margin-top:28px;">মৌজা রেটের তথ্য ইমপোর্ট</h2>
		<p>ফাইলের প্রথম সারিতে কলামের নাম থাকতে হবে (CSV বা JSON; Excel থেকে "CSV UTF-8" হিসেবে সেভ করুন):</p>
		<p><code>district, upazila, area_type, mouza_name, mouza_code, land_category, rate, rate_unit, effective_period, carried, dag_note, source_reference, page_ref, last_verified, verified</code></p>
		<ul style="list-style:disc;margin-left:20px;">
			<li><strong>বাধ্যতামূলক:</strong> district, upazila, mouza_name, land_category, rate, rate_unit</li>
			<li><strong>area_type:</strong> পৌরসভা এলাকা / পৌরসভা এলাকার বাহিরে / বাজার এলাকা</li>
			<li><strong>mouza_code:</strong> জেএল নম্বর (একই নামের মৌজা আলাদা চেনার জন্য)</li>
			<li><strong>carried:</strong> ১ হলে "(*) পূর্ববর্তী বছরের মূল্য বহাল" চিহ্ন দেখাবে</li>
			<li><strong>dag_note:</strong> দাগ নম্বর/টীকা (বাজার এলাকায় বিশেষ করে)</li>
			<li><strong>verified:</strong> ১ হলে "যাচাইকৃত" ধরা হবে; না দিলে ০ (যাচাই বাকি)</li>
		</ul>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sro_mouza_import">
			<?php wp_nonce_field( 'sro_mouza_import' ); ?>
			<p><input type="file" name="rates" accept=".csv,.json" required></p>
			<p><label><input type="checkbox" name="replace_all" value="1"> ইমপোর্টের আগে আগের সব মৌজা রেট মুছে ফেলুন (নতুন সম্পূর্ণ তালিকা দিলে এটা টিক দিন)</label></p>
			<p><button class="button button-primary" type="submit">ইমপোর্ট করুন</button></p>
		</form>

		<?php if ( $count ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('সব মৌজা রেটের তথ্য মুছে যাবে। নিশ্চিত?');" style="margin-top:20px;">
				<input type="hidden" name="action" value="sro_mouza_clear">
				<?php wp_nonce_field( 'sro_mouza_clear' ); ?>
				<button class="button button-link-delete" type="submit">সব মৌজা রেট মুছে ফেলুন</button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/* --------------------------------------------------------------------
 * Read-only REST API used by the public mouza rate tool
 * ------------------------------------------------------------------ */

function sro_mouza_rest_routes() {
	$ns = 'sro/v1';

	register_rest_route(
		$ns,
		'/mouza/locations',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function () {
				global $wpdb;
				$table = sro_mouza_table();
				$rows  = $wpdb->get_results( "SELECT DISTINCT district, upazila, area_type FROM {$table} ORDER BY district, upazila, area_type" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return rest_ensure_response(
					array(
						'rows'       => array_map(
							function ( $r ) {
								return array(
									'district' => $r->district,
									'upazila'  => $r->upazila,
									'area'     => $r->area_type,
								);
							},
							(array) $rows
						),
						'total'      => sro_mouza_count(),
						'unverified' => sro_mouza_unverified_count(),
					)
				);
			},
		)
	);

	register_rest_route(
		$ns,
		'/mouza/list',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'district' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'upazila'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'area'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
			),
			'callback'            => function ( WP_REST_Request $req ) {
				global $wpdb;
				$table = sro_mouza_table();
				$rows  = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DISTINCT mouza_name, mouza_code, MD5(CONCAT_WS('|', district, upazila, area_type, mouza_name, mouza_code)) AS k FROM {$table} WHERE district = %s AND upazila = %s AND area_type = %s ORDER BY mouza_name", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$req['district'],
						$req['upazila'],
						(string) $req['area']
					)
				);
				return rest_ensure_response(
					array_map(
						function ( $r ) {
							return array(
								'key'  => $r->k,
								'name' => $r->mouza_name,
								'code' => $r->mouza_code,
							);
						},
						(array) $rows
					)
				);
			},
		)
	);

	register_rest_route(
		$ns,
		'/mouza/rates',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'key' => array(
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => function ( $v ) {
						return (bool) preg_match( '/^[0-9a-f]{32}$/', (string) $v );
					},
				),
			),
			'callback'            => function ( WP_REST_Request $req ) {
				global $wpdb;
				$table = sro_mouza_table();
				$rows  = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT mouza_name, mouza_code, area_type, land_category, rate, rate_unit, effective_period, carried, dag_note, source_ref, page_ref, last_verified, verified FROM {$table} WHERE MD5(CONCAT_WS('|', district, upazila, area_type, mouza_name, mouza_code)) = %s ORDER BY id", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$req['key']
					)
				);
				return rest_ensure_response(
					array_map(
						function ( $r ) {
							return array(
								'mouza'    => $r->mouza_name,
								'code'     => $r->mouza_code,
								'area'     => $r->area_type,
								'category' => $r->land_category,
								'rate'     => (float) $r->rate,
								'unit'     => $r->rate_unit,
								'period'   => $r->effective_period,
								'carried'  => (bool) $r->carried,
								'dag'      => $r->dag_note,
								'source'   => $r->source_ref,
								'page'     => $r->page_ref,
								'verified' => (bool) $r->verified,
								'checked'  => $r->last_verified,
							);
						},
						(array) $rows
					)
				);
			},
		)
	);
}
add_action( 'rest_api_init', 'sro_mouza_rest_routes' );
