<?php
/**
 * Site footer: useful links, office info, social links.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$phone    = sro_opt( 'office_phone' );
$email    = sro_opt( 'office_email' );
$address  = sro_opt( 'office_address' );
$hours    = sro_opt( 'office_hours' );
$archive  = get_post_type_archive_link( 'deed_writer' );
$archive  = $archive ? $archive : home_url( '/deed-writers/' );
$social   = array();
$networks = array(
	'whatsapp' => array( 'হোয়াটসঅ্যাপ', 'office_whatsapp' ),
	'facebook' => array( 'ফেসবুক', 'office_facebook' ),
	'youtube'  => array( 'ইউটিউব', 'office_youtube' ),
);
$paths    = function_exists( 'sro_social_paths' ) ? sro_social_paths() : array();
foreach ( $networks as $key => $def ) {
	$url = sro_social_url( $key, trim( (string) sro_opt( $def[1] ) ) );
	if ( $url && isset( $paths[ $key ] ) ) {
		$social[] = array( $key, $def[0], $url, $paths[ $key ] );
	}
}
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div>
				<h2 class="footer-h">গুরুত্বপূর্ণ লিংক</h2>
				<ul class="footer-links">
					<li><a href="<?php echo esc_url( $archive ); ?>">দলিল লেখক তালিকা</a></li>
					<li><a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">সেবা সমূহ</a></li>
					<li><a href="<?php echo esc_url( home_url( '/notice/' ) ); ?>">নোটিশ ও তথ্য</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">যোগাযোগ</a></li>
					<li><a href="https://bangladesh.gov.bd/" target="_blank" rel="noopener noreferrer">বাংলাদেশ জাতীয় তথ্য বাতায়ন</a></li>
					<li><a href="https://minland.gov.bd/" target="_blank" rel="noopener noreferrer">ভূমি মন্ত্রণালয়</a></li>
					<li><a href="https://land.gov.bd/" target="_blank" rel="noopener noreferrer">ভূমি তথ্য বাতায়ন</a></li>
				</ul>
			</div>
			<div>
				<h2 class="footer-h"><?php bloginfo( 'name' ); ?></h2>
				<ul class="footer-info">
					<?php if ( $address ) : ?><li><?php echo esc_html( $address ); ?></li><?php endif; ?>
					<?php if ( $hours ) : ?><li>অফিস সময়: <?php echo esc_html( $hours ); ?></li><?php endif; ?>
					<?php if ( $phone ) : ?><li>ফোন: <a href="tel:<?php echo esc_attr( sro_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
					<?php if ( $email ) : ?><li>ইমেইল: <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
					<?php if ( ! $address && ! $hours && ! $phone && ! $email ) : ?>
						<li>রামগঞ্জ, লক্ষ্মীপুর</li>
					<?php endif; ?>
				</ul>
			</div>
			<div>
				<h2 class="footer-h">সামাজিক যোগাযোগ</h2>
				<?php if ( $social ) : ?>
					<ul class="dw-social footer-social">
						<?php foreach ( $social as $s ) : ?>
							<li>
								<a class="soc soc-<?php echo esc_attr( $s[0] ); ?>" href="<?php echo esc_url( $s[2] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $s[1] ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $s[3] ); ?>"/></svg>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="footer-note">সোশ্যাল মিডিয়ার লিংক Customizer → অফিসের তথ্য থেকে যোগ করা যাবে।</p>
				<?php endif; ?>
			</div>
		</div>
		<p class="copyright">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>। সর্বস্বত্ব সংরক্ষিত।</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
