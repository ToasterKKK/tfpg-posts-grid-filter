<?php
/**
 * Plugin Name:       TFPG Posts Grid & Filter
 * Description:       Two companion Gutenberg blocks — a dynamic Posts Grid (with an inner Pagination block) and a Posts Filter — that stay in sync anywhere on the page. Seeds demo content on activation.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Tomer
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tfpg-posts-grid-filter
 *
 * @package TFPG
 */

namespace TFPG;

defined( 'ABSPATH' ) || exit;

define( 'TFPG_VERSION', '1.0.0' );
define( 'TFPG_FILE', __FILE__ );
define( 'TFPG_DIR', plugin_dir_path( __FILE__ ) );
define( 'TFPG_URL', plugin_dir_url( __FILE__ ) );

require_once TFPG_DIR . 'includes/class-content-model.php';
require_once TFPG_DIR . 'includes/class-query.php';
require_once TFPG_DIR . 'includes/class-blocks.php';
require_once TFPG_DIR . 'includes/class-demo-content.php';
require_once TFPG_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

Plugin::init();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once TFPG_DIR . 'includes/class-cli-command.php';
	\WP_CLI::add_command( 'tfpg', CLI_Command::class );
}
