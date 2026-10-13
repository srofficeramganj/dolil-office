<?php
/**
 * Services page (slug: services): citizen services + the deed writers' smart
 * calculator, which opens inline on this same page (no extra URL).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$units = sro_land_units();

/** <option> list for the land units. */
$unit_options = function ( $selected ) use ( $units ) {
	$html = '';
	foreach ( $units as $k => $label ) {
		$html .= sprintf( '<option value="%1$s"%3$s>%2$s</option>', esc_attr( $k ), esc_html( $label ), selected( $k, $selected, false ) );
	}
	return $html;
};

/** Katha/bigha standard selector + custom values (prefix = unique id prefix). */
$standard_box = function ( $prefix ) {
	?>
	<fieldset class="sc-stdbox">
		<legend>কাঠা ও বিঘার মান <small>(এলাকাভেদে ভিন্ন হতে পারে)</small></legend>
		<div class="sc-field">
			<label for="<?php echo esc_attr( $prefix ); ?>-std">ব্যবহৃত মান</label>
			<select id="<?php echo esc_attr( $prefix ); ?>-std" class="sc-std">
				<option value="common">প্রচলিত মান: ১ কাঠা = ৭২০ বর্গফুট, ১ বিঘা = ২০ কাঠা = ১৪,৪০০ বর্গফুট</option>
				<option value="bigha33">১ বিঘা = ৩৩ শতাংশ (১৪,৩৭৪.৮ বর্গফুট), ১ কাঠা = ১ বিঘার ২০ ভাগের ১</option>
				<option value="custom">নিজের মান দিন</option>
			</select>
		</div>
		<div class="sc-custom" hidden>
			<div class="sc-field">
				<label for="<?php echo esc_attr( $prefix ); ?>-katha">১ কাঠা = কত বর্গফুট</label>
				<input type="text" id="<?php echo esc_attr( $prefix ); ?>-katha" class="sc-katha" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৭২০">
				<p class="sc-err" role="alert" hidden></p>
			</div>
			<div class="sc-field">
				<label for="<?php echo esc_attr( $prefix ); ?>-bigha">১ বিঘা = কত বর্গফুট</label>
				<input type="text" id="<?php echo esc_attr( $prefix ); ?>-bigha" class="sc-bigha" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১৪৪০০">
				<p class="sc-err" role="alert" hidden></p>
			</div>
		</div>
		<p class="sc-hint">কাঠা বা বিঘা না থাকলে এই মান ফলাফলে প্রভাব ফেলে না।</p>
	</fieldset>
	<?php
};

/** Action buttons. */
$actions = function () {
	?>
	<div class="sc-actions">
		<button type="button" class="btn" data-act="calc"><?php sro_icon( 'calculator', 18 ); ?><span>গণনা করুন</span></button>
		<button type="button" class="btn btn-outline" data-act="reset"><?php sro_icon( 'rotate-ccw', 18 ); ?><span>রিসেট</span></button>
		<button type="button" class="btn btn-outline" data-act="copy" disabled><?php sro_icon( 'copy', 18 ); ?><span>ফলাফল কপি</span></button>
		<button type="button" class="btn btn-outline" data-act="print" disabled><?php sro_icon( 'printer', 18 ); ?><span>প্রিন্ট</span></button>
	</div>
	<?php
};

/** Result card. */
$result = function () {
	?>
	<section class="sc-result" aria-live="polite">
		<h3>ফলাফল</h3>
		<p class="sc-empty">তথ্য লিখুন, এখানে ফলাফল দেখা যাবে।</p>
		<div class="sc-out" hidden>
			<p class="sc-headline"></p>
			<p class="sc-warn" role="alert" hidden></p>
			<dl class="sc-rows result-dl"></dl>
			<p class="sc-note" hidden></p>
		</div>
	</section>
	<?php
};
?>
<div class="section">
	<div class="container">
		<header class="page-head">
			<h1>সেবা সমূহ</h1>
			<p>নাগরিক ও দলিল লেখকদের জন্য প্রয়োজনীয় অনলাইন সেবা।</p>
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

			<article class="service-card" style="--accent:#E60012">
				<span class="service-card-icon"><?php sro_icon( 'calculator', 40 ); ?></span>
				<h2>দলিল লেখকদের বিশেষ ক্যালকুলেটর</h2>
				<p>জমির পরিমাপ, অংশ অনুযায়ী জমির হিসাব, শতকরা হিসাব ও জমির মূল্য নির্ণয় করুন সহজেই।</p>
				<button type="button" class="btn" id="smart-calc-open" aria-expanded="false" aria-controls="smart-calc"><span class="sc-open-label">ক্যালকুলেটর ব্যবহার করুন →</span></button>
			</article>

			<article class="service-card" style="--accent:#006838">
				<span class="service-card-icon"><?php sro_icon( 'landmark', 40 ); ?></span>
				<h2>সরকারি ভূমি সেবার শর্টকাট</h2>
				<p>খতিয়ান, ই-নামজারি, ভূমি উন্নয়ন কর, মৌজা ম্যাপ ও অন্যান্য সরকারি ভূমি সেবায় সহজেই প্রবেশ করুন।</p>
				<button type="button" class="btn" id="gov-open" aria-expanded="false" aria-controls="gov-shortcuts"><span class="gov-open-label">সরকারি সেবাসমূহ দেখুন →</span></button>
			</article>
		</div>


		<section id="gov-shortcuts" class="gov-shortcuts" aria-labelledby="gov-h" hidden>
			<div class="sc-head">
				<h2 id="gov-h"><?php sro_icon( 'landmark', 26 ); ?> সরকারি ভূমি সেবার শর্টকাট</h2>
				<button type="button" class="btn btn-outline" id="gov-close">বন্ধ করুন</button>
			</div>
			<p class="gov-leave"><?php sro_icon( 'external-link', 18 ); ?> নিচের যেকোনো বাটনে ক্লিক করলে আপনি <strong>SR-Office Ramganj ওয়েবসাইট ছেড়ে সরকারি ওয়েবসাইটে</strong> যাবেন (নতুন ট্যাবে খুলবে)।</p>

			<div class="gov-grid">
				<?php foreach ( sro_gov_shortcuts() as $g ) : ?>
					<article class="gov-card">
						<span class="gov-icon"><?php sro_icon( $g['icon'], 30 ); ?></span>
						<h3><?php echo esc_html( $g['title'] ); ?></h3>
						<p class="gov-note"><?php echo esc_html( $g['note'] ); ?></p>
						<p class="gov-domain"><?php sro_icon( 'external-link', 14 ); ?> <span>সরকারি ওয়েবসাইট: <?php echo esc_html( $g['domain'] ); ?></span></p>
						<a class="btn gov-btn" href="<?php echo esc_url( $g['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<span><?php echo esc_html( $g['btn'] ); ?></span> <?php sro_icon( 'external-link', 18 ); ?>
							<span class="screen-reader-text">(নতুন ট্যাবে সরকারি ওয়েবসাইট খুলবে)</span>
						</a>
					</article>
				<?php endforeach; ?>
			</div>

			<p class="sc-disclaimer">এখানে সরকারি ভূমি সেবার ওয়েবসাইটগুলোর লিংক প্রদান করা হয়েছে। সংশ্লিষ্ট সেবাগুলো সরকারি কর্তৃপক্ষের নিজস্ব ওয়েবসাইটে পরিচালিত হয়।</p>
			<p class="gov-small">এই ওয়েবসাইটে কোনো লগইন তথ্য, এনআইডি বা সরকারি পোর্টালের পাসওয়ার্ড সংগ্রহ বা সংরক্ষণ করা হয় না। লিংকগুলো ১৩ অক্টোবর ২০২৬ তারিখে যাচাই করা হয়েছে।</p>
		</section>

		<section id="smart-calc" class="smart-calc" aria-labelledby="smart-calc-h" hidden>
			<div class="sc-head">
				<h2 id="smart-calc-h"><?php sro_icon( 'calculator', 26 ); ?> দলিল লেখকদের বিশেষ ক্যালকুলেটর</h2>
				<button type="button" class="btn btn-outline" id="smart-calc-close">বন্ধ করুন</button>
			</div>

			<div class="sc-tabs" role="tablist" aria-label="ক্যালকুলেটর বাছুন">
				<button type="button" role="tab" id="sc-tab-measure" aria-controls="sc-tool-measure" aria-selected="true"><?php sro_icon( 'ruler', 22 ); ?><span>জমির পরিমাপ</span></button>
				<button type="button" role="tab" id="sc-tab-share" aria-controls="sc-tool-share" aria-selected="false" tabindex="-1"><?php sro_icon( 'chart-pie', 22 ); ?><span>অংশ অনুযায়ী জমির হিসাব</span></button>
				<button type="button" role="tab" id="sc-tab-percent" aria-controls="sc-tool-percent" aria-selected="false" tabindex="-1"><?php sro_icon( 'percent', 22 ); ?><span>শতকরা হিসাব</span></button>
				<button type="button" role="tab" id="sc-tab-value" aria-controls="sc-tool-value" aria-selected="false" tabindex="-1"><?php sro_icon( 'calculator', 22 ); ?><span>জমির মূল্য হিসাব</span></button>
			</div>

			<!-- 1. জমির পরিমাপ -->
			<div class="sc-tool" id="sc-tool-measure" role="tabpanel" aria-labelledby="sc-tab-measure">
				<h3 class="sc-title">জমির পরিমাপ ক্যালকুলেটর</h3>
				<p class="sc-hint">জমির পরিমাণ এক একক থেকে অন্য এককে রূপান্তর করুন। বাংলা বা ইংরেজি সংখ্যা চলবে।</p>
				<div class="sc-card">
					<div class="sc-field">
						<label for="sc1-qty">জমির পরিমাণ</label>
						<input type="text" id="sc1-qty" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৫ অথবা 5.5">
						<p class="sc-err" role="alert" hidden></p>
					</div>
					<div class="sc-pair">
						<div class="sc-field">
							<label for="sc1-from">যে একক থেকে রূপান্তর করবেন</label>
							<select id="sc1-from" class="sc-from"><?php echo $unit_options( 'shotangsho' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select>
						</div>
						<button type="button" class="btn btn-outline sc-swap" data-act="swap" aria-label="একক অদলবদল করুন"><?php sro_icon( 'arrow-left-right', 20 ); ?><span>অদলবদল</span></button>
						<div class="sc-field">
							<label for="sc1-to">যে এককে রূপান্তর করবেন</label>
							<select id="sc1-to" class="sc-to" data-default-index="5"><?php echo $unit_options( 'sqft' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select>
						</div>
					</div>
					<?php $standard_box( 'sc1' ); ?>
					<?php $actions(); ?>
				</div>
				<?php $result(); ?>
			</div>

			<!-- 2. অংশ অনুযায়ী জমির হিসাব -->
			<div class="sc-tool" id="sc-tool-share" role="tabpanel" aria-labelledby="sc-tab-share" hidden>
				<h3 class="sc-title">অংশ অনুযায়ী জমির হিসাব</h3>
				<p class="sc-hint">মোট জমির পরিমাণ লিখে ভগ্নাংশ, শতকরা অথবা একাধিক অংশীদারের অংশ হিসাব করুন।</p>
				<div class="sc-card">
					<div class="sc-pair sc-pair-simple">
						<div class="sc-field">
							<label for="sc2-total">মোট জমির পরিমাণ</label>
							<input type="text" id="sc2-total" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৩০">
							<p class="sc-err" role="alert" hidden></p>
						</div>
						<div class="sc-field">
							<label for="sc2-unit">জমির একক</label>
							<select id="sc2-unit"><?php echo $unit_options( 'shotangsho' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select>
						</div>
					</div>

					<fieldset class="sc-modes">
						<legend>অংশের ধরন</legend>
						<label><input type="radio" name="sc2-mode" value="fraction" checked> ভগ্নাংশ</label>
						<label><input type="radio" name="sc2-mode" value="percent"> শতকরা</label>
						<label><input type="radio" name="sc2-mode" value="owners"> একাধিক অংশীদার</label>
					</fieldset>

					<div class="sc-mode-box" data-mode="fraction">
						<div class="sc-pair sc-pair-simple">
							<div class="sc-field">
								<label for="sc2-num">লব (উপরের সংখ্যা)</label>
								<input type="text" id="sc2-num" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১">
								<p class="sc-err" role="alert" hidden></p>
							</div>
							<div class="sc-field">
								<label for="sc2-den">হর (নিচের সংখ্যা)</label>
								<input type="text" id="sc2-den" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৩">
								<p class="sc-err" role="alert" hidden></p>
							</div>
						</div>
					</div>

					<div class="sc-mode-box" data-mode="percent" hidden>
						<div class="sc-field">
							<label for="sc2-pct">শতকরা অংশ (%)</label>
							<input type="text" id="sc2-pct" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৩০">
							<p class="sc-err" role="alert" hidden></p>
						</div>
					</div>

					<div class="sc-mode-box" data-mode="owners" hidden>
						<div id="sc2-owners"></div>
						<button type="button" class="btn btn-outline" id="sc2-add">+ অংশীদার যোগ করুন</button>
					</div>

					<?php $actions(); ?>
				</div>
				<?php $result(); ?>
				<p class="sc-disclaimer">এই হিসাবটি প্রদত্ত অংশের ভিত্তিতে গাণিতিক হিসাব। আইনগত মালিকানা বা উত্তরাধিকার নির্ধারণের বিকল্প নয়।</p>
			</div>

			<!-- 3. শতকরা হিসাব -->
			<div class="sc-tool" id="sc-tool-percent" role="tabpanel" aria-labelledby="sc-tab-percent" hidden>
				<h3 class="sc-title">শতকরা হিসাব</h3>
				<p class="sc-hint">যেকোনো সংখ্যার শতকরা মান, শতকরা হার অথবা বৃদ্ধি/হ্রাস বের করুন।</p>
				<div class="sc-card">
					<fieldset class="sc-modes">
						<legend>কী বের করবেন?</legend>
						<label><input type="radio" name="sc3-mode" value="of" checked> শতকরা মান</label>
						<label><input type="radio" name="sc3-mode" value="what"> শতকরা হার</label>
						<label><input type="radio" name="sc3-mode" value="change"> বৃদ্ধি / হ্রাস</label>
					</fieldset>

					<div class="sc-mode-box" data-mode="of">
						<div class="sc-pair sc-pair-simple">
							<div class="sc-field">
								<label for="sc3-total">মোট পরিমাণ</label>
								<input type="text" id="sc3-total" inputmode="decimal" autocomplete="off" placeholder="যেমন: ২০০">
								<p class="sc-err" role="alert" hidden></p>
							</div>
							<div class="sc-field">
								<label for="sc3-pct">শতাংশের হার (%)</label>
								<input type="text" id="sc3-pct" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১৫">
								<p class="sc-err" role="alert" hidden></p>
							</div>
						</div>
					</div>

					<div class="sc-mode-box" data-mode="what" hidden>
						<div class="sc-pair sc-pair-simple">
							<div class="sc-field">
								<label for="sc3-part">অংশের পরিমাণ</label>
								<input type="text" id="sc3-part" inputmode="decimal" autocomplete="off" placeholder="যেমন: ২৫">
								<p class="sc-err" role="alert" hidden></p>
							</div>
							<div class="sc-field">
								<label for="sc3-whole">মোট পরিমাণ</label>
								<input type="text" id="sc3-whole" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১০০">
								<p class="sc-err" role="alert" hidden></p>
							</div>
						</div>
					</div>

					<div class="sc-mode-box" data-mode="change" hidden>
						<div class="sc-pair sc-pair-simple">
							<div class="sc-field">
								<label for="sc3-orig">মূল মূল্য</label>
								<input type="text" id="sc3-orig" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১০০০০০">
								<p class="sc-err" role="alert" hidden></p>
							</div>
							<div class="sc-field">
								<label for="sc3-rate">শতকরা হার (%)</label>
								<input type="text" id="sc3-rate" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১০">
								<p class="sc-err" role="alert" hidden></p>
							</div>
						</div>
						<div class="sc-field">
							<label for="sc3-dir">বৃদ্ধি না হ্রাস?</label>
							<select id="sc3-dir">
								<option value="increase">বৃদ্ধি (+)</option>
								<option value="decrease">হ্রাস (−)</option>
							</select>
						</div>
					</div>

					<?php $actions(); ?>
				</div>
				<?php $result(); ?>
			</div>

			<!-- 4. জমির মূল্য হিসাব -->
			<div class="sc-tool" id="sc-tool-value" role="tabpanel" aria-labelledby="sc-tab-value" hidden>
				<h3 class="sc-title">জমির মূল্য হিসাব</h3>
				<p class="sc-hint">জমির পরিমাণ ও আপনার জানা প্রতি এককের মূল্য দিন, মোট মূল্য হিসাব হবে।</p>
				<div class="sc-card">
					<div class="sc-pair sc-pair-simple">
						<div class="sc-field">
							<label for="sc4-qty">জমির পরিমাণ</label>
							<input type="text" id="sc4-qty" inputmode="decimal" autocomplete="off" placeholder="যেমন: ১০">
							<p class="sc-err" role="alert" hidden></p>
						</div>
						<div class="sc-field">
							<label for="sc4-qunit">জমির একক</label>
							<select id="sc4-qunit"><?php echo $unit_options( 'shotangsho' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select>
						</div>
					</div>
					<div class="sc-pair sc-pair-simple">
						<div class="sc-field">
							<label for="sc4-price">প্রতি এককের মূল্য (টাকা)</label>
							<input type="text" id="sc4-price" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৩০০০০০">
							<p class="sc-err" role="alert" hidden></p>
						</div>
						<div class="sc-field">
							<label for="sc4-punit">মূল্যের একক (প্রতি ... )</label>
							<select id="sc4-punit"><?php echo $unit_options( 'decimal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></select>
						</div>
					</div>
					<?php $standard_box( 'sc4' ); ?>
					<?php $actions(); ?>
				</div>
				<?php $result(); ?>
				<p class="sc-disclaimer">এই মূল্য ব্যবহারকারীর দেওয়া তথ্যের ভিত্তিতে হিসাব করা হয়েছে। এটি সরকারি মৌজা রেট বা বাজারমূল্যের সরকারি নিশ্চয়তা নয়।</p>
			</div>
		</section>
	</div>
</div>
<?php
get_footer();
