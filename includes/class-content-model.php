<?php
/**
 * Custom post type and taxonomies used by the blocks.
 *
 * @package TFPG
 */

namespace TFPG;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the prefixed content model: one post type and two taxonomies.
 *
 * The blocks query this post type instead of core `post` so the demo content
 * never mixes with (or pollutes) a site's real blog posts, and so uninstalling
 * the plugin can remove everything it created.
 */
final class Content_Model {

	/**
	 * Post type slug (max 20 chars).
	 */
	const POST_TYPE = 'tfpg_post';

	/**
	 * Hierarchical, category-like taxonomy.
	 */
	const TAX_CATEGORY = 'tfpg_category';

	/**
	 * Flat, tag-like taxonomy.
	 */
	const TAX_TAG = 'tfpg_tag';

	/**
	 * Maps the public filter keys used in URLs, block markup and JS to the
	 * registered taxonomy names. Keeping this mapping in one place means the
	 * front end never has to know the real taxonomy slugs.
	 *
	 * @return array<string, string> Filter key => taxonomy name.
	 */
	public static function filter_taxonomies() {
		return array(
			'category' => self::TAX_CATEGORY,
			'tag'      => self::TAX_TAG,
		);
	}

	/**
	 * Hooks registration into `init`.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the post type and taxonomies.
	 *
	 * Public so it can also be called directly during activation/uninstall,
	 * when `init` has already fired (or never will).
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Grid Posts', 'tfpg-posts-grid-filter' ),
					'singular_name'      => __( 'Grid Post', 'tfpg-posts-grid-filter' ),
					'add_new_item'       => __( 'Add New Grid Post', 'tfpg-posts-grid-filter' ),
					'edit_item'          => __( 'Edit Grid Post', 'tfpg-posts-grid-filter' ),
					'new_item'           => __( 'New Grid Post', 'tfpg-posts-grid-filter' ),
					'view_item'          => __( 'View Grid Post', 'tfpg-posts-grid-filter' ),
					'search_items'       => __( 'Search Grid Posts', 'tfpg-posts-grid-filter' ),
					'not_found'          => __( 'No grid posts found.', 'tfpg-posts-grid-filter' ),
					'not_found_in_trash' => __( 'No grid posts found in Trash.', 'tfpg-posts-grid-filter' ),
					'all_items'          => __( 'All Grid Posts', 'tfpg-posts-grid-filter' ),
					'menu_name'          => __( 'Grid Posts', 'tfpg-posts-grid-filter' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-grid-view',
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
				'rewrite'      => array( 'slug' => 'grid-posts' ),
				'taxonomies'   => array( self::TAX_CATEGORY, self::TAX_TAG ),
			)
		);

		register_taxonomy(
			self::TAX_CATEGORY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Grid Categories', 'tfpg-posts-grid-filter' ),
					'singular_name' => __( 'Grid Category', 'tfpg-posts-grid-filter' ),
					'all_items'     => __( 'All Grid Categories', 'tfpg-posts-grid-filter' ),
					'edit_item'     => __( 'Edit Grid Category', 'tfpg-posts-grid-filter' ),
					'add_new_item'  => __( 'Add New Grid Category', 'tfpg-posts-grid-filter' ),
					'menu_name'     => __( 'Categories', 'tfpg-posts-grid-filter' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'grid-category' ),
			)
		);

		register_taxonomy(
			self::TAX_TAG,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Grid Tags', 'tfpg-posts-grid-filter' ),
					'singular_name' => __( 'Grid Tag', 'tfpg-posts-grid-filter' ),
					'all_items'     => __( 'All Grid Tags', 'tfpg-posts-grid-filter' ),
					'edit_item'     => __( 'Edit Grid Tag', 'tfpg-posts-grid-filter' ),
					'add_new_item'  => __( 'Add New Grid Tag', 'tfpg-posts-grid-filter' ),
					'menu_name'     => __( 'Tags', 'tfpg-posts-grid-filter' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'grid-tag' ),
			)
		);
	}
}
