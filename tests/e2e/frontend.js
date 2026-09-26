/**
 * Front-end behaviour: filtering logic, counts, URL sync, back/forward,
 * pagination, focus management and rapid clicks — without full reloads.
 *
 * Expects freshly seeded demo content (`wp tfpg reset`).
 */
const assert = require( 'assert' );
const { DEMO_URL: PAGE, launch, run, collectErrors } = require( './helpers' );

const OUT = __dirname + '/screenshots';
require( 'fs' ).mkdirSync( OUT, { recursive: true } );

const titles = ( page ) =>
	page.$$eval( '.tfpg-card__title a', ( els ) =>
		els.map( ( e ) => e.textContent.trim() )
	);
const status = ( page ) => page.textContent( '.tfpg-posts-grid__status' );
const checked = ( page ) =>
	page.$$eval( '.tfpg-filter__checkbox', ( els ) =>
		els.filter( ( e ) => e.checked ).map( ( e ) => e.value )
	);
const counts = ( page ) =>
	page.$$eval( '.tfpg-filter__option', ( els ) =>
		Object.fromEntries(
			els.map( ( e ) => [
				JSON.parse( e.dataset.wpContext ).slug,
				e
					.querySelector( '.tfpg-filter__count [aria-hidden]' )
					?.textContent.trim(),
			] )
		)
	);

async function waitForStatus( page, text ) {
	await page.waitForFunction(
		( t ) =>
			document
				.querySelector( '.tfpg-posts-grid__status' )
				?.textContent.trim() === t,
		text,
		{ timeout: 10000 }
	);
	await page.waitForFunction(
		() => ! document.querySelector( '.tfpg-posts-grid.is-loading' )
	);
}

async function clickChip( page, slug ) {
	await page.click( `.tfpg-filter__checkbox[value="${ slug }"]` );
}

run( 'front end', async () => {
	const browser = await launch();
	const context = await browser.newContext( {
		viewport: { width: 1400, height: 1000 },
	} );
	const page = await context.newPage();
	const errors = collectErrors( page );

	await page.goto( PAGE );
	await page.waitForLoadState( 'networkidle' );
	// Hydrated when the no-JS Apply button is hidden.
	await page.waitForSelector( '.tfpg-filter__apply[hidden]', {
		state: 'attached',
	} );
	assert.strictEqual( await status( page ), '12 posts found.' );
	assert.strictEqual( ( await titles( page ) ).length, 6 );
	assert.ok(
		await page.isVisible( '.tfpg-pagination' ),
		'pagination visible'
	);
	assert.ok(
		! ( await page.isVisible( '.tfpg-filter__clear' ) ),
		'clear hidden without filters'
	);
	await page.screenshot( {
		path: `${ OUT }/01-initial.png`,
		fullPage: true,
	} );
	await page.evaluate( () => ( window.__tfpgMarker = 'same-document' ) );

	// 1. Travel.
	await clickChip( page, 'travel' );
	await waitForStatus( page, '4 posts found.' );
	assert.ok( page.url().includes( 'tfpg_categories=travel' ), page.url() );
	assert.deepStrictEqual( await checked( page ), [ 'travel' ] );
	assert.ok(
		await page.isVisible( '.tfpg-filter__clear' ),
		'clear visible with filters'
	);
	assert.ok(
		! ( await page.isVisible( '.tfpg-pagination' ) ),
		'no pagination with 4 posts'
	);

	// 2. + Food & Drink (OR within categories).
	await clickChip( page, 'food-drink' );
	await waitForStatus( page, '6 posts found.' );
	assert.ok(
		page.url().includes( 'tfpg_categories=travel,food-drink' ),
		page.url()
	);
	const orTitles = await titles( page );
	assert.deepStrictEqual( orTitles.sort(), [
		'A Weekend in Lisbon on a Shoestring',
		'Digital Nomad Visas Compared',
		'Mindful Eating in a Busy Week',
		'Packing Light: One Bag for Two Weeks',
		'Sourdough for Absolute Beginners',
		'Street Food Guide: Eating Well in Bangkok',
	] );

	// 3. + Budget tag (AND across).
	await clickChip( page, 'budget' );
	await waitForStatus( page, '3 posts found.' );
	assert.deepStrictEqual( ( await titles( page ) ).sort(), [
		'A Weekend in Lisbon on a Shoestring',
		'Packing Light: One Bag for Two Weeks',
		'Street Food Guide: Eating Well in Bangkok',
	] );
	const c = await counts( page );
	// Category counts respect the tag filter; tag counts respect the category filter.
	assert.strictEqual( c.technology, '0' );
	assert.strictEqual( c.travel, '3' );
	assert.strictEqual( c[ 'in-depth' ], '3' );
	assert.deepStrictEqual( ( await checked( page ) ).sort(), [
		'budget',
		'food-drink',
		'travel',
	] );
	await page.screenshot( {
		path: `${ OUT }/02-filtered.png`,
		fullPage: true,
	} );

	// 4. + Quick read (OR within tags).
	await clickChip( page, 'quick-read' );
	await waitForStatus( page, '4 posts found.' ); // + Mindful Eating (wellness, food; quick-read)

	// 5. Back button restores both the grid and the checkboxes.
	await page.goBack();
	await waitForStatus( page, '3 posts found.' );
	assert.deepStrictEqual( ( await checked( page ) ).sort(), [
		'budget',
		'food-drink',
		'travel',
	] );
	await page.goBack();
	await waitForStatus( page, '6 posts found.' );
	assert.deepStrictEqual( ( await checked( page ) ).sort(), [
		'food-drink',
		'travel',
	] );
	await page.goForward();
	await waitForStatus( page, '3 posts found.' );
	assert.deepStrictEqual( ( await checked( page ) ).sort(), [
		'budget',
		'food-drink',
		'travel',
	] );

	// 6. Uncheck a chip.
	await clickChip( page, 'travel' );
	await waitForStatus( page, '2 posts found.' ); // food-drink AND budget: Lisbon + Bangkok

	// 7. Empty result, then clear from the grid's empty state.
	await clickChip( page, 'technology' );
	await waitForStatus( page, '2 posts found.' );
	await clickChip( page, 'food-drink' );
	await waitForStatus( page, 'No posts found.' ); // technology AND budget
	assert.ok( await page.isVisible( '.tfpg-posts-grid__empty' ) );
	await page.screenshot( { path: `${ OUT }/02b-empty.png` } );
	await page.click( '.tfpg-posts-grid__clear' );
	await waitForStatus( page, '12 posts found.' );

	// 8. Clear all from the filter.
	await clickChip( page, 'wellness' );
	await waitForStatus( page, '3 posts found.' );
	await page.click( '.tfpg-filter__clear' );
	await waitForStatus( page, '12 posts found.' );
	assert.strictEqual( new URL( page.url() ).search, new URL( PAGE ).search );
	assert.deepStrictEqual( await checked( page ), [] );

	// 9. Pagination.
	await page.click( '.tfpg-pagination__next' );
	await page.waitForFunction( () =>
		window.location.search.includes( 'tfpg_page=2' )
	);
	await page.waitForFunction( () =>
		document
			.querySelector( '.tfpg-pagination .is-current' )
			?.textContent.includes( '2' )
	);
	const p2 = await titles( page );
	assert.strictEqual( p2[ 0 ], 'Sourdough for Absolute Beginners' );
	const focused = await page.evaluate( () =>
		// eslint-disable-next-line @wordpress/no-global-active-element -- Runs in the browser page.
		document.activeElement?.classList.contains( 'tfpg-posts-grid' )
	);
	assert.ok( focused, 'grid focused after pagination' );
	await page.screenshot( { path: `${ OUT }/03-page2.png`, fullPage: true } );

	// 10. Filtering from page 2 resets to page 1.
	await clickChip( page, 'how-to' );
	await waitForStatus( page, '7 posts found.' );
	assert.ok(
		! page.url().includes( 'tfpg_page' ),
		'page reset: ' + page.url()
	);
	assert.ok( await page.isVisible( '.tfpg-pagination' ) );
	await page.click( '.tfpg-pagination__number >> text=2' );
	await page.waitForFunction( () =>
		window.location.search.includes( 'tfpg_page=2' )
	);
	await page.waitForFunction(
		() => document.querySelectorAll( '.tfpg-card' ).length === 1
	);
	assert.ok(
		page.url().includes( 'tfpg_tags=how-to' ),
		'filters kept on page 2: ' + page.url()
	);

	assert.strictEqual(
		await page.evaluate( () => window.__tfpgMarker ),
		'same-document',
		'no full page reloads happened'
	);

	// 11. Rapid clicks: only the final state wins.
	await page.goto( PAGE );
	await page.waitForSelector( '.tfpg-filter__apply[hidden]', {
		state: 'attached',
	} );
	await clickChip( page, 'design' );
	await clickChip( page, 'wellness' );
	await clickChip( page, 'opinion' );
	await waitForStatus( page, '2 posts found.' ); // (design|wellness) & opinion: typography, mindful
	assert.ok(
		page.url().includes( 'tfpg_categories=design,wellness' ) &&
			page.url().includes( 'tfpg_tags=opinion' ),
		page.url()
	);

	// 12. Mobile layout screenshot.
	await page.setViewportSize( { width: 390, height: 900 } );
	await page.goto( PAGE );
	await page.waitForLoadState( 'networkidle' );
	await page.screenshot( { path: `${ OUT }/04-mobile.png`, fullPage: true } );

	assert.deepStrictEqual( errors, [], 'no console errors' );
	await browser.close();
} );
