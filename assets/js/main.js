/**
 * SR Office Ramganj: mobile menu + instant directory search.
 * No dependencies.
 */
( function () {
	'use strict';

	/* ---------- Mobile menu ---------- */
	var toggle = document.querySelector( '.nav-toggle' );
	var nav = document.getElementById( 'primary-nav' );

	function setMenu( open ) {
		if ( ! toggle || ! nav ) {
			return;
		}
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		nav.classList.toggle( 'is-open', open );
	}

	if ( toggle && nav ) {
		toggle.addEventListener( 'click', function () {
			setMenu( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
		} );
		// Close after choosing a link.
		nav.addEventListener( 'click', function ( e ) {
			if ( e.target.closest && e.target.closest( 'a' ) ) {
				setMenu( false );
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				setMenu( false );
				toggle.focus();
			}
		} );
		// Tap outside closes the menu.
		document.addEventListener( 'click', function ( e ) {
			if ( ! nav.contains( e.target ) && ! toggle.contains( e.target ) ) {
				setMenu( false );
			}
		} );
	}

	/* ---------- Directory search ---------- */
	var grid = document.getElementById( 'dw-grid' );
	if ( ! grid ) {
		return;
	}

	var input = document.getElementById( 'dw-search' );
	var area = document.getElementById( 'dw-area' );
	var status = document.getElementById( 'dw-status' );
	var empty = document.getElementById( 'dw-empty' );
	var reset = document.getElementById( 'dw-reset' );
	var cards = Array.prototype.slice.call( grid.querySelectorAll( '.dw-card' ) );

	var bnDigits = { '০': '0', '১': '1', '২': '2', '৩': '3', '৪': '4', '৫': '5', '৬': '6', '৭': '7', '৮': '8', '৯': '9' };

	function normalize( text ) {
		return String( text )
			.toLowerCase()
			.replace( /[০-৯]/g, function ( d ) {
				return bnDigits[ d ];
			} )
			.replace( /\s+/g, ' ' )
			.trim();
	}

	// Pre-compute searchable text once.
	var index = cards.map( function ( card ) {
		var text = normalize( card.getAttribute( 'data-search' ) || '' );
		return {
			el: card,
			text: text,
			// Phone numbers: also match without dashes/spaces.
			digits: text.replace( /[^0-9]/g, '' ),
			areas: ( card.getAttribute( 'data-area' ) || '' ).split( ' ' ),
		};
	} );

	function apply() {
		var q = normalize( input ? input.value : '' );
		var qDigits = q.replace( /[^0-9]/g, '' );
		var selected = area ? area.value : '';
		var shown = 0;

		index.forEach( function ( item ) {
			var matchText = ! q || item.text.indexOf( q ) !== -1 || ( qDigits.length >= 3 && item.digits.indexOf( qDigits ) !== -1 );
			var matchArea = ! selected || item.areas.indexOf( selected ) !== -1;
			var visible = matchText && matchArea;
			item.el.hidden = ! visible;
			if ( visible ) {
				shown++;
			}
		} );

		if ( status ) {
			var total = status.getAttribute( 'data-total' ) || index.length;
			status.textContent = ( q || selected ) ? shown + ' জন পাওয়া গেছে (মোট ' + total + ' জন)' : 'মোট ' + total + ' জন দলিল লেখক';
		}
		if ( empty ) {
			empty.hidden = shown !== 0;
		}
		grid.hidden = shown === 0;
	}

	function clearAll() {
		if ( input ) {
			input.value = '';
		}
		if ( area ) {
			area.value = '';
		}
		apply();
		if ( input ) {
			input.focus();
		}
	}

	if ( input ) {
		input.addEventListener( 'input', apply );
		// Prefill from the home page search (?q=...).
		try {
			var q = new URLSearchParams( window.location.search ).get( 'q' );
			if ( q ) {
				input.value = q;
			}
		} catch ( err ) {
			/* old browser: ignore */
		}
	}
	if ( area ) {
		area.addEventListener( 'change', apply );
	}
	if ( reset ) {
		reset.addEventListener( 'click', clearAll );
	}
	apply();
} )();
