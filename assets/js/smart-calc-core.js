/**
 * SR Office Ramganj: Smart Calculator core.
 *
 * Exact arithmetic: every value is a rational number made of two BigInts, so
 * there are no floating-point errors (0.1 + 0.2 is exactly 0.3). Values are
 * only rounded when they are formatted for display.
 *
 * Works in the browser (window.SROCalc) and in Node (module.exports) for tests.
 */
( function ( root, factory ) {
	'use strict';
	if ( typeof module === 'object' && module.exports ) {
		module.exports = factory();
	} else {
		root.SROCalc = factory();
	}
}( typeof self !== 'undefined' ? self : this, function () {
	'use strict';

	/* ------------------------- Rational numbers ------------------------- */

	function gcd( a, b ) {
		a = a < 0n ? -a : a;
		b = b < 0n ? -b : b;
		while ( b ) {
			var t = a % b;
			a = b;
			b = t;
		}
		return a;
	}

	/** Make a rational n/d (normalised, d > 0). */
	function Q( n, d ) {
		n = BigInt( n );
		d = d === undefined ? 1n : BigInt( d );
		if ( d === 0n ) {
			throw new Error( 'DIV0' );
		}
		if ( d < 0n ) {
			n = -n;
			d = -d;
		}
		var g = gcd( n, d ) || 1n;
		return { n: n / g, d: d / g };
	}

	var ZERO = Q( 0 );
	var ONE = Q( 1 );
	var HUNDRED = Q( 100 );

	function add( a, b ) {
		return Q( a.n * b.d + b.n * a.d, a.d * b.d );
	}
	function sub( a, b ) {
		return Q( a.n * b.d - b.n * a.d, a.d * b.d );
	}
	function mul( a, b ) {
		return Q( a.n * b.n, a.d * b.d );
	}
	function div( a, b ) {
		if ( b.n === 0n ) {
			throw new Error( 'DIV0' );
		}
		return Q( a.n * b.d, a.d * b.n );
	}
	function cmp( a, b ) {
		var l = a.n * b.d;
		var r = b.n * a.d;
		return l < r ? -1 : ( l > r ? 1 : 0 );
	}
	function isZero( a ) {
		return a.n === 0n;
	}

	/* --------------------------- Parsing input -------------------------- */

	var BN_DIGITS = '০১২৩৪৫৬৭৮৯';

	function normalizeText( text ) {
		return String( text === null || text === undefined ? '' : text )
			.replace( /[০-৯]/g, function ( d ) {
				return String( BN_DIGITS.indexOf( d ) );
			} )
			.replace( /[٫،]/g, '.' )
			.replace( /[,\s ]/g, '' );
	}

	/** Bengali digits for display. */
	function toBn( text ) {
		return String( text ).replace( /[0-9]/g, function ( d ) {
			return BN_DIGITS.charAt( Number( d ) );
		} );
	}

	/**
	 * Parse a non-negative decimal number.
	 * @return {{ok:boolean,value?:object,error?:string}}
	 */
	function parseDecimal( text ) {
		var t = normalizeText( text );
		if ( t === '' ) {
			return { ok: false, error: 'EMPTY' };
		}
		if ( t.charAt( 0 ) === '-' ) {
			return { ok: false, error: 'NEGATIVE' };
		}
		if ( t.charAt( 0 ) === '+' ) {
			t = t.slice( 1 );
		}
		var m = t.match( /^(\d*)(?:\.(\d+))?$/ );
		if ( ! m || ( m[ 1 ] === '' && m[ 2 ] === undefined ) ) {
			return { ok: false, error: 'INVALID' };
		}
		var frac = m[ 2 ] || '';
		var digits = ( m[ 1 ] || '0' ) + frac;
		return { ok: true, value: Q( BigInt( digits ), 10n ** BigInt( frac.length ) ) };
	}

	/**
	 * Parse "a/b" (or a plain number) as a ratio.
	 */
	function parseFraction( text ) {
		var t = normalizeText( text );
		if ( t === '' ) {
			return { ok: false, error: 'EMPTY' };
		}
		var parts = t.split( '/' );
		if ( parts.length === 1 ) {
			return parseDecimal( parts[ 0 ] );
		}
		if ( parts.length !== 2 ) {
			return { ok: false, error: 'INVALID' };
		}
		var a = parseDecimal( parts[ 0 ] );
		var b = parseDecimal( parts[ 1 ] );
		if ( ! a.ok ) {
			return a;
		}
		if ( ! b.ok ) {
			return b;
		}
		if ( isZero( b.value ) ) {
			return { ok: false, error: 'ZERO_DENOMINATOR' };
		}
		return { ok: true, value: div( a.value, b.value ) };
	}

	/* ---------------------------- Formatting ---------------------------- */

	/** Indian/Bangladeshi digit grouping: 12,34,567. */
	function group( intStr ) {
		if ( intStr.length <= 3 ) {
			return intStr;
		}
		var head = intStr.slice( 0, -3 );
		var tail = intStr.slice( -3 );
		return head.replace( /\B(?=(\d{2})+(?!\d))/g, ',' ) + ',' + tail;
	}

	/**
	 * Format a rational with at most maxDec decimals (half-up), trimmed.
	 * Returns ASCII digits; pass through toBn() for Bengali.
	 */
	function format( r, maxDec, opts ) {
		opts = opts || {};
		var dec = maxDec === undefined ? 6 : maxDec;
		var minDec = opts.minDec || 0;
		var neg = r.n < 0n;
		var n = neg ? -r.n : r.n;
		var scale = 10n ** BigInt( dec );
		var scaled = n * scale;
		var q = scaled / r.d;
		var rem = scaled % r.d;
		if ( rem * 2n >= r.d ) {
			q += 1n;
		}
		var intPart = ( q / scale ).toString();
		var fracPart = dec > 0 ? ( q % scale ).toString().padStart( dec, '0' ) : '';
		while ( fracPart.length > minDec && fracPart.charAt( fracPart.length - 1 ) === '0' ) {
			fracPart = fracPart.slice( 0, -1 );
		}
		var out = ( opts.group === false ? intPart : group( intPart ) ) + ( fracPart ? '.' + fracPart : '' );
		if ( neg && q !== 0n ) {
			out = '-' + out;
		}
		return out;
	}

	function formatBn( r, maxDec, opts ) {
		return toBn( format( r, maxDec, opts ) );
	}

	/** Money in BDT: whole taka without decimals, otherwise 2 decimals. */
	function formatMoney( r ) {
		var cents = format( r, 2, { minDec: 0 } );
		var fractional = cents.indexOf( '.' ) !== -1;
		return '৳ ' + toBn( format( r, 2, { minDec: fractional ? 2 : 0 } ) );
	}

	/* ------------------------------ Units ------------------------------- */

	var UNIT_LABEL = {
		shotangsho: 'শতাংশ',
		decimal: 'ডেসিমেল',
		katha: 'কাঠা',
		bigha: 'বিঘা',
		acre: 'একর',
		sqft: 'বর্গফুট',
		sqm: 'বর্গমিটার'
	};

	var DECIMAL_SQFT = Q( 4356, 10 ); // 1 decimal = 435.6 sq ft
	var ACRE_SQFT = mul( Q( 100 ), DECIMAL_SQFT ); // 1 acre = 100 decimals = 43,560 sq ft
	var SQM_SQFT = Q( 107639104, 10000000 ); // 1 sq m = 10.7639104 sq ft

	/** Katha / bigha differ by region, so they are always an explicit choice. */
	var STANDARDS = {
		common: {
			label: 'প্রচলিত মান: ১ কাঠা = ৭২০ বর্গফুট, ১ বিঘা = ২০ কাঠা = ১৪,৪০০ বর্গফুট',
			katha: Q( 720 ),
			bigha: Q( 14400 )
		},
		bigha33: {
			label: '১ বিঘা = ৩৩ শতাংশ (১৪,৩৭৪.৮ বর্গফুট), ১ কাঠা = ১ বিঘার ২০ ভাগের ১ (৭১৮.৭৪ বর্গফুট)',
			katha: Q( 143748, 200 ),
			bigha: Q( 143748, 10 )
		}
	};

	/** A user-defined standard from "sq ft per katha" and "sq ft per bigha". */
	function customStandard( kathaSqft, bighaSqft ) {
		return {
			label: 'নিজের দেওয়া মান: ১ কাঠা = ' + formatBn( kathaSqft, 6 ) + ' বর্গফুট, ১ বিঘা = ' + formatBn( bighaSqft, 6 ) + ' বর্গফুট',
			katha: kathaSqft,
			bigha: bighaSqft
		};
	}

	function sqftPerUnit( unit, std ) {
		switch ( unit ) {
			case 'shotangsho':
			case 'decimal':
				return DECIMAL_SQFT;
			case 'acre':
				return ACRE_SQFT;
			case 'sqft':
				return ONE;
			case 'sqm':
				return SQM_SQFT;
			case 'katha':
				return std.katha;
			case 'bigha':
				return std.bigha;
		}
		throw new Error( 'UNIT' );
	}

	function usesStandard( unitA, unitB ) {
		return unitA === 'katha' || unitA === 'bigha' || unitB === 'katha' || unitB === 'bigha';
	}

	/** Exact conversion of a quantity between two units. */
	function convert( qty, from, to, std ) {
		return div( mul( qty, sqftPerUnit( from, std ) ), sqftPerUnit( to, std ) );
	}

	/* -------------------------- Land shares ----------------------------- */

	/** total × numerator ÷ denominator */
	function shareByFraction( total, numerator, denominator ) {
		if ( isZero( denominator ) ) {
			throw new Error( 'DIV0' );
		}
		return div( mul( total, numerator ), denominator );
	}

	/** total × percentage ÷ 100 */
	function shareByPercent( total, percent ) {
		return div( mul( total, percent ), HUNDRED );
	}

	/**
	 * Several owners. Each entry: { label, kind: 'percent' | 'fraction', value }
	 * where value is the percent (e.g. 30) or the ratio (e.g. 1/3 as a rational).
	 */
	function allocateOwners( total, owners ) {
		var totalPct = ZERO;
		var rows = owners.map( function ( o ) {
			var pct = o.kind === 'fraction' ? mul( o.value, HUNDRED ) : o.value;
			totalPct = add( totalPct, pct );
			return { label: o.label, percent: pct, share: shareByPercent( total, pct ) };
		} );
		var allocated = shareByPercent( total, totalPct );
		return {
			rows: rows,
			totalPercent: totalPct,
			allocated: allocated,
			remaining: sub( total, allocated ),
			over: cmp( totalPct, HUNDRED ) > 0
		};
	}

	/* ------------------------- Percentage tools ------------------------- */

	function percentOf( total, percent ) {
		return div( mul( total, percent ), HUNDRED );
	}

	function whatPercent( part, total ) {
		if ( isZero( total ) ) {
			throw new Error( 'DIV0' );
		}
		return mul( div( part, total ), HUNDRED );
	}

	/** direction: 'increase' | 'decrease' */
	function changeByPercent( original, percent, direction ) {
		var factor = div( percent, HUNDRED );
		return direction === 'decrease' ? mul( original, sub( ONE, factor ) ) : mul( original, add( ONE, factor ) );
	}

	/* --------------------------- Land value ----------------------------- */

	/**
	 * Convert the land quantity into the unit the price is quoted for, then
	 * multiply: value = quantity in price unit × price per unit.
	 */
	function landValue( qty, qtyUnit, pricePerUnit, priceUnit, std ) {
		var inPriceUnit = convert( qty, qtyUnit, priceUnit, std );
		return { quantityInPriceUnit: inPriceUnit, total: mul( inPriceUnit, pricePerUnit ) };
	}

	return {
		Q: Q, ZERO: ZERO, ONE: ONE,
		add: add, sub: sub, mul: mul, div: div, cmp: cmp, isZero: isZero,
		parseDecimal: parseDecimal, parseFraction: parseFraction,
		format: format, formatBn: formatBn, formatMoney: formatMoney, toBn: toBn, normalizeText: normalizeText,
		UNIT_LABEL: UNIT_LABEL, STANDARDS: STANDARDS, customStandard: customStandard,
		sqftPerUnit: sqftPerUnit, usesStandard: usesStandard, convert: convert,
		shareByFraction: shareByFraction, shareByPercent: shareByPercent, allocateOwners: allocateOwners,
		percentOf: percentOf, whatPercent: whatPercent, changeByPercent: changeByPercent,
		landValue: landValue
	};
} ) );
