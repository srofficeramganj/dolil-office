<?php
/**
 * Service: registration documents finder.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$docs = sro_registration_documents();
?>
<div class="section">
	<div class="container container-narrow">
		<?php sro_service_head( 'registration-documents' ); ?>

		<div class="tool" data-sro-tool="docs">
			<script type="application/json" id="docs-data"><?php echo wp_json_encode( $docs, JSON_UNESCAPED_UNICODE ); ?></script>

			<form class="tool-card" novalidate>
				<div class="form-row">
					<label for="docs-type">দলিলের ধরন</label>
					<select id="docs-type">
						<option value="">— দলিলের ধরন নির্বাচন করুন —</option>
						<?php foreach ( $docs['types'] as $key => $t ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $t['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</form>

			<section class="result-card" id="docs-result" aria-live="polite" hidden>
				<h2 id="docs-title">প্রয়োজনীয় কাগজপত্র</h2>
				<p class="progress-text"><strong id="docs-count">০</strong> / <strong id="docs-total">০</strong> টি প্রস্তুত</p>
				<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="docs-bar" aria-label="প্রস্তুতির অগ্রগতি">
					<span id="docs-fill"></span>
				</div>
				<ul class="doc-list" id="docs-list"></ul>

				<div class="tool-actions">
					<button type="button" class="btn btn-outline" id="docs-reset">রিসেট</button>
					<button type="button" class="btn" id="docs-print">প্রিন্ট / PDF সংরক্ষণ</button>
				</div>
			</section>

			<p class="result-empty" id="docs-empty">উপরে দলিলের ধরন বাছাই করলে প্রয়োজনীয় কাগজপত্রের তালিকা দেখা যাবে।</p>

			<p class="notice-box">দলিলের ধরন ও পরিস্থিতি অনুযায়ী প্রয়োজনীয় কাগজপত্র পরিবর্তিত হতে পারে। চূড়ান্ত তালিকার জন্য সংশ্লিষ্ট সাব-রেজিস্ট্রার অফিসে যোগাযোগ করুন।</p>
			<p class="hint">এই তালিকা সাধারণ নির্দেশনা। প্রতিটি কাগজ সব দলিলের জন্য আইনত বাধ্যতামূলক—এমন দাবি করা হচ্ছে না।</p>
		</div>
	</div>
</div>
<?php
get_footer();
