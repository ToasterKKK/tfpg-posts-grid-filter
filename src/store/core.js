/**
 * Shared state and navigation.
 *
 * State initialised on the server (see TFPG\Blocks::init_store()):
 * - `state.filters`   Selected term slugs per filter key.
 * - `state.isLoading` True while a client-side navigation is in flight.
 *
 * Config (read-only): `params` — the query-string parameter names.
 */
import {
	store,
	getConfig,
	getContext,
	withSyncEvent,
} from '@wordpress/interactivity';

import { buildUrl, readFiltersFromUrl } from './url';

export const NAMESPACE = 'tfpg';

/**
 * Incremented on every navigation so that only the latest one clears the
 * loading state (a slow, superseded request must not hide the loading state
 * of the request that replaced it).
 */
let navigationId = 0;

export const { state, actions } = store( NAMESPACE, {
	state: {
		/**
		 * Always true on the client (and undefined on the server). Used to hide
		 * no-JS fallbacks such as the filter's "Apply" button once hydrated.
		 */
		isHydrated: true,

		/**
		 * Whether any filter is active. Mirrors the PHP closure.
		 *
		 * @return {boolean} True if at least one term is selected.
		 */
		get hasActiveFilters() {
			return Object.values( state.filters ).some(
				( slugs ) => slugs.length > 0
			);
		},

		/**
		 * Whether the term in the current context is selected. Mirrors the PHP
		 * closure. Context: `{ taxonomy: 'category'|'tag', slug: string }`.
		 *
		 * @return {boolean} True if selected.
		 */
		get isTermSelected() {
			const { taxonomy, slug } = getContext();
			return !! state.filters[ taxonomy ]?.includes( slug );
		},
	},

	actions: {
		/**
		 * Navigates to a URL with the Interactivity Router, which fetches the
		 * page and swaps only the router regions (the grids and the filters),
		 * keeping the URL — and therefore back/forward and shareable links —
		 * in sync with what is on screen.
		 *
		 * @param {string} url Destination URL.
		 */
		*navigate( url ) {
			const id = ++navigationId;
			state.isLoading = true;

			try {
				const { actions: router } =
					yield import( '@wordpress/interactivity-router' );
				// The grid announces its own, more useful, result summary.
				yield router.navigate( url, {
					screenReaderAnnouncement: false,
				} );
			} finally {
				if ( id === navigationId ) {
					state.isLoading = false;
				}
			}
		},

		/**
		 * Applies a new set of filters: resets pagination and navigates.
		 *
		 * @param {Object<string, string[]>} filters Filter key => term slugs.
		 */
		*applyFilters( filters ) {
			state.filters = filters;
			yield actions.navigate(
				buildUrl( filters, getConfig( NAMESPACE ), 1 )
			);
		},

		/**
		 * Removes every active filter. Bound to links whose `href` already
		 * points to the unfiltered URL, so it also works without JS.
		 */
		clearFilters: withSyncEvent( function* ( event ) {
			event.preventDefault();

			const empty = Object.fromEntries(
				Object.keys( state.filters ).map( ( key ) => [ key, [] ] )
			);
			yield actions.applyFilters( empty );
		} ),
	},

	callbacks: {
		/**
		 * Back/forward: the router restores the regions' HTML, and this puts the
		 * client-side filter state back in line with the URL.
		 */
		syncFiltersFromUrl() {
			state.filters = readFiltersFromUrl(
				window.location.href,
				getConfig( NAMESPACE )
			);
		},
	},
} );
