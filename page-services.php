<?php
/**
 * Services page (slug: services): the four public citizen services.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="section">
	<div class="container">
		<header class="page-head">
			<h1>সেবা সমূহ</h1>
			<p>নাগরিকদের জন্য প্রয়োজনীয় চারটি অনলাইন সেবা।</p>
		</header>

		<div class="service-cards">
			<?php foreach ( sro_services_registry() as $slug => $s ) : ?>
				<article class="service-card" style="--accent:<?php echo esc_attr( $s['color'] ); ?>">
					<span class="service-card-icon"><?php sro_icon( $s['icon'], 40 ); ?></span>
					<h2><?php echo esc_html( $s['title'] ); ?></h2>
					<p><?php echo esc_html( $s['desc'] ); ?></p>
					<a class="btn" href="<?php echo esc_url( home_url( '/services/' . $slug . '/' ) ); ?>"><?php echo esc_html( $s['cta'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php
get_footer();
