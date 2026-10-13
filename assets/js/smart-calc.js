/**
 * SR Office Ramganj: Smart Calculator interface (inside /services/).
 * Needs smart-calc-core.js (window.SROCalc).
 */
( function () {
	'use strict';

	var C = window.SROCalc;
	var panel = document.getElementById( 'smart-calc' );
	if ( ! C || ! panel ) {
		return;
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                             */
	/* ------------------------------------------------------------------ */

	function $( sel, ctx ) {
		return ( ctx || panel ).querySelector( sel );
	}
	function $$( sel, ctx ) {
		return Array.prototype.slice.call( ( ctx || panel ).querySelectorAll( sel ) );
	}

	var MESSAGES = {
		EMPTY: 'এই ঘরটি পূরণ করুন।',
		NEGATIVE: 'ঋণাত্মক সংখ্যা চলবে না।',
		INVALID: 'সঠিক সংখ্যা লিখুন (যেমন: ৫ বা ৫.৫)।',
		ZERO_DENOMINATOR: 'হর (নিচের সংখ্যা) শূন্য হতে পারে না।'
	};

	/** Parse a field; on failure show the message under it and return null. */
	function readNumber( id, errors, opts ) {
		opts = opts || {};
		var el = document.getElementById( id );
		var raw = el.value;
		var res = opts.fraction ? C.parseFraction( raw ) : C.parseDecimal( raw );
		if ( ! res.ok ) {
			errors.push( { id: id, message: MESSAGES[ res.error ] || MESSAGES.INVALID } );
			return null;
		}
		if ( opts.positive && C.isZero( res.value ) ) {
			errors.push( { id: id, message: 'শূন্য চলবে না, ০ এর বেশি সংখ্যা দিন।' } );
			return null;
		}
		return res.value;
	}

	function showErrors( root, errors ) {
		$$( '.sc-err', root ).forEach( function ( e ) {
			e.hidden = true;
			e.textContent = '';
		} );
		$$( '[aria-invalid]', root ).forEach( function ( e ) {
			e.removeAttribute( 'aria-invalid' );
		} );
		errors.forEach( function ( e ) {
			var field = document.getElementById( e.id );
			var holder = field && field.closest( '.sc-field' );
			var out = holder && holder.querySelector( '.sc-err' );
			if ( out ) {
				out.textContent = e.message;
				out.hidden = false;
			}
			if ( field ) {
				field.setAttribute( 'aria-invalid', 'true' );
			}
		} );
	}

	/**
	 * Render a result into a tool's result card and remember its plain text.
	 * result = { headline, rows: [[label, value], ...], note, warn }
	 */
	function render( tool, result ) {
		var card = $( '.sc-result', tool );
		var empty = $( '.sc-empty', card );
		var body = $( '.sc-out', card );
		if ( ! result ) {
			body.hidden = true;
			empty.hidden = false;
			card.removeAttribute( 'data-copy' );
			setActionState( tool, false );
			return;
		}
		$( '.sc-headline', body ).textContent = result.headline;
		var dl = $( '.sc-rows', body );
		dl.innerHTML = '';
		( result.rows || [] ).forEach( function ( r ) {
			var wrap = document.createElement( 'div' );
			var dt = document.createElement( 'dt' );
			var dd = document.createElement( 'dd' );
			dt.textContent = r[ 0 ];
			dd.textContent = r[ 1 ];
			wrap.appendChild( dt );
			wrap.appendChild( dd );
			dl.appendChild( wrap );
		} );
		var warn = $( '.sc-warn', body );
		warn.hidden = ! result.warn;
		warn.textContent = result.warn || '';
		var note = $( '.sc-note', body );
		note.textContent = result.note || '';
		note.hidden = ! result.note;
		body.hidden = false;
		empty.hidden = true;

		var lines = [ result.headline ];
		( result.rows || [] ).forEach( function ( r ) {
			lines.push( r[ 0 ] + ': ' + r[ 1 ] );
		} );
		if ( result.warn ) {
			lines.push( '⚠ ' + result.warn );
		}
		lines.push( 'সূত্র: sr-office-ramganj.com (দলিল লেখকদের বিশেষ ক্যালকুলেটর)' );
		card.setAttribute( 'data-copy', lines.join( '\n' ) );
		setActionState( tool, true );
	}

	function setActionState( tool, on ) {
		$$( '[data-act="copy"], [data-act="print"]', tool ).forEach( function ( b ) {
			b.disabled = ! on;
		} );
	}

	function copyText( text, done ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () {
				done( true );
			}, function () {
				done( false );
			} );
			return;
		}
		var ta = document.createElement( 'textarea' );
		ta.value = text;
		ta.setAttribute( 'readonly', '' );
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild( ta );
		ta.select();
		var ok = false;
		try {
			ok = document.execCommand( 'copy' );
		} catch ( e ) {
			ok = false;
		}
		document.body.removeChild( ta );
		done( ok );
	}

	function flash( btn, text ) {
		var span = $( 'span', btn ) || btn;
		var old = span.getAttribute( 'data-old' ) || span.textContent;
		span.setAttribute( 'data-old', old );
		span.textContent = text;
		window.setTimeout( function () {
			span.textContent = old;
		}, 1800 );
	}

	/** Shared katha/bigha standard selector inside a tool. */
	function readStandard( tool, errors ) {
		var sel = $( '.sc-std', tool );
		if ( sel.value === 'custom' ) {
			var k = readNumber( $( '.sc-katha', tool ).id, errors, { positive: true } );
			var b = readNumber( $( '.sc-bigha', tool ).id, errors, { positive: true } );
			if ( k === null || b === null ) {
				return null;
			}
			return C.customStandard( k, b );
		}
		return C.STANDARDS[ sel.value ] || C.STANDARDS.common;
	}

	function bind( tool, compute ) {
		var run = function () {
			var errors = [];
			var result = compute( errors );
			showErrors( tool, errors );
			render( tool, errors.length ? null : result );
		};
		tool.addEventListener( 'input', run );
		tool.addEventListener( 'change', function ( e ) {
			var std = e.target.closest ? e.target.closest( '.sc-std' ) : null;
			if ( std ) {
				var box = $( '.sc-custom', tool );
				if ( box ) {
					box.hidden = std.value !== 'custom';
				}
			}
			run();
		} );
		tool.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '[data-act]' ) : null;
			if ( ! btn ) {
				return;
			}
			var act = btn.getAttribute( 'data-act' );
			if ( act === 'calc' ) {
				run();
			} else if ( act === 'reset' ) {
				$$( 'input[type="text"], input[type="number"]', tool ).forEach( function ( i ) {
					if ( ! i.hasAttribute( 'data-keep' ) ) {
						i.value = i.getAttribute( 'data-default' ) || '';
					}
				} );
				$$( 'select', tool ).forEach( function ( s ) {
					s.selectedIndex = s.getAttribute( 'data-default-index' ) ? Number( s.getAttribute( 'data-default-index' ) ) : 0;
				} );
				var box = $( '.sc-custom', tool );
				if ( box ) {
					box.hidden = true;
				}
				if ( tool.resetExtra ) {
					tool.resetExtra();
				}
				showErrors( tool, [] );
				render( tool, null );
			} else if ( act === 'copy' ) {
				var text = $( '.sc-result', tool ).getAttribute( 'data-copy' ) || '';
				copyText( text, function ( ok ) {
					flash( btn, ok ? 'কপি হয়েছে ✓' : 'কপি করা যায়নি' );
				} );
			} else if ( act === 'print' ) {
				document.body.classList.add( 'print-smart' );
				window.print();
				window.setTimeout( function () {
					document.body.classList.remove( 'print-smart' );
				}, 500 );
			} else if ( act === 'swap' ) {
				var from = $( '.sc-from', tool );
				var to = $( '.sc-to', tool );
				var t = from.value;
				from.value = to.value;
				to.value = t;
				run();
			}
		} );
		tool.run = run;
	}

	var L = C.UNIT_LABEL;
	var bn = C.formatBn;

	/* ------------------------------------------------------------------ */
	/* 1. জমির পরিমাপ                                                       */
	/* ------------------------------------------------------------------ */

	var t1 = $( '#sc-tool-measure' );
	bind( t1, function ( errors ) {
		var qty = readNumber( 'sc1-qty', errors );
		var std = readStandard( t1, errors );
		if ( qty === null || std === null ) {
			return null;
		}
		var from = $( '#sc1-from' ).value;
		var to = $( '#sc1-to' ).value;
		var out = C.convert( qty, from, to, std );
		var factor = C.convert( C.ONE, from, to, std );
		var rows = [
			[ 'মূল পরিমাণ', bn( qty, 6 ) + ' ' + L[ from ] ],
			[ 'রূপান্তরিত পরিমাণ', bn( out, 6 ) + ' ' + L[ to ] ],
			[ 'রূপান্তর গুণক', '১ ' + L[ from ] + ' = ' + bn( factor, 8 ) + ' ' + L[ to ] ]
		];
		rows.push( [ 'ব্যবহৃত মানদণ্ড', C.usesStandard( from, to ) ? std.label : 'ডেসিমেল = ৪৩৫.৬ বর্গফুট; একর = ১০০ ডেসিমেল = ৪৩,৫৬০ বর্গফুট; ১ বর্গমিটার ≈ ১০.৭৬৩৯১০৪ বর্গফুট' ] );
		return { headline: bn( qty, 6 ) + ' ' + L[ from ] + ' = ' + bn( out, 6 ) + ' ' + L[ to ], rows: rows };
	} );

	/* ------------------------------------------------------------------ */
	/* 2. অংশ অনুযায়ী জমির হিসাব                                             */
	/* ------------------------------------------------------------------ */

	var t2 = $( '#sc-tool-share' );
	var ownerSeq = 0;

	function ownerRow( label, value, kind ) {
		ownerSeq++;
		var id = 'sc2-o' + ownerSeq;
		var div = document.createElement( 'div' );
		div.className = 'sc-owner';
		div.innerHTML =
			'<div class="sc-field"><label for="' + id + '-l">অংশীদার</label><input type="text" id="' + id + '-l" class="sc-o-label" autocomplete="off" placeholder="যেমন: ক"></div>' +
			'<div class="sc-field"><label for="' + id + '-v">অংশ</label><input type="text" id="' + id + '-v" class="sc-o-value" inputmode="decimal" autocomplete="off" placeholder="যেমন: ৩০ বা ১/৩"><p class="sc-err" role="alert" hidden></p></div>' +
			'<div class="sc-field"><label for="' + id + '-k">ধরন</label><select id="' + id + '-k" class="sc-o-kind"><option value="percent">শতকরা (%)</option><option value="fraction">ভগ্নাংশ (যেমন ১/৩)</option></select></div>' +
			'<button type="button" class="btn btn-outline sc-o-del" aria-label="এই অংশীদার মুছুন">মুছুন</button>';
		$( '.sc-o-label', div ).value = label || '';
		$( '.sc-o-value', div ).value = value || '';
		$( '.sc-o-kind', div ).value = kind || 'percent';
		return div;
	}

	function share_mode() {
		var r = $( 'input[name="sc2-mode"]:checked', t2 );
		return r ? r.value : 'fraction';
	}

	function syncShareMode() {
		var m = share_mode();
		$$( '.sc-mode-box', t2 ).forEach( function ( b ) {
			b.hidden = b.getAttribute( 'data-mode' ) !== m;
		} );
	}

	function resetOwners() {
		var list = $( '#sc2-owners' );
		list.innerHTML = '';
		list.appendChild( ownerRow( 'ক', '', 'percent' ) );
		list.appendChild( ownerRow( 'খ', '', 'percent' ) );
	}
	resetOwners();

	bind( t2, function ( errors ) {
		var total = readNumber( 'sc2-total', errors );
		var unit = $( '#sc2-unit' ).value;
		var mode = share_mode();
		if ( total === null ) {
			return null;
		}
		var unitLabel = L[ unit ];
		var hundred = C.Q( 100 );

		if ( mode === 'fraction' ) {
			var num = readNumber( 'sc2-num', errors );
			var den = readNumber( 'sc2-den', errors, { positive: true } );
			if ( num === null || den === null ) {
				return null;
			}
			var shareF = C.shareByFraction( total, num, den );
			var pctF = C.mul( C.div( num, den ), hundred );
			var res = {
				headline: bn( total ) + ' ' + unitLabel + '-এর ' + bn( num ) + '/' + bn( den ) + ' অংশ = ' + bn( shareF, 6 ) + ' ' + unitLabel,
				rows: [
					[ 'মোট জমি', bn( total ) + ' ' + unitLabel ],
					[ 'অংশ', bn( num ) + '/' + bn( den ) + ' (' + bn( pctF, 4 ) + '%)' ],
					[ 'হিসাব', bn( total ) + ' × ' + bn( num ) + ' ÷ ' + bn( den ) + ' = ' + bn( shareF, 6 ) + ' ' + unitLabel ],
					[ 'অবশিষ্ট জমি', bn( C.sub( total, shareF ), 6 ) + ' ' + unitLabel ]
				]
			};
			if ( C.cmp( num, den ) > 0 ) {
				res.warn = 'অংশ ১০০%-এর বেশি (লব হরের চেয়ে বড়)। সংখ্যাগুলো আবার দেখে নিন।';
			}
			return res;
		}

		if ( mode === 'percent' ) {
			var pct = readNumber( 'sc2-pct', errors );
			if ( pct === null ) {
				return null;
			}
			var shareP = C.shareByPercent( total, pct );
			var resP = {
				headline: bn( total ) + ' ' + unitLabel + '-এর ' + bn( pct ) + '% = ' + bn( shareP, 6 ) + ' ' + unitLabel,
				rows: [
					[ 'মোট জমি', bn( total ) + ' ' + unitLabel ],
					[ 'শতকরা অংশ', bn( pct ) + '%' ],
					[ 'হিসাব', bn( total ) + ' × ' + bn( pct ) + ' ÷ ১০০ = ' + bn( shareP, 6 ) + ' ' + unitLabel ],
					[ 'অবশিষ্ট জমি', bn( C.sub( total, shareP ), 6 ) + ' ' + unitLabel ]
				]
			};
			if ( C.cmp( pct, hundred ) > 0 ) {
				resP.warn = 'শতকরা অংশ ১০০%-এর বেশি।';
			}
			return resP;
		}

		// Multiple owners
		var owners = [];
		var bad = false;
		$$( '.sc-owner', t2 ).forEach( function ( row, idx ) {
			var vInput = $( '.sc-o-value', row );
			var kind = $( '.sc-o-kind', row ).value;
			var parsed = kind === 'fraction' ? C.parseFraction( vInput.value ) : C.parseDecimal( vInput.value );
			if ( ! parsed.ok ) {
				bad = true;
				errors.push( { id: vInput.id, message: MESSAGES[ parsed.error ] || MESSAGES.INVALID } );
				return;
			}
			owners.push( {
				label: $( '.sc-o-label', row ).value.trim() || 'অংশীদার ' + bn( C.Q( idx + 1 ) ),
				kind: kind,
				value: parsed.value
			} );
		} );
		if ( bad ) {
			return null;
		}
		if ( ! owners.length ) {
			errors.push( { id: 'sc2-total', message: 'কমপক্ষে একজন অংশীদার যোগ করুন।' } );
			return null;
		}
		var alloc = C.allocateOwners( total, owners );
		var rows = alloc.rows.map( function ( r ) {
			return [ r.label, bn( r.share, 6 ) + ' ' + unitLabel + ' (' + bn( r.percent, 4 ) + '%)' ];
		} );
		rows.push( [ 'মোট বণ্টিত জমি', bn( alloc.allocated, 6 ) + ' ' + unitLabel + ' (' + bn( alloc.totalPercent, 4 ) + '%)' ] );
		rows.push( [ 'অবশিষ্ট (অবণ্টিত) জমি', bn( alloc.remaining, 6 ) + ' ' + unitLabel ] );
		var resM = { headline: 'মোট ' + bn( total ) + ' ' + unitLabel + ' জমি ' + bn( C.Q( owners.length ) ) + ' জনের মধ্যে', rows: rows };
		if ( alloc.over ) {
			resM.warn = 'মোট অংশ ১০০%-এর বেশি (' + bn( alloc.totalPercent, 4 ) + '%)। অংশগুলো ঠিক করুন।';
		}
		return resM;
	} );

	t2.resetExtra = function () {
		$( 'input[name="sc2-mode"][value="fraction"]', t2 ).checked = true;
		syncShareMode();
		resetOwners();
	};
	t2.addEventListener( 'change', function ( e ) {
		if ( e.target && e.target.name === 'sc2-mode' ) {
			syncShareMode();
			t2.run();
		}
	} );
	t2.addEventListener( 'click', function ( e ) {
		var add = e.target.closest ? e.target.closest( '#sc2-add' ) : null;
		var del = e.target.closest ? e.target.closest( '.sc-o-del' ) : null;
		if ( add ) {
			$( '#sc2-owners' ).appendChild( ownerRow( '', '', 'percent' ) );
			t2.run();
		} else if ( del ) {
			var rows = $$( '.sc-owner', t2 );
			if ( rows.length > 1 ) {
				del.closest( '.sc-owner' ).remove();
				t2.run();
			}
		}
	} );
	syncShareMode();

	/* ------------------------------------------------------------------ */
	/* 3. শতকরা হিসাব                                                       */
	/* ------------------------------------------------------------------ */

	var t3 = $( '#sc-tool-percent' );

	function pctMode() {
		var r = $( 'input[name="sc3-mode"]:checked', t3 );
		return r ? r.value : 'of';
	}
	function syncPctMode() {
		var m = pctMode();
		$$( '.sc-mode-box', t3 ).forEach( function ( b ) {
			b.hidden = b.getAttribute( 'data-mode' ) !== m;
		} );
	}

	bind( t3, function ( errors ) {
		var m = pctMode();
		if ( m === 'of' ) {
			var total = readNumber( 'sc3-total', errors );
			var pct = readNumber( 'sc3-pct', errors );
			if ( total === null || pct === null ) {
				return null;
			}
			var v = C.percentOf( total, pct );
			return {
				headline: bn( total ) + '-এর ' + bn( pct ) + '% = ' + bn( v, 6 ),
				rows: [
					[ 'সূত্র', 'মোট × শতাংশের হার ÷ ১০০' ],
					[ 'হিসাব', bn( total ) + ' × ' + bn( pct ) + ' ÷ ১০০ = ' + bn( v, 6 ) ]
				]
			};
		}
		if ( m === 'what' ) {
			var part = readNumber( 'sc3-part', errors );
			var tot = readNumber( 'sc3-whole', errors, { positive: true } );
			if ( part === null || tot === null ) {
				return null;
			}
			var p = C.whatPercent( part, tot );
			return {
				headline: bn( tot ) + '-এর মধ্যে ' + bn( part ) + ' = ' + bn( p, 6 ) + '%',
				rows: [
					[ 'সূত্র', 'অংশ ÷ মোট × ১০০' ],
					[ 'হিসাব', bn( part ) + ' ÷ ' + bn( tot ) + ' × ১০০ = ' + bn( p, 6 ) + '%' ]
				]
			};
		}
		var orig = readNumber( 'sc3-orig', errors );
		var rate = readNumber( 'sc3-rate', errors );
		if ( orig === null || rate === null ) {
			return null;
		}
		var dir = $( '#sc3-dir' ).value;
		var out = C.changeByPercent( orig, rate, dir );
		var word = dir === 'decrease' ? 'হ্রাস' : 'বৃদ্ধি';
		var diff = C.sub( out, orig );
		var res = {
			headline: bn( orig ) + '-এর ' + bn( rate ) + '% ' + word + ' = ' + bn( out, 6 ),
			rows: [
				[ 'সূত্র', 'মূল মূল্য × (১ ' + ( dir === 'decrease' ? '−' : '+' ) + ' শতকরা হার ÷ ১০০)' ],
				[ 'পরিবর্তনের পরিমাণ', bn( C.cmp( diff, C.ZERO ) < 0 ? C.sub( C.ZERO, diff ) : diff, 6 ) ],
				[ 'নতুন মূল্য', bn( out, 6 ) ]
			]
		};
		if ( dir === 'decrease' && C.cmp( rate, C.Q( 100 ) ) > 0 ) {
			res.warn = 'হ্রাসের হার ১০০%-এর বেশি, তাই ফলাফল ঋণাত্মক।';
		}
		return res;
	} );
	t3.resetExtra = function () {
		$( 'input[name="sc3-mode"][value="of"]', t3 ).checked = true;
		syncPctMode();
	};
	t3.addEventListener( 'change', function ( e ) {
		if ( e.target && e.target.name === 'sc3-mode' ) {
			syncPctMode();
			t3.run();
		}
	} );
	syncPctMode();

	/* ------------------------------------------------------------------ */
	/* 4. জমির মূল্য হিসাব                                                   */
	/* ------------------------------------------------------------------ */

	var t4 = $( '#sc-tool-value' );
	bind( t4, function ( errors ) {
		var qty = readNumber( 'sc4-qty', errors );
		var price = readNumber( 'sc4-price', errors );
		var std = readStandard( t4, errors );
		if ( qty === null || price === null || std === null ) {
			return null;
		}
		var qUnit = $( '#sc4-qunit' ).value;
		var pUnit = $( '#sc4-punit' ).value;
		var r = C.landValue( qty, qUnit, price, pUnit, std );
		var rows = [
			[ 'মোট জমির পরিমাণ', bn( qty, 6 ) + ' ' + L[ qUnit ] ],
			[ 'প্রতি ' + L[ pUnit ] + ' মূল্য', C.formatMoney( price ) ],
			[ 'হিসাবের জন্য রূপান্তরিত পরিমাণ', bn( r.quantityInPriceUnit, 6 ) + ' ' + L[ pUnit ] ],
			[ 'মোট জমির মূল্য', C.formatMoney( r.total ) ]
		];
		if ( C.usesStandard( qUnit, pUnit ) ) {
			rows.push( [ 'কাঠা/বিঘার মান', std.label ] );
		}
		return {
			headline: 'মোট জমির মূল্য: ' + C.formatMoney( r.total ),
			rows: rows,
			note: 'এই মূল্য ব্যবহারকারীর দেওয়া তথ্যের ভিত্তিতে হিসাব করা হয়েছে। এটি সরকারি মৌজা রেট বা বাজারমূল্যের সরকারি নিশ্চয়তা নয়।'
		};
	} );

	/* ------------------------------------------------------------------ */
	/* Tabs and open/close                                                 */
	/* ------------------------------------------------------------------ */

	var tabs = $$( '[role="tab"]', panel );
	var tools = $$( '.sc-tool', panel );

	function selectTab( tab, focus ) {
		tabs.forEach( function ( t ) {
			var on = t === tab;
			t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			t.tabIndex = on ? 0 : -1;
		} );
		tools.forEach( function ( p ) {
			p.hidden = p.id !== tab.getAttribute( 'aria-controls' );
		} );
		if ( focus ) {
			tab.focus();
		}
	}

	tabs.forEach( function ( tab, i ) {
		tab.addEventListener( 'click', function () {
			selectTab( tab, false );
		} );
		tab.addEventListener( 'keydown', function ( e ) {
			var dir = e.key === 'ArrowRight' ? 1 : ( e.key === 'ArrowLeft' ? -1 : 0 );
			if ( dir ) {
				e.preventDefault();
				selectTab( tabs[ ( i + dir + tabs.length ) % tabs.length ], true );
			}
		} );
	} );

	var openBtn = document.getElementById( 'smart-calc-open' );
	function setOpen( open ) {
		panel.hidden = ! open;
		openBtn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		var label = $( 'span.sc-open-label', openBtn );
		if ( label ) {
			label.textContent = open ? 'ক্যালকুলেটর বন্ধ করুন ↑' : 'ক্যালকুলেটর ব্যবহার করুন →';
		}
		if ( open ) {
			panel.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			var first = tabs[ 0 ];
			selectTab( $( '[role="tab"][aria-selected="true"]', panel ) || first, false );
		}
	}
	openBtn.addEventListener( 'click', function () {
		setOpen( panel.hidden );
	} );
	var closeBtn = $( '#smart-calc-close' );
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', function () {
			setOpen( false );
			openBtn.focus();
		} );
	}
} )();
