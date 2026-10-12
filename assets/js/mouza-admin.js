/**
 * Mouza rates admin: save, add and delete a row without reloading the page.
 */
( function () {
	'use strict';

	var wrap = document.querySelector( '.sro-mouza-admin' );
	if ( ! wrap ) {
		return;
	}
	var ajax = wrap.getAttribute( 'data-ajax' );
	var nonce = wrap.getAttribute( 'data-nonce' );

	function post( data ) {
		var body = new URLSearchParams();
		Object.keys( data ).forEach( function ( k ) {
			body.append( k, data[ k ] );
		} );
		body.append( 'nonce', nonce );
		return fetch( ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json();
		} );
	}

	function message( row, text, ok ) {
		var el = row.querySelector( '.sro-msg' );
		if ( ! el ) {
			return;
		}
		el.textContent = text;
		el.className = 'sro-msg ' + ( ok ? 'ok' : 'err' );
		if ( ok ) {
			window.setTimeout( function () {
				el.textContent = '';
			}, 2500 );
		}
	}

	function collect( row ) {
		var data = { action: 'sro_mouza_save', id: row.getAttribute( 'data-id' ) || '0' };
		Array.prototype.forEach.call( row.querySelectorAll( 'input[name]' ), function ( input ) {
			data[ input.name ] = input.value;
		} );
		return data;
	}

	// Mark edited rows.
	wrap.addEventListener( 'input', function ( e ) {
		var row = e.target.closest ? e.target.closest( 'tr' ) : null;
		if ( row && row.getAttribute( 'data-id' ) !== '0' ) {
			row.classList.add( 'is-dirty' );
		}
	} );

	wrap.addEventListener( 'click', function ( e ) {
		var target = e.target;
		var row = target.closest ? target.closest( 'tr' ) : null;
		if ( ! row ) {
			return;
		}

		if ( target.classList.contains( 'sro-save' ) ) {
			e.preventDefault();
			var isNew = row.getAttribute( 'data-id' ) === '0';
			target.disabled = true;
			message( row, 'সংরক্ষণ হচ্ছে…', true );
			post( collect( row ) ).then( function ( res ) {
				target.disabled = false;
				if ( res && res.success ) {
					if ( isNew ) {
						// Reload so the new row appears in the list.
						window.location.reload();
						return;
					}
					row.classList.remove( 'is-dirty' );
					message( row, 'সংরক্ষিত হয়েছে ✓', true );
				} else {
					message( row, ( res && res.data && res.data.message ) || 'সংরক্ষণ করা যায়নি।', false );
				}
			} ).catch( function () {
				target.disabled = false;
				message( row, 'নেটওয়ার্ক ত্রুটি। আবার চেষ্টা করুন।', false );
			} );
		}

		if ( target.classList.contains( 'sro-del' ) ) {
			e.preventDefault();
			if ( ! window.confirm( 'এই রেটটি মুছে ফেলা হবে। নিশ্চিত?' ) ) {
				return;
			}
			post( { action: 'sro_mouza_delete', id: row.getAttribute( 'data-id' ) } ).then( function ( res ) {
				if ( res && res.success ) {
					row.parentNode.removeChild( row );
				} else {
					message( row, ( res && res.data && res.data.message ) || 'মোছা যায়নি।', false );
				}
			} ).catch( function () {
				message( row, 'নেটওয়ার্ক ত্রুটি।', false );
			} );
		}
	} );
} )();
