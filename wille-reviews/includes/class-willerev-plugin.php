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
		add_action( 'init', array( __CLASS__, 'load_textdomain' ), 0 );
		WILLEREV_Places::init();
		WILLEREV_Frontend::init();
		WILLEREV_Block::init();
		WILLEREV_Elementor::init();

		if ( is_admin() ) {
			WILLEREV_Admin::init();
			WILLEREV_Review::init();
		}
	}

	/**
	 * Bundled translations (languages/wille-reviews-{locale}.l10n.php) as a fallback: a language pack from
	 * translate.wordpress.org (wp-content/languages/plugins) takes precedence and is loaded by WordPress itself.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		$locale = determine_locale();
		if ( file_exists( WP_LANG_DIR . "/plugins/wille-reviews-{$locale}.mo" ) || file_exists( WP_LANG_DIR . "/plugins/wille-reviews-{$locale}.l10n.php" ) ) {
			return;
		}
		// WordPress 6.5+ prefers the .l10n.php variant of this path.
		load_textdomain( 'wille-reviews', WILLEREV_DIR . "languages/wille-reviews-{$locale}.mo", $locale );
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
