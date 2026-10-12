<?php
/**
 * Deed writer directory: instant search + area filter, 3/2/1 column grid.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
global $wp_query;
$areas = get_terms( array( 'taxonomy' => 'writer_area', 'hide_empty' => true ) );
$total = (int) $wp_query->found_posts;
?>
<div class="section">
	<div class="container">
		<header class="page-head">
			<h1>দলিল লেখক তালিকা</h1>
			<p>নাম, সনদ নম্বর, মোবাইল নম্বর বা ঠিকানা লিখে খুঁজুন।</p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="dw-tools" role="search">
				<div class="field">
					<label for="dw-search">খুঁজুন</label>
					<input type="search" id="dw-search" placeholder="নাম, সনদ বা মোবাইল নম্বর" autocomplete="off" enterkeyhint="search">
				</div>
				<?php if ( $areas && ! is_wp_error( $areas ) ) : ?>
					<div class="field">
						<label for="dw-area">এলাকা</label>
						<select id="dw-area">
							<option value="">সব এলাকা</option>
							<?php foreach ( $areas as $area ) : ?>
								<option value="<?php echo esc_attr( $area->slug ); ?>"><?php echo esc_html( $area->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
			</div>

			<p class="dw-status" id="dw-status" role="status" aria-live="polite" data-total="<?php echo esc_attr( $total ); ?>">
				মোট <?php echo esc_html( $total ); ?> জন দলিল লেখক
			</p>

			<div class="dw-grid" id="dw-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card', 'deed-writer' );
				endwhile;
				?>
			</div>

			<div class="dw-empty" id="dw-empty" hidden>
				<p><strong>কোনো দলিল লেখক পাওয়া যায়নি।</strong></p>
				<p>অন্যভাবে লিখে খুঁজুন, অথবা সব তালিকা দেখুন।</p>
				<button class="btn btn-outline" type="button" id="dw-reset">সব তালিকা দেখুন</button>
			</div>
		<?php else : ?>
			<div class="dw-empty">
				<p><strong>এখনো কোনো দলিল লেখক যোগ করা হয়নি।</strong></p>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
