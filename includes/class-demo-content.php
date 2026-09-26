<?php
/**
 * Demo content seeding and cleanup.
 *
 * @package TFPG
 */

namespace TFPG;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Creates (and removes) the demo terms, posts, featured images and page.
 *
 * Seeding is idempotent: every created object is tagged with a stable
 * `_tfpg_demo_key` meta value (or looked up by slug for terms), so running it
 * again — re-activating the plugin, or `wp tfpg seed` — only recreates what is
 * missing and never duplicates content.
 */
final class Demo_Content {

	/**
	 * Option storing the IDs of everything created, for cleanup.
	 */
	const OPTION = 'tfpg_demo_content';

	/**
	 * Meta key marking an object as demo content; the value is its stable key.
	 */
	const META_KEY = '_tfpg_demo_key';

	/**
	 * Stable key of the demo page.
	 */
	const PAGE_KEY = 'demo-page';

	/**
	 * Seeds all demo content.
	 *
	 * @return array{posts: int, terms: int, page_id: int, errors: string[]} Summary.
	 */
	public static function seed() {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$data   = require TFPG_DIR . 'includes/demo-data.php';
		$errors = array();
		$record = array(
			'terms'       => array(),
			'posts'       => array(),
			'attachments' => array(),
			'page'        => 0,
		);

		// 1. Terms.
		$term_sets = array(
			Content_Model::TAX_CATEGORY => $data['categories'],
			Content_Model::TAX_TAG      => $data['tags'],
		);

		foreach ( $term_sets as $taxonomy => $terms ) {
			foreach ( $terms as $slug => $name ) {
				$term_id = self::ensure_term( $taxonomy, $slug, $name );

				if ( is_wp_error( $term_id ) ) {
					$errors[] = $term_id->get_error_message();
					continue;
				}
				$record['terms'][] = $term_id;
			}
		}

		// 2. Posts, newest first, one day apart so the default order is stable.
		$author_id = self::get_author_id();
		$now       = time();

		foreach ( $data['posts'] as $index => $post ) {
			$post_id = self::ensure_post( $post, $author_id, $now - ( $index * DAY_IN_SECONDS ) );

			if ( is_wp_error( $post_id ) ) {
				$errors[] = $post_id->get_error_message();
				continue;
			}
			$record['posts'][] = $post_id;

			wp_set_object_terms( $post_id, $post['categories'], Content_Model::TAX_CATEGORY );
			wp_set_object_terms( $post_id, $post['tags'], Content_Model::TAX_TAG );

			if ( ! has_post_thumbnail( $post_id ) ) {
				$attachment_id = self::ensure_image( $post['key'], $post_id, $post['image_alt'] );

				if ( is_wp_error( $attachment_id ) ) {
					$errors[] = $attachment_id->get_error_message();
					continue;
				}
				set_post_thumbnail( $post_id, $attachment_id );
			}

			$record['attachments'][] = (int) get_post_thumbnail_id( $post_id );
		}

		// 3. Demo page with both blocks.
		$page_id = self::ensure_demo_page( $author_id );

		if ( is_wp_error( $page_id ) ) {
			$errors[] = $page_id->get_error_message();
		} else {
			$record['page'] = $page_id;
		}

		$record['version'] = TFPG_VERSION;
		update_option( self::OPTION, $record, false );

		return array(
			'posts'   => count( $record['posts'] ),
			'terms'   => count( $record['terms'] ),
			'page_id' => (int) $record['page'],
			'errors'  => $errors,
		);
	}

	/**
	 * Removes all demo content created by the plugin.
	 *
	 * Only objects carrying the demo meta marker (and the recorded terms) are
	 * deleted; anything an editor created themselves is left untouched.
	 *
	 * @return int Number of posts, pages and attachments deleted.
	 */
	public static function cleanup() {
		$ids = get_posts(
			array(
				'post_type'        => array( Content_Model::POST_TYPE, 'page', 'attachment' ),
				'post_status'      => array_keys( get_post_stati() ),
				'meta_key'         => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off cleanup query.
				'fields'           => 'ids',
				'posts_per_page'   => -1,
				'suppress_filters' => true,
			)
		);

		foreach ( $ids as $id ) {
			if ( 'attachment' === get_post_type( $id ) ) {
				wp_delete_attachment( $id, true );
			} else {
				wp_delete_post( $id, true );
			}
		}

		$record = get_option( self::OPTION, array() );

		foreach ( (array) ( $record['terms'] ?? array() ) as $term_id ) {
			$term = get_term( (int) $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
			}
		}

		delete_option( self::OPTION );

		return count( $ids );
	}

	/**
	 * Returns the ID of the demo page, if it still exists.
	 *
	 * @return int
	 */
	public static function get_demo_page_id() {
		$record  = get_option( self::OPTION, array() );
		$page_id = (int) ( $record['page'] ?? 0 );

		return ( $page_id && 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id ) ) ? $page_id : 0;
	}

	/**
	 * Returns the block markup of the demo page.
	 *
	 * The filter and the grid sit in *different* columns: they are siblings,
	 * not parent/child, which demonstrates that they sync purely through the
	 * shared store and the URL.
	 *
	 * @return string
	 */
	public static function get_demo_page_content() {
		$intro = __( 'Pick any combination of categories and tags. Within a group, filters are combined with OR; across groups, with AND — a post must match at least one selected category and at least one selected tag. The numbers next to each option show how many posts it would match.', 'tfpg-posts-grid-filter' );
		$aside = __( 'The filter and the grid are two independent blocks. Move either one anywhere on the page and they will still work together.', 'tfpg-posts-grid-filter' );

		return implode(
			"\n\n",
			array(
				'<!-- wp:paragraph -->' . "\n" . '<p>' . esc_html( $intro ) . '</p>' . "\n" . '<!-- /wp:paragraph -->',
				'<!-- wp:columns {"align":"wide"} -->' . "\n"
				. '<div class="wp-block-columns alignwide"><!-- wp:column {"width":"30%"} -->' . "\n"
				. '<div class="wp-block-column" style="flex-basis:30%"><!-- wp:tfpg/posts-filter /-->' . "\n\n"
				. '<!-- wp:paragraph {"fontSize":"small"} -->' . "\n"
				. '<p class="has-small-font-size">' . esc_html( $aside ) . '</p>' . "\n"
				. '<!-- /wp:paragraph --></div>' . "\n"
				. '<!-- /wp:column -->' . "\n\n"
				. '<!-- wp:column {"width":"70%"} -->' . "\n"
				. '<div class="wp-block-column" style="flex-basis:70%"><!-- wp:tfpg/posts-grid {"columns":3,"postsPerPage":6} -->' . "\n"
				. '<!-- wp:tfpg/posts-pagination /-->' . "\n"
				. '<!-- /wp:tfpg/posts-grid --></div>' . "\n"
				. '<!-- /wp:column --></div>' . "\n"
				. '<!-- /wp:columns -->',
			)
		);
	}

	/**
	 * Returns an existing term ID by slug, or creates the term.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $slug     Term slug.
	 * @param string $name     Term name.
	 * @return int|WP_Error
	 */
	private static function ensure_term( $taxonomy, $slug, $name ) {
		$existing = get_term_by( 'slug', $slug, $taxonomy );
		if ( $existing ) {
			return (int) $existing->term_id;
		}

		$result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

		return is_wp_error( $result ) ? $result : (int) $result['term_id'];
	}

	/**
	 * Returns an existing demo post ID, or creates the post.
	 *
	 * @param array $post      Post definition from demo-data.php.
	 * @param int   $author_id Author user ID.
	 * @param int   $timestamp Publish timestamp.
	 * @return int|WP_Error
	 */
	private static function ensure_post( array $post, $author_id, $timestamp ) {
		$existing = self::find_by_key( Content_Model::POST_TYPE, $post['key'] );
		if ( $existing ) {
			return $existing;
		}

		$paragraphs = array_map(
			static function ( $text ) {
				return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";
			},
			$post['content']
		);

		return wp_insert_post(
			array(
				'post_type'     => Content_Model::POST_TYPE,
				'post_status'   => 'publish',
				'post_title'    => $post['title'],
				'post_name'     => $post['key'],
				'post_excerpt'  => $post['excerpt'],
				'post_content'  => implode( "\n\n", $paragraphs ),
				'post_author'   => $author_id,
				'post_date'     => wp_date( 'Y-m-d H:i:s', $timestamp ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
				'meta_input'    => array( self::META_KEY => $post['key'] ),
			),
			true
		);
	}

	/**
	 * Returns an existing demo attachment ID, or copies the bundled image into
	 * the uploads folder and registers it in the media library.
	 *
	 * Images ship with the plugin (no network access needed) and go through
	 * the regular media pipeline so all intermediate sizes and srcset work.
	 *
	 * @param string $key     Stable key; also the image file name.
	 * @param int    $post_id Parent post ID.
	 * @param string $alt     Alternative text.
	 * @return int|WP_Error
	 */
	private static function ensure_image( $key, $post_id, $alt ) {
		$existing = self::find_by_key( 'attachment', $key );
		if ( $existing ) {
			return $existing;
		}

		$source = TFPG_DIR . 'assets/demo-images/' . sanitize_file_name( $key ) . '.jpg';
		if ( ! is_readable( $source ) ) {
			/* translators: %s: file path. */
			return new WP_Error( 'tfpg_missing_image', sprintf( __( 'Demo image not found: %s', 'tfpg-posts-grid-filter' ), $source ) );
		}

		$upload = wp_upload_bits( 'tfpg-' . basename( $source ), null, (string) file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file bundled with the plugin.
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'tfpg_upload_failed', $upload['error'] );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => get_the_title( $post_id ),
				'post_status'    => 'inherit',
				'meta_input'     => array(
					self::META_KEY             => $key,
					'_wp_attachment_image_alt' => $alt,
				),
			),
			$upload['file'],
			$post_id,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

		return (int) $attachment_id;
	}

	/**
	 * Returns the existing demo page ID, or creates the page.
	 *
	 * @param int $author_id Author user ID.
	 * @return int|WP_Error
	 */
	private static function ensure_demo_page( $author_id ) {
		$existing = self::find_by_key( 'page', self::PAGE_KEY );
		if ( $existing ) {
			return $existing;
		}

		return wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Posts Grid Demo', 'tfpg-posts-grid-filter' ),
				'post_name'    => 'posts-grid-demo',
				'post_content' => self::get_demo_page_content(),
				'post_author'  => $author_id,
				'meta_input'   => array( self::META_KEY => self::PAGE_KEY ),
			),
			true
		);
	}

	/**
	 * Finds a non-trashed demo object by its stable key.
	 *
	 * @param string $post_type Post type.
	 * @param string $key       Stable key.
	 * @return int Post ID, or 0.
	 */
	private static function find_by_key( $post_type, $key ) {
		$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'attachment' === $post_type ? 'inherit' : array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'meta_key'         => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Runs only while seeding.
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Runs only while seeding.
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'suppress_filters' => true,
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Picks the author for demo content: the current user if they can publish,
	 * otherwise the first administrator.
	 *
	 * @return int
	 */
	private static function get_author_id() {
		if ( current_user_can( 'publish_posts' ) ) {
			return get_current_user_id();
		}

		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'fields'  => 'ID',
				'orderby' => 'ID',
			)
		);

		return $admins ? (int) $admins[0] : 0;
	}
}
