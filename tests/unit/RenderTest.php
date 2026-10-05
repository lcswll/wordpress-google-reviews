<?php
/**
 * Renderer: argument normalization, review selection, colors, escaping and every layout × style.
 *
 * @package Wille_Reviews
 */

namespace WILLEREV\Tests;

use WILLEREV_Install;
use WILLEREV_Render;
use WILLEREV_Test_Plugin;

final class RenderTest extends TestCase {

	public function test_args_fall_back_to_saved_defaults(): void {
		$defaults = array_merge(
			WILLEREV_Install::defaults(),
			array(
				'layout' => 'masonry',
				'style'  => 'dark',
				'limit'  => 3,
			)
		);
		$args     = WILLEREV_Render::args( array(), $defaults );

		$this->assertSame( 'masonry', $args['layout'] );
		$this->assertSame( 'dark', $args['style'] );
		$this->assertSame( 3, $args['limit'] );
		$this->assertTrue( $args['header'] );
		$this->assertSame( 'auto', $args['link'] );
	}

	public function test_args_validate_every_value(): void {
		$args = WILLEREV_Render::args(
			array(
				'layout'     => 'slideshow',
				'style'      => 'DARK',
				'limit'      => '-4',
				'columns'    => '9',
				'min_rating' => '7',
				'radius'     => '100',
				'accent'     => 'javascript:alert(1)',
				'header'     => 'no',
				'avatars'    => 'off',
				'cta'        => 'true',
				'sort'       => 'random',
				'align'      => 'middle',
				'id'         => 'my reviews"',
				'class'      => 'a b"<x>',
			),
			WILLEREV_Install::defaults()
		);

		$this->assertSame( 'grid', $args['layout'] );
		$this->assertSame( 'dark', $args['style'] );
		$this->assertSame( 1, $args['limit'] );
		$this->assertSame( 4, $args['columns'] );
		$this->assertSame( 5, $args['min_rating'] );
		$this->assertSame( 32, $args['radius'] );
		$this->assertSame( '#1a73e8', $args['accent'] );
		$this->assertFalse( $args['header'] );
		$this->assertFalse( $args['avatars'] );
		$this->assertTrue( $args['cta'] );
		$this->assertSame( 'newest', $args['sort'] );
		$this->assertSame( 'left', $args['align'] );
		$this->assertSame( 'myreviews', $args['id'] );
		$this->assertSame( 'a bx', $args['class'] );
	}

	public function test_hidden_avatars_setting_wins_over_attribute(): void {
		$defaults = array_merge( WILLEREV_Install::defaults(), array( 'hide_avatars' => 1 ) );
		$this->assertFalse( WILLEREV_Render::args( array( 'avatars' => 'yes' ), $defaults )['avatars'] );
	}

	public function test_select_filters_sorts_and_limits(): void {
		$reviews = array(
			$this->review(
				array(
					'rating' => 3,
					'time'   => 300,
				)
			),
			$this->review(
				array(
					'rating' => 5,
					'time'   => 100,
				)
			),
			$this->review(
				array(
					'rating' => 4,
					'time'   => 200,
				)
			),
			$this->review(
				array(
					'rating' => 5,
					'time'   => 50,
				)
			),
		);

		$this->assertSame( array( 300, 200, 100 ), array_column( WILLEREV_Render::select( $reviews, 0, 3, 'newest' ), 'time' ) );
		$this->assertSame( array( 100, 50, 200 ), array_column( WILLEREV_Render::select( $reviews, 4, 5, 'rating' ), 'time' ) );
		$this->assertSame( array(), WILLEREV_Render::select( array(), 0, 5, 'newest' ) );
	}

	public function test_colors(): void {
		$this->assertSame( '#abc', WILLEREV_Render::hex_color( ' #ABC ', '#000' ) );
		$this->assertSame( '#000', WILLEREV_Render::hex_color( 'red', '#000' ) );
		$this->assertSame( '#000', WILLEREV_Render::hex_color( '#12345', '#000' ) );
		$this->assertSame( '#ffffff', WILLEREV_Render::ink_on( '#000000' ) );
		$this->assertSame( '#1f2328', WILLEREV_Render::ink_on( '#fff' ) );
		$this->assertSame( '#1f2328', WILLEREV_Render::ink_on( '#fbbc04' ), 'yellow needs dark text' );
		$this->assertSame( '#ffffff', WILLEREV_Render::ink_on( '#1a73e8' ) );
	}

	public function test_stars_are_accessible_and_partially_filled(): void {
		$html = WILLEREV_Render::stars( 4.3 );
		$this->assertStringContainsString( 'role="img"', $html );
		$this->assertStringContainsString( 'aria-label="4.3 out of 5 stars"', $html );
		$this->assertStringContainsString( 'width:86%', $html );
		$this->assertSame( 10, substr_count( $html, '<svg' ) );
		$this->assertStringContainsString( 'width:100%', WILLEREV_Render::stars( 7 ) );
	}

	public function test_card_escapes_review_content(): void {
		$html = WILLEREV_Render::card(
			$this->review(
				array(
					'author'     => '<img src=x onerror=alert(1)>',
					'author_url' => 'javascript:alert(1)',
					'text'       => "Line one\n<script>alert(1)</script>",
				)
			)
		);
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
		$this->assertStringContainsString( '<br />', $html );
		$this->assertStringContainsString( 'data-rating="5"', $html );
	}

	public function test_card_without_photo_shows_initial_and_without_name_a_fallback(): void {
		$html = WILLEREV_Render::card( $this->review( array( 'author' => 'Élise' ) ) );
		$this->assertStringContainsString( 'willerev-avatar--initial', $html );
		$this->assertStringContainsString( '>É<', $html );
		$this->assertStringContainsString( 'Google user', WILLEREV_Render::card( $this->review( array( 'author' => '' ) ) ) );
	}

	public function test_every_layout_and_style_renders(): void {
		$data = $this->place(
			array(
				$this->review(),
				$this->review(
					array(
						'id'     => 'r2',
						'rating' => 2,
						'author' => 'Ben',
					)
				),
			)
		);
		foreach ( WILLEREV_Render::LAYOUTS as $layout ) {
			foreach ( WILLEREV_Render::STYLES as $style ) {
				$args = WILLEREV_Render::args(
					array(
						'layout' => $layout,
						'style'  => $style,
					),
					WILLEREV_Install::defaults()
				);
				$html = WILLEREV_Render::render( $args, $data );
				$this->assertStringContainsString( 'willerev--layout-' . $layout, $html, "$layout/$style" );
				$this->assertStringContainsString( 'willerev--style-' . $style, $html, "$layout/$style" );
				$this->assertStringContainsString( '--willerev-accent:#1a73e8', $html );
			}
		}
	}

	public function test_section_respects_minimum_rating_and_shows_slider_controls(): void {
		$data = $this->place(
			array(
				$this->review(),
				$this->review(
					array(
						'rating' => 2,
						'author' => 'Ben',
						'time'   => 1790000001,
					)
				),
			)
		);
		$args = WILLEREV_Render::args(
			array(
				'layout'     => 'carousel',
				'min_rating' => 4,
			),
			WILLEREV_Install::defaults()
		);
		$html = WILLEREV_Render::render( $args, $data );
		$this->assertStringContainsString( 'Anna', $html );
		$this->assertStringNotContainsString( 'Ben', $html );
		$this->assertStringNotContainsString( 'willerev__nav', $html, 'one card left: no arrows' );

		$args['min_rating'] = 0;
		$this->assertStringContainsString( 'willerev__nav--next', WILLEREV_Render::render( $args, $data ) );
	}

	public function test_badge_links_to_reviews_page_or_google(): void {
		$data = $this->place( array() );
		$this->assertSame( 'https://maps.google.com/?cid=1', WILLEREV_Render::link_target( 'auto', $data ) );

		WILLEREV_Test_Plugin::$settings = array( 'reviews_url' => 'https://site.example/reviews/' );
		$this->assertSame( 'https://site.example/reviews/#google-reviews', WILLEREV_Render::link_target( 'auto', $data ) );
		$this->assertSame( 'https://maps.google.com/?cid=1', WILLEREV_Render::link_target( 'google', $data ) );
		$this->assertSame( '', WILLEREV_Render::link_target( 'none', $data ) );

		$html = WILLEREV_Render::render( WILLEREV_Render::args( array( 'layout' => 'badge' ), WILLEREV_Install::defaults() ), $data );
		$this->assertStringContainsString( 'href="https://site.example/reviews/#google-reviews"', $html );
		$this->assertStringNotContainsString( 'target="_blank"', $html, 'own page opens in the same tab' );
		$this->assertStringContainsString( 'aria-label="Rated 4.8 out of 5 on Google, 127 reviews"', $html );
	}

	public function test_no_data_shows_a_hint_to_admins_only(): void {
		$args = WILLEREV_Render::args( array(), WILLEREV_Install::defaults() );

		$this->assertSame( '', WILLEREV_Render::render( $args ), 'not connected' );
		$this->can = true;
		$this->assertStringContainsString( 'willerev-notice', WILLEREV_Render::render( $args ) );
	}

	public function test_demo_data_has_every_field(): void {
		$demo = WILLEREV_Render::demo_data();
		$this->assertCount( 5, $demo['reviews'] );
		foreach ( $demo['reviews'] as $review ) {
			$this->assertSame( array( 'id', 'author', 'author_url', 'avatar', 'rating', 'text', 'time', 'relative' ), array_keys( $review ) );
		}
		$html = WILLEREV_Render::render( WILLEREV_Render::args( array(), WILLEREV_Install::defaults() ), $demo, true );
		$this->assertStringContainsString( 'Sample data', $html );
	}
}
