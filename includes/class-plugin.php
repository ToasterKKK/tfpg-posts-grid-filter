<?php
/**
 * Plugin bootstrap, activation and admin integration.
 *
 * @package TFPG
 */

namespace TFPG;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin together.
 */
final class Plugin {

	/**
	 * Transient that tells the next admin screen to show the "demo ready" notice.
	 */
	const NOTICE_TRANSIENT = 'tfpg_activation_notice';

	/**
	 * Registers runtime hooks.
	 */
	public static function init() {
		Content_Model::init();
		Blocks::init();

		add_action( 'admin_notices', array( __CLASS__, 'render_activation_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TFPG_FILE ), array( __CLASS__, 'add_action_links' ) );
	}

	/**
	 * Activation: register the content model and seed the demo content.
	 *
	 * @param bool $network_wide Whether the plugin is being network-activated.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 100,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_site();
				// Rewrite rules are rebuilt lazily on each site's next request.
				delete_option( 'rewrite_rules' );
				restore_current_blog();
			}
			return;
		}

		self::activate_site();
		flush_rewrite_rules();
	}

	/**
	 * Seeds a single site.
	 */
	private static function activate_site() {
		// `init` has already fired during activation, so register directly.
		Content_Model::register();

		$result = Demo_Content::seed();

		set_transient( self::NOTICE_TRANSIENT, $result, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Deactivation: drop the post type's rewrite rules. Content is kept; it is
	 * only removed when the plugin is deleted (see uninstall.php).
	 */
	public static function deactivate() {
		unregister_post_type( Content_Model::POST_TYPE );
		flush_rewrite_rules();
	}

	/**
	 * Shows a one-time notice linking to the demo page after activation.
	 */
	public static function render_activation_notice() {
		$result = get_transient( self::NOTICE_TRANSIENT );

		if ( false === $result || ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		delete_transient( self::NOTICE_TRANSIENT );

		$page_id = Demo_Content::get_demo_page_id();
		$errors  = isset( $result['errors'] ) ? (array) $result['errors'] : array();
		?>
		<div class="notice notice-<?php echo esc_attr( $errors ? 'warning' : 'success' ); ?> is-dismissible">
			<p>
				<strong><?php esc_html_e( 'TFPG Posts Grid & Filter is ready.', 'tfpg-posts-grid-filter' ); ?></strong>
				<?php
				printf(
					/* translators: 1: number of demo posts, 2: number of terms. */
					esc_html__( 'Demo content is in place: %1$d posts and %2$d categories/tags.', 'tfpg-posts-grid-filter' ),
					(int) ( $result['posts'] ?? 0 ),
					(int) ( $result['terms'] ?? 0 )
				);
				?>
				<?php if ( $page_id ) : ?>
					<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php esc_html_e( 'View the demo page', 'tfpg-posts-grid-filter' ); ?></a>
					|
					<a href="<?php echo esc_url( (string) get_edit_post_link( $page_id ) ); ?>"><?php esc_html_e( 'Edit it in the block editor', 'tfpg-posts-grid-filter' ); ?></a>
				<?php endif; ?>
			</p>
			<?php foreach ( $errors as $error ) : ?>
				<p><?php echo esc_html( $error ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Adds a "Demo page" shortcut to the plugin's row on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public static function add_action_links( $links ) {
		$page_id = Demo_Content::get_demo_page_id();

		if ( $page_id ) {
			array_unshift(
				$links,
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_permalink( $page_id ) ),
					esc_html__( 'Demo page', 'tfpg-posts-grid-filter' )
				)
			);
		}

		return $links;
	}
}
