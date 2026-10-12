<?php
/**
 * Contact page (slug: contact).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$phone   = sro_opt( 'office_phone' );
$email   = sro_opt( 'office_email' );
$address = sro_opt( 'office_address' );
$hours   = sro_opt( 'office_hours' );
$info    = sro_office_info();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sent  = isset( $_GET['sent'] );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$error = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
?>
<div class="section">
	<div class="container">
		<header class="page-head"><h1><?php the_title(); ?></h1></header>

		<div class="contact-grid">
			<section class="entry" aria-labelledby="info-h">
				<h2 id="info-h">অফিসের তথ্য</h2>
				<dl class="info-dl">
					<div><dt>অফিসের নাম</dt><dd><?php echo esc_html( $info['name'] ); ?></dd></div>
					<div><dt>উপজেলা</dt><dd><?php echo esc_html( $info['upazila'] ); ?></dd></div>
					<div><dt>জেলা</dt><dd><?php echo esc_html( $info['district'] ); ?></dd></div>
					<div><dt>বিভাগ</dt><dd><?php echo esc_html( $info['division'] ); ?></dd></div>
					<div><dt>দেশ</dt><dd><?php echo esc_html( $info['country'] ); ?></dd></div>
					<?php if ( $address ) : ?><div><dt>বিস্তারিত ঠিকানা</dt><dd><?php echo esc_html( $address ); ?></dd></div><?php endif; ?>
					<div><dt>Office Address (English)</dt><dd lang="en"><?php echo esc_html( $info['address_en'] ); ?></dd></div>
					<?php if ( $phone ) : ?><div><dt>ফোন</dt><dd><a href="tel:<?php echo esc_attr( sro_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></dd></div><?php endif; ?>
					<?php if ( $email ) : ?><div><dt>ইমেইল</dt><dd><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></dd></div><?php endif; ?>
					<?php if ( $hours ) : ?><div><dt>অফিস সময়</dt><dd><?php echo esc_html( $hours ); ?></dd></div><?php endif; ?>
				</dl>
				<?php if ( $phone ) : ?>
					<p style="margin-top:16px;"><a class="btn btn-block" href="tel:<?php echo esc_attr( sro_tel( $phone ) ); ?>">এখনই কল করুন</a></p>
				<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
					<p class="footer-note">(শুধু অ্যাডমিন দেখছেন) ফোন, ইমেইল ও অফিস সময় যোগ করতে Appearance → Customize → "অফিসের তথ্য" এ যান।</p>
				<?php endif; ?>
			</section>

			<section class="entry" aria-labelledby="form-h">
				<h2 id="form-h">বার্তা পাঠান</h2>

				<?php if ( $sent ) : ?>
					<div class="notice notice-success" role="status">আপনার বার্তা পাঠানো হয়েছে। ধন্যবাদ।</div>
				<?php elseif ( $error ) : ?>
					<div class="notice notice-error" role="alert">
						<?php echo 'missing' === $error ? 'নাম, বার্তা এবং ফোন বা ইমেইল দিন।' : 'বার্তা পাঠানো যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন বা ফোনে যোগাযোগ করুন।'; ?>
					</div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sro_contact">
					<?php wp_nonce_field( 'sro_contact', 'sro_contact_nonce' ); ?>
					<div class="hp-field" aria-hidden="true">
						<label for="website">এই ঘরটি খালি রাখুন</label>
						<input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
					</div>
					<div class="form-row">
						<label for="c-name">আপনার নাম</label>
						<input type="text" id="c-name" name="name" required autocomplete="name">
					</div>
					<div class="form-row">
						<label for="c-phone">মোবাইল নম্বর</label>
						<input type="tel" id="c-phone" name="phone" inputmode="tel" autocomplete="tel">
					</div>
					<div class="form-row">
						<label for="c-email">ইমেইল (ঐচ্ছিক)</label>
						<input type="email" id="c-email" name="email" inputmode="email" autocomplete="email">
					</div>
					<div class="form-row">
						<label for="c-message">বার্তা</label>
						<textarea id="c-message" name="message" required></textarea>
					</div>
					<button class="btn btn-block" type="submit">বার্তা পাঠান</button>
				</form>
			</section>
		</div>

		<?php $map = sro_map_urls(); ?>
		<section class="entry contact-map" aria-labelledby="map-h" style="margin-top:24px;">
			<h2 id="map-h">অফিসের অবস্থান</h2>
			<div class="footer-map">
				<iframe title="অফিসের অবস্থান (Google Maps)" src="<?php echo esc_url( $map['embed'] ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
			</div>
			<p style="margin-top:12px;"><a class="btn" href="<?php echo esc_url( $map['link'] ); ?>" target="_blank" rel="noopener noreferrer">দিক নির্দেশনা পান (Google Maps)</a></p>
		</section>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( get_the_content() ) ) :
				?>
				<div class="entry entry-content" style="margin-top:24px;"><?php the_content(); ?></div>
				<?php
			endif;
		endwhile;
		?>
	</div>
</div>
<?php
get_footer();
