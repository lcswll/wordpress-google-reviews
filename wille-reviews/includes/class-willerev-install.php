<?php
/**
 * Activation, deactivation and the default settings.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install module.
 */
class WILLEREV_Install {

	/**
	 * Default settings. Design keys are also the defaults of every shortcode/block attribute.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			// Connection.
			'api_key'             => '',
			'place_id'            => '',
			'language'            => '',
			'cache_hours'         => 12,
			'original_text'       => 1,
			'hide_avatars'        => 0,
			'reviews_url'         => '',
			// Design defaults.
			'layout'              => 'grid',
			'style'               => 'light',
			'accent'              => '#1a73e8',
			'radius'              => 12,
			'columns'             => 3,
			'limit'               => 5,
			'min_rating'          => 0,
			'lines'               => 5,
			'show_header'         => 1,
			'show_avatars'        => 1,
			'show_cta'            => 1,
			// Floating badge.
			'floating'            => 0,
			'floating_position'   => 'right',
			// Data.
			'delete_on_uninstall' => 0,
		);
	}

	/**
	 * Activation (per site; network activation runs it for every site).
	 *
	 * @param bool $network_wide Whether the plugin is network-activated.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::activate_site();
				restore_current_blog();
			}
			return;
		}
		self::activate_site();
	}

	/**
	 * Activation for the current site.
	 *
	 * @return void
	 */
	protected static function activate_site() {
		add_option( 'willerev_settings', self::defaults() );
		add_option( 'willerev_activated_at', time(), '', false );
		set_transient( 'willerev_welcome_notice', 1, HOUR_IN_SECONDS );
		WILLEREV_Places::schedule();
	}

	/**
	 * Deactivation: stop the refresh cron (data stays until uninstall).
	 *
	 * @param bool $network_wide Whether the plugin is network-deactivated.
	 * @return void
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				WILLEREV_Places::unschedule();
				restore_current_blog();
			}
			return;
		}
		WILLEREV_Places::unschedule();
	}
}
