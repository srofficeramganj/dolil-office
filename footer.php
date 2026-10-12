<?php
/**
 * Site footer.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$phone   = sro_opt( 'office_phone' );
$email   = sro_opt( 'office_email' );
$address = sro_opt( 'office_address' );
$hours   = sro_opt( 'office_hours' );
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div>
				<p><strong><?php bloginfo( 'name' ); ?></strong></p>
				<?php if ( $address ) : ?><p><?php echo esc_html( $address ); ?></p><?php endif; ?>
				<?php if ( $hours ) : ?><p>অফিস সময়: <?php echo esc_html( $hours ); ?></p><?php endif; ?>
			</div>
			<div>
				<?php if ( $phone ) : ?>
					<p>ফোন: <a href="tel:<?php echo esc_attr( sro_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<p>ইমেইল: <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<p class="copyright">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>। সর্বস্বত্ব সংরক্ষিত।</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
