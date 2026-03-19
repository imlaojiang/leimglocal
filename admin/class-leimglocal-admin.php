<?php
/**
 * Admin hooks: settings, scripts, settings page.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeimgLocal_Admin
 */
class LeimgLocal_Admin {

	/**
	 * Singleton.
	 *
	 * @var LeimgLocal_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return LeimgLocal_Admin
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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . LEIMGLOCAL_PLUGIN_BASENAME, array( $this, 'plugin_links' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_menu() {
		add_options_page(
			__( '图片本地化', 'leimglocal' ),
			__( '图片本地化', 'leimglocal' ),
			'manage_options',
			'leimglocal',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'leimglocal_settings',
			'leimglocal_enabled',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					return $v === '1' ? '1' : '0';
				},
			)
		);
		register_setting(
			'leimglocal_settings',
			'leimglocal_auto_paste',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					return $v === '1' ? '1' : '0';
				},
			)
		);
		register_setting(
			'leimglocal_settings',
			'leimglocal_quality',
			array(
				'type'              => 'integer',
				'sanitize_callback' => function ( $v ) {
					$v = absint( $v );
					return $v >= 1 && $v <= 100 ? $v : 90;
				},
			)
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled    = get_option( 'leimglocal_enabled', '1' );
		$auto_paste = get_option( 'leimglocal_auto_paste', '1' );
		$quality    = get_option( 'leimglocal_quality', 90 );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>插件发布页：<a href="https://www.itbulu.com/leimglocal.html" target="_blank">查看这里</a>，公众号：老蒋朋友圈。</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'leimglocal_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( '启用插件', 'leimglocal' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="leimglocal_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
								<?php esc_html_e( '开启后，编辑器中的「本地化图片」及粘贴上传功能才会生效', 'leimglocal' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '粘贴时自动上传', 'leimglocal' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="leimglocal_auto_paste" value="1" <?php checked( $auto_paste, '1' ); ?> />
								<?php esc_html_e( '在编辑器中粘贴截图或图片时自动上传到媒体库并插入', 'leimglocal' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '图片质量', 'leimglocal' ); ?></th>
						<td>
							<input type="number" name="leimglocal_quality" value="<?php echo esc_attr( $quality ); ?>" min="1" max="100" />
							<p class="description"><?php esc_html_e( '本地化时 JPEG 压缩质量 (1-100)，仅作预留，当前版本使用原图。', 'leimglocal' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add settings link on plugins list.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function plugin_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=leimglocal' ) ) . '">' . esc_html__( '设置', 'leimglocal' ) . '</a>';
		return $links;
	}

	/**
	 * Get script localization data for admin.
	 *
	 * @param int $post_id Current post ID.
	 * @return array
	 */
	public static function get_script_data( $post_id = 0 ) {
		return array(
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'leimglocal_localize' ),
			'paste_nonce' => wp_create_nonce( 'leimglocal_paste' ),
			'post_id'     => (int) $post_id,
			'enabled'     => leimglocal_is_enabled(),
			'auto_paste'  => get_option( 'leimglocal_auto_paste', '1' ) === '1',
			'i18n'        => array(
				'button'        => __( '本地化图片', 'leimglocal' ),
				'processing'     => __( '正在处理…', 'leimglocal' ),
				'done'          => __( '完成', 'leimglocal' ),
				'error'         => __( '操作失败', 'leimglocal' ),
				'no_images'     => __( '未发现需要本地化的外部图片', 'leimglocal' ),
			),
		);
	}
}
