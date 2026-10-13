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
		'calculator'      => '<rect width="16" height="20" x="4" y="2" rx="2"/><line x1="8" x2="16" y1="6" y2="6"/><line x1="16" x2="16" y1="14" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/>',
		'chart-pie'       => '<path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z"/><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>',
		'percent'         => '<line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
		'rotate-ccw'      => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
		'copy'            => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
		'printer'         => '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/>',
		'arrow-left-right' => '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
		'landmark'        => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
		'file-search'     => '<path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M4.268 21a2 2 0 0 0 1.727 1H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3"/><circle cx="5" cy="14" r="3"/><path d="m7 16 1.5 1.5"/>',
		'file-pen-line'   => '<path d="m18 5-2.414-2.414A2 2 0 0 0 14.172 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12"/><path d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/><path d="M8 18h1"/>',
		'receipt'         => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
		'map'             => '<path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/>',
		'folder-down'     => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/><path d="M12 10v6"/><path d="m9 13 3 3 3-3"/>',
		'book-open'       => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
		'external-link'   => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
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
	if ( is_page( 'services' ) ) {
		wp_enqueue_script(
			'sro-gov-shortcuts',
			get_template_directory_uri() . '/assets/js/gov-shortcuts.js',
			array(),
			sro_asset_version( 'assets/js/gov-shortcuts.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_enqueue_script(
			'sro-smart-calc-core',
			get_template_directory_uri() . '/assets/js/smart-calc-core.js',
			array(),
			sro_asset_version( 'assets/js/smart-calc-core.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_enqueue_script(
			'sro-smart-calc',
			get_template_directory_uri() . '/assets/js/smart-calc.js',
			array( 'sro-smart-calc-core' ),
			sro_asset_version( 'assets/js/smart-calc.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		return;
	}
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

/**
 * Government land service shortcuts (all verified official sites, HTTP 200 on
 * 2026-10-13). They open the official website in a new tab; nothing is stored.
 */
function sro_gov_shortcuts() {
	return array(
		array(
			'title'  => 'খতিয়ান অনুসন্ধান',
			'icon'   => 'file-search',
			'url'    => 'https://dlrms.land.gov.bd/',
			'domain' => 'dlrms.land.gov.bd',
			'btn'    => 'খতিয়ান সেবায় যান',
			'note'   => 'ভূমি তথ্য বাতায়নের "ভূমি রেকর্ড ও ম্যাপ" সেবার অফিসিয়াল প্রবেশ পথ (DLRMS)।',
		),
		array(
			'title'  => 'ই-নামজারি',
			'icon'   => 'file-pen-line',
			'url'    => 'https://mutation.land.gov.bd/',
			'domain' => 'mutation.land.gov.bd',
			'btn'    => 'ই-নামজারি সেবায় যান',
			'note'   => 'ভূমি মন্ত্রণালয়ের মিউটেশন (নামজারি) সিস্টেম।',
		),
		array(
			'title'  => 'ভূমি উন্নয়ন কর',
			'icon'   => 'receipt',
			'url'    => 'https://ldtax.gov.bd/',
			'domain' => 'ldtax.gov.bd',
			'btn'    => 'ভূমি কর সেবায় যান',
			'note'   => 'অনলাইনে ভূমি উন্নয়ন কর সংক্রান্ত সেবা।',
		),
		array(
			'title'  => 'মৌজা ম্যাপ ও ভূমি রেকর্ড',
			'icon'   => 'map',
			'url'    => 'https://map.land.gov.bd/',
			'domain' => 'map.land.gov.bd',
			'btn'    => 'ম্যাপ ও রেকর্ড সেবায় যান',
			'note'   => '"ভূমি নকশা" (জমির মানচিত্র) পোর্টাল। সব উপজেলার মানচিত্র সেখানে না-ও থাকতে পারে।',
		),
		array(
			'title'  => 'সরকারি ভূমি ফরম',
			'icon'   => 'folder-down',
			'url'    => 'https://ldtax.gov.bd/vumiseba-form',
			'domain' => 'ldtax.gov.bd',
			'btn'    => 'ফরমসমূহ দেখুন',
			'note'   => 'লীজ, নামজারিসহ বিভিন্ন ফরম PDF আকারে ডাউনলোড করা যায়।',
		),
		array(
			'title'  => 'ভূমি আইন ও বিধিমালা',
			'icon'   => 'book-open',
			'url'    => 'https://ldtax.gov.bd/ain-and-bidhi',
			'domain' => 'ldtax.gov.bd',
			'btn'    => 'আইন ও বিধিমালা দেখুন',
			'note'   => 'ভূমি সংক্রান্ত আইন ও বিধিমালার তালিকা।',
		),
	);
}
