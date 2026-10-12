<?php
/**
 * Site header with mobile hamburger navigation.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content">মূল অংশে যান</a>

<header class="site-header">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( has_custom_logo() ) : ?>
				<?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'alt' => get_bloginfo( 'name' ), 'class' => 'brand-logo' ) ); ?>
			<?php else : ?>
				<img class="brand-logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" width="520" height="392" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php endif; ?>
		</a>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
			<span class="screen-reader-text">মেনু খুলুন বা বন্ধ করুন</span>
			<span class="bars" aria-hidden="true"></span>
		</button>

		<nav id="primary-nav" class="primary-nav" aria-label="প্রধান মেনু">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'depth'          => 1,
					'fallback_cb'    => 'sro_fallback_menu',
				)
			);
			?>
		</nav>
	</div>
</header>

<main id="content">
