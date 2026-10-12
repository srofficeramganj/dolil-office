<?php
/**
 * Contact form handler (admin-post, nonce + honeypot, wp_mail).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sro_handle_contact() {
	$back = wp_get_referer() ? remove_query_arg( array( 'sent', 'error' ), wp_get_referer() ) : home_url( '/contact/' );

	if ( ! isset( $_POST['sro_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sro_contact_nonce'] ) ), 'sro_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'error', 'nonce', $back ) );
		exit;
	}

	// Honeypot: bots fill this, people never see it.
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'sent', '1', $back ) );
		exit;
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $name || '' === $message || ( '' === $phone && '' === $email ) ) {
		wp_safe_redirect( add_query_arg( 'error', 'missing', $back ) );
		exit;
	}

	$to      = sro_opt( 'office_email', get_option( 'admin_email' ) );
	$subject = 'ওয়েবসাইট থেকে বার্তা: ' . $name;
	$body    = "নাম: $name\nফোন: $phone\nইমেইল: $email\n\n$message";
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $email ) ) {
		$headers[] = 'Reply-To: ' . $email;
	}

	$ok = wp_mail( $to, $subject, $body, $headers );
	wp_safe_redirect( add_query_arg( $ok ? 'sent' : 'error', $ok ? '1' : 'mail', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_sro_contact', 'sro_handle_contact' );
add_action( 'admin_post_sro_contact', 'sro_handle_contact' );
