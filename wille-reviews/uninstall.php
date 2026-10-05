<?php
/**
 * Uninstall: the cron event always goes; settings, cached reviews and mirrored photos only when the user opted in.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove data for the current site.
 *
 * @return void
 */
function willerev_uninstall_site() {
	wp_clear_scheduled_hook( 'willerev_refresh' );

	$settings = get_option( 'willerev_settings', array() );
	if ( empty( $settings['delete_on_uninstall'] ) ) {
		return;
	}

	delete_option( 'willerev_settings' );
	delete_option( 'willerev_backup' );
	delete_option( 'willerev_status' );
	delete_option( 'willerev_activated_at' );
	delete_option( 'willerev_review' );
	delete_transient( 'willerev_data' );
	delete_transient( 'willerev_refresh_lock' );
	delete_transient( 'willerev_welcome_notice' );

	// Mirrored profile photos (uploads/wille-reviews/).
	$uploads = wp_upload_dir( null, false );
	$dir     = trailingslashit( $uploads['basedir'] ) . 'wille-reviews';
	$files   = glob( $dir . '/*' );
	foreach ( is_array( $files ) ? $files : array() as $file ) {
		if ( is_file( $file ) && preg_match( '/^[a-f0-9]{32}\.(jpg|png|gif|webp)$/', basename( $file ) ) ) {
			wp_delete_file( $file );
		}
	}
	global $wp_filesystem;
	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ( WP_Filesystem() && $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->is_dir( $dir ) ) {
		$wp_filesystem->rmdir( $dir );
	}
}

if ( is_multisite() ) {
	$willerev_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $willerev_site_ids as $willerev_site_id ) {
		switch_to_blog( (int) $willerev_site_id );
		willerev_uninstall_site();
		restore_current_blog();
	}
} else {
	willerev_uninstall_site();
}
