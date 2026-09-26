/**
 * Editor-side constants. Must match TFPG\Content_Model on the server.
 */
export const POST_TYPE = 'tfpg_post';

export const TAXONOMIES = {
	category: 'tfpg_category',
	tag: 'tfpg_tag',
};

/**
 * REST query used for the editor previews. The grid and its pagination build
 * the exact same query object so they share one resolved request in the
 * core-data store.
 *
 * @param {number} perPage Posts per page.
 * @return {Object} REST query arguments.
 */
export const getPreviewQuery = ( perPage ) => ( {
	per_page: perPage,
	page: 1,
	orderby: 'date',
	order: 'desc',
	status: 'publish',
} );
