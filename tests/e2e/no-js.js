/**
 * Progressive enhancement: filtering and pagination with JavaScript disabled.
 */
const assert = require( 'assert' );
const { DEMO_URL, launch, run } = require( './helpers' );

run( 'no-JS fallback', async () => {
	const browser = await launch();
	const context = await browser.newContext( { javaScriptEnabled: false } );
	const page = await context.newPage();

	await page.goto( DEMO_URL );
	assert.ok(
		await page.isVisible( '.tfpg-filter__apply' ),
		'Apply button visible without JS'
	);
	assert.ok(
		! ( await page.isVisible( '.tfpg-filter__clear' ) ),
		'Clear hidden without filters'
	);

	await page.check( '.tfpg-filter__checkbox[value="travel"]' );
	await page.check( '.tfpg-filter__checkbox[value="technology"]' );
	await page.check( '.tfpg-filter__checkbox[value="remote-work"]' );
	await Promise.all( [
		page.waitForNavigation(),
		page.click( '.tfpg-filter__apply' ),
	] );
	const titles = await page.$$eval( '.tfpg-card__title a', ( els ) =>
		els.map( ( e ) => e.textContent.trim() )
	);
	assert.deepStrictEqual( titles.sort(), [
		'Digital Nomad Visas Compared',
		'Why Every Remote Team Needs a Written Culture',
	] );
	assert.ok(
		await page.isChecked( '.tfpg-filter__checkbox[value="travel"]' )
	);
	assert.ok(
		await page.isVisible( '.tfpg-filter__clear' ),
		'Clear visible with filters'
	);

	// Plain pagination links.
	await page.click( '.tfpg-filter__clear' );
	await page.waitForLoadState();
	await page.click( '.tfpg-pagination__next' );
	await page.waitForLoadState();
	assert.ok( page.url().includes( 'tfpg_page=2' ) );
	assert.strictEqual(
		await page
			.textContent( '.tfpg-pagination .is-current' )
			.then( ( t ) => t.replace( /\D/g, '' ) ),
		'2'
	);

	await browser.close();
} );
