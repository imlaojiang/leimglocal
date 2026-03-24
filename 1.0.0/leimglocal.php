<?php
/**
 * Plugin Name:       Leimg Local
 * Plugin URI:        https://www.itbulu.com/leimglocal.html
 * Description:       将编辑器中的外部图片本地化到媒体库，支持传统编辑器与古登堡，支持粘贴截图自动上传。
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            老蒋和他的伙伴们
 * Author URI:        https://www.laojiang.me/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       leimglocal
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LEIMGLOCAL_VERSION', '1.0.0' );
define( 'LEIMGLOCAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEIMGLOCAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LEIMGLOCAL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Whether the plugin is enabled (master switch).
 *
 * @return bool
 */
function leimglocal_is_enabled() {
	return get_option( 'leimglocal_enabled', '1' ) === '1';
}

require_once LEIMGLOCAL_PLUGIN_DIR . 'includes/class-leimglocal-image-service.php';
require_once LEIMGLOCAL_PLUGIN_DIR . 'includes/class-leimglocal-ajax.php';
require_once LEIMGLOCAL_PLUGIN_DIR . 'admin/class-leimglocal-admin.php';
require_once LEIMGLOCAL_PLUGIN_DIR . 'admin/class-leimglocal-classic-editor.php';
require_once LEIMGLOCAL_PLUGIN_DIR . 'admin/class-leimglocal-gutenberg.php';

/**
 * Main plugin bootstrap.
 */
function leimglocal_init() {
	load_plugin_textdomain( 'leimglocal', false, dirname( LEIMGLOCAL_PLUGIN_BASENAME ) . '/languages' );
	LeimgLocal_Admin::get_instance();
	if ( leimglocal_is_enabled() ) {
		LeimgLocal_Classic_Editor::get_instance();
		LeimgLocal_Gutenberg::get_instance();
		LeimgLocal_Ajax::get_instance();
	}
}
add_action( 'plugins_loaded', 'leimglocal_init' );

/**
 * Activation: set default options.
 */
function leimglocal_activate() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( LEIMGLOCAL_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Leimg Local 需要 PHP 7.4 或更高版本。', 'leimglocal' ),
			esc_html__( '插件激活失败', 'leimglocal' ),
			array( 'back_link' => true )
		);
	}
	add_option( 'leimglocal_enabled', '1' );
	add_option( 'leimglocal_auto_paste', '1' );
	add_option( 'leimglocal_quality', 90 );
}
register_activation_hook( __FILE__, 'leimglocal_activate' );
