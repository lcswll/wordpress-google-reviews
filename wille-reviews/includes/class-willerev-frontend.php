<?php
/**
 * Front end: the [wille_reviews] shortcode and the optional floating badge.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end module.
 */
class WILLEREV_Frontend {

	/**
	 * Shortcode tag.
	 */
	const SHORTCODE = 'wille_reviews';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( 'WILLEREV_Render', 'register_assets' ) );
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_footer', array( __CLASS__, 'floating_badge' ) );
	}

	/**
	 * [wille_reviews layout="grid|carousel|list|masonry|badge|social" style="light|dark|minimal|quote|accent" …].
	 *
	 * @param array<string,mixed>|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		return WILLEREV_Render::render( WILLEREV_Render::args( $atts, willerev()->settings() ) );
	}

	/**
	 * Floating rating badge in a corner of every front-end page (opt-in).
	 *
	 * @return void
	 */
	public static function floating_badge() {
		$settings = willerev()->settings();
		if ( empty( $settings['floating'] ) || is_admin() || is_feed() || is_embed() ) {
			return;
		}
		/**
		 * Filters whether the floating badge shows on the current page.
		 *
		 * @param bool $show Default true.
		 */
		if ( ! apply_filters( 'willerev_show_floating_badge', true ) ) {
			return;
		}
		$data = WILLEREV_Places::data();
		if ( null === $data || (int) $data['count'] <= 0 ) {
			return;
		}
		wp_enqueue_style( 'willerev' );
		wp_enqueue_script( 'willerev' );
		$args = WILLEREV_Render::args( array( 'layout' => 'badge' ), $settings );
		$side = 'left' === $settings['floating_position'] ? 'left' : 'right';
		echo WILLEREV_Render::badge( $args, $data, array( 'willerev--floating', 'willerev--floating-' . $side ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in badge().
	}
}
