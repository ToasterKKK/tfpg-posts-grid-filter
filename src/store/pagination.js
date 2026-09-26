/**
 * Actions used by the Posts Grid Pagination block.
 */
import { store, getElement, withSyncEvent } from '@wordpress/interactivity';

import { NAMESPACE, actions } from './core';

/**
 * Whether a click should be left to the browser (new tab, download, …).
 *
 * @param {MouseEvent} event Click event.
 * @return {boolean} True for modified or non-primary clicks.
 */
const isModifiedClick = ( event ) =>
	event.button !== 0 ||
	event.metaKey ||
	event.ctrlKey ||
	event.shiftKey ||
	event.altKey ||
	event.defaultPrevented;

store( NAMESPACE, {
	actions: {
		/**
		 * Loads another page of results in place. The link's `href` is a
		 * regular URL (filters + page), so this is a pure enhancement.
		 *
		 * @param {MouseEvent} event Click event.
		 */
		goToPage: withSyncEvent( function* ( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			event.preventDefault();

			const { ref } = getElement();
			const regionId = ref
				.closest( '[data-wp-router-region]' )
				?.getAttribute( 'data-wp-router-region' );

			yield actions.navigate( ref.href );

			// Move focus to the refreshed grid so keyboard and screen reader
			// users land on the new results, and bring it into view.
			const grid =
				regionId &&
				document.querySelector(
					`[data-wp-router-region="${ window.CSS.escape( regionId ) }"]`
				);

			if ( grid ) {
				grid.focus( { preventScroll: true } );
				if ( grid.getBoundingClientRect().top < 0 ) {
					grid.scrollIntoView( {
						behavior: 'smooth',
						block: 'start',
					} );
				}
			}
		} ),

		/**
		 * Prefetches the linked page on hover/focus so the click feels instant.
		 */
		*prefetchPage() {
			const { ref } = getElement();
			const { actions: router } =
				yield import( '@wordpress/interactivity-router' );
			yield router.prefetch( ref.href );
		},
	},
} );
