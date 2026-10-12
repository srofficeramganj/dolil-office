<?php
/**
 * Service: mouza rate information and reference value calculator.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$units    = sro_land_units();
$has_data = sro_mouza_count() > 0;
?>
<div class="section">
	<div class="container container-narrow">
		<?php sro_service_head( 'mouza-rate' ); ?>

		<div class="tool" data-sro-tool="mouza" data-api="<?php echo esc_url( rest_url( 'sro/v1/mouza/' ) ); ?>" data-has-data="<?php echo $has_data ? '1' : '0'; ?>">

			<?php if ( ! $has_data ) : ?>
				<div class="notice-box notice-pending" role="status">
					<strong>মৌজা রেটের তথ্য এখনো সংযুক্ত করা হয়নি।</strong>
					সরকারি যাচাইকৃত তথ্য যুক্ত হলে এখানে মৌজা ও জমির শ্রেণি অনুযায়ী রেট দেখা যাবে। তথ্য না থাকায় কোনো মূল্য হিসাব করা হচ্ছে না।
				</div>
			<?php endif; ?>

			<form class="tool-card" novalidate>
				<div class="form-row">
					<label for="mz-district">জেলা</label>
					<select id="mz-district"><option value="লক্ষ্মীপুর">লক্ষ্মীপুর</option></select>
				</div>
				<div class="form-row">
					<label for="mz-upazila">উপজেলা</label>
					<select id="mz-upazila"><option value="রামগঞ্জ">রামগঞ্জ</option></select>
				</div>
				<div class="form-row">
					<label for="mz-search">মৌজার নাম খুঁজুন</label>
					<input type="search" id="mz-search" autocomplete="off" placeholder="মৌজার নাম লিখুন" <?php disabled( ! $has_data ); ?>>
				</div>
				<div class="form-row">
					<label for="mz-mouza">মৌজা</label>
					<select id="mz-mouza" <?php disabled( ! $has_data ); ?>><option value="">— মৌজা নির্বাচন করুন —</option></select>
					<p class="hint" id="mz-mouza-hint" hidden></p>
				</div>
				<div class="form-row">
					<label for="mz-category">জমির শ্রেণি</label>
					<select id="mz-category" disabled><option value="">— আগে মৌজা নির্বাচন করুন —</option></select>
				</div>

				<div class="calc-units">
					<div class="form-row">
						<label for="mz-qty">জমির পরিমাণ</label>
						<input type="text" id="mz-qty" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৫ অথবা 5.5" disabled>
					</div>
					<div class="form-row">
						<label for="mz-unit">পরিমাপের একক</label>
						<select id="mz-unit" disabled>
							<?php foreach ( $units as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $k, 'shotangsho' ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<p class="field-error" id="mz-error" role="alert" hidden></p>
				<p class="hint">কাঠা/বিঘা এককে প্রচলিত মান (১ কাঠা = ৭২০ বর্গফুট, ১ বিঘা = ১৪,৪০০ বর্গফুট) ব্যবহৃত হয়; এটি এলাকাভেদে ভিন্ন হতে পারে।</p>

				<div class="tool-actions">
					<button type="button" class="btn btn-outline" id="mz-reset">ফিল্টার পরিষ্কার করুন</button>
					<button type="button" class="btn btn-outline" id="mz-print" disabled>প্রিন্ট করুন</button>
				</div>
			</form>

			<section class="result-card" id="mz-result" aria-live="polite" aria-labelledby="mz-result-h">
				<h2 id="mz-result-h">ফলাফল</h2>
				<p class="result-empty" id="mz-empty">
					<?php echo $has_data ? 'মৌজা ও জমির শ্রেণি নির্বাচন করলে এখানে তথ্য দেখা যাবে।' : 'মৌজা রেটের তথ্য এখনো সংযুক্ত করা হয়নি।'; ?>
				</p>
				<div id="mz-output" hidden>
					<p class="result-big" id="mz-big"></p>
					<dl class="result-dl" id="mz-dl"></dl>
				</div>
			</section>

			<p class="notice-box">এখানে প্রদর্শিত মূল্য সংশ্লিষ্ট সরকারি মৌজা রেটের ভিত্তিতে একটি তথ্যভিত্তিক হিসাব। এটি জমির প্রকৃত বাজারমূল্য বা চূড়ান্ত রেজিস্ট্রেশন খরচ নয়।</p>
		</div>
	</div>
</div>
<?php
get_footer();
