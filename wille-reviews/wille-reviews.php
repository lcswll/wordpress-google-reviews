<?php
/**
 * Plugin Name:       Wille Reviews – Review Widgets for Google
 * Plugin URI:        https://github.com/lcswll/wordpress-google-reviews
 * Description:       Show your Google reviews in six layouts and five styles – grid, slider, list, wall, badge, social proof. Cached, no visitor requests to Google.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Lucas Wille
 * Author URI:        https://lucaswille.de/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wille-reviews
 * Domain Path:       /languages
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WILLEREV_VERSION', '1.0.0' );
define( 'WILLEREV_FILE', __FILE__ );
define( 'WILLEREV_DIR', plugin_dir_path( __FILE__ ) );
define( 'WILLEREV_URL', plugin_dir_url( __FILE__ ) );

require_once WILLEREV_DIR . 'includes/class-willerev-install.php';
require_once WILLEREV_DIR . 'includes/class-willerev-places.php';
require_once WILLEREV_DIR . 'includes/class-willerev-avatars.php';
require_once WILLEREV_DIR . 'includes/class-willerev-render.php';
require_once WILLEREV_DIR . 'includes/class-willerev-frontend.php';
require_once WILLEREV_DIR . 'includes/class-willerev-block.php';
require_once WILLEREV_DIR . 'includes/class-willerev-selftest.php';
require_once WILLEREV_DIR . 'includes/class-willerev-plugin.php';

if ( is_admin() ) {
	require_once WILLEREV_DIR . 'includes/admin/class-willerev-admin.php';
	require_once WILLEREV_DIR . 'includes/admin/class-willerev-review.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WILLEREV_DIR . 'includes/class-willerev-cli.php';
	WP_CLI::add_command( 'willerev', 'WILLEREV_CLI' );
}

register_activation_hook( __FILE__, array( 'WILLEREV_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WILLEREV_Install', 'deactivate' ) );

/**
 * Returns the main plugin instance.
 *
 * @return WILLEREV_Plugin
 */
function willerev() {
	return WILLEREV_Plugin::instance();
}

/**
 * Boots the plugin (plugins_loaded callback; actions discard return values).
 *
 * @return void
 */
function willerev_boot() {
	willerev();
}

add_action( 'plugins_loaded', 'willerev_boot' );
