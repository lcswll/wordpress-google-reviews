<?php
/**
 * Elementor integration: a "Google Reviews" widget with the options of the shortcode, so nobody has to copy
 * shortcodes into Elementor pages. Loaded only when Elementor is active (the widget class extends Elementor's).
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor module.
 */
class WILLEREV_Elementor {

	/**
	 * Widget name (Elementor's internal ID).
	 */
	const WIDGET = 'willerev-reviews';

	/**
	 * Hook up (the hook only fires when Elementor is active).
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the widget.
	 *
	 * @param object $widgets_manager Elementor\Widgets_Manager.
	 * @return void
	 */
	public static function register( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) || ! method_exists( $widgets_manager, 'register' ) ) {
			return;
		}
		require_once WILLEREV_DIR . 'includes/elementor/class-willerev-elementor-widget.php';
		$widgets_manager->register( new WILLEREV_Elementor_Widget() );
	}

	/**
	 * Select options with an empty "design page default" entry first.
	 *
	 * @param array<int|string,string> $options Value → label (PHP turns numeric keys into integers).
	 * @return array<int|string,string>
	 */
	public static function with_default( array $options ) {
		return array( '' => __( 'Default', 'wille-reviews' ) ) + $options;
	}

	/**
	 * Widget settings → shortcode attributes; empty values fall back to the saved design (pure).
	 *
	 * @param array<string,mixed> $settings Elementor widget settings.
	 * @return array<string,mixed>
	 */
	public static function to_atts( array $settings ) {
		$atts = array();
		foreach ( array( 'layout', 'style', 'sort', 'link', 'align' ) as $key ) {
			if ( isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				$atts[ $key ] = $settings[ $key ];
			}
		}
		foreach ( array( 'accent', 'count_color' ) as $key ) {
			if ( isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				$atts[ $key ] = $settings[ $key ];
			}
		}
		foreach ( array( 'columns', 'limit', 'min_rating', 'lines' ) as $key ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] && is_numeric( $settings[ $key ] ) ) {
				$atts[ $key ] = (int) $settings[ $key ];
			}
		}
		// Slider control: unit "px" plus a size, which is empty while unset.
		if ( isset( $settings['radius']['size'] ) && '' !== $settings['radius']['size'] && is_numeric( $settings['radius']['size'] ) ) {
			$atts['radius'] = (int) $settings['radius']['size'];
		}
		foreach ( array( 'header', 'avatars', 'cta' ) as $key ) {
			if ( isset( $settings[ $key ] ) && in_array( $settings[ $key ], array( 'yes', 'no' ), true ) ) {
				$atts[ $key ] = $settings[ $key ];
			}
		}
		return $atts;
	}

	/**
	 * Widget HTML. In the Elementor editor the styles show with sample data until a place is connected.
	 *
	 * @param array<string,mixed> $settings Elementor widget settings.
	 * @param bool                $editor   Rendered in the Elementor editor.
	 * @return string
	 */
	public static function render( array $settings, $editor = false ) {
		$args = WILLEREV_Render::args( self::to_atts( $settings ), willerev()->settings() );
		$data = WILLEREV_Places::data();
		if ( null === $data && $editor ) {
			return WILLEREV_Render::render( $args, WILLEREV_Render::demo_data(), true );
		}
		return WILLEREV_Render::render( $args, $data );
	}
}
