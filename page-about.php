<?php
/**
 * About page (slug: about): office introduction + executive committee profiles.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$members = sro_committee_members();
$info    = sro_office_info();
?>
<div class="section">
	<div class="container">
		<header class="page-head about-head">
			<h1><?php the_title(); ?></h1>
			<p><?php echo esc_html( $info['name'] ); ?>, <?php echo esc_html( $info['district'] ); ?></p>
		</header>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( get_the_content() ) ) :
				?>
				<div class="entry entry-content about-intro"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>

		<section class="committee" aria-labelledby="committee-h">
			<h2 class="section-title" id="committee-h">বর্তমান কার্যকরী কমিটি</h2>

			<?php if ( $members ) : ?>
				<div class="member-grid">
					<?php
					foreach ( $members as $i => $m ) :
						$pos    = get_post_meta( $m->ID, '_sro_position', true );
						$mobile = get_post_meta( $m->ID, '_sro_mobile', true );
						?>
						<article class="member<?php echo 0 === $i ? ' is-lead' : ''; ?>">
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
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="dw-empty">
					<p><strong>কমিটির সদস্যদের তথ্য শীঘ্রই যোগ করা হবে।</strong></p>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<p class="footer-note">(শুধু অ্যাডমিন দেখছেন) ড্যাশবোর্ডে <strong>কার্যকরী কমিটি → নতুন সদস্য</strong> থেকে সদস্য যোগ করুন।</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
</div>
<?php
get_footer();
