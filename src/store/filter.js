/**
 * Actions used by the Posts Filter block.
 */
import { store, getContext, withSyncEvent } from '@wordpress/interactivity';

import { NAMESPACE, state, actions } from './core';

store( NAMESPACE, {
	actions: {
		/**
		 * Toggles the term in the current context and refreshes the grids.
		 * Context: `{ taxonomy: 'category'|'tag', slug: string }`.
		 */
		*toggleTerm() {
			const { taxonomy, slug } = getContext();
			const current = state.filters[ taxonomy ] || [];
			const next = current.includes( slug )
				? current.filter( ( item ) => item !== slug )
				: [ ...current, slug ];

			yield actions.applyFilters( {
				...state.filters,
				[ taxonomy ]: next,
			} );
		},

		/**
		 * The form only submits when JS is unavailable (or on Enter); with JS
		 * the filters are already applied on change, so just re-apply them.
		 */
		submitForm: withSyncEvent( function* ( event ) {
			event.preventDefault();
			yield actions.applyFilters( { ...state.filters } );
		} ),
	},
} );
