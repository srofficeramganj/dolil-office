/**
 * Services page: expand / collapse the government land service shortcuts
 * (inline, no navigation).
 */
( function () {
	'use strict';

	var openBtn = document.getElementById( 'gov-open' );
	var panel = document.getElementById( 'gov-shortcuts' );
	if ( ! openBtn || ! panel ) {
		return;
	}
	var closeBtn = document.getElementById( 'gov-close' );
	var label = openBtn.querySelector( '.gov-open-label' );

	function setOpen( open ) {
		panel.hidden = ! open;
		openBtn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( label ) {
			label.textContent = open ? 'সরকারি সেবাসমূহ বন্ধ করুন ↑' : 'সরকারি সেবাসমূহ দেখুন →';
		}
		if ( open ) {
			panel.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}

	openBtn.addEventListener( 'click', function () {
		setOpen( panel.hidden );
	} );
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', function () {
			setOpen( false );
			openBtn.focus();
		} );
	}
} )();
