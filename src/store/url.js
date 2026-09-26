/**
 * URL helpers shared by the view modules.
 *
 * The URL is the canonical filter state (see includes/class-query.php), so the
 * client only ever needs to translate between the store's `filters` object and
 * the query string. Parameter names come from the server via `getConfig()`, so
 * PHP stays the single source of truth for them.
 */

/**
 * Returns the query-string parameter names configured by the server.
 *
 * @param {Object} config Store config (`getConfig( 'tfpg' )`).
 * @return {{filters: Object<string, string>, page: string}} Param names.
 */
const getParams = ( config ) => config.params;

/**
 * Whether a query-string key belongs to the plugin, including the `key[]`
 * array form produced by the no-JS form fallback.
 *
 * @param {string}   key   Query-string key.
 * @param {string[]} names Plugin parameter names.
 * @return {boolean} True if the key is owned by the plugin.
 */
const isOwnParam = ( key, names ) =>
	names.some( ( name ) => key === name || key === `${ name }[]` );

/**
 * Reads the filters encoded in a URL.
 *
 * @param {string} href   URL to parse.
 * @param {Object} config Store config.
 * @return {Object<string, string[]>} Filter key => term slugs.
 */
export const readFiltersFromUrl = ( href, config ) => {
	const url = new URL( href, window.location.href );
	const filters = {};

	Object.entries( getParams( config ).filters ).forEach(
		( [ key, param ] ) => {
			const values = [
				...url.searchParams.getAll( param ),
				...url.searchParams.getAll( `${ param }[]` ),
			];

			filters[ key ] = [
				...new Set(
					values
						.flatMap( ( value ) => value.split( ',' ) )
						.map( ( value ) => value.trim() )
						.filter( Boolean )
				),
			];
		}
	);

	return filters;
};

/**
 * Builds the URL of the current page for a set of filters and a page number.
 *
 * Any other query arguments already in the URL are preserved.
 *
 * @param {Object<string, string[]>} filters Filter key => term slugs.
 * @param {Object}                   config  Store config.
 * @param {number}                   page    1-based page number.
 * @return {string} Absolute URL.
 */
export const buildUrl = ( filters, config, page = 1 ) => {
	const params = getParams( config );
	const url = new URL( window.location.href );
	const ownNames = [ ...Object.values( params.filters ), params.page ];

	[ ...url.searchParams.keys() ]
		.filter( ( key ) => isOwnParam( key, ownNames ) )
		.forEach( ( key ) => url.searchParams.delete( key ) );

	Object.entries( params.filters ).forEach( ( [ key, param ] ) => {
		if ( filters[ key ]?.length ) {
			url.searchParams.set( param, filters[ key ].join( ',' ) );
		}
	} );

	if ( page > 1 ) {
		url.searchParams.set( params.page, String( page ) );
	}

	url.hash = '';

	// Keep commas readable (`a,b` rather than `a%2Cb`) in shareable URLs.
	return url.toString().replace( /%2C/gi, ',' );
};
