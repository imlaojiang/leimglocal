<?php
/**
 * Download external images and upload to WordPress media library.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeimgLocal_Image_Service
 */
class LeimgLocal_Image_Service {

	/**
	 * Allowed image mime types.
	 *
	 * @var array
	 */
	private static $allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

	/**
	 * Download image from URL and add to media library.
	 *
	 * @param string $url       Image URL.
	 * @param int    $post_id   Optional. Post to attach to.
	 * @param string $filename  Optional. Filename override.
	 * @return array{ success: bool, attachment_id?: int, url?: string, error?: string }
	 */
	public static function download_and_attach( $url, $post_id = 0, $filename = '' ) {
		$url = esc_url_raw( $url );
		if ( empty( $url ) ) {
			return array( 'success' => false, 'error' => __( '无效的图片地址', 'leimglocal' ) );
		}

		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return array(
				'success' => false,
				'error'   => $tmp->get_error_message(),
			);
		}

		$file_array = array(
			'name'     => $filename ? sanitize_file_name( $filename ) : basename( wp_parse_url( $url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		);

		if ( empty( $file_array['name'] ) || strpos( $file_array['name'], '.' ) === false ) {
			$file_array['name'] = 'leimglocal-' . wp_rand( 1000, 9999 ) . '.jpg';
		}

		$id = media_handle_sideload( $file_array, $post_id, null, array( 'test_form' => false ) );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			return array(
				'success' => false,
				'error'   => $id->get_error_message(),
			);
		}

		$attach_url = wp_get_attachment_url( $id );
		return array(
			'success'       => true,
			'attachment_id' => (int) $id,
			'url'           => $attach_url ? $attach_url : '',
		);
	}

	/**
	 * Upload image from base64 data (paste from clipboard / screenshot).
	 *
	 * @param string $data     Base64 data (with or without data:image/...;base64, prefix).
	 * @param int    $post_id  Optional. Post to attach to.
	 * @return array{ success: bool, attachment_id?: int, url?: string, error?: string }
	 */
	public static function upload_from_base64( $data, $post_id = 0 ) {
		if ( empty( $data ) || ! is_string( $data ) ) {
			return array( 'success' => false, 'error' => __( '无效的图片数据', 'leimglocal' ) );
		}

		if ( preg_match( '#^data:image/(\w+);base64,(.+)$#', $data, $m ) ) {
			$ext = strtolower( $m[1] );
			$raw = base64_decode( $m[2], true );
		} else {
			$raw = base64_decode( $data, true );
			$ext = 'png';
		}

		if ( $raw === false || strlen( $raw ) === 0 ) {
			return array( 'success' => false, 'error' => __( 'Base64 解码失败', 'leimglocal' ) );
		}

		$type = wp_check_filetype( 'x.' . $ext, null );
		if ( empty( $type['type'] ) || ! in_array( $type['type'], self::$allowed_mimes, true ) ) {
			$type = array( 'type' => 'image/png', 'ext' => 'png' );
		}

		$upload = wp_upload_bits( 'leimglocal-paste-' . wp_rand( 1000, 9999 ) . '.' . $type['ext'], null, $raw );
		if ( ! empty( $upload['error'] ) ) {
			return array( 'success' => false, 'error' => $upload['error'] );
		}

		$attachment = array(
			'post_mime_type' => $type['type'],
			'post_title'     => sanitize_file_name( pathinfo( $upload['file'], PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
		if ( is_wp_error( $id ) ) {
			@unlink( $upload['file'] );
			return array( 'success' => false, 'error' => $id->get_error_message() );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_generate_attachment_metadata( $id, $upload['file'] );

		$attach_url = wp_get_attachment_url( $id );
		return array(
			'success'       => true,
			'attachment_id' => (int) $id,
			'url'           => $attach_url ? $attach_url : '',
		);
	}

	/**
	 * Extract all img src URLs from HTML content.
	 *
	 * @param string $content HTML content.
	 * @return array List of image URLs (external only if $site_host provided).
	 */
	public static function extract_image_urls( $content, $site_host = null ) {
		if ( empty( $content ) ) {
			return array();
		}
		$urls = array();
		if ( preg_match_all( '#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $content, $m ) ) {
			foreach ( $m[1] as $url ) {
				$url = trim( $url );
				if ( $url === '' ) {
					continue;
				}
				if ( $site_host !== null ) {
					$parsed = wp_parse_url( $url );
					$host   = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
					if ( $host === $site_host ) {
						continue;
					}
				}
				$urls[] = $url;
			}
		}
		return array_unique( $urls );
	}

	/**
	 * Replace image URLs in content with new URLs (by mapping old_url => new_url).
	 *
	 * @param string $content HTML content.
	 * @param array  $map     Map of old_url => new_url.
	 * @return string
	 */
	public static function replace_urls_in_content( $content, $map ) {
		if ( empty( $map ) ) {
			return $content;
		}
		foreach ( $map as $old => $new ) {
			$content = str_replace( $old, $new, $content );
		}
		// Clean up image attributes after localization.
		$content = self::clean_image_attributes( $content );
		return $content;
	}

	/**
	 * Clean up image attributes, removing external website attributes.
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	public static function clean_image_attributes( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}
		// Match all img tags.
		return preg_replace_callback(
			'#<img([^>]+)>#i',
			array( __CLASS__, 'clean_single_image' ),
			$content
		);
	}

	/**
	 * Clean attributes for a single image tag.
	 *
	 * @param array $matches Matches from preg_replace_callback.
	 * @return string Cleaned img tag.
	 */
	public static function clean_single_image( $matches ) {
		$attrs = $matches[1];
		$src   = '';
		$alt   = '';

		// Extract src and alt (keep these).
		if ( preg_match( '#src=["\']([^"\']+)["\']#i', $attrs, $src_match ) ) {
			$src = $src_match[1];
		}
		if ( preg_match( '#alt=["\']([^"\']*)["\']#i', $attrs, $alt_match ) ) {
			$alt = $alt_match[1];
		}

		// Build clean img tag with only src and alt.
		$clean = '<img src="' . esc_attr( $src ) . '"';
		if ( ! empty( $alt ) ) {
			$clean .= ' alt="' . esc_attr( $alt ) . '"';
		}
		$clean .= ' />';

		return $clean;
	}

	/**
	 * Get current site host for filtering internal images.
	 *
	 * @return string
	 */
	public static function get_site_host() {
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		return $home ? strtolower( $home ) : '';
	}
}
