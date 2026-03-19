<?php
/**
 * Classic Editor: toolbar button + paste image auto-upload.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeimgLocal_Classic_Editor
 */
class LeimgLocal_Classic_Editor {

	/**
	 * Singleton.
	 *
	 * @var LeimgLocal_Classic_Editor
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return LeimgLocal_Classic_Editor
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'media_buttons', array( $this, 'render_button' ), 11 );
	}

	/**
	 * Enqueue scripts for post edit screen (classic editor).
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$post_id = 0;
		if ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
			$post_id = (int) $GLOBALS['post']->ID;
		}
		wp_enqueue_script(
			'leimglocal-classic',
			LEIMGLOCAL_PLUGIN_URL . 'admin/js/classic-editor.js',
			array( 'jquery' ),
			LEIMGLOCAL_VERSION,
			true
		);
		wp_localize_script( 'leimglocal-classic', 'leimglocalClassic', LeimgLocal_Admin::get_script_data( $post_id ) );
	}

	/**
	 * Output localize button next to "Add Media" button (for classic only; Gutenberg has its own UI).
	 */
	public function render_button() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'post' ) {
			return;
		}
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		// Only show when using classic editor (no block editor for this post).
		if ( function_exists( 'use_block_editor_for_post' ) && isset( $GLOBALS['post'] ) && use_block_editor_for_post( $GLOBALS['post'] ) ) {
			return;
		}
		?>
		<button type="button" class="button" id="leimglocal-localize-btn" style="margin-left: 8px;">
			<span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 4px;"></span>
			<?php esc_html_e( '本地化图片', 'leimglocal' ); ?>
		</button>
		<span class="leimglocal-status" style="margin-left: 8px; color: #2271b1;"></span>
		<?php
	}
}
