<?php
/**
 * WP-CLI: wp willerev status|refresh|flush|selftest.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage the Google reviews from the command line.
 */
class WILLEREV_CLI {

	/**
	 * Show the connection status and the cached rating.
	 *
	 * ## EXAMPLES
	 *
	 *     wp willerev status
	 *
	 * @return void
	 */
	public function status() {
		if ( ! WILLEREV_Places::is_configured() ) {
			WP_CLI::warning( 'Not connected: API key or place ID missing.' );
			return;
		}
		$status = WILLEREV_Places::status();
		if ( null !== $status ) {
			WP_CLI::log( sprintf( 'Last request: %s – %s (%s)', $status['state'], $status['message'], gmdate( 'Y-m-d H:i', $status['time'] ) . ' UTC' ) );
		}
		$data = WILLEREV_Places::backup();
		if ( null === $data ) {
			WP_CLI::warning( 'No reviews cached.' );
			return;
		}
		WP_CLI::log( sprintf( '%s: %s/5 from %d reviews, %d cached', $data['name'], $data['rating'], $data['count'], count( (array) $data['reviews'] ) ) );
	}

	/**
	 * Load the reviews from Google now.
	 *
	 * ## EXAMPLES
	 *
	 *     wp willerev refresh
	 *
	 * @return void
	 */
	public function refresh() {
		if ( ! WILLEREV_Places::is_configured() ) {
			WP_CLI::error( 'Not connected: API key or place ID missing.' );
			return;
		}
		delete_transient( WILLEREV_Places::LOCK );
		$data = WILLEREV_Places::refresh();
		if ( null === $data ) {
			$status = WILLEREV_Places::status();
			WP_CLI::error( null !== $status ? $status['message'] : 'Request failed.' );
			return;
		}
		WP_CLI::success( sprintf( '%s: %s/5 from %d reviews.', $data['name'], $data['rating'], $data['count'] ) );
	}

	/**
	 * Clear the cache, the backup and the mirrored profile photos.
	 *
	 * ## EXAMPLES
	 *
	 *     wp willerev flush
	 *
	 * @return void
	 */
	public function flush() {
		WILLEREV_Places::flush();
		WP_CLI::success( 'Cache cleared.' );
	}

	/**
	 * Run the built-in self-test (exit code 1 on failure).
	 *
	 * ## EXAMPLES
	 *
	 *     wp willerev selftest
	 *
	 * @return void
	 */
	public function selftest() {
		$result = WILLEREV_Selftest::run();
		foreach ( $result['failures'] as $failure ) {
			WP_CLI::warning( $failure );
		}
		if ( ! empty( $result['failures'] ) ) {
			WP_CLI::error( sprintf( '%d passed, %d failed.', $result['passed'], count( $result['failures'] ) ) );
			return;
		}
		WP_CLI::success( sprintf( '%d assertions passed.', $result['passed'] ) );
	}
}
