<?php
/**
 * Frontend lightbox integration.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeimgLocal_Frontend
 */
class LeimgLocal_Frontend {

	/**
	 * Singleton.
	 *
	 * @var LeimgLocal_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return LeimgLocal_Frontend
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets for lightbox.
	 */
	public function enqueue_assets() {
		if ( get_option( 'leimglocal_lightbox_enabled', '0' ) !== '1' ) {
			return;
		}

		wp_enqueue_style(
			'leimglocal-lightbox',
			LEIMGLOCAL_PLUGIN_URL . 'assets/css/lightbox.css',
			array(),
			LEIMGLOCAL_VERSION
		);

		wp_enqueue_script(
			'leimglocal-lightbox',
			LEIMGLOCAL_PLUGIN_URL . 'assets/js/lightbox.js',
			array(),
			LEIMGLOCAL_VERSION,
			true
		);

		wp_localize_script(
			'leimglocal-lightbox',
			'leimglocalLightbox',
			array(
				'min_size'          => (int) get_option( 'leimglocal_lightbox_min_size', 800 ),
				'post_content_only' => get_option( 'leimglocal_lightbox_post_content_only', '1' ) === '1',
				'show_icon'         => get_option( 'leimglocal_lightbox_show_icon', '0' ) === '1',
				'close_text'        => __( '关闭预览', 'leimglocal' ),
			)
		);
	}
}
