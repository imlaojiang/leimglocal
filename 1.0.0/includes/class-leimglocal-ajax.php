<?php
/**
 * AJAX handlers for image localize and paste upload.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeimgLocal_Ajax
 */
class LeimgLocal_Ajax {

	/**
	 * Singleton.
	 *
	 * @var LeimgLocal_Ajax
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return LeimgLocal_Ajax
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
		add_action( 'wp_ajax_leimglocal_localize', array( $this, 'ajax_localize' ) );
		add_action( 'wp_ajax_leimglocal_paste_upload', array( $this, 'ajax_paste_upload' ) );
	}

	/**
	 * AJAX: Localize images in content (replace external URLs with local).
	 */
	public function ajax_localize() {
		check_ajax_referer( 'leimglocal_localize', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'leimglocal' ) ) );
		}

		$content = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( $content === '' ) {
			wp_send_json_error( array( 'message' => __( '内容为空', 'leimglocal' ) ) );
		}

		$site_host = LeimgLocal_Image_Service::get_site_host();
		$urls      = LeimgLocal_Image_Service::extract_image_urls( $content, $site_host );
		if ( empty( $urls ) ) {
			wp_send_json_success( array(
				'message' => __( '未发现需要本地化的外部图片', 'leimglocal' ),
				'content' => $content,
			) );
		}

		$map = array();
		foreach ( $urls as $url ) {
			$result = LeimgLocal_Image_Service::download_and_attach( $url, $post_id );
			if ( ! empty( $result['success'] ) && ! empty( $result['url'] ) ) {
				$map[ $url ] = $result['url'];
			}
		}

		$new_content = LeimgLocal_Image_Service::replace_urls_in_content( $content, $map );
		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of images */
				_n( '已本地化 %d 张图片', '已本地化 %d 张图片', count( $map ), 'leimglocal' ),
				count( $map )
			),
			'content' => $new_content,
			'count'   => count( $map ),
		) );
	}

	/**
	 * AJAX: Upload pasted image (base64).
	 */
	public function ajax_paste_upload() {
		check_ajax_referer( 'leimglocal_paste', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( '权限不足', 'leimglocal' ) ) );
		}

		$data    = isset( $_POST['data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['data'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( $data === '' ) {
			wp_send_json_error( array( 'message' => __( '未收到图片数据', 'leimglocal' ) ) );
		}

		$result = LeimgLocal_Image_Service::upload_from_base64( $data, $post_id );
		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( array(
				'url'           => $result['url'],
				'attachment_id' => $result['attachment_id'],
			) );
		}
		wp_send_json_error( array( 'message' => isset( $result['error'] ) ? $result['error'] : __( '上传失败', 'leimglocal' ) ) );
	}
}
