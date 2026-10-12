/**
 * Mouza rates: step-by-step import. Reads the CSV in the browser and sends it
 * to the server in small batches, so hosting time limits are never hit.
 */
( function () {
	'use strict';

	var box = document.getElementById( 'sro-batch-import' );
	if ( ! box ) {
		return;
	}
	var ajax = box.getAttribute( 'data-ajax' );
	var nonce = box.getAttribute( 'data-nonce' );
	var fileEl = document.getElementById( 'sro-batch-file' );
	var replaceEl = document.getElementById( 'sro-batch-replace' );
	var startBtn = document.getElementById( 'sro-batch-start' );
	var bar = document.getElementById( 'sro-batch-bar' );
	var status = document.getElementById( 'sro-batch-status' );
	var BATCH = 60;

	/** Small RFC-4180 style CSV parser (quotes, commas, newlines in quotes). */
	function parseCsv( text ) {
		var rows = [];
		var row = [];
		var field = '';
		var inQuotes = false;
		var i;
		if ( text.charCodeAt( 0 ) === 0xFEFF ) {
			text = text.slice( 1 );
		}
		for ( i = 0; i < text.length; i++ ) {
			var c = text.charAt( i );
			if ( inQuotes ) {
				if ( c === '"' ) {
					if ( text.charAt( i + 1 ) === '"' ) {
						field += '"';
						i++;
					} else {
						inQuotes = false;
					}
				} else {
					field += c;
				}
			} else if ( c === '"' ) {
				inQuotes = true;
			} else if ( c === ',' ) {
				row.push( field );
				field = '';
			} else if ( c === '\n' || c === '\r' ) {
				if ( c === '\r' && text.charAt( i + 1 ) === '\n' ) {
					i++;
				}
				row.push( field );
				field = '';
				if ( row.length > 1 || row[ 0 ] !== '' ) {
					rows.push( row );
				}
				row = [];
			} else {
				field += c;
			}
		}
		if ( field !== '' || row.length ) {
			row.push( field );
			rows.push( row );
		}
		return rows;
	}

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

	function say( text, kind ) {
		status.textContent = text;
		status.style.color = kind === 'err' ? '#b32d2e' : ( kind === 'ok' ? '#1a7f37' : '' );
	}

	startBtn.addEventListener( 'click', function () {
		var file = fileEl.files && fileEl.files[ 0 ];
		if ( ! file ) {
			say( 'আগে একটি CSV ফাইল বাছুন।', 'err' );
			return;
		}
		startBtn.disabled = true;
		say( 'ফাইল পড়া হচ্ছে…' );

		var reader = new FileReader();
		reader.onload = function () {
			var table = parseCsv( String( reader.result ) );
			if ( table.length < 2 ) {
				say( 'ফাইলে কোনো সারি পাওয়া যায়নি।', 'err' );
				startBtn.disabled = false;
				return;
			}
			var head = table[ 0 ].map( function ( h ) {
				return h.trim().toLowerCase();
			} );
			var items = table.slice( 1 ).map( function ( cells ) {
				var o = {};
				head.forEach( function ( h, i ) {
					o[ h ] = ( cells[ i ] || '' ).trim();
				} );
				return o;
			} );

			var total = items.length;
			var done = 0;
			var added = 0;
			var skipped = 0;
			var first = true;

			function next() {
				if ( done >= total ) {
					bar.style.width = '100%';
					say( 'সম্পন্ন ✓ যোগ হয়েছে: ' + added + ', বাদ গেছে: ' + skipped + '। পেজ রিফ্রেশ করে দেখুন।', 'ok' );
					startBtn.disabled = false;
					return;
				}
				var slice = items.slice( done, done + BATCH );
				post( {
					action: 'sro_mouza_import_batch',
					replace: first && replaceEl.checked ? '1' : '0',
					rows: JSON.stringify( slice )
				} ).then( function ( res ) {
					if ( ! res || ! res.success ) {
						throw new Error( ( res && res.data && res.data.message ) || 'সার্ভার ত্রুটি' );
					}
					first = false;
					done += slice.length;
					added += res.data.added;
					skipped += res.data.skipped;
					bar.style.width = Math.round( ( done / total ) * 100 ) + '%';
					say( done + ' / ' + total + ' সারি প্রক্রিয়া হয়েছে…' );
					next();
				} ).catch( function ( err ) {
					say( 'ত্রুটি: ' + err.message + ' (প্রথম ' + done + ' টি সারি যোগ হয়েছে। আবার চেষ্টা করলে "আগের সব মুছুন" টিক না দিয়ে বাকি ফাইল দিন।)', 'err' );
					startBtn.disabled = false;
				} );
			}
			next();
		};
		reader.onerror = function () {
			say( 'ফাইল পড়া যায়নি।', 'err' );
			startBtn.disabled = false;
		};
		reader.readAsText( file, 'utf-8' );
	} );
} )();
