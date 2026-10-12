<?php
/**
 * One deed writer profile card.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$id      = get_the_ID();
$cert    = get_post_meta( $id, '_sro_cert', true );
$mobile  = get_post_meta( $id, '_sro_mobile', true );
$address = get_post_meta( $id, '_sro_address', true );
$terms   = get_the_terms( $id, 'writer_area' );
$areas   = ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'slug' ) : array();
$search  = trim( get_the_title() . ' ' . $cert . ' ' . $mobile . ' ' . $address );
?>
<article class="dw-card" data-search="<?php echo esc_attr( $search ); ?>" data-area="<?php echo esc_attr( implode( ' ', $areas ) ); ?>">
	<a class="dw-photo" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'dw_card', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
		<?php else : ?>
			<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg>
		<?php endif; ?>
	</a>
	<div class="dw-body">
		<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<dl class="dw-meta">
			<?php if ( $cert ) : ?>
				<div><dt>সনদ নং</dt><dd><?php echo esc_html( $cert ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $mobile ) : ?>
				<div><dt>মোবাইল</dt><dd><?php echo esc_html( $mobile ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $address ) : ?>
				<div><dt>ঠিকানা</dt><dd><?php echo esc_html( $address ); ?></dd></div>
			<?php endif; ?>
		</dl>
		<div class="dw-actions">
			<?php if ( $mobile ) : ?>
				<a class="btn" href="tel:<?php echo esc_attr( sro_tel( $mobile ) ); ?>">কল করুন</a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<a class="btn btn-outline" href="<?php the_permalink(); ?>">বিস্তারিত</a>
		</div>
	</div>
</article>
