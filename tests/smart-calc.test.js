/**
 * Unit tests for assets/js/smart-calc-core.js
 * Run:  node tests/smart-calc.test.js
 */
'use strict';

const assert = require( 'assert' );
const C = require( '../assets/js/smart-calc-core.js' );

let passed = 0;
function test( name, fn ) {
	try {
		fn();
		passed++;
		console.log( 'PASS ' + name );
	} catch ( e ) {
		console.log( 'FAIL ' + name + '\n     ' + e.message );
		process.exitCode = 1;
	}
}

const num = ( s ) => {
	const p = C.parseDecimal( s );
	assert.ok( p.ok, 'could not parse ' + s );
	return p.value;
};
const fmt = ( r, d ) => C.format( r, d );
const std = C.STANDARDS.common;

/* ---------- Parsing ---------- */
test( 'parses English and Bengali digits and commas', () => {
	assert.strictEqual( fmt( num( '1,234.5' ) ), '1,234.5' );
	assert.strictEqual( fmt( num( '১২,৩৪৫.৬৭' ) ), '12,345.67' );
	assert.strictEqual( fmt( num( '.5' ) ), '0.5' );
});
test( 'rejects empty, negative and invalid input', () => {
	assert.strictEqual( C.parseDecimal( '' ).error, 'EMPTY' );
	assert.strictEqual( C.parseDecimal( '-5' ).error, 'NEGATIVE' );
	assert.strictEqual( C.parseDecimal( 'abc' ).error, 'INVALID' );
	assert.strictEqual( C.parseDecimal( '5.5.5' ).error, 'INVALID' );
	assert.strictEqual( C.parseDecimal( '.' ).error, 'INVALID' );
});
test( 'fractions parse and zero denominator is rejected', () => {
	assert.strictEqual( fmt( C.parseFraction( '1/4' ).value, 4 ), '0.25' );
	assert.strictEqual( fmt( C.parseFraction( '২/৫' ).value ), '0.4' );
	assert.strictEqual( C.parseFraction( '1/0' ).error, 'ZERO_DENOMINATOR' );
	assert.strictEqual( C.parseFraction( '1/2/3' ).error, 'INVALID' );
});

/* ---------- Exactness ---------- */
test( 'no floating point error: 0.1 + 0.2 equals 0.3 exactly', () => {
	const s = C.add( num( '0.1' ), num( '0.2' ) );
	assert.strictEqual( C.cmp( s, num( '0.3' ) ), 0 );
});
test( 'very large numbers are handled', () => {
	const big = num( '99999999999999999999999999' );
	assert.strictEqual( C.format( C.mul( big, C.Q( 2 ) ), 0, { group: false } ), '199999999999999999999999998' );
});

/* ---------- Land measurement ---------- */
test( '5 shotangsho = 2,178 square feet (the required example)', () => {
	assert.strictEqual( fmt( C.convert( num( '5' ), 'shotangsho', 'sqft', std ) ), '2,178' );
});
test( '1 decimal = 435.6 sq ft; 1 acre = 100 decimals = 43,560 sq ft', () => {
	assert.strictEqual( fmt( C.convert( num( '1' ), 'decimal', 'sqft', std ) ), '435.6' );
	assert.strictEqual( fmt( C.convert( num( '1' ), 'acre', 'decimal', std ) ), '100' );
	assert.strictEqual( fmt( C.convert( num( '1' ), 'acre', 'sqft', std ) ), '43,560' );
});
test( '1 square metre = 10.7639104 sq ft', () => {
	assert.strictEqual( fmt( C.convert( num( '1' ), 'sqm', 'sqft', std ), 7 ), '10.7639104' );
});
test( 'shotangsho and decimal are the same', () => {
	assert.strictEqual( C.cmp( C.convert( num( '7.3' ), 'shotangsho', 'decimal', std ), num( '7.3' ) ), 0 );
});
test( 'katha and bigha follow the chosen standard', () => {
	assert.strictEqual( fmt( C.convert( num( '1' ), 'bigha', 'katha', std ) ), '20' );
	assert.strictEqual( fmt( C.convert( num( '5' ), 'katha', 'sqft', std ) ), '3,600' );
	const s33 = C.STANDARDS.bigha33;
	assert.strictEqual( fmt( C.convert( num( '1' ), 'bigha', 'shotangsho', s33 ) ), '33' );
	assert.strictEqual( fmt( C.convert( num( '1' ), 'katha', 'sqft', s33 ), 2 ), '718.74' );
});
test( 'custom standard works', () => {
	const custom = C.customStandard( num( '800' ), num( '16000' ) );
	assert.strictEqual( fmt( C.convert( num( '2' ), 'bigha', 'katha', custom ) ), '40' );
});
test( 'round trip returns the original value exactly', () => {
	const back = C.convert( C.convert( num( '12.345' ), 'acre', 'sqm', std ), 'sqm', 'acre', std );
	assert.strictEqual( C.cmp( back, num( '12.345' ) ), 0 );
});

/* ---------- Land shares ---------- */
test( 'fraction share: 1/2, 1/3, 2/5 of 30', () => {
	assert.strictEqual( fmt( C.shareByFraction( num( '30' ), C.Q( 1 ), C.Q( 2 ) ) ), '15' );
	assert.strictEqual( fmt( C.shareByFraction( num( '30' ), C.Q( 1 ), C.Q( 3 ) ) ), '10' );
	assert.strictEqual( fmt( C.shareByFraction( num( '30' ), C.Q( 2 ), C.Q( 5 ) ) ), '12' );
});
test( 'fraction share with a zero denominator throws', () => {
	assert.throws( () => C.shareByFraction( num( '30' ), C.Q( 1 ), C.Q( 0 ) ), /DIV0/ );
});
test( 'percentage share: 30 shotangsho at 30% = 9', () => {
	assert.strictEqual( fmt( C.shareByPercent( num( '30' ), num( '30' ) ) ), '9' );
});
test( 'multiple owners: 50% + 30% + 20% of 30 = 15 + 9 + 6, nothing remaining', () => {
	const r = C.allocateOwners( num( '30' ), [
		{ label: 'ক', kind: 'percent', value: num( '50' ) },
		{ label: 'খ', kind: 'percent', value: num( '30' ) },
		{ label: 'গ', kind: 'percent', value: num( '20' ) }
	] );
	assert.deepStrictEqual( r.rows.map( ( x ) => fmt( x.share ) ), [ '15', '9', '6' ] );
	assert.strictEqual( fmt( r.allocated ), '30' );
	assert.strictEqual( fmt( r.remaining ), '0' );
	assert.strictEqual( r.over, false );
});
test( 'multiple owners: thirds add up exactly (no 33.33 + 33.33 + 33.33 drift)', () => {
	const third = C.parseFraction( '1/3' ).value;
	const r = C.allocateOwners( num( '30' ), [
		{ label: 'ক', kind: 'fraction', value: third },
		{ label: 'খ', kind: 'fraction', value: third },
		{ label: 'গ', kind: 'fraction', value: third }
	] );
	assert.strictEqual( fmt( r.allocated ), '30' );
	assert.strictEqual( fmt( r.remaining ), '0' );
	assert.strictEqual( C.cmp( r.totalPercent, num( '100' ) ), 0 );
});
test( 'multiple owners: partial allocation shows remaining; over 100% is flagged', () => {
	const part = C.allocateOwners( num( '30' ), [ { label: 'ক', kind: 'percent', value: num( '40' ) } ] );
	assert.strictEqual( fmt( part.remaining ), '18' );
	const over = C.allocateOwners( num( '30' ), [
		{ label: 'ক', kind: 'percent', value: num( '70' ) },
		{ label: 'খ', kind: 'percent', value: num( '50' ) }
	] );
	assert.strictEqual( over.over, true );
	assert.strictEqual( fmt( over.remaining ), '-6' );
});

/* ---------- Percentage ---------- */
test( 'percent of: 15% of 200 = 30', () => {
	assert.strictEqual( fmt( C.percentOf( num( '200' ), num( '15' ) ) ), '30' );
});
test( 'what percent: 25 of 100 = 25; zero total throws', () => {
	assert.strictEqual( fmt( C.whatPercent( num( '25' ), num( '100' ) ) ), '25' );
	assert.throws( () => C.whatPercent( num( '5' ), num( '0' ) ), /DIV0/ );
});
test( 'increase / decrease: 100,000 ± 10%', () => {
	assert.strictEqual( fmt( C.changeByPercent( num( '100000' ), num( '10' ), 'increase' ) ), '1,10,000' );
	assert.strictEqual( fmt( C.changeByPercent( num( '100000' ), num( '10' ), 'decrease' ) ), '90,000' );
});
test( 'percent results keep exact decimals', () => {
	assert.strictEqual( fmt( C.whatPercent( num( '1' ), num( '3' ) ), 4 ), '33.3333' );
	assert.strictEqual( fmt( C.percentOf( num( '0.07' ), num( '0.5' ) ), 5 ), '0.00035' );
});

/* ---------- Land value ---------- */
test( 'land value: 10 shotangsho at 3,00,000 per decimal = 30,00,000', () => {
	const r = C.landValue( num( '10' ), 'shotangsho', num( '300000' ), 'decimal', std );
	assert.strictEqual( fmt( r.total ), '30,00,000' );
	assert.strictEqual( C.formatMoney( r.total ), '৳ ৩০,০০,০০০' );
});
test( 'land value converts to the price unit first: 1 acre at 5,000 per decimal = 5,00,000', () => {
	const r = C.landValue( num( '1' ), 'acre', num( '5000' ), 'decimal', std );
	assert.strictEqual( fmt( r.quantityInPriceUnit ), '100' );
	assert.strictEqual( fmt( r.total ), '5,00,000' );
});
test( 'land value in sq ft: 2,178 sq ft at 100 per sq ft', () => {
	const r = C.landValue( num( '5' ), 'shotangsho', num( '100' ), 'sqft', std );
	assert.strictEqual( fmt( r.total ), '2,17,800' );
});
test( 'money formatting: whole taka vs paisa, Bangladeshi grouping', () => {
	assert.strictEqual( C.formatMoney( num( '1234567' ) ), '৳ ১২,৩৪,৫৬৭' );
	assert.strictEqual( C.formatMoney( num( '1234567.5' ) ), '৳ ১২,৩৪,৫৬৭.৫০' );
	assert.strictEqual( C.formatMoney( num( '0.125' ) ), '৳ ০.১৩' );
});

/* ---------- Formatting ---------- */
test( 'formatting rounds half up and groups lakh/crore', () => {
	assert.strictEqual( fmt( num( '2.675' ), 2 ), '2.68' );
	assert.strictEqual( fmt( num( '10000000' ) ), '1,00,00,000' );
	assert.strictEqual( C.formatBn( num( '1234.5' ) ), '১,২৩৪.৫' );
});
test( 'unit labels and the katha/bigha standard flag', () => {
	assert.strictEqual( C.usesStandard( 'sqft', 'acre' ), false );
	assert.strictEqual( C.usesStandard( 'katha', 'acre' ), true );
	assert.throws( () => C.sqftPerUnit( 'zzz', std ), /UNIT/ );
});

console.log( '\n' + passed + ' tests passed' + ( process.exitCode ? ' (with failures)' : '' ) );
