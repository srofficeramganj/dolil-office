<?php
/**
 * Single deed writer profile.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$archive = get_post_type_archive_link( 'deed_writer' );
?>
<div class="section">
	<div class="container">
		<p><a href="<?php echo esc_url( $archive ? $archive : home_url( '/deed-writers/' ) ); ?>">&larr; সব দলিল লেখক</a></p>
		<?php
		while ( have_posts() ) :
			the_post();
			$id      = get_the_ID();
			$cert    = get_post_meta( $id, '_sro_cert', true );
			$mobile  = get_post_meta( $id, '_sro_mobile', true );
			$address = get_post_meta( $id, '_sro_address', true );
			$terms   = get_the_terms( $id, 'writer_area' );
			?>
			<article class="entry">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="single-photo"><?php the_post_thumbnail( 'large' ); ?></div>
				<?php endif; ?>
				<h1><?php the_title(); ?></h1>
				<dl class="dw-meta" style="max-width:520px;">
					<?php if ( $cert ) : ?><div><dt>সনদ নং</dt><dd><?php echo esc_html( $cert ); ?></dd></div><?php endif; ?>
					<?php if ( $mobile ) : ?><div><dt>মোবাইল</dt><dd><?php echo esc_html( $mobile ); ?></dd></div><?php endif; ?>
					<?php if ( $address ) : ?><div><dt>ঠিকানা</dt><dd><?php echo esc_html( $address ); ?></dd></div><?php endif; ?>
					<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
						<div><dt>এলাকা</dt><dd><?php echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ); ?></dd></div>
					<?php endif; ?>
				</dl>
				<div class="dw-foot" style="margin-top:20px;max-width:520px;">
					<?php if ( $mobile ) : ?>
						<div class="dw-actions">
							<a class="btn" href="tel:<?php echo esc_attr( sro_tel( $mobile ) ); ?>">কল করুন</a>
						</div>
					<?php endif; ?>
					<?php sro_render_social( $id ); ?>
				</div>
				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="entry-content" style="margin-top:20px;"><?php the_content(); ?></div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	</div>
</div>
<?php
get_footer();
