<?php
/**
 * Notice board (page slug: notice): list of notices with PDF / image.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged = isset( $_GET['np'] ) ? max( 1, absint( $_GET['np'] ) ) : 1;
$query = new WP_Query(
	array(
		'post_type'      => 'sro_notice',
		'posts_per_page' => 10,
		'paged'          => $paged,
	)
);
?>
<div class="section">
	<div class="container">
		<header class="page-head">
			<h1><?php the_title(); ?></h1>
		</header>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( get_the_content() ) ) :
				?>
				<div class="entry entry-content" style="margin-bottom:20px;"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>

		<?php if ( $query->have_posts() ) : ?>
			<div class="notice-list">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$files = sro_notice_files( get_the_ID() );
					?>
					<article class="notice-card">
						<?php if ( $files['img_url'] ) : ?>
							<a class="notice-thumb" href="<?php echo esc_url( $files['img_url'] ); ?>" target="_blank" rel="noopener">
								<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
							</a>
						<?php endif; ?>
						<div class="notice-body">
							<time class="notice-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( sro_bn_date( get_post_time( 'U', true ) ) ); ?></time>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( trim( get_the_content() ) ) : ?>
								<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 30, '…' ) ); ?></p>
							<?php endif; ?>
							<div class="notice-actions">
								<?php if ( $files['pdf_url'] ) : ?>
									<a class="btn" href="<?php echo esc_url( $files['pdf_url'] ); ?>" target="_blank" rel="noopener">PDF দেখুন</a>
									<a class="btn btn-outline" href="<?php echo esc_url( $files['pdf_url'] ); ?>" download>ডাউনলোড</a>
								<?php endif; ?>
								<?php if ( $files['img_url'] ) : ?>
									<a class="btn btn-outline" href="<?php echo esc_url( $files['img_url'] ); ?>" target="_blank" rel="noopener">ছবি দেখুন</a>
								<?php endif; ?>
								<?php if ( ! $files['pdf_url'] && ! $files['img_url'] ) : ?>
									<a class="btn btn-outline" href="<?php the_permalink(); ?>">বিস্তারিত</a>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			$links = paginate_links(
				array(
					'base'      => add_query_arg( 'np', '%#%', get_permalink() ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $query->max_num_pages,
					'prev_text' => '&larr; আগের',
					'next_text' => 'পরের &rarr;',
				)
			);
			if ( $links ) {
				echo '<nav class="pagination" aria-label="পেজ">' . wp_kses_post( $links ) . '</nav>';
			}
			wp_reset_postdata();
			?>
		<?php else : ?>
			<div class="dw-empty"><p><strong>এখনো কোনো নোটিশ প্রকাশ করা হয়নি।</strong></p></div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
