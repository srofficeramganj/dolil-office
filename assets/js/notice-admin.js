/**
 * Notice edit screen: choose or upload a PDF from the media library.
 */
( function ( $ ) {
	'use strict';

	var frame = null;

	$( document ).on( 'click', '.sro-pick-pdf', function ( e ) {
		e.preventDefault();

		if ( ! frame ) {
			frame = wp.media( {
				title: 'PDF ফাইল বাছুন',
				button: { text: 'এই ফাইল ব্যবহার করুন' },
				library: { type: 'application/pdf' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var file = frame.state().get( 'selection' ).first().toJSON();
				$( '#sro_notice_pdf' ).val( file.id );
				$( '.sro-pdf-name' ).text( file.filename || file.title );
				$( '.sro-remove-pdf' ).show();
			} );
		}
		frame.open();
	} );

	$( document ).on( 'click', '.sro-remove-pdf', function ( e ) {
		e.preventDefault();
		$( '#sro_notice_pdf' ).val( '' );
		$( '.sro-pdf-name' ).text( 'কোনো ফাইল নেই' );
		$( this ).hide();
	} );
} )( jQuery );
