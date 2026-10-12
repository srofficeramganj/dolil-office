<?php
/**
 * 404 error state.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="section">
	<div class="container">
		<div class="dw-empty">
			<h1>পেজটি পাওয়া যায়নি</h1>
			<p>আপনি যে পেজটি খুঁজছেন সেটি সরানো হয়েছে বা ঠিকানা ভুল।</p>
			<p><a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">হোম পেজে ফিরে যান</a></p>
		</div>
	</div>
</div>
<?php
get_footer();
