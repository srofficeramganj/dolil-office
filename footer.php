<?php
/**
 * Site footer: office identity, useful links, contact details, social links and map.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$info     = sro_office_info();
$phone    = sro_opt( 'office_phone' );
$email    = sro_opt( 'office_email' );
$address  = sro_opt( 'office_address' );
$hours    = sro_opt( 'office_hours' );
$archive  = get_post_type_archive_link( 'deed_writer' );
$archive  = $archive ? $archive : home_url( '/deed-writers/' );
$map      = sro_map_urls();
$social   = array();
$networks = array(
	'whatsapp' => array( 'হোয়াটসঅ্যাপ', 'office_whatsapp' ),
	'facebook' => array( 'ফেসবুক', 'office_facebook' ),
	'youtube'  => array( 'ইউটিউব', 'office_youtube' ),
);
$paths    = sro_social_paths();
foreach ( $networks as $key => $def ) {
	$url = sro_social_url( $key, trim( (string) sro_opt( $def[1] ) ) );
	if ( $url && isset( $paths[ $key ] ) ) {
		$social[] = array( $key, $def[0], $url, $paths[ $key ] );
	}
}
$icons = array(
	'phone' => 'M6 3h3l2 5-2 1.5a11 11 0 0 0 5.5 5.5L16 13l5 2v3a3 3 0 0 1-3 3A15 15 0 0 1 3 6a3 3 0 0 1 3-3z',
	'mail'  => 'M3 6h18v12H3zM3 7l9 7 9-7',
	'clock' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7v5l3 2',
	'pin'   => 'M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
);
$icon = function ( $name ) use ( $icons ) {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . esc_attr( $icons[ $name ] ) . '"/></svg>';
};
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-brand">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-horizontal.png' ); ?>" width="772" height="214" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" loading="lazy">
				<p class="footer-office"><?php echo esc_html( $info['name'] ); ?></p>
				<p class="footer-address">
					<?php if ( $address ) : ?>
						<?php echo esc_html( $address ); ?><br>
					<?php endif; ?>
					<?php echo esc_html( $info['upazila'] . ', ' . $info['district'] . ', ' . $info['division'] . ', ' . $info['country'] ); ?>
				</p>
				<p class="footer-address-en" lang="en"><?php echo esc_html( $info['address_en'] ); ?></p>
			</div>

			<div>
				<h2 class="footer-h">গুরুত্বপূর্ণ লিংক</h2>
				<ul class="footer-links">
					<li><a href="<?php echo esc_url( $archive ); ?>">দলিল লেখক তালিকা</a></li>
					<li><a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">সেবা সমূহ</a></li>
					<li><a href="<?php echo esc_url( home_url( '/notice/' ) ); ?>">নোটিশ ও তথ্য</a></li>
					<li><a href="<?php echo esc_url( home_url( '/committee/' ) ); ?>">কার্যকরী কমিটি</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">যোগাযোগ</a></li>
					<li><a href="https://bangladesh.gov.bd/" target="_blank" rel="noopener noreferrer">বাংলাদেশ জাতীয় তথ্য বাতায়ন</a></li>
					<li><a href="https://minland.gov.bd/" target="_blank" rel="noopener noreferrer">ভূমি মন্ত্রণালয়</a></li>
					<li><a href="https://land.gov.bd/" target="_blank" rel="noopener noreferrer">ভূমি তথ্য বাতায়ন</a></li>
				</ul>
			</div>

			<div>
				<h2 class="footer-h">যোগাযোগ</h2>
				<ul class="contact-list">
					<li>
						<span class="ci"><?php echo $icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span>
							<small>অফিস মোবাইল</small>
							<?php if ( $phone && sro_phone_ok( $phone ) ) : ?>
								<a href="tel:<?php echo esc_attr( sro_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
							<?php elseif ( $phone ) : ?>
								<strong><?php echo esc_html( $phone ); ?></strong>
							<?php else : ?>
								<em>শীঘ্রই যোগ করা হবে</em>
							<?php endif; ?>
						</span>
					</li>
					<li>
						<span class="ci"><?php echo $icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span>
							<small>ইমেইল</small>
							<?php if ( $email ) : ?>
								<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">বার্তা পাঠান</a>
							<?php endif; ?>
						</span>
					</li>
					<?php if ( $hours ) : ?>
						<li>
							<span class="ci"><?php echo $icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><small>অফিস সময়</small><?php echo esc_html( $hours ); ?></span>
						</li>
					<?php endif; ?>
					<li>
						<span class="ci"><?php echo $icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><small>অবস্থান</small><a href="<?php echo esc_url( $map['link'] ); ?>" target="_blank" rel="noopener noreferrer">Google Maps-এ দেখুন</a></span>
					</li>
				</ul>
				<?php if ( $social ) : ?>
					<ul class="dw-social footer-social" aria-label="সামাজিক যোগাযোগ">
						<?php foreach ( $social as $s ) : ?>
							<li>
								<a class="soc soc-<?php echo esc_attr( $s[0] ); ?>" href="<?php echo esc_url( $s[2] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $s[1] ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $s[3] ); ?>"/></svg>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>

		<div class="footer-map">
			<iframe
				title="<?php echo esc_attr( $info['name'] ); ?> এর অবস্থান (Google Maps)"
				src="<?php echo esc_url( $map['embed'] ); ?>"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				allowfullscreen></iframe>
			<a class="btn footer-map-btn" href="<?php echo esc_url( $map['link'] ); ?>" target="_blank" rel="noopener noreferrer">দিক নির্দেশনা পান (Google Maps)</a>
		</div>

		<p class="copyright">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $info['name'] ); ?>। সর্বস্বত্ব সংরক্ষিত।</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
