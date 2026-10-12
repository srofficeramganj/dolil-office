<?php
/**
 * Home page: hero, notice ticker, services grid, banner, FAQ.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$archive = get_post_type_archive_link( 'deed_writer' );
$archive = $archive ? $archive : home_url( '/deed-writers/' );
$phone   = sro_opt( 'office_phone' );
$hours   = sro_opt( 'office_hours' );

// Ticker: Customizer lines if set, otherwise the newest notices.
$notices = array();
foreach ( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) sro_opt( 'office_notices' ) ) ) ) as $line ) {
	$notices[] = array( 'text' => $line, 'url' => '' );
}
if ( ! $notices ) {
	$notices = sro_latest_notices( 5 );
}
if ( ! $notices ) {
	$notices = array( array( 'text' => 'দলিল লেখকদের তথ্য এখন অনলাইনে: নাম, সনদ নম্বর বা মোবাইল নম্বর দিয়ে খুঁজুন।', 'url' => '' ) );
}

// Service cards: title, text, link, accent colour, icon path (24x24 line icons).
$services = array(
	array( 'দলিল লেখক তালিকা', 'সব দলিল লেখকের তথ্য', $archive, '#0b5d3b', 'M4 6h16M4 12h16M4 18h10' ),
	array( 'দলিল লেখক খুঁজুন', 'নাম, সনদ বা মোবাইল দিয়ে', $archive . '#dw-search', '#1d4ed8', 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm9 16-4-4' ),
	array( 'সেবা সমূহ', 'অফিসের সেবা ও তথ্য', home_url( '/services/' ), '#0e7490', 'M7 3h8l4 4v14H7zM14 3v5h5M10 13h6M10 17h6' ),
	array( 'নোটিশ ও তথ্য', 'সর্বশেষ ঘোষণা ও বিজ্ঞপ্তি', home_url( '/notice/' ), '#b45309', 'M6 9a6 6 0 1 1 12 0c0 6 2 7 2 7H4s2-1 2-7zM10 20a2 2 0 0 0 4 0' ),
	array( 'আমাদের সম্পর্কে', 'অফিস পরিচিতি', home_url( '/about/' ), '#7e22ce', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 11v6M12 7.5v.5' ),
	array( 'যোগাযোগ', 'ঠিকানা ও বার্তা পাঠান', home_url( '/contact/' ), '#be123c', 'M3 6h18v12H3zM3 7l9 7 9-7' ),
	array( 'ফোনে যোগাযোগ', $phone ? $phone : 'অফিসের ফোন নম্বর', $phone ? 'tel:' . sro_tel( $phone ) : home_url( '/contact/' ), '#15803d', 'M6 3h3l2 5-2 1.5a11 11 0 0 0 5.500 5.500L16 13l5 2v3a3 3 0 0 1-3 3A15 15 0 0 1 3 6a3 3 0 0 1 3-3z' ),
	array( 'সচরাচর জিজ্ঞাসা', 'সাধারণ প্রশ্নের উত্তর', '#faq', '#0f766e', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM9.500 9.500a2.500 2.500 0 1 1 3.500 2.300c-.700.400-1 .900-1 1.700M12 17v.5' ),
);

// FAQ (question, answer).
$faqs = array(
	array( 'দলিল লেখকের তথ্য কীভাবে খুঁজব?', '"দলিল লেখক তালিকা" পেজে সার্চ বক্সে নাম, সনদ নম্বর, মোবাইল নম্বর বা গ্রামের নাম লিখলেই সাথে সাথে ফলাফল দেখা যায়।' ),
	array( 'দলিল লেখকের সাথে কীভাবে যোগাযোগ করব?', 'প্রতিটি প্রোফাইলে "কল করুন" বাটন আছে, চাপলে সরাসরি ফোন কল হবে। তাছাড়া হোয়াটসঅ্যাপ, ফেসবুক ও ইউটিউব আইকনে চাপলেও যোগাযোগ করা যাবে (যাদের লিংক দেওয়া আছে)।' ),
	array( 'তালিকায় কোনো তথ্য ভুল থাকলে কী করব?', '"যোগাযোগ" পেজের ফর্ম বা ফোনের মাধ্যমে অফিসকে জানান, তথ্য সংশোধন করা হবে।' ),
	array( 'অফিস কখন খোলা থাকে?', $hours ? 'অফিস সময়: ' . $hours . '।' : 'অফিসের সময় ও ঠিকানা "যোগাযোগ" পেজে দেওয়া আছে।' ),
);
?>

<section class="hero">
	<div class="container hero-center">
		<h1 class="hero-title">সনদপ্রাপ্ত <mark>দলিল লেখকগণের</mark> তথ্য খুঁজুন</h1>
		<p class="hero-sub">নাম, সনদ নম্বর বা মোবাইল নম্বর দিয়ে খুঁজুন।</p>
		<form class="hero-search" role="search" method="get" action="<?php echo esc_url( $archive ); ?>">
			<div class="field">
				<label class="screen-reader-text" for="hero-q">দলিল লেখক খুঁজুন</label>
				<input type="search" id="hero-q" name="q" placeholder="নাম, সনদ বা মোবাইল নম্বর লিখুন" autocomplete="off" enterkeyhint="search">
			</div>
			<button class="btn hero-btn" type="submit">খুঁজুন</button>
		</form>
	</div>
</section>

<section class="ticker" aria-label="ঘোষণা">
	<div class="container ticker-inner">
		<span class="ticker-label">ঘোষণা</span>
		<div class="ticker-track">
			<div class="ticker-move">
				<?php foreach ( $notices as $n ) : ?>
					<span class="ticker-item">
						<?php if ( $n['url'] ) : ?>
							<a href="<?php echo esc_url( $n['url'] ); ?>"><?php echo esc_html( $n['text'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $n['text'] ); ?>
						<?php endif; ?>
					</span>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<section class="section services" aria-labelledby="services-h">
	<div class="container">
		<h2 class="section-title" id="services-h">আমাদের সেবা</h2>
		<div class="svc-grid">
			<?php foreach ( $services as $s ) : ?>
				<a class="svc" href="<?php echo esc_url( $s[2] ); ?>" style="--accent:<?php echo esc_attr( $s[3] ); ?>">
					<span class="svc-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?php echo esc_attr( $s[4] ); ?>"/></svg>
					</span>
					<strong><?php echo esc_html( $s[0] ); ?></strong>
					<small><?php echo esc_html( $s[1] ); ?></small>
				</a>
			<?php endforeach; ?>
		</div>
		<p class="banner-wrap">
			<a class="banner-link" href="<?php echo esc_url( $archive ); ?>">দলিল লেখকদের পূর্ণ তালিকা দেখুন &rarr;</a>
		</p>
	</div>
</section>

<section class="section faq" id="faq" aria-labelledby="faq-h">
	<div class="container">
		<h2 class="section-title" id="faq-h">সচরাচর জিজ্ঞাসা</h2>
		<div class="faq-list">
			<?php foreach ( $faqs as $f ) : ?>
				<details class="faq-item">
					<summary><?php echo esc_html( $f[0] ); ?></summary>
					<p><?php echo esc_html( $f[1] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
		<p class="banner-wrap"><a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">আরো জানতে যোগাযোগ করুন</a></p>
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
