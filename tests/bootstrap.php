<?php
/**
 * PHPUnit bootstrap: plugin classes without WordPress (Brain Monkey stubs the functions).
 *
 * @package Wille_Reviews
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'ABSPATH', __DIR__ . '/fixtures/wordpress/' );
define( 'WILLEREV_VERSION', '0.0.0-test' );
define( 'WILLEREV_FILE', dirname( __DIR__ ) . '/wille-reviews/wille-reviews.php' );
define( 'WILLEREV_DIR', dirname( __DIR__ ) . '/wille-reviews/' );
define( 'WILLEREV_URL', 'https://example.org/wp-content/plugins/wille-reviews/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );

// Plugin classes are autoloaded via composer.json "autoload-dev" (classmap of wille-reviews/includes/).
require __DIR__ . '/stubs/polyfills.php';
require __DIR__ . '/stubs/class-willerev-test-plugin.php';
require __DIR__ . '/stubs/functions.php';
