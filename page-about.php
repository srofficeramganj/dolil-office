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
			<h2 class="section-title" id="committee-h"><?php echo esc_html( sro_opt( 'committee_title', 'রামগঞ্জ দলিল লেখক সমিতি' ) ); ?></h2>
			<p class="committee-sub"><?php echo esc_html( sro_opt( 'committee_sub', 'ত্রি-বার্ষিক কার্যকরী পরিষদ ২০২৬ খ্রি.' ) ); ?></p>

			<?php
			if ( $members ) :
				$tiers = array( 1 => array(), 2 => array(), 3 => array(), 4 => array() );
				foreach ( $members as $m ) {
					$tiers[ sro_committee_tier( get_post_meta( $m->ID, '_sro_position', true ) ) ][] = $m;
				}
				foreach ( $tiers as $t => $list ) :
					if ( ! $list ) {
						continue;
					}
					?>
					<div class="panel-tier tier-row-<?php echo (int) $t; ?>">
						<?php
						foreach ( $list as $m ) {
							sro_committee_card( $m, $t );
						}
						?>
					</div>
				<?php endforeach; ?>
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
