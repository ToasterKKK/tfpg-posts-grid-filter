<?php
/**
 * WP-CLI commands.
 *
 * @package TFPG
 */

namespace TFPG;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the TFPG demo content.
 *
 * ## EXAMPLES
 *
 *     wp tfpg seed
 *     wp tfpg reset
 *     wp tfpg cleanup
 */
final class CLI_Command {

	/**
	 * Creates any missing demo content (safe to run repeatedly).
	 *
	 * @when after_wp_load
	 */
	public function seed() {
		$result = Demo_Content::seed();
		$this->report( $result );
	}

	/**
	 * Deletes all demo content and seeds it again from scratch.
	 *
	 * @when after_wp_load
	 */
	public function reset() {
		$deleted = Demo_Content::cleanup();
		WP_CLI::log( sprintf( 'Deleted %d demo objects.', $deleted ) );

		$this->report( Demo_Content::seed() );
	}

	/**
	 * Deletes all demo content (posts, images, terms and the demo page).
	 *
	 * @when after_wp_load
	 */
	public function cleanup() {
		$deleted = Demo_Content::cleanup();
		WP_CLI::success( sprintf( 'Deleted %d demo objects.', $deleted ) );
	}

	/**
	 * Prints a seeding summary.
	 *
	 * @param array $result Result of Demo_Content::seed().
	 */
	private function report( array $result ) {
		foreach ( $result['errors'] as $error ) {
			WP_CLI::warning( $error );
		}

		WP_CLI::success(
			sprintf(
				'Demo content ready: %d posts, %d terms. Demo page: %s',
				$result['posts'],
				$result['terms'],
				$result['page_id'] ? get_permalink( $result['page_id'] ) : 'n/a'
			)
		);
	}
}
