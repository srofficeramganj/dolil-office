<?php
/**
 * Generic page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="section">
	<div class="container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="entry">
				<header class="page-head"><h1><?php the_title(); ?></h1></header>
				<div class="entry-content">
					<?php
					if ( trim( get_the_content() ) ) {
						the_content();
					} else {
						echo '<p>এই পেজের তথ্য শীঘ্রই যোগ করা হবে।</p>';
					}
					?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</div>
<?php
get_footer();
