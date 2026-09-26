/**
 * Block editor: the seeded demo page parses without validation errors, the
 * previews render, Inspector controls update attributes, and the grid gets
 * its Pagination inner block from the template. Nothing is saved.
 *
 * Requires PAGE_ID (the demo page ID: `wp post list --post_type=page --name=posts-grid-demo --field=ID`).
 */
const assert = require( 'assert' );
const { BASE, launch, run, collectErrors } = require( './helpers' );

const PAGE_ID = process.env.PAGE_ID;
const OUT = __dirname + '/screenshots';
require( 'fs' ).mkdirSync( OUT, { recursive: true } );

run( 'block editor', async () => {
	assert.ok( PAGE_ID, 'Set PAGE_ID to the demo page ID.' );
	const browser = await launch();
	const context = await browser.newContext( {
		viewport: { width: 1500, height: 1100 },
	} );
	const page = await context.newPage();
	const errors = collectErrors( page );

	// Log in.
	await page.goto( `${ BASE }/wp-login.php` );
	await page.fill( '#user_login', process.env.WP_USER || 'admin' );
	await page.fill( '#user_pass', process.env.WP_PASS || 'admin' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );

	// Open the demo page in the editor.
	await page.goto(
		`${ BASE }/wp-admin/post.php?post=${ PAGE_ID }&action=edit`
	);
	await page.waitForFunction(
		() =>
			window.wp?.data?.select( 'core/block-editor' )?.getBlocks().length >
			0,
		null,
		{ timeout: 60000 }
	);
	await page.evaluate( () => {
		wp.data
			.dispatch( 'core/preferences' )
			.set( 'core/edit-post', 'welcomeGuide', false );
	} );

	const report = await page.evaluate( () => {
		const out = [];
		const walk = ( blocks, depth = 0 ) =>
			blocks.forEach( ( b ) => {
				out.push( {
					name: b.name,
					valid: b.isValid,
					depth,
					attrs: b.attributes,
				} );
				walk( b.innerBlocks, depth + 1 );
			} );
		walk( wp.data.select( 'core/block-editor' ).getBlocks() );
		return out;
	} );
	assert.ok(
		report.every( ( b ) => b.valid ),
		'all blocks valid'
	);
	assert.ok( report.some( ( b ) => b.name === 'tfpg/posts-grid' ) );
	assert.ok(
		report.some(
			( b ) => b.name === 'tfpg/posts-pagination' && b.depth > 0
		)
	);
	assert.ok( report.some( ( b ) => b.name === 'tfpg/posts-filter' ) );

	// Wait for the grid preview to load inside the canvas iframe.
	const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
	await canvas
		.locator( '.wp-block-tfpg-posts-grid .tfpg-card' )
		.first()
		.waitFor( { timeout: 30000 } );
	await canvas
		.locator( '.wp-block-tfpg-posts-filter .tfpg-filter__option' )
		.first()
		.waitFor( { timeout: 30000 } );
	await page.waitForTimeout( 1500 );
	const cardCount = await canvas
		.locator( '.wp-block-tfpg-posts-grid .tfpg-card' )
		.count();
	assert.strictEqual(
		cardCount,
		6,
		'editor preview shows postsPerPage cards'
	);
	const imgCount = await canvas
		.locator( '.wp-block-tfpg-posts-grid img.tfpg-card__image' )
		.count();
	assert.strictEqual( imgCount, 6, 'featured images in preview' );

	// Select the grid block and change settings through the Inspector.
	await canvas
		.locator( '.wp-block-tfpg-posts-grid .tfpg-card' )
		.first()
		.click();
	await page.evaluate( () => {
		const grid = wp.data
			.select( 'core/block-editor' )
			.getBlocksByName( 'tfpg/posts-grid' )[ 0 ];
		wp.data.dispatch( 'core/block-editor' ).selectBlock( grid );
	} );
	await page.waitForTimeout( 500 );
	await page.screenshot( { path: `${ OUT }/05-editor.png` } );

	const columnsGroup = page.getByRole( 'radiogroup', { name: 'Columns' } );
	await columnsGroup.getByRole( 'radio', { name: '2' } ).click();
	const perPage = page.getByRole( 'spinbutton', { name: 'Posts per page' } );
	await perPage.fill( '4' );
	await perPage.press( 'Tab' );
	await page.waitForTimeout( 1500 );
	const attrs = await page.evaluate( () => {
		const id = wp.data
			.select( 'core/block-editor' )
			.getBlocksByName( 'tfpg/posts-grid' )[ 0 ];
		return wp.data.select( 'core/block-editor' ).getBlockAttributes( id );
	} );
	assert.strictEqual( attrs.columns, 2 );
	assert.strictEqual( attrs.postsPerPage, 4 );
	await canvas.locator( '.tfpg-posts-grid--columns-2' ).waitFor();
	await page.waitForFunction( () => true );
	assert.strictEqual(
		await canvas.locator( '.wp-block-tfpg-posts-grid .tfpg-card' ).count(),
		4
	);
	await page.screenshot( { path: `${ OUT }/06-editor-2cols.png` } );

	// Serialized content round-trips.
	const content = await page.evaluate( () =>
		wp.data.select( 'core/editor' ).getEditedPostContent()
	);
	assert.ok(
		content.includes(
			'<!-- wp:tfpg/posts-grid {"columns":2,"postsPerPage":4} -->'
		),
		content
	);
	assert.ok( content.includes( '<!-- wp:tfpg/posts-pagination /-->' ) );

	// Insert the blocks on a brand-new page: grid gets its pagination from the template.
	await page.goto( `${ BASE }/wp-admin/post-new.php?post_type=page` );
	await page.waitForFunction(
		() =>
			window.wp?.data?.select( 'core/block-editor' ) &&
			wp.blocks.getBlockType( 'tfpg/posts-grid' ),
		null,
		{ timeout: 60000 }
	);
	// Dismiss the "choose a pattern" modal shown for new pages.
	await page.waitForTimeout( 1500 );
	if ( await page.locator( '.components-modal__screen-overlay' ).count() ) {
		await page.keyboard.press( 'Escape' );
	}
	await page.evaluate( () => {
		const { createBlock } = wp.blocks;
		wp.data.dispatch( 'core/block-editor' ).insertBlocks( [
			createBlock( 'tfpg/posts-filter' ),
			createBlock( 'core/paragraph', {
				content: 'Some content between the blocks.',
			} ),
			createBlock( 'tfpg/posts-grid' ),
		] );
	} );
	await page.waitForTimeout( 2000 );
	const newTree = await page.evaluate( () =>
		wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.map( ( b ) => [ b.name, b.innerBlocks.map( ( i ) => i.name ) ] )
	);
	assert.deepStrictEqual( newTree[ 2 ], [
		'tfpg/posts-grid',
		[ 'tfpg/posts-pagination' ],
	] );

	// The pagination block cannot be inserted outside the grid.
	const canInsertRoot = await page.evaluate( () =>
		wp.data
			.select( 'core/block-editor' )
			.canInsertBlockType( 'tfpg/posts-pagination', '' )
	);
	assert.strictEqual(
		canInsertRoot,
		false,
		'pagination restricted to grid parent'
	);
	// …and the inserter never offers a second one (it is added via the template).
	const offered = await page.evaluate( () => {
		const grid = wp.data
			.select( 'core/block-editor' )
			.getBlocksByName( 'tfpg/posts-grid' )[ 0 ];
		return wp.data
			.select( 'core/block-editor' )
			.getInserterItems( grid )
			.map( ( i ) => i.name );
	} );
	assert.ok(
		! offered.includes( 'tfpg/posts-pagination' ),
		'pagination hidden from inserter'
	);

	// Removing the pagination shows an "Add pagination" button that restores it.
	await page.evaluate( () => {
		const grid = wp.data
			.select( 'core/block-editor' )
			.getBlocksByName( 'tfpg/posts-grid' )[ 0 ];
		const [ pagination ] = wp.data
			.select( 'core/block-editor' )
			.getBlockOrder( grid );
		wp.data.dispatch( 'core/block-editor' ).removeBlock( pagination );
		wp.data.dispatch( 'core/block-editor' ).selectBlock( grid );
	} );
	await canvas.getByRole( 'button', { name: 'Add pagination' } ).click();
	const restored = await page.evaluate( () => {
		const grid = wp.data
			.select( 'core/block-editor' )
			.getBlocksByName( 'tfpg/posts-grid' )[ 0 ];
		return wp.data
			.select( 'core/block-editor' )
			.getBlocks( grid )
			.map( ( b ) => b.name );
	} );
	assert.deepStrictEqual( restored, [ 'tfpg/posts-pagination' ] );

	await page.screenshot( { path: `${ OUT }/07-editor-new-page.png` } );

	// Only report errors that come from this plugin (core and offline
	// network noise, e.g. gravatar requests, are out of scope).
	const ours = errors.filter( ( e ) => /tfpg/i.test( e ) );
	assert.deepStrictEqual( ours, [], 'no plugin console errors' );
	await browser.close();
} );
