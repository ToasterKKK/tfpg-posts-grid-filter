/**
 * Shared helpers for the end-to-end checks.
 *
 * Environment variables:
 * - BASE         Site URL (default http://localhost:8080).
 * - DEMO_URL     Demo page URL (default `${ BASE }/posts-grid-demo/`).
 * - WP_USER / WP_PASS  Admin credentials for the editor check (admin/admin).
 * - CHROMIUM_PATH      Optional path to a Chromium binary.
 */
/* eslint-disable no-console -- CLI test runner output. */
const { chromium } = require( 'playwright' );

const BASE = ( process.env.BASE || 'http://localhost:8080' ).replace(
	/\/$/,
	''
);
const DEMO_URL = process.env.DEMO_URL || `${ BASE }/posts-grid-demo/`;

const launch = () =>
	chromium.launch(
		process.env.CHROMIUM_PATH
			? { executablePath: process.env.CHROMIUM_PATH }
			: {}
	);

const run = ( name, fn ) =>
	fn()
		.then( () => console.log( `✔ ${ name }` ) )
		.catch( ( error ) => {
			console.error( `✘ ${ name }\n`, error );
			process.exit( 1 );
		} );

/**
 * Collects page errors and console errors/warnings.
 *
 * @param {Object} page Playwright page.
 * @return {string[]} Live list of collected messages.
 */
const collectErrors = ( page ) => {
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( `pageerror: ${ e.message }` ) );
	page.on( 'console', ( m ) => {
		if ( [ 'error', 'warning' ].includes( m.type() ) ) {
			errors.push( `${ m.type() }: ${ m.text() }` );
		}
	} );
	return errors;
};

module.exports = { BASE, DEMO_URL, launch, run, collectErrors };
