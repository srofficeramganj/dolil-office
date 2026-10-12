<?php
/**
 * Service: land purchase verification checklist.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$groups = sro_purchase_checklist();
$n      = 0;
?>
<div class="section">
	<div class="container container-narrow">
		<?php sro_service_head( 'land-purchase-checklist' ); ?>

		<div class="tool" data-sro-tool="checklist" data-store="sro_purchase_checklist">
			<section class="progress-card" aria-live="polite">
				<p class="progress-text"><strong id="cl-done">০</strong> টি যাচাই সম্পন্ন, <strong id="cl-todo">০</strong> টি বাকি, <strong id="cl-na">০</strong> টি প্রযোজ্য নয়</p>
				<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="cl-bar" aria-label="যাচাইয়ের অগ্রগতি">
					<span id="cl-fill"></span>
				</div>
				<p class="progress-pct" id="cl-pct">অগ্রগতি: ০%</p>
				<p class="hint">এই অগ্রগতি আপনার নিজের চেকলিস্টের অবস্থা দেখায়; এটি জমির আইনগত নিরাপত্তার প্রমাণ নয়।</p>
			</section>

			<?php foreach ( $groups as $title => $items ) : ?>
				<fieldset class="check-group">
					<legend><?php echo esc_html( $title ); ?></legend>
					<?php
					foreach ( $items as $item ) :
						$n++;
						$name = 'item' . $n;
						?>
						<div class="check-item" data-item="<?php echo esc_attr( $name ); ?>">
							<p class="check-label"><?php echo esc_html( $item ); ?></p>
							<div class="check-options" role="radiogroup" aria-label="<?php echo esc_attr( $item ); ?>">
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>" value="done"> <span>যাচাই সম্পন্ন</span></label>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>" value="todo" checked> <span>যাচাই বাকি</span></label>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>" value="na"> <span>প্রযোজ্য নয়</span></label>
							</div>
						</div>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>

			<div class="tool-actions">
				<button type="button" class="btn btn-outline" id="cl-reset">রিসেট</button>
				<button type="button" class="btn" id="cl-print">প্রিন্ট / PDF সংরক্ষণ</button>
			</div>

			<p class="notice-box">এই চেকলিস্টটি শুধুমাত্র তথ্য ও সচেতনতার জন্য। জমির প্রকৃত মালিকানা ও আইনগত অবস্থা নিশ্চিত করতে সংশ্লিষ্ট কর্তৃপক্ষ এবং প্রয়োজনে আইনজীবীর পরামর্শ নিন।</p>
		</div>
	</div>
</div>
<?php
get_footer();
