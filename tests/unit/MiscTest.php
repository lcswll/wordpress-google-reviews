<?php
/**
 * Smaller pure helpers: block attributes, avatar file types, place IDs, settings sections, review request.
 *
 * @package Wille_Reviews
 */

namespace WILLEREV\Tests;

use WILLEREV_Admin;
use WILLEREV_Avatars;
use WILLEREV_Block;
use WILLEREV_Elementor;
use WILLEREV_Install;
use WILLEREV_Review;
use WILLEREV_Selftest;

final class MiscTest extends TestCase {

	public function test_elementor_settings_map_to_shortcode_options(): void {
		// Fresh widget: everything on "Default" – the saved design applies.
		$this->assertSame(
			array(),
			WILLEREV_Elementor::to_atts(
				array(
					'layout'     => '',
					'columns'    => '',
					'limit'      => '',
					'min_rating' => '',
					'header'     => '',
					'accent'     => '',
					'radius'     => array(
						'unit' => 'px',
						'size' => '',
					),
				)
			)
		);
		$this->assertSame(
			array(
				'layout'     => 'carousel',
				'style'      => 'dark',
				'align'      => 'center',
				'accent'     => '#f5b400',
				'columns'    => 2,
				'limit'      => 4,
				'min_rating' => 0,
				'lines'      => 0,
				'radius'     => 16,
				'header'     => 'yes',
				'cta'        => 'no',
			),
			WILLEREV_Elementor::to_atts(
				array(
					'layout'     => 'carousel',
					'style'      => 'dark',
					'align'      => 'center',
					'accent'     => '#f5b400',
					'columns'    => 2, // Numeric select keys arrive as integers or strings.
					'limit'      => '4',
					'min_rating' => '0',
					'lines'      => 0,
					'radius'     => array(
						'unit' => 'px',
						'size' => 16,
					),
					'header'     => 'yes',
					'cta'        => 'no',
					'avatars'    => 'maybe',
					'sort'       => array( 'not', 'a', 'string' ),
				)
			)
		);
		$this->assertSame( array( '', 'yes' ), array_keys( WILLEREV_Elementor::with_default( array( 'yes' => 'Show' ) ) ) );
	}

	public function test_block_attributes_map_to_shortcode_options(): void {
		$this->assertSame(
			array(),
			WILLEREV_Block::to_atts(
				array(
					'minRating' => -1,
					'columns'   => 0,
					'header'    => '',
				)
			)
		);
		$this->assertSame(
			array(
				'layout'     => 'carousel',
				'style'      => 'accent',
				'columns'    => 2,
				'min_rating' => 0,
				'cta'        => 'no',
				'align'      => 'center',
				'class'      => 'is-x alignwide',
			),
			WILLEREV_Block::to_atts(
				array(
					'layout'    => 'carousel',
					'style'     => 'accent',
					'columns'   => 2,
					'minRating' => 0,
					'cta'       => 'no',
					'avatars'   => 'maybe',
					'alignment' => 'center',
					'align'     => 'wide',
					'className' => 'is-x',
				)
			)
		);
	}

	public function test_avatar_type_from_magic_bytes(): void {
		$this->assertSame( 'jpg', WILLEREV_Avatars::image_type( "\xFF\xD8\xFF\xE0rest" ) );
		$this->assertSame( 'png', WILLEREV_Avatars::image_type( "\x89PNG\r\n\x1A\nrest" ) );
		$this->assertSame( 'gif', WILLEREV_Avatars::image_type( 'GIF89a...' ) );
		$this->assertSame( 'webp', WILLEREV_Avatars::image_type( 'RIFF1234WEBPVP8 ' ) );
		$this->assertSame( '', WILLEREV_Avatars::image_type( '<?php echo 1;' ) );
		$this->assertSame( '', WILLEREV_Avatars::image_type( '' ) );
	}

	public function test_place_id_as_pasted(): void {
		$this->assertSame( 'ChIJN1t_tDeuEmsRUsoyG83frY4', WILLEREV_Admin::sanitize_place_id( ' places/ChIJN1t_tDeuEmsRUsoyG83frY4 ' ) );
		$this->assertSame( 'ChIJabc', WILLEREV_Admin::sanitize_place_id( 'ChIJ<abc>' ) );
	}

	public function test_saving_one_page_keeps_the_other_pages_settings(): void {
		$this->options['willerev_settings'] = array_merge(
			WILLEREV_Install::defaults(),
			array(
				'api_key'  => 'KEY',
				'place_id' => 'PLACE',
				'floating' => 1,
				'layout'   => 'list',
			)
		);

		$design = WILLEREV_Admin::sanitize_settings(
			array(
				'_section' => 'design',
				'layout'   => 'social',
				'style'    => 'quote',
				'accent'   => '#FF0000',
				'columns'  => '7',
			)
		);
		$this->assertSame( 'social', $design['layout'] );
		$this->assertSame( 'quote', $design['style'] );
		$this->assertSame( '#ff0000', $design['accent'] );
		$this->assertSame( 4, $design['columns'] );
		$this->assertSame( 0, $design['show_header'], 'unchecked box on the design page' );
		$this->assertSame( 'KEY', $design['api_key'], 'connection untouched' );
		$this->assertSame( 1, $design['floating'], 'connection untouched' );
		$this->assertArrayNotHasKey( '_section', $design );

		$connection = WILLEREV_Admin::sanitize_settings(
			array(
				'_section'    => 'connection',
				'api_key'     => ' AIza-key_1 ',
				'place_id'    => 'places/ChIJx',
				'language'    => 'de-AT',
				'cache_hours' => '0',
			)
		);
		$this->assertSame( 'AIza-key_1', $connection['api_key'] );
		$this->assertSame( 'ChIJx', $connection['place_id'] );
		$this->assertSame( 'de-AT', $connection['language'] );
		$this->assertSame( 1, $connection['cache_hours'] );
		$this->assertSame( 0, $connection['floating'] );
		$this->assertSame( 'list', $connection['layout'], 'design untouched' );

		$invalid = WILLEREV_Admin::sanitize_settings(
			array(
				'_section' => 'connection',
				'language' => 'deutsch!',
			)
		);
		$this->assertSame( '', $invalid['language'] );
	}

	public function test_review_request_timing(): void {
		$now = 1790000000;
		$this->assertTrue( WILLEREV_Review::should_ask( array(), $now, $now - 14 * DAY_IN_SECONDS, true ) );
		$this->assertFalse( WILLEREV_Review::should_ask( array(), $now, $now - 13 * DAY_IN_SECONDS, true ), 'too new' );
		$this->assertFalse( WILLEREV_Review::should_ask( array(), $now, $now - 90 * DAY_IN_SECONDS, false ), 'not working yet' );
		$this->assertFalse( WILLEREV_Review::should_ask( array(), $now, 0, true ), 'unknown activation' );
		$this->assertFalse( WILLEREV_Review::should_ask( array( 'state' => 'done' ), $now, 1, true ) );
		$later = array(
			'state' => 'later',
			'until' => $now + 1,
		);
		$this->assertFalse( WILLEREV_Review::should_ask( $later, $now, 1, true ) );
		$later['until'] = $now - 1;
		$this->assertTrue( WILLEREV_Review::should_ask( $later, $now, 1, true ), 'asks again after the snooze' );
	}

	public function test_built_in_selftest_passes(): void {
		$result = WILLEREV_Selftest::run();
		$this->assertSame( array(), $result['failures'] );
		$this->assertGreaterThan( 25, $result['passed'] );
	}
}
