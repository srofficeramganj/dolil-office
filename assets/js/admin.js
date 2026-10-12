/**
 * Quick Edit for deed writers: fill the extra fields from the row's hidden data.
 */
( function ( $ ) {
	'use strict';

	if ( typeof inlineEditPost === 'undefined' ) {
		return;
	}

	var original = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		original.apply( this, arguments );

		var postId = 0;
		if ( typeof id === 'object' ) {
			postId = parseInt( this.getId( id ), 10 );
		} else {
			postId = parseInt( id, 10 );
		}
		if ( ! postId ) {
			return;
		}

		var row = $( '#post-' + postId );
		var editRow = $( '#edit-' + postId );

		row.find( '.sro-qd' ).each( function () {
			var key = $( this ).data( 'key' );
			editRow.find( '[name="' + key + '"]' ).val( $( this ).text() );
		} );
	};
} )( jQuery );
