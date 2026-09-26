<?php
/**
 * Request parsing and post querying shared by all three blocks.
 *
 * @package TFPG
 */

namespace TFPG;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for "which posts should the grid show right now".
 *
 * The URL query string is the canonical filter state:
 *
 *   ?tfpg_categories=travel,food&tfpg_tags=budget&tfpg_page=2
 *
 * Every block reads it through this class, so the Posts Grid, its inner
 * Pagination block and the Posts Filter always agree with each other — on the
 * initial server render, after a client-side navigation, and without JS.
 */
final class Query {

	/**
	 * Query-string parameter for the current page of the grid.
	 *
	 * A custom parameter (instead of `paged`/`page`) avoids clashing with core
	 * pagination of the page the blocks are placed on.
	 */
	const PARAM_PAGE = 'tfpg_page';

	/**
	 * Hard cap on selectable terms per taxonomy, to keep crafted URLs from
	 * generating huge SQL IN() lists.
	 */
	const MAX_TERMS_PER_FILTER = 50;

	/**
	 * Hard cap on posts per page.
	 */
	const MAX_PER_PAGE = 50;

	/**
	 * Per-request memo of grid queries, keyed by their arguments. The grid and
	 * its pagination block both ask for the same query; it only runs once.
	 *
	 * @var array<string, WP_Query>
	 */
	private static $queries = array();

	/**
	 * Per-request memo of the parsed filters.
	 *
	 * @var array<string, string[]>|null
	 */
	private static $filters = null;

	/**
	 * Returns the query-string parameter used for a filter key.
	 *
	 * The names are deliberately NOT the taxonomy names: `tfpg_category` and
	 * `tfpg_tag` are public query vars, and `/?tfpg_category=x` would make
	 * WordPress render a taxonomy archive instead of the page the blocks are
	 * on (e.g. a static front page).
	 *
	 * @param string $key Filter key (`category` or `tag`).
	 * @return string
	 */
	public static function param_for( $key ) {
		$params = array(
			'category' => 'tfpg_categories',
			'tag'      => 'tfpg_tags',
		);

		return $params[ $key ] ?? 'tfpg_' . $key . 's';
	}

	/**
	 * Returns all query-string parameters owned by the plugin.
	 *
	 * @return array{filters: array<string, string>, page: string}
	 */
	public static function params() {
		$filters = array();
		foreach ( array_keys( Content_Model::filter_taxonomies() ) as $key ) {
			$filters[ $key ] = self::param_for( $key );
		}

		return array(
			'filters' => $filters,
			'page'    => self::PARAM_PAGE,
		);
	}

	/**
	 * Reads and validates the active filters from the current request.
	 *
	 * Accepts both the comma-separated format written by the JS
	 * (`?tfpg_tags=a,b`) and the array format submitted by the no-JS form
	 * fallback (`?tfpg_tags[]=a&tfpg_tags[]=b`). Unknown slugs are dropped so a
	 * stale link can never produce a filter that the UI cannot display.
	 *
	 * @return array<string, string[]> Filter key => list of term slugs.
	 */
	public static function get_active_filters() {
		if ( null !== self::$filters ) {
			return self::$filters;
		}

		$filters = array();

		foreach ( Content_Model::filter_taxonomies() as $key => $taxonomy ) {
			$param = self::param_for( $key );
			// Read-only, public filtering (no state change, so no nonce); values
			// are sanitized below with sanitize_title().
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw = isset( $_GET[ $param ] ) ? wp_unslash( $_GET[ $param ] ) : array();

			$raw   = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
			$slugs = array_map( 'sanitize_title', array_filter( $raw, 'is_scalar' ) );
			$slugs = array_slice( array_values( array_unique( array_filter( $slugs ) ) ), 0, self::MAX_TERMS_PER_FILTER );

			if ( $slugs ) {
				$existing = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'slug'       => $slugs,
						'fields'     => 'slugs',
						'hide_empty' => false,
					)
				);
				$slugs    = is_wp_error( $existing ) ? array() : array_values( array_intersect( $slugs, $existing ) );
			}

			$filters[ $key ] = $slugs;
		}

		self::$filters = $filters;

		return $filters;
	}

	/**
	 * Returns the requested grid page (1-based).
	 *
	 * @return int
	 */
	public static function get_current_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, public pagination.
		return isset( $_GET[ self::PARAM_PAGE ] ) ? max( 1, absint( $_GET[ self::PARAM_PAGE ] ) ) : 1;
	}

	/**
	 * Builds WP_Query arguments for a set of filters.
	 *
	 * Filtering logic: OR within a taxonomy (`operator => IN`), AND across
	 * taxonomies (`relation => AND`). A taxonomy with no selected terms does
	 * not constrain the results at all.
	 *
	 * @param int                     $per_page Posts per page (-1 for all).
	 * @param array<string, string[]> $filters  Filter key => term slugs.
	 * @param int                     $page     1-based page number.
	 * @return array
	 */
	public static function build_query_args( $per_page, array $filters, $page = 1 ) {
		$tax_query = array( 'relation' => 'AND' );

		foreach ( Content_Model::filter_taxonomies() as $key => $taxonomy ) {
			if ( empty( $filters[ $key ] ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $filters[ $key ],
				'operator' => 'IN',
			);
		}

		$args = array(
			'post_type'           => Content_Model::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $per_page,
			'paged'               => max( 1, (int) $page ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);

		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtering by taxonomy is the whole point of the block.
		}

		/**
		 * Filters the WP_Query arguments used by the Posts Grid.
		 *
		 * @param array                   $args    WP_Query arguments.
		 * @param array<string, string[]> $filters Active filters (filter key => term slugs).
		 */
		return apply_filters( 'tfpg_query_args', $args, $filters );
	}

	/**
	 * Returns the (memoized) query for the grid, based on the current request.
	 *
	 * @param int $per_page Posts per page.
	 * @return WP_Query
	 */
	public static function get_grid_query( $per_page ) {
		$per_page = min( self::MAX_PER_PAGE, max( 1, (int) $per_page ) );
		$filters  = self::get_active_filters();
		$page     = self::get_current_page();
		$memo_key = md5( (string) wp_json_encode( array( $per_page, $filters, $page ) ) );

		if ( isset( self::$queries[ $memo_key ] ) ) {
			return self::$queries[ $memo_key ];
		}

		$query = new WP_Query( self::build_query_args( $per_page, $filters, $page ) );

		// An out-of-range page (e.g. an old shared link after content changed)
		// falls back to the first page instead of rendering an empty grid.
		if ( $page > 1 && ! $query->have_posts() ) {
			$query = new WP_Query( self::build_query_args( $per_page, $filters, 1 ) );
		}

		update_post_thumbnail_cache( $query );

		self::$queries[ $memo_key ] = $query;

		return $query;
	}

	/**
	 * Returns how many posts each term of a filter would match, given the
	 * *other* active filters ("disjunctive faceting").
	 *
	 * Because selections are ORed inside a taxonomy, a category's count must
	 * ignore the other selected categories but respect the selected tags, and
	 * vice versa. This makes the AND-across / OR-within logic visible in the UI.
	 *
	 * @param string $key Filter key (`category` or `tag`).
	 * @return array<string, int> Term slug => number of matching posts.
	 */
	public static function get_term_counts( $key ) {
		$taxonomies = Content_Model::filter_taxonomies();
		if ( ! isset( $taxonomies[ $key ] ) ) {
			return array();
		}

		$other_filters         = self::get_active_filters();
		$other_filters[ $key ] = array();

		$cache_key = 'counts:' . md5(
			(string) wp_json_encode(
				array(
					$key,
					$other_filters,
					wp_cache_get_last_changed( 'posts' ),
					wp_cache_get_last_changed( 'terms' ),
				)
			)
		);

		$counts = wp_cache_get( $cache_key, 'tfpg' );
		if ( is_array( $counts ) ) {
			return $counts;
		}

		$args = array_merge(
			self::build_query_args( -1, $other_filters ),
			array(
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$post_ids = ( new WP_Query( $args ) )->posts;
		$counts   = array();

		if ( $post_ids ) {
			$terms = wp_get_object_terms( $post_ids, $taxonomies[ $key ], array( 'fields' => 'all_with_object_id' ) );

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$counts[ $term->slug ] = ( $counts[ $term->slug ] ?? 0 ) + 1;
				}
			}
		}

		wp_cache_set( $cache_key, $counts, 'tfpg' );

		return $counts;
	}

	/**
	 * Returns the terms to offer in a filter: every term that has at least one
	 * published post.
	 *
	 * @param string $key Filter key (`category` or `tag`).
	 * @return \WP_Term[]
	 */
	public static function get_filter_terms( $key ) {
		$taxonomies = Content_Model::filter_taxonomies();
		if ( ! isset( $taxonomies[ $key ] ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomies[ $key ],
				'hide_empty' => true,
				'orderby'    => 'name',
			)
		);

		return is_wp_error( $terms ) ? array() : $terms;
	}

	/**
	 * Builds a URL for the current page with the given filters and page number,
	 * normalising any plugin parameters already in the URL.
	 *
	 * @param array<string, string[]> $filters Filter key => term slugs.
	 * @param int                     $page    1-based page number.
	 * @return string Relative URL (not escaped).
	 */
	public static function build_url( array $filters, $page = 1 ) {
		$url = self::get_base_url();

		$args = array();
		foreach ( self::params()['filters'] as $key => $param ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$args[ $param ] = implode( ',', $filters[ $key ] );
			}
		}
		if ( $page > 1 ) {
			$args[ self::PARAM_PAGE ] = (int) $page;
		}

		return $args ? add_query_arg( $args, $url ) : $url;
	}

	/**
	 * Returns the current request URL without any plugin parameters.
	 *
	 * @return string Relative URL (not escaped).
	 */
	public static function get_base_url() {
		$params = array_values( self::params()['filters'] );
		$params = array_merge( $params, array( self::PARAM_PAGE ) );

		return remove_query_arg( $params );
	}

	/**
	 * Returns the non-plugin query arguments of the current request, so the
	 * no-JS filter form can carry them over (e.g. `?page_id=12` on sites
	 * without pretty permalinks).
	 *
	 * @return array<string, string>
	 */
	public static function get_foreign_query_args() {
		$own  = array_merge( array_values( self::params()['filters'] ), array( self::PARAM_PAGE ) );
		$args = array();

		// Values are only echoed back (escaped) as hidden inputs, and sanitized
		// per value below.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( wp_unslash( $_GET ) as $name => $value ) {
			if ( in_array( (string) $name, $own, true ) || ! is_scalar( $value ) ) {
				continue;
			}
			// Names and values are escaped with esc_attr() when printed.
			$args[ (string) $name ] = sanitize_text_field( (string) $value );
		}

		return $args;
	}

	/**
	 * Computes the list of page links to display, with ellipses.
	 *
	 * Example for current = 6, total = 12, mid size = 1:
	 * `[1, '…', 5, 6, 7, '…', 12]`
	 *
	 * @param int $current  Current page.
	 * @param int $total    Total pages.
	 * @param int $mid_size Pages to show on each side of the current one.
	 * @return array<int|string> Page numbers, or the string 'dots'.
	 */
	public static function pagination_range( $current, $total, $mid_size = 1 ) {
		$items = array();
		$prev  = 0;

		for ( $page = 1; $page <= $total; $page++ ) {
			$is_edge    = 1 === $page || $total === $page;
			$is_in_band = abs( $page - $current ) <= $mid_size;

			if ( ! $is_edge && ! $is_in_band ) {
				continue;
			}

			if ( $prev && $page - $prev > 1 ) {
				// A single hidden page is shown instead of an ellipsis.
				$items[] = ( 2 === $page - $prev ) ? $page - 1 : 'dots';
			}

			$items[] = $page;
			$prev    = $page;
		}

		return $items;
	}

	/**
	 * Resets the per-request memo. Useful for tests and CLI.
	 */
	public static function reset() {
		self::$queries = array();
		self::$filters = null;
	}
}
