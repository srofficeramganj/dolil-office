<?php
/**
 * Home page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$archive = get_post_type_archive_link( 'deed_writer' );
$archive = $archive ? $archive : home_url( '/deed-writers/' );
?>

<section class="hero">
	<div class="container">
		<h1><?php bloginfo( 'name' ); ?></h1>
		<p>এলাকার দলিল লেখকদের নাম, সনদ নম্বর ও মোবাইল নম্বর এক জায়গায়। নাম, সনদ নম্বর বা মোবাইল নম্বর দিয়ে খুঁজুন।</p>
		<form class="hero-search" role="search" method="get" action="<?php echo esc_url( $archive ); ?>">
			<div class="field">
				<label class="screen-reader-text" for="hero-q">দলিল লেখক খুঁজুন</label>
				<input type="search" id="hero-q" name="q" placeholder="নাম, সনদ বা মোবাইল নম্বর লিখুন" autocomplete="off" enterkeyhint="search">
			</div>
			<button class="btn" type="submit" style="background:#fff;color:#083f28;border-color:#fff;">খুঁজুন</button>
		</form>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="tiles">
			<a class="tile" href="<?php echo esc_url( $archive ); ?>">
				<strong>দলিল লেখক তালিকা</strong>
				সব দলিল লেখকের তথ্য দেখুন ও সরাসরি কল করুন।
			</a>
			<a class="tile" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">
				<strong>সেবা সমূহ</strong>
				অফিসের সেবা ও প্রয়োজনীয় তথ্য জানুন।
			</a>
			<a class="tile" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
				<strong>যোগাযোগ</strong>
				অফিসের ঠিকানা, ফোন ও বার্তা পাঠানোর ফর্ম।
			</a>
		</div>
	</div>
</section>

<?php
// Optional intro text from the page set as "Homepage" in Settings → Reading.
while ( have_posts() ) :
	the_post();
	if ( trim( get_the_content() ) ) :
		?>
		<section class="section">
			<div class="container">
				<div class="entry entry-content"><?php the_content(); ?></div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
