/**
 * A block that only appears after a client-side navigation (the Pagination
 * block, once filters are cleared) must be interactive straight away.
 */
const assert = require( 'assert' );
const { DEMO_URL, launch, run, collectErrors } = require( './helpers' );

run( 'block rendered after navigation', async () => {
	const browser = await launch();
	const page = await browser.newPage();
	const errors = collectErrors( page );
	// Start on a filtered URL where the pagination block is not rendered at all.
	const url = new URL( DEMO_URL );
	url.searchParams.set( 'tfpg_categories', 'travel' );
	await page.goto( url.toString() );
	await page.waitForSelector( '.tfpg-filter__apply[hidden]', {
		state: 'attached',
	} );
	assert.strictEqual( await page.locator( '.tfpg-pagination' ).count(), 0 );
	await page.evaluate( () => ( window.__marker = 1 ) );
	await page.click( '.tfpg-filter__clear' );
	await page.waitForFunction(
		() =>
			document
				.querySelector( '.tfpg-posts-grid__status' )
				.textContent.trim() === '12 posts found.'
	);
	await page.click( '.tfpg-pagination__next' );
	await page.waitForFunction( () =>
		document
			.querySelector( '.tfpg-pagination .is-current' )
			?.textContent.includes( '2' )
	);
	assert.strictEqual(
		await page.evaluate( () => window.__marker ),
		1,
		'pagination worked without reload'
	);
	assert.deepStrictEqual( errors, [], 'no console errors' );
	await browser.close();
} );
