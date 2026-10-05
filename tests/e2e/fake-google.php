<?php
/**
 * Plugin Name: Fake Google (e2e)
 * Description: Answers the Places API (New) and the profile photo server inside Playground, so the runtime tests
 * exercise the real request → normalize → cache → mirror pipeline without network access or an API key.
 *
 * Copied to wp-content/mu-plugins/ by the blueprints. Key "TEST-KEY" gets a place, any other key a 403 like
 * Google's. Every API call is counted in the option e2e_google_calls.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 *
 * @package Wille_Reviews
 */

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );

		if ( 'places.googleapis.com' === $host ) {
			update_option( 'e2e_google_calls', (int) get_option( 'e2e_google_calls', 0 ) + 1, false );
			update_option(
				'e2e_google_last',
				array(
					'url'     => $url,
					'headers' => $args['headers'],
				),
				false
			);
			if ( 'TEST-KEY' !== ( $args['headers']['X-Goog-Api-Key'] ?? '' ) ) {
				return array(
					'headers'  => array( 'content-type' => 'application/json' ),
					'body'     => wp_json_encode(
						array(
							'error' => array(
								'code'    => 403,
								'message' => 'API key not valid. Please pass a valid API key.',
								'status'  => 'PERMISSION_DENIED',
							),
						)
					),
					'response' => array(
						'code'    => 403,
						'message' => 'Forbidden',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
			$day     = DAY_IN_SECONDS;
			$reviews = array(
				array( 'Anna Becker', 5, 2, 'Wunderbarer Kaffee und der beste Käsekuchen der Stadt. Das Team ist herzlich und nimmt sich Zeit – wir kommen jeden Samstag!', true ),
				array( 'Jonas Weber', 5, 6, 'Schnell, freundlich, faire Preise.', true ),
				array( 'Mira Schulz', 4, 15, 'Sehr gemütlich, nur am Wochenende oft voll. Die Zimtschnecken sind ein Traum, der Service war trotz des Andrangs aufmerksam und freundlich. Parkplätze gibt es in der Seitenstraße, ansonsten ist man zu Fuß vom Bahnhof in fünf Minuten da. Für Familien mit Kinderwagen ist genug Platz, und es gibt sogar eine kleine Spielecke. Wir haben uns rundum wohlgefühlt und kommen definitiv wieder – nächstes Mal zum Frühstück!', false ),
				array( 'Lukas Hoffmann', 2, 40, 'Leider lange gewartet.', false ),
				array( 'Sophie Wagner', 5, 70, '', true ),
			);
			$items   = array();
			foreach ( $reviews as $index => $review ) {
				$items[] = array(
					'name'                           => 'places/ChIJtest/reviews/r' . $index,
					'relativePublishTimeDescription' => 'vor ' . $review[2] . ' Tagen',
					'rating'                         => $review[1],
					'text'                           => array( 'text' => '' !== $review[3] ? '[translated] ' . $review[3] : '' ),
					'originalText'                   => array( 'text' => $review[3] ),
					'authorAttribution'              => array(
						'displayName' => $review[0],
						'uri'         => 'https://www.google.com/maps/contrib/' . ( 1000 + $index ),
						'photoUri'    => $review[4] ? 'https://lh3.googleusercontent.com/a/e2e-photo-' . $index . '=s128-c0x00000000-cc-rp-mo' : '',
					),
					'publishTime'                    => gmdate( 'Y-m-d\TH:i:s\Z', time() - $review[2] * $day ),
				);
			}
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode(
					array(
						'id'              => 'ChIJtest',
						'displayName'     => array(
							'text'         => 'Café Sonnenschein',
							'languageCode' => 'de',
						),
						'rating'          => 4.7,
						'userRatingCount' => 312,
						'googleMapsUri'   => 'https://maps.google.com/?cid=4242',
						'reviews'         => $items,
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		if ( 'lh3.googleusercontent.com' === $host ) {
			// Drawn test portraits (tests/e2e/fixtures/avatar-*.png), one per photo URL.
			$index = preg_match( '/e2e-photo-(\d+)/', $url, $m ) ? (int) $m[1] % 3 : 0;
			return array(
				'headers'  => array( 'content-type' => 'image/png' ),
				'body'     => (string) file_get_contents( '/e2e/fixtures/avatar-' . $index . '.png' ),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		return $pre;
	},
	10,
	3
);
