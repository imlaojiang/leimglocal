<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package LeimgLocal
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'leimglocal_enabled' );
delete_option( 'leimglocal_auto_paste' );
delete_option( 'leimglocal_quality' );
