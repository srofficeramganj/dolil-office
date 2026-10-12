<?php
/**
 * Fallback template (blog / generic lists).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="section">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<?php if ( is_home() && ! is_front_page() ) : ?>
				<header class="page-head"><h1><?php single_post_title(); ?></h1></header>
			<?php endif; ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article class="entry" style="margin-bottom:16px;">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="entry-content"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="dw-empty"><p>কোনো লেখা পাওয়া যায়নি।</p></div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
