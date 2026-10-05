<?php
/**
 * Core plugin orchestrator.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class (singleton).
 */
class WILLEREV_Plugin {

	/**
	 * Instance.
	 *
	 * @var WILLEREV_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Cached settings.
	 *
	 * @var array<string,mixed>|null
	 */
	protected $settings = null;

	/**
	 * Singleton accessor.
	 *
	 * @return WILLEREV_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Boot all modules.
	 *
	 * @return void
	 */
	protected function boot() {
		WILLEREV_Places::init();
		WILLEREV_Frontend::init();
		WILLEREV_Block::init();

		if ( is_admin() ) {
			WILLEREV_Admin::init();
			WILLEREV_Review::init();
		}
	}

	/**
	 * Settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function settings() {
		if ( null === $this->settings ) {
			$saved          = get_option( 'willerev_settings', array() );
			$this->settings = wp_parse_args( is_array( $saved ) ? $saved : array(), WILLEREV_Install::defaults() );
		}
		return $this->settings;
	}

	/**
	 * Clear the settings cache (after save).
	 *
	 * @return void
	 */
	public function flush_settings_cache() {
		$this->settings = null;
	}
}
