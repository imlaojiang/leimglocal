<?php
/**
 * Gutenberg (Block Editor): toolbar button + paste image auto-upload.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeimgLocal_Gutenberg
 */
class LeimgLocal_Gutenberg {

	/**
	 * Singleton.
	 *
	 * @var LeimgLocal_Gutenberg
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return LeimgLocal_Gutenberg
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_assets() {
		$post_id = 0;
		if ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
			$post_id = (int) $GLOBALS['post']->ID;
		}

		wp_enqueue_script(
			'leimglocal-gutenberg',
			LEIMGLOCAL_PLUGIN_URL . 'admin/js/gutenberg.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-i18n', 'wp-blocks' ),
			LEIMGLOCAL_VERSION,
			true
		);
		wp_localize_script( 'leimglocal-gutenberg', 'leimglocalGutenberg', LeimgLocal_Admin::get_script_data( $post_id ) );
	}
}
