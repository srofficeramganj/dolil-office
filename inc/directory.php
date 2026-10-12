<?php
/**
 * Deed writer directory: post type, area taxonomy, simple edit screen,
 * friendly admin list with Quick Edit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editable profile fields: key => [label, input type, placeholder, hint].
 */
function sro_dw_fields() {
	return array(
		'_sro_cert'     => array( 'সনদ নম্বর', 'text', 'যেমন: ২৬০৯ বা ০৪/৯৬ ইং', '' ),
		'_sro_mobile'   => array( 'মোবাইল নম্বর', 'tel', '01XXXXXXXXX', 'এই নম্বরে "কল করুন" বাটন কাজ করবে।' ),
		'_sro_address'  => array( 'ঠিকানা / গ্রাম', 'text', 'যেমন: রতনপুর, রামগঞ্জ', '' ),
		'_sro_whatsapp' => array( 'হোয়াটসঅ্যাপ', 'text', '01XXXXXXXXX (শুধু নম্বর লিখলেই হবে)', 'ফাঁকা রাখলে আইকন ধূসর থাকবে।' ),
		'_sro_facebook' => array( 'ফেসবুক', 'url', 'https://www.facebook.com/...', 'প্রোফাইলের পুরো লিংক পেস্ট করুন।' ),
		'_sro_youtube'  => array( 'ইউটিউব', 'url', 'https://www.youtube.com/...', 'চ্যানেলের পুরো লিংক পেস্ট করুন।' ),
	);
}

function sro_register_directory() {
	register_post_type(
		'deed_writer',
		array(
			'labels'        => array(
				'name'                  => 'দলিল লেখক',
				'singular_name'         => 'দলিল লেখক',
				'add_new'               => 'নতুন যোগ করুন',
				'add_new_item'          => 'নতুন দলিল লেখক যোগ করুন',
				'edit_item'             => 'দলিল লেখকের প্রোফাইল',
				'all_items'             => 'সব দলিল লেখক',
				'search_items'          => 'দলিল লেখক খুঁজুন',
				'featured_image'        => 'প্রোফাইল ছবি',
				'set_featured_image'    => 'ছবি বসান',
				'remove_featured_image' => 'ছবি সরান',
				'use_featured_image'    => 'এই ছবি ব্যবহার করুন',
			),
			'public'        => true,
			'has_archive'   => 'deed-writers',
			'rewrite'       => array( 'slug' => 'deed-writer' ),
			'menu_icon'     => 'dashicons-id',
			'menu_position' => 5,
			'supports'      => array( 'title', 'thumbnail' ),
			'show_in_rest'  => false,
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
			'show_admin_column' => true,
			'public'       => true,
			'rewrite'      => array( 'slug' => 'writer-area' ),
			'show_in_rest' => false,
		)
	);
}
add_action( 'init', 'sro_register_directory' );

/**
 * Plain, simple edit screen: no block editor for deed writers.
 */
function sro_dw_no_block_editor( $use, $post_type ) {
	return 'deed_writer' === $post_type ? false : $use;
}
add_filter( 'use_block_editor_for_post_type', 'sro_dw_no_block_editor', 10, 2 );

function sro_dw_title_placeholder( $text, $post ) {
	return ( $post && 'deed_writer' === $post->post_type ) ? 'এখানে দলিল লেখকের নাম লিখুন' : $text;
}
add_filter( 'enter_title_here', 'sro_dw_title_placeholder', 10, 2 );

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
 * Meta boxes: profile details on top, photo in the side column.
 */
function sro_directory_metabox() {
	remove_meta_box( 'postimagediv', 'deed_writer', 'side' );
	add_meta_box( 'postimagediv', 'প্রোফাইল ছবি', 'post_thumbnail_meta_box', 'deed_writer', 'side', 'high' );
	add_meta_box( 'sro_dw_details', 'প্রোফাইলের তথ্য', 'sro_directory_metabox_html', 'deed_writer', 'normal', 'high' );
}
add_action( 'add_meta_boxes_deed_writer', 'sro_directory_metabox' );

function sro_directory_metabox_html( $post ) {
	wp_nonce_field( 'sro_dw_save', 'sro_dw_nonce' );
	$fields = sro_dw_fields();

	$field = function ( $key ) use ( $post, $fields ) {
		list( $label, $type, $placeholder, $hint ) = $fields[ $key ];
		printf(
			'<p class="sro-field"><label for="%1$s">%2$s</label><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s" autocomplete="off">%6$s</p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( get_post_meta( $post->ID, $key, true ) ),
			esc_attr( $placeholder ),
			$hint ? '<span class="sro-hint">' . esc_html( $hint ) . '</span>' : ''
		);
	};

	echo '<div class="sro-box">';
	echo '<h3>১. পরিচয়</h3><div class="sro-grid">';
	$field( '_sro_cert' );
	$field( '_sro_address' );
	echo '</div>';
	echo '<h3>২. ফোন</h3><div class="sro-grid">';
	$field( '_sro_mobile' );
	echo '</div>';
	echo '<h3>৩. সোশ্যাল মিডিয়া (ঐচ্ছিক)</h3><div class="sro-grid">';
	$field( '_sro_whatsapp' );
	$field( '_sro_facebook' );
	$field( '_sro_youtube' );
	echo '</div>';
	echo '<p class="sro-hint">নাম উপরের ঘরে লিখুন। ছবি ডানদিকের "প্রোফাইল ছবি" বক্স থেকে বসান। শেষে ডানদিকের নীল <strong>Publish / Update</strong> বাটন চাপুন।</p>';
	echo '</div>';
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
	foreach ( array_keys( sro_dw_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}
add_action( 'save_post_deed_writer', 'sro_directory_save' );

/**
 * Admin list: photo, name, certificate, mobile, address, social status.
 */
function sro_directory_columns( $cols ) {
	return array(
		'cb'                    => $cols['cb'],
		'sro_thumb'             => 'ছবি',
		'title'                 => 'নাম',
		'sro_cert'              => 'সনদ নং',
		'sro_mobile'            => 'মোবাইল',
		'sro_address'           => 'ঠিকানা',
		'sro_social'            => 'সোশ্যাল',
		'taxonomy-writer_area'  => 'এলাকা',
	);
}
add_filter( 'manage_deed_writer_posts_columns', 'sro_directory_columns' );

function sro_directory_column_data( $col, $post_id ) {
	switch ( $col ) {
		case 'sro_thumb':
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:6px;width:48px;height:48px;object-fit:cover;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span style="display:inline-block;width:48px;height:48px;border-radius:6px;background:#e5ebe7;"></span>';
			}
			break;
		case 'sro_cert':
			echo esc_html( get_post_meta( $post_id, '_sro_cert', true ) );
			break;
		case 'sro_mobile':
			echo esc_html( get_post_meta( $post_id, '_sro_mobile', true ) );
			// Hidden values used by Quick Edit.
			echo '<div class="hidden">';
			foreach ( array_keys( sro_dw_fields() ) as $key ) {
				printf( '<span class="sro-qd" data-key="%s">%s</span>', esc_attr( $key ), esc_html( get_post_meta( $post_id, $key, true ) ) );
			}
			echo '</div>';
			break;
		case 'sro_address':
			echo esc_html( get_post_meta( $post_id, '_sro_address', true ) );
			break;
		case 'sro_social':
			$parts = array();
			foreach ( sro_social_links( $post_id ) as $l ) {
				$name    = array( 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'youtube' => 'YouTube' );
				$parts[] = $l['url']
					? '<span style="color:#1a7f37;">&#10003; ' . esc_html( $name[ $l['key'] ] ) . '</span>'
					: '<span style="color:#a0a8a3;">&#8211; ' . esc_html( $name[ $l['key'] ] ) . '</span>';
			}
			echo implode( '<br>', $parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			break;
	}
}
add_action( 'manage_deed_writer_posts_custom_column', 'sro_directory_column_data', 10, 2 );

function sro_directory_sortable( $cols ) {
	$cols['sro_cert'] = 'sro_cert';
	return $cols;
}
add_filter( 'manage_edit-deed_writer_sortable_columns', 'sro_directory_sortable' );

/**
 * Quick Edit: change mobile, social links etc. right in the list.
 */
function sro_quick_edit_box( $column, $post_type ) {
	if ( 'deed_writer' !== $post_type || 'sro_mobile' !== $column ) {
		return;
	}
	wp_nonce_field( 'sro_dw_save', 'sro_dw_nonce', false );
	echo '<fieldset class="inline-edit-col-left"><div class="inline-edit-col">';
	echo '<span class="title inline-edit-categories-label">প্রোফাইলের তথ্য</span>';
	foreach ( sro_dw_fields() as $key => $def ) {
		printf(
			'<label><span class="title">%1$s</span><span class="input-text-wrap"><input type="text" name="%2$s" value="" placeholder="%3$s"></span></label>',
			esc_html( $def[0] ),
			esc_attr( $key ),
			esc_attr( $def[2] )
		);
	}
	echo '</div></fieldset>';
}
add_action( 'quick_edit_custom_box', 'sro_quick_edit_box', 10, 2 );

/**
 * Admin CSS (edit screen) and Quick Edit script (list screen).
 */
function sro_directory_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || 'deed_writer' !== $screen->post_type ) {
		return;
	}
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_add_inline_style(
			'wp-admin',
			'#sro_dw_details .inside{margin:0;padding:0}
			.sro-box{padding:16px 20px 8px}
			.sro-box h3{margin:18px 0 8px;font-size:15px;color:#0b5d3b;border-bottom:1px solid #dcdcde;padding-bottom:6px}
			.sro-box h3:first-child{margin-top:0}
			.sro-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:4px 20px}
			.sro-field{margin:0 0 12px}
			.sro-field label{display:block;font-weight:600;font-size:14px;margin-bottom:4px}
			.sro-field input{width:100%;min-height:40px;font-size:15px}
			.sro-hint{display:block;color:#646970;font-size:12px;margin-top:3px}
			#titlediv #title{font-size:20px;padding:10px 12px;height:auto}'
		);
	}
	if ( 'edit.php' === $hook ) {
		wp_add_inline_style( 'wp-admin', '.column-sro_thumb{width:64px}.column-sro_social{width:120px}.column-sro_cert,.column-sro_mobile{width:130px}' );
		wp_enqueue_script( 'sro-admin', get_template_directory_uri() . '/assets/js/admin.js', array( 'jquery', 'inline-edit-post' ), SRO_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'sro_directory_admin_assets' );
