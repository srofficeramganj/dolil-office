<?php
/**
 * Service: land measurement calculator.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$units = sro_land_units();
?>
<div class="section">
	<div class="container container-narrow">
		<?php sro_service_head( 'land-measurement-calculator' ); ?>

		<div class="tool" data-sro-tool="calc">
			<form class="tool-card" novalidate>
				<div class="form-row">
					<label for="calc-qty">জমির পরিমাণ</label>
					<input type="text" id="calc-qty" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৫ অথবা 5.5">
					<p class="field-error" id="calc-qty-err" role="alert" hidden></p>
				</div>

				<div class="calc-units">
					<div class="form-row">
						<label for="calc-from">যে একক থেকে রূপান্তর করবেন</label>
						<select id="calc-from">
							<?php foreach ( $units as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $k, 'shotangsho' ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<button type="button" class="btn btn-outline calc-swap" id="calc-swap" aria-label="একক অদলবদল করুন">&#8645; অদলবদল</button>
					<div class="form-row">
						<label for="calc-to">যে এককে রূপান্তর করবেন</label>
						<select id="calc-to">
							<?php foreach ( $units as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $k, 'sqft' ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<fieldset class="std-box">
					<legend>কাঠা ও বিঘার মান <small>(এলাকাভেদে ভিন্ন হতে পারে)</small></legend>
					<div class="form-row">
						<label for="calc-std">ব্যবহৃত মান</label>
						<select id="calc-std">
							<option value="common">প্রচলিত মান: ১ কাঠা = ৭২০ বর্গফুট, ১ বিঘা = ২০ কাঠা = ১৪,৪০০ বর্গফুট</option>
							<option value="bigha33">১ বিঘা = ৩৩ শতাংশ (১৪,৩৭৪.৮ বর্গফুট), ১ কাঠা = ১ বিঘার ২০ ভাগের ১</option>
							<option value="custom">নিজের মান দিন</option>
						</select>
					</div>
					<div class="std-custom" id="calc-custom" hidden>
						<div class="form-row">
							<label for="calc-katha-sqft">১ কাঠা = কত বর্গফুট</label>
							<input type="text" id="calc-katha-sqft" inputmode="decimal" placeholder="যেমন: ৭২০">
						</div>
						<div class="form-row">
							<label for="calc-bigha-sqft">১ বিঘা = কত বর্গফুট</label>
							<input type="text" id="calc-bigha-sqft" inputmode="decimal" placeholder="যেমন: ১৪৪০০">
						</div>
					</div>
					<p class="hint">কাঠা বা বিঘা না থাকলে এই মান ফলাফলে প্রভাব ফেলে না। আপনার দলিলে ব্যবহৃত মান নিশ্চিত হয়ে নিন।</p>
				</fieldset>

				<div class="tool-actions">
					<button type="button" class="btn btn-outline" id="calc-reset">রিসেট</button>
					<button type="button" class="btn btn-outline" id="calc-copy" disabled>ফলাফল কপি করুন</button>
					<button type="button" class="btn btn-outline" id="calc-print" disabled>প্রিন্ট করুন</button>
				</div>
			</form>

			<section class="result-card" id="calc-result" aria-live="polite" aria-labelledby="calc-result-h">
				<h2 id="calc-result-h">ফলাফল</h2>
				<p class="result-empty" id="calc-empty">জমির পরিমাণ লিখলে এখানে ফলাফল দেখা যাবে।</p>
				<div id="calc-output" hidden>
					<p class="result-big" id="calc-big"></p>
					<dl class="result-dl">
						<div><dt>মূল পরিমাণ</dt><dd id="calc-orig"></dd></div>
						<div><dt>রূপান্তরিত পরিমাণ</dt><dd id="calc-conv"></dd></div>
						<div><dt>রূপান্তর গুণক</dt><dd id="calc-factor"></dd></div>
						<div><dt>ব্যবহৃত মানদণ্ড</dt><dd id="calc-standard"></dd></div>
					</dl>
				</div>
			</section>

			<p class="hint">ধ্রুবক: ১ ডেসিমেল (শতাংশ) = ৪৩৫.৬ বর্গফুট, ১ একর = ১০০ ডেসিমেল, ১ বর্গমিটার ≈ ১০.৭৬৩৯১০৪ বর্গফুট। এটি একটি তথ্যভিত্তিক হিসাব, আইনগত মাপের বিকল্প নয়।</p>
		</div>
	</div>
</div>
<?php
get_footer();
