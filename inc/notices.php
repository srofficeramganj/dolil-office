<?php
/**
 * Notices: post type with a PDF file and an image, shown on /notice/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sro_register_notices() {
	register_post_type(
		'sro_notice',
		array(
			'labels'        => array(
				'name'                  => 'নোটিশ',
				'singular_name'         => 'নোটিশ',
				'add_new'               => 'নতুন নোটিশ',
				'add_new_item'          => 'নতুন নোটিশ যোগ করুন',
				'edit_item'             => 'নোটিশ সম্পাদনা',
				'all_items'             => 'সব নোটিশ',
				'search_items'          => 'নোটিশ খুঁজুন',
				'featured_image'        => 'নোটিশের ছবি',
				'set_featured_image'    => 'ছবি বসান',
				'remove_featured_image' => 'ছবি সরান',
				'use_featured_image'    => 'এই ছবি ব্যবহার করুন',
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'notices' ),
			'menu_icon'     => 'dashicons-megaphone',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'  => false,
		)
	);
}
add_action( 'init', 'sro_register_notices' );

// Plain edit screen (no block editor).
function sro_notice_no_block_editor( $use, $post_type ) {
	return 'sro_notice' === $post_type ? false : $use;
}
add_filter( 'use_block_editor_for_post_type', 'sro_notice_no_block_editor', 10, 2 );

function sro_notice_title_placeholder( $text, $post ) {
	return ( $post && 'sro_notice' === $post->post_type ) ? 'এখানে নোটিশের শিরোনাম লিখুন' : $text;
}
add_filter( 'enter_title_here', 'sro_notice_title_placeholder', 10, 2 );

/**
 * Bengali date, e.g. ১৩ অক্টোবর ২০২৬.
 */
function sro_bn_date( $timestamp ) {
	$months = array( 'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর' );
	$digits = array(
		'0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
		'5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
	);
	$day    = wp_date( 'j', $timestamp );
	$month  = (int) wp_date( 'n', $timestamp );
	$year   = wp_date( 'Y', $timestamp );
	return strtr( $day, $digits ) . ' ' . $months[ $month - 1 ] . ' ' . strtr( $year, $digits );
}

/**
 * PDF and image links of a notice.
 */
function sro_notice_files( $post_id ) {
	$out    = array(
		'pdf_url'  => '',
		'pdf_name' => '',
		'img_url'  => '',
	);
	$pdf_id = (int) get_post_meta( $post_id, '_sro_notice_pdf', true );
	if ( $pdf_id && 'application/pdf' === get_post_mime_type( $pdf_id ) ) {
		$out['pdf_url']  = (string) wp_get_attachment_url( $pdf_id );
		$out['pdf_name'] = wp_basename( (string) get_attached_file( $pdf_id ) );
	}
	if ( has_post_thumbnail( $post_id ) ) {
		$out['img_url'] = (string) get_the_post_thumbnail_url( $post_id, 'full' );
	}
	return $out;
}

/**
 * Meta boxes.
 */
function sro_notice_metabox() {
	remove_meta_box( 'postimagediv', 'sro_notice', 'side' );
	add_meta_box( 'postimagediv', 'নোটিশের ছবি (ঐচ্ছিক)', 'post_thumbnail_meta_box', 'sro_notice', 'side', 'high' );
	add_meta_box( 'sro_notice_pdf_box', 'PDF ফাইল (ঐচ্ছিক)', 'sro_notice_pdf_html', 'sro_notice', 'normal', 'high' );
}
add_action( 'add_meta_boxes_sro_notice', 'sro_notice_metabox' );

function sro_notice_pdf_html( $post ) {
	wp_nonce_field( 'sro_notice_save', 'sro_notice_nonce' );
	$pdf_id = (int) get_post_meta( $post->ID, '_sro_notice_pdf', true );
	$name   = $pdf_id ? wp_basename( (string) get_attached_file( $pdf_id ) ) : '';
	?>
	<div class="sro-box">
		<input type="hidden" id="sro_notice_pdf" name="_sro_notice_pdf" value="<?php echo esc_attr( $pdf_id ? $pdf_id : '' ); ?>">
		<p>
			<button type="button" class="button button-primary button-large sro-pick-pdf">PDF ফাইল বাছুন / আপলোড করুন</button>
			<button type="button" class="button button-large sro-remove-pdf"<?php echo $pdf_id ? '' : ' style="display:none"'; ?>>PDF সরান</button>
		</p>
		<p>নির্বাচিত ফাইল: <strong class="sro-pdf-name"><?php echo $name ? esc_html( $name ) : 'কোনো ফাইল নেই'; ?></strong></p>
		<p class="sro-hint">নোটিশের ছবি ডানদিকের "নোটিশের ছবি" বক্স থেকে দিন। বিবরণ নিচের বড় ঘরে লিখুন (ঐচ্ছিক)। শেষে ডানদিকের নীল <strong>Publish / Update</strong> বাটন চাপুন।</p>
	</div>
	<?php
}

function sro_notice_save( $post_id ) {
	if ( ! isset( $_POST['sro_notice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sro_notice_nonce'] ) ), 'sro_notice_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$pdf_id = isset( $_POST['_sro_notice_pdf'] ) ? absint( $_POST['_sro_notice_pdf'] ) : 0;
	if ( $pdf_id && 'application/pdf' !== get_post_mime_type( $pdf_id ) ) {
		$pdf_id = 0;
	}
	if ( $pdf_id ) {
		update_post_meta( $post_id, '_sro_notice_pdf', $pdf_id );
	} else {
		delete_post_meta( $post_id, '_sro_notice_pdf' );
	}
}
add_action( 'save_post_sro_notice', 'sro_notice_save' );

/**
 * Admin list columns.
 */
function sro_notice_columns( $cols ) {
	return array(
		'cb'        => $cols['cb'],
		'title'     => 'শিরোনাম',
		'sro_files' => 'ফাইল',
		'date'      => 'তারিখ',
	);
}
add_filter( 'manage_sro_notice_posts_columns', 'sro_notice_columns' );

function sro_notice_column_data( $col, $post_id ) {
	if ( 'sro_files' !== $col ) {
		return;
	}
	$f = sro_notice_files( $post_id );
	echo $f['pdf_url'] ? '<span style="color:#1a7f37;">&#10003; PDF</span>' : '<span style="color:#a0a8a3;">&#8211; PDF</span>';
	echo '<br>';
	echo $f['img_url'] ? '<span style="color:#1a7f37;">&#10003; ছবি</span>' : '<span style="color:#a0a8a3;">&#8211; ছবি</span>';
}
add_action( 'manage_sro_notice_posts_custom_column', 'sro_notice_column_data', 10, 2 );

/**
 * Admin assets: media picker + a little CSS on the edit screen.
 */
function sro_notice_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'sro_notice' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'sro-notice-admin', get_template_directory_uri() . '/assets/js/notice-admin.js', array( 'jquery' ), SRO_VERSION, true );
	wp_add_inline_style(
		'wp-admin',
		'#sro_notice_pdf_box .inside{margin:0;padding:0}
		.sro-box{padding:16px 20px 8px}
		.sro-hint{display:block;color:#646970;font-size:12px;margin-top:3px}
		#titlediv #title{font-size:20px;padding:10px 12px;height:auto}'
	);
}
add_action( 'admin_enqueue_scripts', 'sro_notice_admin_assets' );

/**
 * Notices for the home page ticker: newest first.
 *
 * @return array[] Each item has text and url.
 */
function sro_latest_notices( $limit = 5 ) {
	$items = array();
	$q     = new WP_Query(
		array(
			'post_type'           => 'sro_notice',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	foreach ( $q->posts as $p ) {
		$items[] = array(
			'text' => get_the_title( $p ),
			'url'  => get_permalink( $p ),
		);
	}
	wp_reset_postdata();
	return $items;
}
