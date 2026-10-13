<?php
/**
 * SR Office Ramganj theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SRO_VERSION', '1.14.4' );

require_once get_template_directory() . '/inc/directory.php';
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/updater.php';
require_once get_template_directory() . '/inc/importer.php';
require_once get_template_directory() . '/inc/notices.php';
require_once get_template_directory() . '/inc/services.php';
require_once get_template_directory() . '/inc/mouza.php';
require_once get_template_directory() . '/inc/mouza-admin.php';
require_once get_template_directory() . '/inc/committee.php';

/**
 * Theme setup.
 */
function sro_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
	register_nav_menus( array( 'primary' => 'প্রধান মেনু' ) );
	add_image_size( 'dw_card', 640, 480, true );
}
add_action( 'after_setup_theme', 'sro_setup' );

/**
 * Cache-busting version: file modified time, so every deploy refreshes browsers.
 */
function sro_asset_version( $relative ) {
	$path = get_template_directory() . '/' . $relative;
	return file_exists( $path ) ? (string) filemtime( $path ) : SRO_VERSION;
}

/**
 * Assets: one stylesheet, one deferred script, no libraries.
 */
function sro_assets() {
	wp_enqueue_style( 'sro-style', get_stylesheet_uri(), array(), sro_asset_version( 'style.css' ) );
	wp_enqueue_script(
		'sro-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		sro_asset_version( 'assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'sro_assets' );

// Keep the front end light.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Customizer: office details used in the header, footer and contact page.
 */
function sro_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'sro_office',
		array(
			'title'    => 'অফিসের তথ্য',
			'priority' => 30,
		)
	);
	$fields = array(
		'office_phone'   => 'ফোন নম্বর',
		'office_email'   => 'ইমেইল',
		'office_address' => 'ঠিকানা',
		'office_hours'   => 'অফিস সময়',
		'office_whatsapp' => 'হোয়াটসঅ্যাপ (নম্বর বা লিংক)',
		'office_facebook' => 'ফেসবুক পেজের লিংক',
		'office_youtube'  => 'ইউটিউব চ্যানেলের লিংক',
		'office_notices'  => 'ঘোষণা (হোম পেজে চলমান লেখা, প্রতি লাইনে একটি)',
		'office_map'      => 'গুগল ম্যাপ লিংক বা embed কোড (ঐচ্ছিক)',
		'committee_title' => 'সমিতির নাম (আমাদের সম্পর্কে পেজে)',
		'committee_sub'   => 'কমিটির উপশিরোনাম (যেমন: ত্রি-বার্ষিক কার্যকরী পরিষদ ২০২৬ খ্রি.)',
	);
	foreach ( $fields as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => '',
				'sanitize_callback' => 'office_email' === $key ? 'sanitize_email' : ( 'office_notices' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'sro_office',
				'type'    => 'office_email' === $key ? 'email' : ( 'office_notices' === $key ? 'textarea' : 'text' ),
			)
		);
	}
}
add_action( 'customize_register', 'sro_customize_register' );

/**
 * Read an office option.
 */
function sro_opt( $key, $default = '' ) {
	$value = get_theme_mod( $key, '' );
	if ( '' !== $value ) {
		return $value;
	}
	if ( '' !== $default ) {
		return $default;
	}
	// Shown until the office sets its own values in Customizer.
	$defaults = array(
		'office_email' => 'sr.office.ramganj@gmail.com',
		'office_phone' => '01950087xxx', // Demo number: replace in Customizer.
	);
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/**
 * Is this a dialable phone number (a demo like 01950087xxx is not)?
 */
function sro_phone_ok( $number ) {
	return (bool) preg_match( '/^\+?[0-9]{10,15}$/', sro_tel( $number ) );
}

/**
 * Turn a phone number (Bengali or English digits) into a tel: safe string.
 */
function sro_tel( $number ) {
	$number = strtr( (string) $number, array(
		'০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
		'৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
	) );
	$number = preg_replace( '/[^0-9+]/', '', $number );
	// Bangladesh mobile: 01XXXXXXXXX becomes +8801XXXXXXXXX so it dials from any phone.
	if ( preg_match( '/^01[0-9]{9}$/', $number ) ) {
		$number = '+88' . $number;
	}
	return $number;
}

/**
 * Default pages the six navigation links point to.
 */
function sro_default_pages() {
	return array(
		'about'     => 'আমাদের সম্পর্কে',
		'committee' => 'কার্যকরী কমিটি',
		'services' => 'সেবা সমূহ',
		'notice'   => 'নোটিশ ও তথ্য',
		'contact'  => 'যোগাযোগ',
	);
}

/**
 * Fallback menu when no menu is assigned: always six links.
 */
function sro_fallback_menu() {
	$pages = sro_default_pages();
	$items = array(
		home_url( '/' )                => 'হোম',
		home_url( '/about/' )          => $pages['about'],
		home_url( '/deed-writers/' )   => 'দলিল লেখক',
		home_url( '/services/' )       => $pages['services'],
		home_url( '/notice/' )         => $pages['notice'],
		home_url( '/contact/' )        => $pages['contact'],
	);
	echo '<ul class="menu">';
	foreach ( $items as $url => $label ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * On activation: create the default pages and a six-link menu.
 */
function sro_activate() {
	$ids = array();
	foreach ( sro_default_pages() as $slug => $title ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => '',
				)
			);
		} else {
			$id = $page->ID;
		}
		$ids[ $slug ] = (int) $id;
	}

	$menu_name = 'প্রধান মেনু';
	if ( ! wp_get_nav_menu_object( $menu_name ) ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( ! is_wp_error( $menu_id ) ) {
			$custom = array(
				'হোম'        => home_url( '/' ),
				'দলিল লেখক' => home_url( '/deed-writers/' ),
			);
			wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'হোম', 'menu-item-url' => $custom['হোম'], 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
			foreach ( array( 'about' ) as $slug ) {
				wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object-id' => $ids[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
			}
			wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'দলিল লেখক', 'menu-item-url' => $custom['দলিল লেখক'], 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
			foreach ( array( 'services', 'notice', 'contact' ) as $slug ) {
				wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object-id' => $ids[ $slug ], 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
			}
			$locations            = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
	update_option( 'sro_needs_flush', 1 );
}
add_action( 'after_switch_theme', 'sro_activate' );

/**
 * Flush rewrite rules once, after the custom post type is registered.
 */
function sro_maybe_flush() {
	if ( get_option( 'sro_flushed_ver' ) !== SRO_VERSION ) {
		flush_rewrite_rules();
		update_option( 'sro_flushed_ver', SRO_VERSION, false );
	}
	delete_option( 'sro_needs_flush' );
}
add_action( 'init', 'sro_maybe_flush', 99 );

/**
 * Favicon, app icon and social image from the theme (used unless a Site Icon is set in Customizer).
 */
function sro_head_icons() {
	$base = get_template_directory_uri() . '/assets/images/';
	if ( ! has_site_icon() ) {
		printf( "<link rel=\"icon\" href=\"%s\" sizes=\"any\">
", esc_url( $base . 'favicon.ico' ) );
		printf( "<link rel=\"icon\" type=\"image/png\" sizes=\"192x192\" href=\"%s\">
", esc_url( $base . 'icon-192.png' ) );
		printf( "<link rel=\"apple-touch-icon\" href=\"%s\">
", esc_url( $base . 'apple-touch-icon.png' ) );
	}
	printf( "<meta name=\"theme-color\" content=\"#0b5d3b\">
" );
	printf( "<meta property=\"og:image\" content=\"%s\">
", esc_url( $base . 'logo.png' ) );
}
add_action( 'wp_head', 'sro_head_icons', 5 );

/**
 * Social networks of a deed writer. 'url' is empty when no link is set.
 *
 * @return array[] Each item has key, label, url and path.
 */
function sro_social_paths() {
	return array(
		'whatsapp' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
		'facebook' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
		'youtube'  => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
	);
}

function sro_social_links( $post_id ) {
	$paths  = sro_social_paths();
	$labels = array(
		'whatsapp' => 'হোয়াটসঅ্যাপে মেসেজ করুন',
		'facebook' => 'ফেসবুক প্রোফাইল',
		'youtube'  => 'ইউটিউব চ্যানেল',
	);
	$out    = array();
	foreach ( $paths as $key => $path ) {
		$raw = trim( (string) get_post_meta( $post_id, '_sro_' . $key, true ) );
		// Older profiles had a single "social" link: treat it as Facebook.
		if ( 'facebook' === $key && '' === $raw ) {
			$raw = trim( (string) get_post_meta( $post_id, '_sro_social', true ) );
		}
		$out[] = array(
			'key'   => $key,
			'label' => $labels[ $key ],
			'url'   => sro_social_url( $key, $raw ),
			'path'  => $path,
		);
	}
	return $out;
}

/**
 * Turn what an editor typed (number, username or link) into a URL.
 */
function sro_social_url( $type, $value ) {
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $value ) ) {
		return esc_url_raw( $value );
	}
	if ( 'whatsapp' === $type ) {
		$digits = preg_replace( '/[^0-9]/', '', sro_tel( $value ) );
		if ( preg_match( '/^0[0-9]{10}$/', $digits ) ) {
			$digits = '880' . substr( $digits, 1 );
		}
		return strlen( $digits ) >= 10 ? 'https://wa.me/' . $digits : '';
	}
	if ( false !== strpos( $value, '.' ) ) {
		return esc_url_raw( 'https://' . ltrim( $value, '/' ) );
	}
	$host = 'facebook' === $type ? 'https://www.facebook.com/' : 'https://www.youtube.com/';
	return esc_url_raw( $host . ltrim( $value, '/' ) );
}

/**
 * Print round social icons (only those filled in).
 */
function sro_render_social( $post_id ) {
	echo '<ul class="dw-social" aria-label="সোশ্যাল মিডিয়া">';
	foreach ( sro_social_links( $post_id ) as $l ) {
		$icon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . esc_attr( $l['path'] ) . '"/></svg>';
		if ( $l['url'] ) {
			printf(
				'<li><a class="soc soc-%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">%4$s</a></li>',
				esc_attr( $l['key'] ),
				esc_url( $l['url'] ),
				esc_attr( $l['label'] ),
				$icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} else {
			printf(
				'<li><span class="soc soc-off" aria-hidden="true">%s</span></li>',
				$icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
	}
	echo '</ul>';
}

/**
 * Fixed office identity (shown on the contact page and in the footer).
 */
function sro_office_info() {
	return array(
		'name'       => 'সাব-রেজিস্ট্রারের কার্যালয়, রামগঞ্জ',
		'upazila'    => 'রামগঞ্জ',
		'district'   => 'লক্ষ্মীপুর',
		'division'   => 'চট্টগ্রাম',
		'country'    => 'বাংলাদেশ',
		'address_en' => 'Sub-Registrar Office, Ramganj, Lakshmipur, Chattogram, Bangladesh.',
	);
}

/**
 * Google Maps URLs for the office: an embeddable one and a "directions" link.
 *
 * The Customizer field may hold a Google Maps link or a full "Embed a map"
 * iframe code. Without it, the office address is used as the search query.
 *
 * @return array{embed:string,link:string}
 */
function sro_map_urls() {
	$info  = sro_office_info();
	$query = $info['address_en'];
	$raw   = trim( (string) get_theme_mod( 'office_map', '' ) );

	$out = array(
		'embed' => 'https://maps.google.com/maps?q=' . rawurlencode( $query ) . '&hl=bn&z=15&output=embed',
		'link'  => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query ),
	);
	if ( '' === $raw ) {
		return $out;
	}
	// Pasted <iframe src="https://www.google.com/maps/embed?...">.
	if ( preg_match( '/src=["\']([^"\']+)["\']/i', $raw, $m ) && 0 === strpos( $m[1], 'https://www.google.com/maps/embed' ) ) {
		$out['embed'] = $m[1];
		return $out;
	}
	// Pasted a normal Google Maps link.
	if ( preg_match( '#^https://(www\.)?(google\.com/maps|maps\.google\.com|maps\.app\.goo\.gl|goo\.gl/maps)#i', $raw ) ) {
		$out['link'] = esc_url_raw( $raw );
	}
	return $out;
}
