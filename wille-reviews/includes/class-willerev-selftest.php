<?php
/**
 * Built-in self-test: assertions over the plugin's pure functions.
 *
 * Runs via `wp willerev selftest` and in the integration test, so the same checks can gate a deploy and be run
 * on a live site.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Self-test runner.
 */
class WILLEREV_Selftest {

	/**
	 * Run all assertions.
	 *
	 * @return array{passed:int,failures:string[]}
	 */
	public static function run() {
		$failures = array();
		$passed   = 0;

		$check = static function ( $label, $actual, $expected ) use ( &$failures, &$passed ) {
			if ( $actual === $expected ) {
				++$passed;
			} else {
				$failures[] = sprintf( '%s: expected %s, got %s', $label, wp_json_encode( $expected ), wp_json_encode( $actual ) );
			}
		};

		// Language for the API.
		$check( 'language from locale', WILLEREV_Places::language_code( '', 'de_DE' ), 'de' );
		$check( 'language from setting', WILLEREV_Places::language_code( 'en-GB', 'de_DE' ), 'en' );
		$check( 'language fallback', WILLEREV_Places::language_code( '', '' ), 'en' );

		// API response normalization.
		$data = WILLEREV_Places::normalize(
			array(
				'displayName'     => array( 'text' => 'Café <b>Test</b>' ),
				'rating'          => 4.86,
				'userRatingCount' => 213,
				'googleMapsUri'   => 'https://maps.google.com/?cid=1',
				'reviews'         => array(
					array(
						'name'              => 'places/x/reviews/old',
						'rating'            => 4,
						'publishTime'       => '2026-01-02T10:00:00Z',
						'text'              => array( 'text' => 'Translated' ),
						'originalText'      => array( 'text' => 'Original' ),
						'authorAttribution' => array(
							'displayName' => 'Old',
							'photoUri'    => 'https://example.org/photo-x',
						),
					),
					array(
						'name'        => 'places/x/reviews/new',
						'rating'      => 9,
						'publishTime' => '2026-03-02T10:00:00Z',
					),
					'garbage',
				),
			),
			'ChIJabc',
			true
		);
		$check( 'normalize name stripped', $data['name'], 'Café Test' );
		$check( 'normalize rating rounded', $data['rating'], 4.9 );
		$check( 'normalize count', $data['count'], 213 );
		$check( 'normalize skips garbage', count( $data['reviews'] ), 2 );
		$check( 'normalize newest first', $data['reviews'][0]['author'], '' );
		$check( 'normalize rating clamped', $data['reviews'][0]['rating'], 5 );
		$check( 'normalize original text', $data['reviews'][1]['text'], 'Original' );
		$check( 'normalize write url', $data['write_url'], 'https://search.google.com/local/writereview?placeid=ChIJabc' );
		$translated = WILLEREV_Places::normalize(
			array(
				'reviews' => array(
					array(
						'text'         => array( 'text' => 'T' ),
						'originalText' => array( 'text' => 'O' ),
					),
				),
			),
			'x',
			false
		);
		$check( 'normalize translated text', $translated['reviews'][0]['text'], 'T' );
		$check(
			'error message',
			WILLEREV_Places::error_message(
				403,
				array(
					'error' => array(
						'status'  => 'PERMISSION_DENIED',
						'message' => 'API not enabled',
					),
				)
			),
			'HTTP 403 PERMISSION_DENIED: API not enabled'
		);

		// Selection.
		$reviews = array(
			array(
				'rating' => 3,
				'time'   => 30,
			),
			array(
				'rating' => 5,
				'time'   => 10,
			),
			array(
				'rating' => 4,
				'time'   => 20,
			),
		);
		$check( 'select min rating', count( WILLEREV_Render::select( $reviews, 4, 10, 'newest' ) ), 2 );
		$check( 'select newest', WILLEREV_Render::select( $reviews, 0, 1, 'newest' )[0]['time'], 30 );
		$check( 'select best', WILLEREV_Render::select( $reviews, 0, 1, 'rating' )[0]['rating'], 5 );

		// Arguments.
		$args = WILLEREV_Render::args(
			array(
				'layout' => 'carousel',
				'style'  => 'nope',
				'limit'  => '99',
				'header' => 'no',
				'accent' => 'red',
			),
			WILLEREV_Install::defaults()
		);
		$check( 'args layout', $args['layout'], 'carousel' );
		$check( 'args invalid style falls back', $args['style'], 'light' );
		$check( 'args limit clamped', $args['limit'], WILLEREV_Render::MAX_LIMIT );
		$check( 'args header off', $args['header'], false );
		$check( 'args invalid accent', $args['accent'], '#1a73e8' );
		$check( 'ink on light accent', WILLEREV_Render::ink_on( '#ffeb3b' ), '#1f2328' );
		$check( 'ink on dark accent', WILLEREV_Render::ink_on( '#1a73e8' ), '#ffffff' );

		// Block attributes.
		$check(
			'block attributes',
			WILLEREV_Block::to_atts(
				array(
					'layout'    => 'badge',
					'minRating' => -1,
					'header'    => '',
					'avatars'   => 'no',
				)
			),
			array(
				'layout'  => 'badge',
				'avatars' => 'no',
			)
		);

		// Avatar file types.
		$check( 'image type png', WILLEREV_Avatars::image_type( "\x89PNG\r\n\x1A\nxxxx" ), 'png' );
		$check( 'image type rejects html', WILLEREV_Avatars::image_type( '<svg onload=alert(1)>' ), '' );

		return array(
			'passed'   => $passed,
			'failures' => $failures,
		);
	}
}
