/**
 * Mouza rates admin: save, add, delete and verify rows without reloading.
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
		Array.prototype.forEach.call( row.querySelectorAll( 'input[name], select[name]' ), function ( el ) {
			if ( el.type === 'checkbox' ) {
				data[ el.name ] = el.checked ? '1' : '0';
			} else {
				data[ el.name ] = el.value;
			}
		} );
		return data;
	}

	// Mark edited rows.
	function markDirty( e ) {
		var row = e.target.closest ? e.target.closest( 'tr' ) : null;
		if ( row && row.getAttribute( 'data-id' ) !== '0' ) {
			row.classList.add( 'is-dirty' );
		}
	}
	wrap.addEventListener( 'input', markDirty );
	wrap.addEventListener( 'change', markDirty );

	wrap.addEventListener( 'click', function ( e ) {
		var target = e.target;
		var row = target.closest ? target.closest( 'tr' ) : null;

		if ( target.id === 'sro-verify-page' ) {
			e.preventDefault();
			var ids = wrap.getAttribute( 'data-ids' );
			if ( ! ids ) {
				return;
			}
			if ( ! window.confirm( 'এই পেজের সব সারি "যাচাইকৃত" হিসেবে চিহ্নিত হবে। আপনি কি মূল কাগজের সাথে মিলিয়ে দেখেছেন?' ) ) {
				return;
			}
			var msg = document.getElementById( 'sro-bulk-msg' );
			target.disabled = true;
			post( { action: 'sro_mouza_bulk_verify', ids: ids, value: '1' } ).then( function ( res ) {
				target.disabled = false;
				if ( res && res.success ) {
					msg.textContent = 'যাচাইকৃত হয়েছে ✓ (পেজ রিফ্রেশ হচ্ছে)';
					msg.className = 'sro-msg ok';
					window.setTimeout( function () {
						window.location.reload();
					}, 700 );
				} else {
					msg.textContent = ( res && res.data && res.data.message ) || 'সংরক্ষণ করা যায়নি।';
					msg.className = 'sro-msg err';
				}
			} ).catch( function () {
				target.disabled = false;
				msg.textContent = 'নেটওয়ার্ক ত্রুটি।';
				msg.className = 'sro-msg err';
			} );
			return;
		}

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
						window.location.reload();
						return;
					}
					row.classList.remove( 'is-dirty' );
					var ver = row.querySelector( 'input[name="verified"]' );
					row.classList.toggle( 'is-unverified', ! ( ver && ver.checked ) );
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
