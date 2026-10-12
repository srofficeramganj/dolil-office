<?php
/**
 * Citizen services: registry of the four public tools, their pages and icons.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The services shown on /services/. Other services can be added later;
 * only entries listed here are public.
 */
function sro_services_registry() {
	return array(
		'land-measurement-calculator' => array(
			'title' => 'জমির পরিমাপ ক্যালকুলেটর',
			'desc'  => 'সহজেই জমির পরিমাণ এক একক থেকে অন্য এককে রূপান্তর করুন।',
			'cta'   => 'ক্যালকুলেটর খুলুন',
			'icon'  => 'ruler',
			'color' => '#006838',
		),
		'land-purchase-checklist'     => array(
			'title' => 'জমি ক্রয়ের পূর্বে যাচাই চেকলিস্ট',
			'desc'  => 'জমি কেনার আগে গুরুত্বপূর্ণ তথ্য ও কাগজপত্র যাচাই করে সম্ভাব্য ঝুঁকি কমান।',
			'cta'   => 'চেকলিস্ট দেখুন',
			'icon'  => 'clipboard-check',
			'color' => '#E60012',
		),
		'registration-documents'      => array(
			'title' => 'দলিল রেজিস্ট্রেশনের প্রয়োজনীয় কাগজপত্র',
			'desc'  => 'আপনার দলিলের ধরন নির্বাচন করে প্রয়োজনীয় কাগজপত্র সম্পর্কে জানুন।',
			'cta'   => 'কাগজপত্র জানুন',
			'icon'  => 'files',
			'color' => '#004D2B',
		),
		'mouza-rate'                  => array(
			'title' => 'মৌজা অনুযায়ী জমির মূল্য / মৌজা রেট',
			'desc'  => 'মৌজা ও জমির শ্রেণি নির্বাচন করে প্রকাশিত সরকারি মৌজা রেট সম্পর্কে জানুন এবং প্রযোজ্য ক্ষেত্রে আনুমানিক মূল্য হিসাব করুন।',
			'cta'   => 'মৌজা রেট দেখুন',
			'icon'  => 'map-pinned',
			'color' => '#B3000E',
		),
	);
}

/**
 * Lucide icon paths (24x24, stroke).
 */
function sro_icon_markup( $name ) {
	$icons = array(
		'ruler'           => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>',
		'clipboard-check' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
		'files'           => '<path d="M20 7h-3a2 2 0 0 1-2-2V2"/><path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z"/><path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8"/>',
		'map-pinned'      => '<path d="M18 8c0 3.613-3.869 7.429-5.393 8.795a1 1 0 0 1-1.214 0C9.87 15.429 6 11.613 6 8a6 6 0 0 1 12 0"/><circle cx="12" cy="8" r="2"/><path d="M8.714 14h-3.71a1 1 0 0 0-.948.683l-2.004 6A1 1 0 0 0 3 22h18a1 1 0 0 0 .948-1.316l-2-6a1 1 0 0 0-.949-.684h-3.712"/>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function sro_icon( $name, $size = 32 ) {
	printf(
		'<svg class="lucide" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $size,
		sro_icon_markup( $name ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Make sure /services/ and its four child pages exist (once per theme version).
 */
function sro_ensure_service_pages() {
	if ( get_option( 'sro_pages_ver' ) === SRO_VERSION ) {
		return;
	}
	$parent = get_page_by_path( 'services' );
	if ( $parent ) {
		$parent_id = (int) $parent->ID;
	} else {
		$parent_id = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'সেবা সমূহ',
				'post_name'   => 'services',
			)
		);
	}
	if ( $parent_id ) {
		foreach ( sro_services_registry() as $slug => $s ) {
			if ( ! get_page_by_path( 'services/' . $slug ) ) {
				wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => $s['title'],
						'post_name'    => $slug,
						'post_parent'  => $parent_id,
						'post_content' => '',
					)
				);
			}
		}
		update_option( 'sro_pages_ver', SRO_VERSION, false );
	}
}
add_action( 'init', 'sro_ensure_service_pages', 98 );

/**
 * Is the current request one of the four service pages?
 */
function sro_is_service_page() {
	return is_page() && in_array( get_post_field( 'post_name', get_queried_object_id() ), array_keys( sro_services_registry() ), true );
}

/**
 * Service page assets (loaded only on the four service pages).
 */
function sro_services_assets() {
	if ( ! sro_is_service_page() ) {
		return;
	}
	wp_enqueue_script(
		'sro-services',
		get_template_directory_uri() . '/assets/js/services.js',
		array(),
		sro_asset_version( 'assets/js/services.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'sro_services_assets' );

/**
 * Shared page header for a service detail page.
 */
function sro_service_head( $slug ) {
	$reg = sro_services_registry();
	$s   = $reg[ $slug ];
	?>
	<nav class="crumbs" aria-label="পথ">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">হোম</a> &rsaquo;
		<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">সেবা সমূহ</a> &rsaquo;
		<span><?php echo esc_html( $s['title'] ); ?></span>
	</nav>
	<header class="tool-head" style="--accent:<?php echo esc_attr( $s['color'] ); ?>">
		<span class="tool-head-icon"><?php sro_icon( $s['icon'], 36 ); ?></span>
		<div>
			<h1><?php echo esc_html( $s['title'] ); ?></h1>
			<p><?php echo esc_html( $s['desc'] ); ?></p>
		</div>
	</header>
	<?php
}

/**
 * Land measurement units (labels only; factors live in the JS/PHP data).
 */
function sro_land_units() {
	return array(
		'shotangsho' => 'শতাংশ',
		'decimal'    => 'ডেসিমেল',
		'katha'      => 'কাঠা',
		'bigha'      => 'বিঘা',
		'acre'       => 'একর',
		'sqft'       => 'বর্গফুট',
		'sqm'        => 'বর্গমিটার',
	);
}

/**
 * Purchase checklist: category => items.
 */
function sro_purchase_checklist() {
	return array(
		'A. মালিকানা যাচাই'    => array( 'বিক্রেতার পরিচয় যাচাই', 'বিক্রেতার মালিকানা যাচাই', 'পূর্ববর্তী দলিল যাচাই', 'মালিকানার ধারাবাহিকতা যাচাই' ),
		'B. জমির রেকর্ড'        => array( 'খতিয়ান যাচাই', 'দাগ নম্বর যাচাই', 'মৌজা যাচাই', 'নামজারি যাচাই' ),
		'C. কর ও অন্যান্য তথ্য' => array( 'ভূমি উন্নয়ন কর পরিশোধের তথ্য', 'বকেয়া কর সংক্রান্ত তথ্য', 'জমির শ্রেণি যাচাই' ),
		'D. বাস্তব অবস্থা'      => array( 'জমির সীমানা যাচাই', 'বাস্তব দখল যাচাই', 'রাস্তা ও প্রবেশপথ যাচাই', 'জমি নিয়ে বিরোধ বা মামলা আছে কি না যাচাই' ),
	);
}

/**
 * Registration documents guidance (general information, not an official list).
 * Edit here to change the lists shown to citizens.
 */
function sro_registration_documents() {
	$common = array(
		'জাতীয় পরিচয়পত্র বা গ্রহণযোগ্য পরিচয়পত্র (সংশ্লিষ্ট সকল পক্ষের)',
		'প্রাসঙ্গিক খতিয়ান',
		'পূর্ববর্তী মালিকানার দলিল',
		'নামজারি সংক্রান্ত কাগজপত্র (যেখানে প্রযোজ্য)',
		'ভূমি উন্নয়ন করের রসিদ',
		'জমির তফসিল',
		'ছবি ও অন্যান্য পরিচয়সংক্রান্ত তথ্য (যেখানে প্রযোজ্য)',
	);
	return array(
		'common' => $common,
		'types'  => array(
			'sale'      => array(
				'label' => 'সাফ-কবলা দলিল',
				'extra' => array( 'বিক্রেতার মালিকানা প্রমাণের কাগজ', 'ক্রেতা ও বিক্রেতার তথ্য (পিতা/স্বামীর নাম, ঠিকানা)', 'সাক্ষীর পরিচয় (যেখানে প্রযোজ্য)' ),
			),
			'heba'      => array(
				'label' => 'হেবা দলিল',
				'extra' => array( 'দাতার মালিকানা প্রমাণের কাগজ', 'দাতা ও গ্রহীতার সম্পর্কের তথ্য', 'গ্রহীতার পরিচয়সংক্রান্ত তথ্য' ),
			),
			'danpotro'  => array(
				'label' => 'দানপত্র দলিল',
				'extra' => array( 'দাতার মালিকানা প্রমাণের কাগজ', 'দাতা ও গ্রহীতার তথ্য', 'গ্রহীতার পরিচয়সংক্রান্ত তথ্য' ),
			),
			'bontonama' => array(
				'label' => 'বণ্টননামা দলিল',
				'extra' => array( 'সকল অংশীদারের পরিচয় ও তথ্য', 'উত্তরাধিকার/ওয়ারিশ সংক্রান্ত কাগজ (যেখানে প্রযোজ্য)', 'বণ্টনের তফসিল' ),
			),
			'bondhok'   => array(
				'label' => 'বন্ধক দলিল',
				'extra' => array( 'বন্ধকদাতার মালিকানা প্রমাণের কাগজ', 'বন্ধকগ্রহীতার পরিচয়সংক্রান্ত তথ্য', 'বন্ধকের শর্ত ও অর্থের পরিমাণ সংক্রান্ত তথ্য' ),
			),
			'other'     => array(
				'label' => 'অন্যান্য দলিল',
				'extra' => array( 'দলিলের ধরন অনুযায়ী অতিরিক্ত কাগজপত্র (সাব-রেজিস্ট্রার অফিস থেকে জেনে নিন)' ),
			),
		),
	);
}
