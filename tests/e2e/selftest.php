<?php
/**
 * Integration self-test inside a real WordPress (Playground): activation, the built-in self-test, the full
 * Places pipeline against the fake Google (tests/e2e/fake-google.php) – request headers, cache, backup, photo
 * mirror, error fallback and backoff, 30-day limit –, every layout through shortcode and block, the floating
 * badge, settings sections, deactivation and uninstall.
 *
 * Writes /e2e-out/selftest.json (scripts/e2e.mjs reads it and fails on any failed assertion).
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.WP.AlternativeFunctions, WordPress.Security.NonceVerification
 *
 * @package Wille_Reviews
 */

require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
// The settings sanitizer is registered on admin_init (options.php); load it so update_option() runs it here, too.
require_once WILLEREV_DIR . 'includes/admin/class-willerev-admin.php';
WILLEREV_Admin::register_settings();

$results = array();

/**
 * Records one assertion.
 *
 * @param bool   $ok
 * @param string $name
 * @param mixed  $detail
 */
function check( $ok, $name, $detail = null ) {
	global $results;
	$results[] = array(
		'ok'     => (bool) $ok,
		'name'   => $name,
		'detail' => $ok ? null : $detail,
	);
}

/** Number of requests the fake Places API answered. */
function google_calls() {
	wp_cache_delete( 'e2e_google_calls', 'options' );
	return (int) get_option( 'e2e_google_calls', 0 );
}

/** Save settings the way a full programmatic update does (every key validated). */
function save_settings( array $changes ) {
	update_option( 'willerev_settings', array_merge( willerev()->settings(), $changes ) );
	willerev()->flush_settings_cache();
}

try {
	$plugin = 'wille-reviews/wille-reviews.php';
	$admin  = get_user_by( 'login', 'admin' );

	// ---------------------------------------------------------------- activation.
	check( is_plugin_active( $plugin ), 'plugin is active' );
	check( is_array( get_option( 'willerev_settings' ) ), 'default settings stored' );
	check( (int) get_option( 'willerev_activated_at' ) > 0, 'activation time stored' );
	check( false === wp_next_scheduled( WILLEREV_Places::CRON ), 'no cron while not connected' );
	check( shortcode_exists( 'wille_reviews' ), 'shortcode registered' );
	check( WP_Block_Type_Registry::get_instance()->is_registered( 'wille-reviews/reviews' ), 'block registered' );

	// --------------------------------------------------------- built-in self-test.
	$self = WILLEREV_Selftest::run();
	check( array() === $self['failures'], "built-in self-test ({$self['passed']} assertions)", $self['failures'] );

	// ------------------------------------------------------------ not connected.
	wp_set_current_user( 0 );
	check( '' === do_shortcode( '[wille_reviews]' ), 'visitors see nothing while not connected' );
	wp_set_current_user( $admin->ID );
	check( false !== strpos( do_shortcode( '[wille_reviews]' ), 'willerev-notice' ), 'admins see a setup hint' );
	check( 0 === google_calls(), 'no request to Google without key and place' );

	// --------------------------------------------------------------- connecting.
	save_settings(
		array(
			'api_key'  => 'TEST-KEY',
			'place_id' => 'places/ChIJtest',
			'language' => '',
		)
	);
	check( 'ChIJtest' === willerev()->settings()['place_id'], 'place ID "places/…" prefix is stripped on save', willerev()->settings()['place_id'] );
	check( false !== wp_next_scheduled( WILLEREV_Places::CRON ), 'cron scheduled once connected' );

	$data = WILLEREV_Places::data();
	check( is_array( $data ) && 'Café Sonnenschein' === $data['name'], 'place loaded', $data );
	check( 4.7 === $data['rating'] && 312 === $data['count'], 'rating and count', array( $data['rating'], $data['count'] ) );
	check( 5 === count( $data['reviews'] ), 'five reviews', count( $data['reviews'] ) );
	check( 'Anna Becker' === $data['reviews'][0]['author'], 'newest review first', $data['reviews'][0]['author'] );
	check( 0 === strpos( $data['reviews'][0]['text'], 'Wunderbarer' ), 'original (untranslated) text by default', $data['reviews'][0]['text'] );
	check( 1 === google_calls(), 'exactly one API request', google_calls() );

	$last = get_option( 'e2e_google_last' );
	check( false !== strpos( $last['url'], 'places.googleapis.com/v1/places/ChIJtest' ), 'Place Details (New) endpoint', $last['url'] );
	check( false !== strpos( $last['url'], 'languageCode=en' ), 'site language used when none is set', $last['url'] );
	check( 'TEST-KEY' === $last['headers']['X-Goog-Api-Key'], 'key sent as header, not in the URL', $last['headers'] );
	check( false === strpos( $last['url'], 'TEST-KEY' ), 'key not in the URL' );
	check( WILLEREV_Places::FIELDS === $last['headers']['X-Goog-FieldMask'], 'field mask limits the billed fields' );

	// Photo mirror.
	$dir    = WILLEREV_Avatars::paths()['dir'];
	$photos = glob( $dir . '/*.png' );
	check( 3 === count( (array) $photos ), 'three profile photos mirrored', $photos );
	$avatar = (string) $data['reviews'][0]['avatar'];
	check( 0 === strpos( $avatar, WILLEREV_Avatars::paths()['url'] ), 'avatar served from uploads', $avatar );
	check( ! isset( $data['reviews'][0]['avatar_remote'] ), 'remote photo URL not stored' );
	check( '' === (string) $data['reviews'][3]['avatar'], 'review without photo keeps an empty avatar' );

	// Cache.
	$again = WILLEREV_Places::data();
	check( $again === $data && 1 === google_calls(), 'second read served from cache', google_calls() );
	check( is_array( get_option( WILLEREV_Places::BACKUP ) ), 'backup stored' );
	$status = WILLEREV_Places::status();
	check( null !== $status && 'ok' === $status['state'], 'status ok', $status );

	// ---------------------------------------------------------------- rendering.
	wp_set_current_user( 0 );
	foreach ( WILLEREV_Render::LAYOUTS as $layout ) {
		foreach ( WILLEREV_Render::STYLES as $style ) {
			$html = do_shortcode( sprintf( '[wille_reviews layout="%s" style="%s"]', $layout, $style ) );
			if ( false === strpos( $html, 'willerev--layout-' . $layout ) || false === strpos( $html, 'willerev--style-' . $style ) ) {
				check( false, "shortcode {$layout}/{$style}", substr( $html, 0, 300 ) );
			}
		}
	}
	check( true, 'shortcode renders all 30 layout × style combinations' );

	$grid = do_shortcode( '[wille_reviews]' );
	check( false !== strpos( $grid, 'Anna Becker' ) && false !== strpos( $grid, 'Based on 312 reviews' ), 'grid shows reviews and summary' );
	check( false === strpos( $grid, 'googleusercontent.com' ), 'no image is loaded from Google' );
	check( false !== strpos( $grid, 'search.google.com/local/writereview?placeid=ChIJtest' ), 'write-a-review link' );
	check( wp_style_is( 'willerev', 'enqueued' ) && wp_script_is( 'willerev', 'enqueued' ), 'assets enqueued with a widget' );

	$filtered = do_shortcode( '[wille_reviews min_rating="4" limit="2" header="no"]' );
	check( 2 === substr_count( $filtered, 'class="willerev-card"' ), 'limit applied', substr_count( $filtered, 'class="willerev-card"' ) );
	check( false === strpos( $filtered, 'Lukas Hoffmann' ) && false === strpos( $filtered, 'willerev__header' ), 'minimum rating and header switch' );

	$block = do_blocks( '<!-- wp:wille-reviews/reviews {"layout":"badge","style":"dark","align":"wide"} /-->' );
	check( false !== strpos( $block, 'willerev--layout-badge' ) && false !== strpos( $block, 'willerev--style-dark' ) && false !== strpos( $block, 'alignwide' ), 'block renders with its attributes', substr( $block, 0, 300 ) );

	// Floating badge.
	ob_start();
	WILLEREV_Frontend::floating_badge();
	check( '' === ob_get_clean(), 'floating badge off by default' );
	save_settings( array( 'floating' => 1 ) );
	ob_start();
	WILLEREV_Frontend::floating_badge();
	$floating = (string) ob_get_clean();
	check( false !== strpos( $floating, 'willerev--floating-right' ) && false !== strpos( $floating, 'willerev-badge__close' ), 'floating badge when enabled', $floating );

	// ------------------------------------------------------------------- errors.
	$calls = google_calls();
	save_settings( array( 'api_key' => 'BAD-KEY' ) );
	check( false === get_transient( WILLEREV_Places::CACHE ), 'key change clears the cache' );
	$fallback = WILLEREV_Places::data();
	check( is_array( $fallback ) && 312 === $fallback['count'], 'API error: last good data is used', $fallback );
	$status = WILLEREV_Places::status();
	check( 'error' === $status['state'] && false !== strpos( $status['message'], 'PERMISSION_DENIED' ), 'error recorded with Google\'s message', $status );
	WILLEREV_Places::data();
	WILLEREV_Places::data();
	check( google_calls() === $calls + 1, 'backoff: no retry storm after an error', google_calls() - $calls );

	$backup               = get_option( WILLEREV_Places::BACKUP );
	$backup['fetched_at'] = time() - 31 * DAY_IN_SECONDS;
	update_option( WILLEREV_Places::BACKUP, $backup, false );
	check( null === WILLEREV_Places::backup(), 'backup older than 30 days is not shown (Google caching terms)' );

	// ------------------------------------------------------ privacy, place change.
	delete_transient( WILLEREV_Places::LOCK );
	save_settings(
		array(
			'api_key'      => 'TEST-KEY',
			'hide_avatars' => 1,
		)
	);
	$plain = WILLEREV_Places::data();
	check( is_array( $plain ) && '' === (string) $plain['reviews'][0]['avatar'], 'photos off: nothing downloaded', $plain['reviews'][0] ?? null );
	check( array() === (array) glob( $dir . '/*.png' ), 'photos off: mirrored files removed' );
	check( false === strpos( do_shortcode( '[wille_reviews avatars="yes"]' ), '<img' ), 'photos off wins over the shortcode option' );

	save_settings( array( 'place_id' => 'ChIJother' ) );
	check( false === get_option( WILLEREV_Places::BACKUP ), 'another place: the previous place\'s backup is dropped' );

	// ------------------------------------------------------- settings sections.
	$design = WILLEREV_Admin::sanitize_settings(
		array(
			'_section' => 'design',
			'layout'   => 'carousel',
			'style'    => 'bubble',
		)
	);
	check( 'carousel' === $design['layout'] && 'TEST-KEY' === $design['api_key'], 'saving the design keeps the connection', $design );

	// ---------------------------------------------- deactivation, uninstall.
	deactivate_plugins( $plugin );
	check( false === wp_next_scheduled( WILLEREV_Places::CRON ), 'deactivation clears cron' );
	check( is_array( get_option( 'willerev_settings' ) ), 'deactivation keeps the settings' );

	update_option( 'willerev_settings', array_merge( get_option( 'willerev_settings' ), array( 'delete_on_uninstall' => 1 ) ) );
	uninstall_plugin( $plugin );
	global $wpdb;
	$leftover = $wpdb->get_col( $wpdb->prepare( 'SELECT option_name FROM %i WHERE option_name LIKE %s', $wpdb->options, '%' . $wpdb->esc_like( 'willerev' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	check( array() === $leftover, 'uninstall leaves no options behind (opt-in)', $leftover );
	check( ! is_dir( $dir ), 'uninstall removes the photo folder' );
} catch ( Throwable $e ) {
	check( false, 'uncaught ' . get_class( $e ), $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
}

$failed = count( array_filter( $results, static fn( $r ) => ! $r['ok'] ) );
file_put_contents(
	'/e2e-out/selftest.json',
	wp_json_encode(
		array(
			'php'     => PHP_VERSION,
			'wp'      => get_bloginfo( 'version' ),
			'passed'  => count( $results ) - $failed,
			'failed'  => $failed,
			'results' => $results,
		),
		JSON_PRETTY_PRINT
	)
);
