<?php
/**
 * Test double for the plugin accessor willerev() (defined in wille-reviews.php, which boots every module).
 * Tests set the settings via WILLEREV_Test_Plugin::$settings.
 *
 * @package Wille_Reviews
 */

// phpcs:disable Squiz.Commenting
final class WILLEREV_Test_Plugin {
	/** @var array<string,mixed> */
	public static $settings = array();

	/** @return array<string,mixed> */
	public function settings() {
		return array_merge( WILLEREV_Install::defaults(), self::$settings );
	}

	public function flush_settings_cache(): void {
	}
}
