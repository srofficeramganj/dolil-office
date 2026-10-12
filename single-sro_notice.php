<?php
/**
 * Single notice.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="section">
	<div class="container">
		<p><a href="<?php echo esc_url( home_url( '/notice/' ) ); ?>">&larr; সব নোটিশ</a></p>
		<?php
		while ( have_posts() ) :
			the_post();
			$files = sro_notice_files( get_the_ID() );
			?>
			<article class="entry">
				<time class="notice-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( sro_bn_date( get_post_time( 'U', true ) ) ); ?></time>
				<h1><?php the_title(); ?></h1>

				<?php if ( $files['img_url'] ) : ?>
					<p><a href="<?php echo esc_url( $files['img_url'] ); ?>" target="_blank" rel="noopener"><?php the_post_thumbnail( 'large', array( 'alt' => get_the_title() ) ); ?></a></p>
				<?php endif; ?>

				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php endif; ?>

				<?php if ( $files['pdf_url'] || $files['img_url'] ) : ?>
					<div class="notice-actions" style="margin-top:20px;">
						<?php if ( $files['pdf_url'] ) : ?>
							<a class="btn" href="<?php echo esc_url( $files['pdf_url'] ); ?>" target="_blank" rel="noopener">PDF দেখুন</a>
							<a class="btn btn-outline" href="<?php echo esc_url( $files['pdf_url'] ); ?>" download>PDF ডাউনলোড</a>
						<?php endif; ?>
						<?php if ( $files['img_url'] ) : ?>
							<a class="btn btn-outline" href="<?php echo esc_url( $files['img_url'] ); ?>" target="_blank" rel="noopener">ছবি বড় করে দেখুন</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	</div>
</div>
<?php
get_footer();
