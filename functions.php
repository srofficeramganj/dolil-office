<?php
/**
 * SR Office Ramganj theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SRO_VERSION', '1.2.0' );

require_once get_template_directory() . '/inc/directory.php';
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/updater.php';

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
	);
	foreach ( $fields as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => '',
				'sanitize_callback' => 'office_email' === $key ? 'sanitize_email' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'sro_office',
				'type'    => 'office_email' === $key ? 'email' : 'text',
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
	return '' !== $value ? $value : $default;
}

/**
 * Turn a phone number (Bengali or English digits) into a tel: safe string.
 */
function sro_tel( $number ) {
	$number = strtr( (string) $number, array(
		'০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
		'৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
	) );
	return preg_replace( '/[^0-9+]/', '', $number );
}

/**
 * Default pages the six navigation links point to.
 */
function sro_default_pages() {
	return array(
		'about'    => 'আমাদের সম্পর্কে',
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
	if ( get_option( 'sro_needs_flush' ) ) {
		flush_rewrite_rules();
		delete_option( 'sro_needs_flush' );
	}
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
