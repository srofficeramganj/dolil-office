<?php
/**
 * Executive committee: members with photo, position and phone, shown on the About page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sro_register_committee() {
	register_post_type(
		'sro_committee',
		array(
			'labels'              => array(
				'name'                  => 'কার্যকরী কমিটি',
				'singular_name'         => 'কমিটি সদস্য',
				'add_new'               => 'নতুন সদস্য',
				'add_new_item'          => 'নতুন সদস্য যোগ করুন',
				'edit_item'             => 'সদস্যের প্রোফাইল',
				'all_items'             => 'সব সদস্য',
				'featured_image'        => 'সদস্যের ছবি',
				'set_featured_image'    => 'ছবি বসান',
				'remove_featured_image' => 'ছবি সরান',
				'use_featured_image'    => 'এই ছবি ব্যবহার করুন',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-groups',
			'menu_position'       => 8,
			'supports'            => array( 'title', 'thumbnail' ),
			'show_in_rest'        => false,
		)
	);
}
add_action( 'init', 'sro_register_committee' );

function sro_committee_no_block_editor( $use, $post_type ) {
	return 'sro_committee' === $post_type ? false : $use;
}
add_filter( 'use_block_editor_for_post_type', 'sro_committee_no_block_editor', 10, 2 );

function sro_committee_title_placeholder( $text, $post ) {
	return ( $post && 'sro_committee' === $post->post_type ) ? 'এখানে সদস্যের নাম লিখুন' : $text;
}
add_filter( 'enter_title_here', 'sro_committee_title_placeholder', 10, 2 );

/**
 * Meta box: position, mobile, serial.
 */
function sro_committee_metabox() {
	remove_meta_box( 'postimagediv', 'sro_committee', 'side' );
	add_meta_box( 'postimagediv', 'সদস্যের ছবি', 'post_thumbnail_meta_box', 'sro_committee', 'side', 'high' );
	add_meta_box( 'sro_committee_box', 'সদস্যের তথ্য', 'sro_committee_metabox_html', 'sro_committee', 'normal', 'high' );
}
add_action( 'add_meta_boxes_sro_committee', 'sro_committee_metabox' );

function sro_committee_metabox_html( $post ) {
	wp_nonce_field( 'sro_committee_save', 'sro_committee_nonce' );
	$pos    = get_post_meta( $post->ID, '_sro_position', true );
	$mobile = get_post_meta( $post->ID, '_sro_mobile', true );
	$serial = get_post_meta( $post->ID, '_sro_serial', true );
	?>
	<div class="sro-box">
		<div class="sro-grid">
			<p class="sro-field">
				<label for="_sro_position">পদবী</label>
				<input type="text" id="_sro_position" name="_sro_position" value="<?php echo esc_attr( $pos ); ?>" placeholder="যেমন: সভাপতি, সাধারণ সম্পাদক, সদস্য">
			</p>
			<p class="sro-field">
				<label for="_sro_mobile">মোবাইল নম্বর (ঐচ্ছিক)</label>
				<input type="tel" id="_sro_mobile" name="_sro_mobile" value="<?php echo esc_attr( $mobile ); ?>" placeholder="01XXXXXXXXX">
			</p>
			<p class="sro-field">
				<label for="_sro_serial">ক্রম (ছোট নম্বর আগে দেখাবে)</label>
				<input type="number" id="_sro_serial" name="_sro_serial" value="<?php echo esc_attr( '' !== $serial && 999 !== (int) $serial ? $serial : '' ); ?>" min="1" placeholder="যেমন: ১ = সভাপতি, ২ = সহ-সভাপতি…">
				<span class="sro-hint">নম্বর না দিলে সবার শেষে আসবে।</span>
			</p>
		</div>
		<p class="sro-hint">নাম উপরের ঘরে লিখুন। ছবি ডানদিকের "সদস্যের ছবি" বক্স থেকে বসান। শেষে ডানদিকের নীল <strong>Publish / Update</strong> বাটন চাপুন।</p>
	</div>
	<?php
}

function sro_committee_save( $post_id ) {
	if ( ! isset( $_POST['sro_committee_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sro_committee_nonce'] ) ), 'sro_committee_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['_sro_position'] ) ) {
		update_post_meta( $post_id, '_sro_position', sanitize_text_field( wp_unslash( $_POST['_sro_position'] ) ) );
	}
	if ( isset( $_POST['_sro_mobile'] ) ) {
		update_post_meta( $post_id, '_sro_mobile', sanitize_text_field( wp_unslash( $_POST['_sro_mobile'] ) ) );
	}
	$serial = isset( $_POST['_sro_serial'] ) ? absint( $_POST['_sro_serial'] ) : 0;
	update_post_meta( $post_id, '_sro_serial', $serial > 0 ? $serial : 999 );
}
add_action( 'save_post_sro_committee', 'sro_committee_save' );

/**
 * Admin list: photo, name, position, serial, mobile (sorted by serial).
 */
function sro_committee_columns( $cols ) {
	return array(
		'cb'            => $cols['cb'],
		'sro_c_thumb'   => 'ছবি',
		'title'         => 'নাম',
		'sro_c_pos'     => 'পদবী',
		'sro_c_serial'  => 'ক্রম',
		'sro_c_mobile'  => 'মোবাইল',
	);
}
add_filter( 'manage_sro_committee_posts_columns', 'sro_committee_columns' );

function sro_committee_column_data( $col, $post_id ) {
	switch ( $col ) {
		case 'sro_c_thumb':
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:50%;width:48px;height:48px;object-fit:cover;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span style="display:inline-block;width:48px;height:48px;border-radius:50%;background:#e5ebe7;"></span>';
			}
			break;
		case 'sro_c_pos':
			echo esc_html( get_post_meta( $post_id, '_sro_position', true ) );
			break;
		case 'sro_c_serial':
			$s = (int) get_post_meta( $post_id, '_sro_serial', true );
			echo $s && 999 !== $s ? esc_html( $s ) : '&mdash;';
			break;
		case 'sro_c_mobile':
			echo esc_html( get_post_meta( $post_id, '_sro_mobile', true ) );
			break;
	}
}
add_action( 'manage_sro_committee_posts_custom_column', 'sro_committee_column_data', 10, 2 );

function sro_committee_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'sro_committee' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', '_sro_serial' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'sro_committee_admin_order' );

function sro_committee_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || 'sro_committee' !== $screen->post_type ) {
		return;
	}
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_add_inline_style(
			'wp-admin',
			'#sro_committee_box .inside{margin:0;padding:0}
			.sro-box{padding:16px 20px 8px}
			.sro-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:4px 20px}
			.sro-field{margin:0 0 12px}
			.sro-field label{display:block;font-weight:600;font-size:14px;margin-bottom:4px}
			.sro-field input{width:100%;min-height:40px;font-size:15px}
			.sro-hint{display:block;color:#646970;font-size:12px;margin-top:3px}
			#titlediv #title{font-size:20px;padding:10px 12px;height:auto}'
		);
	}
}
add_action( 'admin_enqueue_scripts', 'sro_committee_admin_assets' );

/* --------------------------------------------------------------------
 * Quick CSV import: name, position, mobile, serial
 * ------------------------------------------------------------------ */

function sro_committee_import_menu() {
	add_submenu_page( 'edit.php?post_type=sro_committee', 'CSV থেকে ইমপোর্ট', 'CSV ইমপোর্ট', 'manage_options', 'sro-committee-import', 'sro_committee_import_page' );
}
add_action( 'admin_menu', 'sro_committee_import_menu' );

function sro_committee_import_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'অনুমতি নেই।' );
	}
	check_admin_referer( 'sro_committee_import' );
	$back = admin_url( 'edit.php?post_type=sro_committee&page=sro-committee-import' );

	if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) {
		wp_safe_redirect( add_query_arg( 'sro_err', 'file', $back ) );
		exit;
	}
	$name = isset( $_FILES['csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ) : '';
	if ( 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
		wp_safe_redirect( add_query_arg( 'sro_err', 'type', $back ) );
		exit;
	}
	$content = (string) file_get_contents( $_FILES['csv']['tmp_name'] );
	$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );
	$lines   = array_values( array_filter( preg_split( '/\r\n|\r|\n/', $content ), 'strlen' ) );
	if ( count( $lines ) < 2 ) {
		wp_safe_redirect( add_query_arg( 'sro_err', 'empty', $back ) );
		exit;
	}
	$head = array_map( 'strtolower', array_map( 'trim', str_getcsv( array_shift( $lines ) ) ) );
	$added   = 0;
	$skipped = 0;
	foreach ( $lines as $line ) {
		$cells = str_getcsv( $line );
		$row   = array();
		foreach ( $head as $i => $key ) {
			$row[ $key ] = isset( $cells[ $i ] ) ? trim( $cells[ $i ] ) : '';
		}
		$title = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
		if ( '' === $title ) {
			$skipped++;
			continue;
		}
		$position = isset( $row['position'] ) ? sanitize_text_field( $row['position'] ) : '';
		$exists   = get_posts(
			array(
				'post_type'      => 'sro_committee',
				'post_status'    => 'any',
				'title'          => $title,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_sro_position',
				'meta_value'     => $position,
			)
		);
		if ( $exists ) {
			$skipped++;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'sro_committee',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		if ( is_wp_error( $id ) || ! $id ) {
			$skipped++;
			continue;
		}
		$serial = isset( $row['serial'] ) ? absint( sro_mouza_digits( $row['serial'] ) ) : 0;
		update_post_meta( $id, '_sro_position', $position );
		update_post_meta( $id, '_sro_mobile', isset( $row['mobile'] ) ? sanitize_text_field( $row['mobile'] ) : '' );
		update_post_meta( $id, '_sro_serial', $serial > 0 ? $serial : 999 );
		$added++;
	}
	wp_safe_redirect(
		add_query_arg(
			array(
				'sro_added'   => $added,
				'sro_skipped' => $skipped,
			),
			$back
		)
	);
	exit;
}
add_action( 'admin_post_sro_committee_import', 'sro_committee_import_handle' );

function sro_committee_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$err   = isset( $_GET['sro_err'] ) ? sanitize_key( wp_unslash( $_GET['sro_err'] ) ) : '';
	$added = isset( $_GET['sro_added'] ) ? (int) $_GET['sro_added'] : null;
	$skip  = isset( $_GET['sro_skipped'] ) ? (int) $_GET['sro_skipped'] : 0;
	// phpcs:enable
	$errors = array(
		'file'  => 'ফাইল আপলোড হয়নি।',
		'type'  => 'শুধু .csv ফাইল দিন।',
		'empty' => 'ফাইলে কোনো সারি নেই।',
	);
	?>
	<div class="wrap">
		<h1>কমিটি সদস্য CSV ইমপোর্ট</h1>
		<?php if ( $err && isset( $errors[ $err ] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $errors[ $err ] ); ?></p></div>
		<?php elseif ( null !== $added ) : ?>
			<div class="notice notice-success"><p>যোগ হয়েছে: <strong><?php echo esc_html( $added ); ?></strong>, বাদ (নাম নেই বা আগেই আছে): <strong><?php echo esc_html( $skip ); ?></strong>। ছবি প্রতিটি সদস্যের প্রোফাইলে গিয়ে বসান।</p></div>
		<?php endif; ?>
		<p>প্রথম সারিতে কলামের নাম: <code>name, position, mobile, serial</code></p>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sro_committee_import">
			<?php wp_nonce_field( 'sro_committee_import' ); ?>
			<p><input type="file" name="csv" accept=".csv" required></p>
			<p><button class="button button-primary" type="submit">ইমপোর্ট করুন</button></p>
		</form>
	</div>
	<?php
}

/**
 * Members, ordered by serial then name.
 *
 * @return WP_Post[]
 */
function sro_committee_members() {
	return get_posts(
		array(
			'post_type'      => 'sro_committee',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_key'       => '_sro_serial',
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'title'          => 'ASC',
			),
		)
	);
}

/**
 * Panel row for a position: 1 = president, 2 = vice president / general secretary,
 * 3 = other office bearers, 4 = ordinary members.
 */
function sro_committee_tier( $position ) {
	$p = trim( preg_replace( '/\s+/u', ' ', (string) $position ) );
	if ( 'সভাপতি' === $p ) {
		return 1;
	}
	if ( in_array( $p, array( 'সহ-সভাপতি', 'সহ সভাপতি', 'সহসভাপতি', 'সাধারণ সম্পাদক', 'সাধারন সম্পাদক' ), true ) ) {
		return 2;
	}
	if ( false !== mb_strpos( $p, 'সদস্য' ) ) {
		return 4;
	}
	return 3;
}

/**
 * Print one member card.
 */
function sro_committee_card( $m, $tier ) {
	$pos    = get_post_meta( $m->ID, '_sro_position', true );
	$mobile = get_post_meta( $m->ID, '_sro_mobile', true );
	?>
	<article class="member tier-<?php echo (int) $tier; ?>">
		<div class="member-photo">
			<?php if ( has_post_thumbnail( $m->ID ) ) : ?>
				<?php echo get_the_post_thumbnail( $m->ID, 'dw_card', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr( $m->post_title ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg>
			<?php endif; ?>
		</div>
		<h3><?php echo esc_html( $m->post_title ); ?></h3>
		<?php if ( $pos ) : ?><p class="member-role"><?php echo esc_html( $pos ); ?></p><?php endif; ?>
		<?php if ( $mobile ) : ?>
			<a class="member-call" href="tel:<?php echo esc_attr( sro_tel( $mobile ) ); ?>"><?php echo esc_html( $mobile ); ?></a>
		<?php endif; ?>
	</article>
	<?php
}

/**
 * One-time setup: make sure the "কার্যকরী কমিটি" page exists (it is linked from
 * the footer and the home page, not from the main menu).
 */
function sro_committee_setup() {
	if ( '1' === get_option( 'sro_committee_setup' ) ) {
		return;
	}
	$page = get_page_by_path( 'committee' );
	if ( $page ) {
		$page_id = (int) $page->ID;
	} else {
		$page_id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'কার্যকরী কমিটি',
				'post_name'    => 'committee',
				'post_content' => '',
			)
		);
	}
	if ( ! $page_id ) {
		return;
	}

	update_option( 'sro_committee_setup', '1', false );
}
add_action( 'init', 'sro_committee_setup', 99 );

/**
 * One-time clean-up of the main menu (earlier versions added a committee item
 * and renamed the home item): remove the committee item and call the item that
 * points to the front page "আমাদের সম্পর্কে".
 */
function sro_menu_cleanup() {
	if ( '1' === get_option( 'sro_menu_fix_2' ) ) {
		return;
	}
	$locations = get_nav_menu_locations();
	if ( empty( $locations['primary'] ) ) {
		update_option( 'sro_menu_fix_2', '1', false );
		return;
	}
	$menu_id      = (int) $locations['primary'];
	$committee    = get_page_by_path( 'committee' );
	$committee_id = $committee ? (int) $committee->ID : 0;
	$front        = (int) get_option( 'page_on_front' );

	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( 'page' === $item->object && $committee_id && (int) $item->object_id === $committee_id ) {
			wp_delete_post( $item->ID, true );
			continue;
		}
		if ( 'page' === $item->object && $front && (int) $item->object_id === $front && 'হোম' === $item->title ) {
			wp_update_nav_menu_item(
				$menu_id,
				$item->ID,
				array(
					'menu-item-title'     => 'আমাদের সম্পর্কে',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $item->object_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $item->menu_order,
				)
			);
		}
	}
	update_option( 'sro_menu_fix_2', '1', false );
}
add_action( 'init', 'sro_menu_cleanup', 100 );
