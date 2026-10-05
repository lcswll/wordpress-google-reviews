<?php
/**
 * Base test case: Brain Monkey + the WordPress helpers the plugin's pure functions use.
 *
 * @package Wille_Reviews
 */

namespace WILLEREV\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use WILLEREV_Test_Plugin;

abstract class TestCase extends PHPUnitTestCase {

	/** @var array<string,mixed> Options by name (get_option stub). */
	protected $options = array();

	/** @var bool Result of current_user_can(). */
	protected $can = false;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		WILLEREV_Test_Plugin::$settings = array();
		$this->options                  = array();
		$this->can                      = false;

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\stubs(
			array(
				'wp_json_encode'          => static function ( $data, $flags = 0 ) {
					return json_encode( $data, $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				},
				'wp_parse_url'            => static function ( $url, $component = -1 ) {
					return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				},
				'sanitize_text_field'     => static function ( $text ) {
					return trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) $text ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags, WordPressVIPMinimum.Functions.StripTags
				},
				'sanitize_textarea_field' => static function ( $text ) {
					return trim( strip_tags( (string) $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags, WordPressVIPMinimum.Functions.StripTags
				},
				'sanitize_key'            => static function ( $key ) {
					return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
				},
				'sanitize_html_class'     => static function ( $name ) {
					return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $name );
				},
				'number_format_i18n'      => static function ( $number, $decimals = 0 ) {
					return number_format( (float) $number, $decimals );
				},
				'human_time_diff'         => static function ( $from, $to ) {
					return (int) round( abs( $to - $from ) / 86400 ) . ' days';
				},
				'home_url'                => 'https://site.example',
				'admin_url'               => static function ( $path = '' ) {
					return 'https://site.example/wp-admin/' . $path;
				},
				'current_user_can'        => function () {
					return $this->can;
				},
				'get_option'              => function ( $name, $fallback = false ) {
					return $this->options[ $name ] ?? $fallback;
				},
				'wp_parse_args'           => static function ( $args, $defaults = array() ) {
					return array_merge( $defaults, (array) $args );
				},
				'absint'                  => static function ( $value ) {
					return abs( (int) $value );
				},
				'wp_kses'                 => static function ( $html ) {
					return $html;
				},
				'wp_enqueue_style'        => null,
				'wp_enqueue_script'       => null,
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * A normalized review.
	 *
	 * @param array<string,mixed> $overrides Fields to change.
	 * @return array<string,mixed>
	 */
	protected function review( array $overrides = array() ) {
		return array_merge(
			array(
				'id'         => 'r1',
				'author'     => 'Anna',
				'author_url' => 'https://www.google.com/maps/contrib/1',
				'avatar'     => '',
				'rating'     => 5,
				'text'       => 'Great!',
				'time'       => 1790000000,
				'relative'   => 'a week ago',
			),
			$overrides
		);
	}

	/**
	 * Place data with the given reviews.
	 *
	 * @param array<int,array<string,mixed>> $reviews Reviews.
	 * @return array<string,mixed>
	 */
	protected function place( array $reviews ) {
		return array(
			'name'       => 'Test GmbH',
			'rating'     => 4.8,
			'count'      => 127,
			'url'        => 'https://maps.google.com/?cid=1',
			'write_url'  => 'https://search.google.com/local/writereview?placeid=x',
			'reviews'    => $reviews,
			'fetched_at' => 1790000000,
		);
	}
}
