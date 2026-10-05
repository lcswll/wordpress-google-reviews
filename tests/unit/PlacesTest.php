<?php
/**
 * Places API (New): response normalization, language, error messages.
 *
 * @package Wille_Reviews
 */

namespace WILLEREV\Tests;

use WILLEREV_Places;

final class PlacesTest extends TestCase {

	/** @return array<string,mixed> A Place Details response as the API sends it. */
	private function response(): array {
		return array(
			'id'              => 'ChIJabc',
			'displayName'     => array(
				'text'         => 'Bäckerei Müller',
				'languageCode' => 'de',
			),
			'rating'          => 4.66,
			'userRatingCount' => 1234,
			'googleMapsUri'   => 'https://maps.google.com/?cid=123',
			'reviews'         => array(
				array(
					'name'                           => 'places/ChIJabc/reviews/a',
					'relativePublishTimeDescription' => 'vor 3 Monaten',
					'rating'                         => 4,
					'text'                           => array( 'text' => 'Very good bread' ),
					'originalText'                   => array( 'text' => 'Sehr gutes Brot' ),
					'authorAttribution'              => array(
						'displayName' => 'Anna <script>x</script>',
						'uri'         => 'https://www.google.com/maps/contrib/1',
						'photoUri'    => 'https://lh3.googleusercontent.com/a/photo=s128',
					),
					'publishTime'                    => '2026-06-01T08:00:00.123456Z',
				),
				array(
					'name'        => 'places/ChIJabc/reviews/b',
					'rating'      => 5,
					'publishTime' => '2026-09-01T08:00:00Z',
				),
			),
		);
	}

	public function test_normalizes_place_and_reviews(): void {
		$data = WILLEREV_Places::normalize( $this->response(), 'ChIJabc', true );

		$this->assertSame( 'Bäckerei Müller', $data['name'] );
		$this->assertSame( 4.7, $data['rating'] );
		$this->assertSame( 1234, $data['count'] );
		$this->assertSame( 'https://maps.google.com/?cid=123', $data['url'] );
		$this->assertSame( 'https://search.google.com/local/writereview?placeid=ChIJabc', $data['write_url'] );
		$this->assertCount( 2, $data['reviews'] );
	}

	public function test_newest_review_first_and_fields_sanitized(): void {
		$data   = WILLEREV_Places::normalize( $this->response(), 'ChIJabc', true );
		$newest = $data['reviews'][0];
		$older  = $data['reviews'][1];

		$this->assertSame( 5, $newest['rating'] );
		$this->assertSame( '', $newest['text'], 'a review without text stays empty' );
		$this->assertSame( 'Anna x', $older['author'], 'tags are stripped from names' );
		$this->assertSame( 'https://lh3.googleusercontent.com/a/photo=s128', $older['avatar_remote'] );
		$this->assertSame( '', $older['avatar'], 'the local copy is filled in by refresh()' );
		$this->assertSame( strtotime( '2026-06-01T08:00:00Z' ), $older['time'] );
		$this->assertSame( 12, strlen( $older['id'] ) );
	}

	public function test_original_or_translated_text(): void {
		$original   = WILLEREV_Places::normalize( $this->response(), 'x', true );
		$translated = WILLEREV_Places::normalize( $this->response(), 'x', false );

		$this->assertSame( 'Sehr gutes Brot', $original['reviews'][1]['text'] );
		$this->assertSame( 'Very good bread', $translated['reviews'][1]['text'] );
	}

	public function test_survives_empty_and_broken_responses(): void {
		$data = WILLEREV_Places::normalize(
			array(
				'rating'  => 9,
				'reviews' => array( 'nope', null, array( 'rating' => -3 ) ),
			),
			'x'
		);
		$this->assertSame( 5.0, $data['rating'] );
		$this->assertSame( 0, $data['count'] );
		$this->assertSame( '', $data['name'] );
		$this->assertCount( 1, $data['reviews'] );
		$this->assertSame( 0, $data['reviews'][0]['rating'] );
		$this->assertSame( 0, $data['reviews'][0]['time'] );
	}

	public function test_language_code(): void {
		$this->assertSame( 'de', WILLEREV_Places::language_code( '', 'de_DE_formal' ) );
		$this->assertSame( 'pt', WILLEREV_Places::language_code( 'pt-BR', 'de_DE' ) );
		$this->assertSame( 'en', WILLEREV_Places::language_code( '', '' ) );
		$this->assertSame( 'de', WILLEREV_Places::language_code( '<de>', 'en_US' ) );
	}

	public function test_error_message(): void {
		$this->assertSame(
			'HTTP 403 PERMISSION_DENIED: Places API (New) has not been used in project 1',
			WILLEREV_Places::error_message(
				403,
				array(
					'error' => array(
						'code'    => 403,
						'status'  => 'PERMISSION_DENIED',
						'message' => 'Places API (New) has not been used in project 1',
					),
				)
			)
		);
		$this->assertSame( 'HTTP 502', WILLEREV_Places::error_message( 502, null ) );
	}

	public function test_error_message_appends_reason(): void {
		$this->assertSame(
			'HTTP 403 PERMISSION_DENIED: Requests from referer are blocked. (API_KEY_HTTP_REFERRER_BLOCKED)',
			WILLEREV_Places::error_message(
				403,
				array(
					'error' => array(
						'status'  => 'PERMISSION_DENIED',
						'message' => 'Requests from referer <empty> are blocked.',
						'details' => array( array( 'reason' => 'API_KEY_HTTP_REFERRER_BLOCKED' ) ),
					),
				)
			)
		);
	}

	public function test_hint(): void {
		$cases = array(
			'HTTP 403 PERMISSION_DENIED: Requests from referer <empty> are blocked. (API_KEY_HTTP_REFERRER_BLOCKED)' => 'HTTP referrers',
			'HTTP 403 PERMISSION_DENIED: Places API (New) has not been used in project 1 before or it is disabled.' => 'not enabled',
			'HTTP 403 PERMISSION_DENIED: This API method requires billing to be enabled. (BILLING_DISABLED)' => 'billing account',
			'HTTP 400 INVALID_ARGUMENT: API key not valid. Please pass a valid API key. (API_KEY_INVALID)' => 'does not accept the API key',
			'HTTP 403 PERMISSION_DENIED: This API key is not authorized to use this service or API.' => 'API restrictions',
			'HTTP 400 INVALID_ARGUMENT: Not a valid Place ID: foo' => 'cannot find this Place ID',
			'HTTP 404 NOT_FOUND' => 'cannot find this Place ID',
			'cURL error 28: Operation timed out after 12001 milliseconds' => 'could not reach Google',
		);
		foreach ( $cases as $message => $expected ) {
			$this->assertStringContainsString( $expected, WILLEREV_Places::hint( $message ), $message );
		}
		$this->assertSame( '', WILLEREV_Places::hint( 'HTTP 500 INTERNAL' ) );
	}
}
