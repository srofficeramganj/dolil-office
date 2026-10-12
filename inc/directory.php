<?php
/**
 * Deed writer directory: post type, area taxonomy, details meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sro_register_directory() {
	register_post_type(
		'deed_writer',
		array(
			'labels'       => array(
				'name'          => 'দলিল লেখক',
				'singular_name' => 'দলিল লেখক',
				'add_new'       => 'নতুন যোগ করুন',
				'add_new_item'  => 'নতুন দলিল লেখক যোগ করুন',
				'edit_item'     => 'দলিল লেখক সম্পাদনা',
				'all_items'     => 'সব দলিল লেখক',
				'search_items'  => 'দলিল লেখক খুঁজুন',
			),
			'public'       => true,
			'has_archive'  => 'deed-writers',
			'rewrite'      => array( 'slug' => 'deed-writer' ),
			'menu_icon'    => 'dashicons-id',
			'menu_position' => 5,
			'supports'     => array( 'title', 'thumbnail', 'editor' ),
			'show_in_rest' => true,
		)
	);

	register_taxonomy(
		'writer_area',
		'deed_writer',
		array(
			'labels'       => array(
				'name'          => 'এলাকা',
				'singular_name' => 'এলাকা',
				'add_new_item'  => 'নতুন এলাকা যোগ করুন',
			),
			'hierarchical' => true,
			'public'       => true,
			'rewrite'      => array( 'slug' => 'writer-area' ),
			'show_in_rest' => true,
		)
	);
}
add_action( 'init', 'sro_register_directory' );

/**
 * Show every deed writer on one page so search and filters are instant.
 */
function sro_directory_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'deed_writer' ) || $query->is_tax( 'writer_area' ) ) {
		$query->set( 'posts_per_page', -1 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'sro_directory_query' );

/**
 * Details meta box.
 */
function sro_directory_metabox() {
	add_meta_box( 'sro_dw_details', 'দলিল লেখকের তথ্য', 'sro_directory_metabox_html', 'deed_writer', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'sro_directory_metabox' );

function sro_directory_metabox_html( $post ) {
	wp_nonce_field( 'sro_dw_save', 'sro_dw_nonce' );
	$fields = array(
		'_sro_cert'    => array( 'সনদ নম্বর', 'text' ),
		'_sro_mobile'  => array( 'মোবাইল নম্বর', 'tel' ),
		'_sro_address' => array( 'ঠিকানা', 'text' ),
		'_sro_social'  => array( 'সোশ্যাল মিডিয়া লিংক (ফেসবুক/হোয়াটসঅ্যাপ ইত্যাদি)', 'url' ),
	);
	foreach ( $fields as $key => $def ) {
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="widefat"></p>',
			esc_attr( $key ),
			esc_html( $def[0] ),
			esc_attr( $def[1] ),
			esc_attr( get_post_meta( $post->ID, $key, true ) )
		);
	}
	echo '<p class="description">ছবি দিতে ডানদিকের "Featured image" ব্যবহার করুন। এলাকা ডানদিকের "এলাকা" বক্স থেকে বাছুন।</p>';
}

function sro_directory_save( $post_id ) {
	if ( ! isset( $_POST['sro_dw_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sro_dw_nonce'] ) ), 'sro_dw_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( '_sro_cert', '_sro_mobile', '_sro_address' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	if ( isset( $_POST['_sro_social'] ) ) {
		update_post_meta( $post_id, '_sro_social', esc_url_raw( wp_unslash( $_POST['_sro_social'] ) ) );
	}
}
add_action( 'save_post_deed_writer', 'sro_directory_save' );

/**
 * Admin list columns.
 */
function sro_directory_columns( $cols ) {
	$cols['sro_cert']   = 'সনদ নং';
	$cols['sro_mobile'] = 'মোবাইল';
	return $cols;
}
add_filter( 'manage_deed_writer_posts_columns', 'sro_directory_columns' );

function sro_directory_column_data( $col, $post_id ) {
	if ( 'sro_cert' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_sro_cert', true ) );
	} elseif ( 'sro_mobile' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_sro_mobile', true ) );
	}
}
add_action( 'manage_deed_writer_posts_custom_column', 'sro_directory_column_data', 10, 2 );
