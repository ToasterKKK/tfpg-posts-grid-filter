<?php
/**
 * Uninstall routine: removes the demo content and plugin options.
 *
 * Posts that editors created themselves in the Grid Posts post type are kept.
 *
 * @package TFPG
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

defined( 'TFPG_DIR' ) || define( 'TFPG_DIR', plugin_dir_path( __FILE__ ) );

require_once TFPG_DIR . 'includes/class-content-model.php';
require_once TFPG_DIR . 'includes/class-demo-content.php';

/**
 * Cleans up a single site.
 */
function tfpg_uninstall_site() {
	// Taxonomies must be registered for wp_delete_term() to work.
	TFPG\Content_Model::register();
	TFPG\Demo_Content::cleanup();
	delete_transient( 'tfpg_activation_notice' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $tfpg_site_id ) {
		switch_to_blog( $tfpg_site_id );
		tfpg_uninstall_site();
		restore_current_blog();
	}
} else {
	tfpg_uninstall_site();
}
