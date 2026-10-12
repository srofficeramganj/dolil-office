/**
 * SR Office Ramganj citizen services: land calculator, purchase checklist,
 * registration documents finder, mouza rate lookup. No dependencies.
 */
( function () {
	'use strict';

	/* ---------------- Core helpers (also used by tests) ---------------- */

	var BN = { '০': '0', '১': '1', '২': '2', '৩': '3', '৪': '4', '৫': '5', '৬': '6', '৭': '7', '৮': '8', '৯': '9' };
	var EN_TO_BN = '০১২৩৪৫৬৭৮৯';

	function toEn( text ) {
		return String( text ).replace( /[০-৯]/g, function ( d ) {
			return BN[ d ];
		} );
	}

	function toBn( text ) {
		return String( text ).replace( /[0-9]/g, function ( d ) {
			return EN_TO_BN.charAt( Number( d ) );
		} );
	}

	/** Parse a number typed in Bengali or English digits. Returns NaN if invalid. */
	function parseNumber( text ) {
		var t = toEn( text ).replace( /[,\s]/g, '' );
		if ( ! /^(\d+\.?\d*|\.\d+)$/.test( t ) ) {
			return NaN;
		}
		return parseFloat( t );
	}

	/** Format a number with Bengali digits, grouping and trimmed decimals. */
	function formatNumber( n, maxDecimals ) {
		if ( ! isFinite( n ) ) {
			return '';
		}
		var dec = maxDecimals === undefined ? 6 : maxDecimals;
		var abs = Math.abs( n );
		if ( abs > 0 && abs < 0.000001 ) {
			dec = 10;
		}
		var fixed = n.toFixed( dec );
		fixed = fixed.replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
		var parts = fixed.split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		return toBn( parts.join( '.' ) );
	}

	var UNIT_LABEL = {
		shotangsho: 'শতাংশ',
		decimal: 'ডেসিমেল',
		katha: 'কাঠা',
		bigha: 'বিঘা',
		acre: 'একর',
		sqft: 'বর্গফুট',
		sqm: 'বর্গমিটার'
	};

	var DECIMAL_SQFT = 435.6;
	var STANDARDS = {
		common: {
			label: 'প্রচলিত মান: ১ কাঠা = ৭২০ বর্গফুট, ১ বিঘা = ২০ কাঠা = ১৪,৪০০ বর্গফুট',
			katha: 720,
			bigha: 14400
		},
		bigha33: {
			label: '১ বিঘা = ৩৩ শতাংশ (১৪,৩৭৪.৮ বর্গফুট), ১ কাঠা = ১ বিঘার ২০ ভাগের ১',
			katha: ( 33 * DECIMAL_SQFT ) / 20,
			bigha: 33 * DECIMAL_SQFT
		}
	};

	/** Square feet in one unit, for a given katha/bigha standard. */
	function sqftPerUnit( unit, std ) {
		switch ( unit ) {
			case 'shotangsho':
			case 'decimal':
				return DECIMAL_SQFT;
			case 'acre':
				return 100 * DECIMAL_SQFT;
			case 'sqft':
				return 1;
			case 'sqm':
				return 10.7639104;
			case 'katha':
				return std.katha;
			case 'bigha':
				return std.bigha;
		}
		return NaN;
	}

	function convertArea( qty, from, to, std ) {
		var factor = sqftPerUnit( from, std ) / sqftPerUnit( to, std );
		return { value: qty * factor, factor: factor };
	}

	var core = {
		toEn: toEn,
		toBn: toBn,
		parseNumber: parseNumber,
		formatNumber: formatNumber,
		convertArea: convertArea,
		sqftPerUnit: sqftPerUnit,
		STANDARDS: STANDARDS,
		UNIT_LABEL: UNIT_LABEL
	};

	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = core;
	}
	if ( typeof document === 'undefined' ) {
		return;
	}

	/* ---------------- DOM helpers ---------------- */

	function $( sel, root ) {
		return ( root || document ).querySelector( sel );
	}

	function $$( sel, root ) {
		return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) );
	}

	function store( key, value ) {
		try {
			if ( value === undefined ) {
				return JSON.parse( window.localStorage.getItem( key ) );
			}
			if ( value === null ) {
				window.localStorage.removeItem( key );
			} else {
				window.localStorage.setItem( key, JSON.stringify( value ) );
			}
		} catch ( e ) {
			/* storage unavailable: ignore */
		}
		return null;
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
		var old = btn.getAttribute( 'data-label' ) || btn.textContent;
		btn.setAttribute( 'data-label', old );
		btn.textContent = text;
		window.setTimeout( function () {
			btn.textContent = old;
		}, 1800 );
	}

	/* ---------------- 1. Land measurement calculator ---------------- */

	function initCalc( root ) {
		var qtyEl = $( '#calc-qty', root );
		var fromEl = $( '#calc-from', root );
		var toEl = $( '#calc-to', root );
		var stdEl = $( '#calc-std', root );
		var customBox = $( '#calc-custom', root );
		var kathaEl = $( '#calc-katha-sqft', root );
		var bighaEl = $( '#calc-bigha-sqft', root );
		var errEl = $( '#calc-qty-err', root );
		var emptyEl = $( '#calc-empty', root );
		var outEl = $( '#calc-output', root );
		var copyBtn = $( '#calc-copy', root );
		var printBtn = $( '#calc-print', root );
		var lastText = '';

		function showError( msg ) {
			errEl.textContent = msg;
			errEl.hidden = ! msg;
			qtyEl.setAttribute( 'aria-invalid', msg ? 'true' : 'false' );
		}

		function currentStd() {
			if ( stdEl.value === 'custom' ) {
				var k = parseNumber( kathaEl.value );
				var b = parseNumber( bighaEl.value );
				if ( ! ( k > 0 ) || ! ( b > 0 ) ) {
					return null;
				}
				return {
					label: 'নিজের দেওয়া মান: ১ কাঠা = ' + formatNumber( k ) + ' বর্গফুট, ১ বিঘা = ' + formatNumber( b ) + ' বর্গফুট',
					katha: k,
					bigha: b
				};
			}
			return STANDARDS[ stdEl.value ] || STANDARDS.common;
		}

		function clearResult() {
			outEl.hidden = true;
			emptyEl.hidden = false;
			copyBtn.disabled = true;
			printBtn.disabled = true;
			lastText = '';
		}

		function update() {
			customBox.hidden = stdEl.value !== 'custom';
			var raw = qtyEl.value.trim();
			if ( ! raw ) {
				showError( '' );
				clearResult();
				return;
			}
			var qty = parseNumber( raw );
			if ( isNaN( qty ) ) {
				showError( 'সঠিক সংখ্যা লিখুন (যেমন: ৫ বা ৫.৫)।' );
				clearResult();
				return;
			}
			var std = currentStd();
			var needsStd = fromEl.value === 'katha' || fromEl.value === 'bigha' || toEl.value === 'katha' || toEl.value === 'bigha';
			if ( ! std ) {
				if ( needsStd ) {
					showError( 'নিজের মানের জন্য কাঠা ও বিঘার বর্গফুট সংখ্যা দিন।' );
					clearResult();
					return;
				}
				std = STANDARDS.common;
			}
			showError( '' );

			var res = convertArea( qty, fromEl.value, toEl.value, std );
			var fromL = UNIT_LABEL[ fromEl.value ];
			var toL = UNIT_LABEL[ toEl.value ];
			var orig = formatNumber( qty ) + ' ' + fromL;
			var conv = formatNumber( res.value ) + ' ' + toL;
			var factor = '১ ' + fromL + ' = ' + formatNumber( res.factor ) + ' ' + toL;

			$( '#calc-big', root ).textContent = conv;
			$( '#calc-orig', root ).textContent = orig;
			$( '#calc-conv', root ).textContent = conv;
			$( '#calc-factor', root ).textContent = factor;
			$( '#calc-standard', root ).textContent = needsStd ? std.label : 'ডেসিমেল = ৪৩৫.৬ বর্গফুট; একর = ১০০ ডেসিমেল; ১ বর্গমিটার ≈ ১০.৭৬৩৯১০৪ বর্গফুট';

			outEl.hidden = false;
			emptyEl.hidden = true;
			copyBtn.disabled = false;
			printBtn.disabled = false;
			lastText = 'জমির পরিমাপ রূপান্তর\n' + orig + ' = ' + conv + '\nগুণক: ' + factor + '\nমানদণ্ড: ' + $( '#calc-standard', root ).textContent + '\nসূত্র: sr-office-ramganj.com';
		}

		[ qtyEl, kathaEl, bighaEl ].forEach( function ( el ) {
			el.addEventListener( 'input', update );
		} );
		[ fromEl, toEl, stdEl ].forEach( function ( el ) {
			el.addEventListener( 'change', update );
		} );

		$( '#calc-swap', root ).addEventListener( 'click', function () {
			var t = fromEl.value;
			fromEl.value = toEl.value;
			toEl.value = t;
			update();
		} );
		$( '#calc-reset', root ).addEventListener( 'click', function () {
			qtyEl.value = '';
			fromEl.value = 'shotangsho';
			toEl.value = 'sqft';
			stdEl.value = 'common';
			kathaEl.value = '';
			bighaEl.value = '';
			update();
			qtyEl.focus();
		} );
		copyBtn.addEventListener( 'click', function () {
			copyText( lastText, function ( ok ) {
				flash( copyBtn, ok ? 'কপি হয়েছে ✓' : 'কপি করা যায়নি' );
			} );
		} );
		printBtn.addEventListener( 'click', function () {
			window.print();
		} );
		update();
	}

	/* ---------------- 2. Land purchase checklist ---------------- */

	function initChecklist( root ) {
		var key = root.getAttribute( 'data-store' ) || 'sro_checklist';
		var items = $$( '.check-item', root );

		function counts() {
			var c = { done: 0, todo: 0, na: 0 };
			items.forEach( function ( it ) {
				var sel = $( 'input:checked', it );
				c[ sel ? sel.value : 'todo' ]++;
			} );
			return c;
		}

		function render() {
			var c = counts();
			var applicable = items.length - c.na;
			var pct = applicable > 0 ? Math.round( ( c.done / applicable ) * 100 ) : 0;
			$( '#cl-done', root ).textContent = toBn( c.done );
			$( '#cl-todo', root ).textContent = toBn( c.todo );
			$( '#cl-na', root ).textContent = toBn( c.na );
			$( '#cl-fill', root ).style.width = pct + '%';
			$( '#cl-bar', root ).setAttribute( 'aria-valuenow', String( pct ) );
			$( '#cl-pct', root ).textContent = 'অগ্রগতি: ' + toBn( pct ) + '%';
			items.forEach( function ( it ) {
				var sel = $( 'input:checked', it );
				it.setAttribute( 'data-state', sel ? sel.value : 'todo' );
			} );
		}

		function save() {
			var state = {};
			items.forEach( function ( it ) {
				var sel = $( 'input:checked', it );
				state[ it.getAttribute( 'data-item' ) ] = sel ? sel.value : 'todo';
			} );
			store( key, state );
		}

		var saved = store( key );
		if ( saved && typeof saved === 'object' ) {
			items.forEach( function ( it ) {
				var v = saved[ it.getAttribute( 'data-item' ) ];
				var input = v && $( 'input[value="' + v + '"]', it );
				if ( input ) {
					input.checked = true;
				}
			} );
		}

		root.addEventListener( 'change', function ( e ) {
			if ( e.target && e.target.type === 'radio' ) {
				render();
				save();
			}
		} );
		$( '#cl-reset', root ).addEventListener( 'click', function () {
			items.forEach( function ( it ) {
				$( 'input[value="todo"]', it ).checked = true;
			} );
			store( key, null );
			render();
		} );
		$( '#cl-print', root ).addEventListener( 'click', function () {
			window.print();
		} );
		render();
	}

	/* ---------------- 3. Registration documents ---------------- */

	function initDocs( root ) {
		var data;
		try {
			data = JSON.parse( $( '#docs-data', root ).textContent );
		} catch ( e ) {
			return;
		}
		var typeEl = $( '#docs-type', root );
		var resultEl = $( '#docs-result', root );
		var emptyEl = $( '#docs-empty', root );
		var listEl = $( '#docs-list', root );
		var storeKey = 'sro_docs_checked';

		function progress() {
			var boxes = $$( 'input[type="checkbox"]', listEl );
			var done = boxes.filter( function ( b ) {
				return b.checked;
			} ).length;
			var pct = boxes.length ? Math.round( ( done / boxes.length ) * 100 ) : 0;
			$( '#docs-count', root ).textContent = toBn( done );
			$( '#docs-total', root ).textContent = toBn( boxes.length );
			$( '#docs-fill', root ).style.width = pct + '%';
			$( '#docs-bar', root ).setAttribute( 'aria-valuenow', String( pct ) );
			return done;
		}

		function render( type ) {
			listEl.innerHTML = '';
			if ( ! type || ! data.types[ type ] ) {
				resultEl.hidden = true;
				emptyEl.hidden = false;
				return;
			}
			var t = data.types[ type ];
			var saved = store( storeKey ) || {};
			var checked = saved[ type ] || [];
			var all = data.common.concat( t.extra );
			all.forEach( function ( text, i ) {
				var li = document.createElement( 'li' );
				var label = document.createElement( 'label' );
				var cb = document.createElement( 'input' );
				cb.type = 'checkbox';
				cb.value = String( i );
				cb.checked = checked.indexOf( i ) !== -1;
				var span = document.createElement( 'span' );
				span.textContent = text;
				label.appendChild( cb );
				label.appendChild( span );
				li.appendChild( label );
				listEl.appendChild( li );
			} );
			$( '#docs-title', root ).textContent = t.label + ': প্রয়োজনীয় কাগজপত্র (সাধারণ নির্দেশনা)';
			resultEl.hidden = false;
			emptyEl.hidden = true;
			progress();
		}

		typeEl.addEventListener( 'change', function () {
			render( typeEl.value );
		} );
		listEl.addEventListener( 'change', function () {
			progress();
			var saved = store( storeKey ) || {};
			saved[ typeEl.value ] = $$( 'input[type="checkbox"]', listEl )
				.filter( function ( b ) {
					return b.checked;
				} )
				.map( function ( b ) {
					return parseInt( b.value, 10 );
				} );
			store( storeKey, saved );
		} );
		$( '#docs-reset', root ).addEventListener( 'click', function () {
			$$( 'input[type="checkbox"]', listEl ).forEach( function ( b ) {
				b.checked = false;
			} );
			var saved = store( storeKey ) || {};
			delete saved[ typeEl.value ];
			store( storeKey, saved );
			progress();
		} );
		$( '#docs-print', root ).addEventListener( 'click', function () {
			window.print();
		} );
		render( '' );
	}

	/* ---------------- 4. Mouza rate ---------------- */

	function initMouza( root ) {
		var api = root.getAttribute( 'data-api' );
		var hasData = root.getAttribute( 'data-has-data' ) === '1';
		var districtEl = $( '#mz-district', root );
		var upazilaEl = $( '#mz-upazila', root );
		var searchEl = $( '#mz-search', root );
		var mouzaEl = $( '#mz-mouza', root );
		var hintEl = $( '#mz-mouza-hint', root );
		var catEl = $( '#mz-category', root );
		var qtyEl = $( '#mz-qty', root );
		var unitEl = $( '#mz-unit', root );
		var errEl = $( '#mz-error', root );
		var emptyEl = $( '#mz-empty', root );
		var outEl = $( '#mz-output', root );
		var printBtn = $( '#mz-print', root );
		var mouzas = [];
		var rates = [];

		function setError( msg ) {
			errEl.textContent = msg;
			errEl.hidden = ! msg;
		}

		function get( path, params ) {
			var q = Object.keys( params || {} )
				.map( function ( k ) {
					return encodeURIComponent( k ) + '=' + encodeURIComponent( params[ k ] );
				} )
				.join( '&' );
			return fetch( api + path + ( q ? '?' + q : '' ), { credentials: 'same-origin' } ).then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'http ' + r.status );
				}
				return r.json();
			} );
		}

		function fillSelect( el, options, placeholder, keepValue ) {
			var prev = keepValue ? el.value : '';
			el.innerHTML = '';
			var ph = document.createElement( 'option' );
			ph.value = '';
			ph.textContent = placeholder;
			el.appendChild( ph );
			options.forEach( function ( o ) {
				var opt = document.createElement( 'option' );
				opt.value = o.value;
				opt.textContent = o.label;
				el.appendChild( opt );
			} );
			if ( prev ) {
				el.value = prev;
			}
		}

		function resetResult( msg ) {
			outEl.hidden = true;
			emptyEl.hidden = false;
			emptyEl.textContent = msg;
			printBtn.disabled = true;
		}

		function renderMouzaList() {
			var term = toEn( searchEl.value ).trim().toLowerCase();
			var list = mouzas.filter( function ( m ) {
				return ! term || toEn( m.name ).toLowerCase().indexOf( term ) !== -1 || ( m.code && toEn( m.code ).indexOf( term ) !== -1 );
			} );
			// Same name more than once: show the code so they can be told apart.
			var names = {};
			mouzas.forEach( function ( m ) {
				names[ m.name ] = ( names[ m.name ] || 0 ) + 1;
			} );
			fillSelect(
				mouzaEl,
				list.map( function ( m ) {
					var label = m.name;
					if ( names[ m.name ] > 1 || m.code ) {
						label += m.code ? ' (কোড: ' + m.code + ')' : '';
					}
					return { value: m.key, label: label };
				} ),
				list.length ? '— মৌজা নির্বাচন করুন (' + toBn( list.length ) + ' টি) —' : '— কোনো মৌজা পাওয়া যায়নি —',
				true
			);
			hintEl.hidden = list.length > 0 || ! mouzas.length;
			hintEl.textContent = 'এই নামে কোনো মৌজা পাওয়া যায়নি। অন্যভাবে লিখে চেষ্টা করুন বা "ফিল্টার পরিষ্কার করুন" চাপুন।';
		}

		function loadMouzas() {
			mouzas = [];
			rates = [];
			fillSelect( catEl, [], '— আগে মৌজা নির্বাচন করুন —' );
			catEl.disabled = true;
			qtyEl.disabled = true;
			unitEl.disabled = true;
			if ( ! hasData ) {
				return;
			}
			resetResult( 'মৌজা লোড হচ্ছে…' );
			get( 'list', { district: districtEl.value, upazila: upazilaEl.value } ).then( function ( rows ) {
				mouzas = rows;
				renderMouzaList();
				resetResult( rows.length ? 'মৌজা ও জমির শ্রেণি নির্বাচন করলে এখানে তথ্য দেখা যাবে।' : 'এই উপজেলার জন্য মৌজা রেটের তথ্য এখনো সংযুক্ত করা হয়নি।' );
			} ).catch( function () {
				resetResult( 'তথ্য লোড করা যায়নি। ইন্টারনেট সংযোগ দেখে আবার চেষ্টা করুন।' );
			} );
		}

		function loadRates() {
			rates = [];
			fillSelect( catEl, [], '— জমির শ্রেণি নির্বাচন করুন —' );
			if ( ! mouzaEl.value ) {
				catEl.disabled = true;
				qtyEl.disabled = true;
				unitEl.disabled = true;
				resetResult( 'মৌজা ও জমির শ্রেণি নির্বাচন করলে এখানে তথ্য দেখা যাবে।' );
				return;
			}
			resetResult( 'রেট লোড হচ্ছে…' );
			get( 'rates', { key: mouzaEl.value } ).then( function ( rows ) {
				rates = rows;
				fillSelect(
					catEl,
					rows.map( function ( r, i ) {
						return { value: String( i ), label: r.category };
					} ),
					rows.length ? '— জমির শ্রেণি নির্বাচন করুন —' : '— শ্রেণি পাওয়া যায়নি —'
				);
				catEl.disabled = ! rows.length;
				qtyEl.disabled = ! rows.length;
				unitEl.disabled = ! rows.length;
				resetResult( rows.length ? 'জমির শ্রেণি নির্বাচন করুন।' : 'এই মৌজার জন্য রেটের তথ্য পাওয়া যায়নি।' );
			} ).catch( function () {
				resetResult( 'তথ্য লোড করা যায়নি। আবার চেষ্টা করুন।' );
			} );
		}

		/** Unit named in the rate, e.g. "প্রতি শতাংশ" -> shotangsho. */
		function unitFromRate( text ) {
			var t = String( text );
			var map = [ [ 'শতাংশ', 'shotangsho' ], [ 'শতক', 'shotangsho' ], [ 'ডেসিমেল', 'decimal' ], [ 'একর', 'acre' ], [ 'কাঠা', 'katha' ], [ 'বিঘা', 'bigha' ], [ 'বর্গফুট', 'sqft' ], [ 'বর্গমিটার', 'sqm' ] ];
			for ( var i = 0; i < map.length; i++ ) {
				if ( t.indexOf( map[ i ][ 0 ] ) !== -1 ) {
					return map[ i ][ 1 ];
				}
			}
			return '';
		}

		function row( dl, label, value ) {
			var wrap = document.createElement( 'div' );
			var dt = document.createElement( 'dt' );
			var dd = document.createElement( 'dd' );
			dt.textContent = label;
			dd.textContent = value;
			wrap.appendChild( dt );
			wrap.appendChild( dd );
			dl.appendChild( wrap );
		}

		function calculate() {
			setError( '' );
			var idx = catEl.value;
			if ( idx === '' || ! rates[ idx ] ) {
				resetResult( 'জমির শ্রেণি নির্বাচন করুন।' );
				return;
			}
			var r = rates[ idx ];
			var dl = $( '#mz-dl', root );
			dl.innerHTML = '';
			row( dl, 'নির্বাচিত মৌজা', r.mouza );
			row( dl, 'জমির শ্রেণি', r.category );
			row( dl, 'প্রযোজ্য সরকারি রেট', formatNumber( r.rate, 2 ) + ' টাকা' );
			row( dl, 'রেটের একক', r.unit );

			var big = $( '#mz-big', root );
			var raw = qtyEl.value.trim();
			if ( raw ) {
				var qty = parseNumber( raw );
				var rateUnit = unitFromRate( r.unit );
				if ( isNaN( qty ) ) {
					setError( 'সঠিক পরিমাণ লিখুন (যেমন: ৫ বা ৫.৫)।' );
					big.textContent = '';
				} else if ( ! rateUnit ) {
					setError( 'রেটের একক ("' + r.unit + '") চেনা যায়নি, তাই আনুমানিক মূল্য হিসাব করা যায়নি।' );
					big.textContent = '';
				} else {
					var inRateUnit = convertArea( qty, unitEl.value, rateUnit, STANDARDS.common ).value;
					var total = inRateUnit * r.rate;
					big.textContent = 'আনুমানিক তথ্যভিত্তিক মূল্য: ' + formatNumber( total, 2 ) + ' টাকা';
					row( dl, 'জমির পরিমাণ', formatNumber( qty ) + ' ' + UNIT_LABEL[ unitEl.value ] );
					row( dl, 'রেটের এককে পরিমাণ', formatNumber( inRateUnit ) + ' ' + UNIT_LABEL[ rateUnit ] );
					row( dl, 'হিসাবকৃত রেফারেন্স মূল্য', formatNumber( total, 2 ) + ' টাকা' );
					if ( unitEl.value === 'katha' || unitEl.value === 'bigha' || rateUnit === 'katha' || rateUnit === 'bigha' ) {
						row( dl, 'কাঠা/বিঘার মান', STANDARDS.common.label );
					}
				}
			} else {
				big.textContent = formatNumber( r.rate, 2 ) + ' টাকা (' + r.unit + ')';
			}
			row( dl, 'তথ্যের উৎস', r.source || 'উল্লেখ নেই' );
			row( dl, 'কার্যকর সময়কাল', r.period || 'উল্লেখ নেই' );
			if ( r.verified ) {
				row( dl, 'সর্বশেষ যাচাই', r.verified );
			}
			outEl.hidden = false;
			emptyEl.hidden = true;
			printBtn.disabled = false;
		}

		searchEl.addEventListener( 'input', renderMouzaList );
		mouzaEl.addEventListener( 'change', loadRates );
		catEl.addEventListener( 'change', calculate );
		qtyEl.addEventListener( 'input', calculate );
		unitEl.addEventListener( 'change', calculate );
		upazilaEl.addEventListener( 'change', loadMouzas );
		$( '#mz-reset', root ).addEventListener( 'click', function () {
			searchEl.value = '';
			qtyEl.value = '';
			mouzaEl.value = '';
			renderMouzaList();
			loadRates();
		} );
		printBtn.addEventListener( 'click', function () {
			window.print();
		} );

		// Districts / upazilas that actually have data (default list stays as fallback).
		if ( hasData ) {
			get( 'locations' ).then( function ( res ) {
				if ( ! res.rows || ! res.rows.length ) {
					return;
				}
				var districts = [];
				res.rows.forEach( function ( r ) {
					if ( districts.indexOf( r.district ) === -1 ) {
						districts.push( r.district );
					}
				} );
				fillSelect( districtEl, districts.map( function ( d ) {
					return { value: d, label: d };
				} ), '— জেলা —' );
				districtEl.value = districts.indexOf( 'লক্ষ্মীপুর' ) !== -1 ? 'লক্ষ্মীপুর' : districts[ 0 ];

				var fillUpazilas = function () {
					var ups = res.rows.filter( function ( r ) {
						return r.district === districtEl.value;
					} ).map( function ( r ) {
						return { value: r.upazila, label: r.upazila };
					} );
					fillSelect( upazilaEl, ups, '— উপজেলা —' );
					upazilaEl.value = ups.length ? ups[ 0 ].value : '';
				};
				fillUpazilas();
				districtEl.addEventListener( 'change', function () {
					fillUpazilas();
					loadMouzas();
				} );
				loadMouzas();
			} ).catch( function () {
				resetResult( 'তথ্য লোড করা যায়নি। আবার চেষ্টা করুন।' );
			} );
		}
	}

	/* ---------------- Boot ---------------- */

	function boot() {
		$$( '[data-sro-tool]' ).forEach( function ( root ) {
			switch ( root.getAttribute( 'data-sro-tool' ) ) {
				case 'calc':
					initCalc( root );
					break;
				case 'checklist':
					initChecklist( root );
					break;
				case 'docs':
					initDocs( root );
					break;
				case 'mouza':
					initMouza( root );
					break;
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
