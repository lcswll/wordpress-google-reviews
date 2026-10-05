<?php
/**
 * Block "Google Reviews" (wille-reviews/reviews): the same widget as the shortcode, with layout and style
 * pickers in the block sidebar and a server-side preview. No build step: the editor script is plain JS.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block module.
 */
class WILLEREV_Block {

	/**
	 * Block name.
	 */
	const NAME = 'wille-reviews/reviews';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	/**
	 * Register the editor script and the block type.
	 *
	 * @return void
	 */
	public static function register() {
		wp_register_script(
			'willerev-block',
			WILLEREV_URL . 'assets/js/willerev-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
			WILLEREV_VERSION,
			true
		);

		$layouts = array();
		foreach ( WILLEREV_Render::layouts() as $slug => $layout ) {
			$layouts[] = array(
				'value' => $slug,
				'label' => $layout['label'],
			);
		}
		$styles = array();
		foreach ( WILLEREV_Render::styles() as $slug => $label ) {
			$styles[] = array(
				'value' => $slug,
				'label' => $label,
			);
		}
		$settings = willerev()->settings();
		wp_localize_script(
			'willerev-block',
			'willerevBlock',
			array(
				'name'     => self::NAME,
				'layouts'  => $layouts,
				'styles'   => $styles,
				'defaults' => array(
					'layout'  => (string) $settings['layout'],
					'style'   => (string) $settings['style'],
					'limit'   => (int) $settings['limit'],
					'columns' => (int) $settings['columns'],
					'header'  => ! empty( $settings['show_header'] ),
					'avatars' => ! empty( $settings['show_avatars'] ),
					'cta'     => ! empty( $settings['show_cta'] ),
					'accent'  => (string) $settings['accent'],
				),
				'i18n'     => array(
					'title'       => __( 'Google Reviews', 'wille-reviews' ),
					'description' => __( 'Your Google rating and latest reviews in the layout and style of your choice.', 'wille-reviews' ),
					'design'      => __( 'Design', 'wille-reviews' ),
					'content'     => __( 'Content', 'wille-reviews' ),
					'layout'      => __( 'Layout', 'wille-reviews' ),
					'style'       => __( 'Style', 'wille-reviews' ),
					'accent'      => __( 'Accent color', 'wille-reviews' ),
					'columns'     => __( 'Columns', 'wille-reviews' ),
					'limit'       => __( 'Number of reviews', 'wille-reviews' ),
					'minRating'   => __( 'Minimum stars', 'wille-reviews' ),
					'all'         => __( 'All reviews', 'wille-reviews' ),
					'sort'        => __( 'Order', 'wille-reviews' ),
					'newest'      => __( 'Newest first', 'wille-reviews' ),
					'best'        => __( 'Best first', 'wille-reviews' ),
					'header'      => __( 'Show summary header', 'wille-reviews' ),
					'avatars'     => __( 'Show profile photos', 'wille-reviews' ),
					'cta'         => __( 'Show buttons to Google', 'wille-reviews' ),
					'alignment'   => __( 'Alignment', 'wille-reviews' ),
					'left'        => __( 'Left', 'wille-reviews' ),
					'center'      => __( 'Center', 'wille-reviews' ),
					'right'       => __( 'Right', 'wille-reviews' ),
					'defaultHint' => __( 'Unset options follow the defaults on the Wille Reviews design page.', 'wille-reviews' ),
				),
			)
		);

		register_block_type(
			self::NAME,
			array(
				'title'           => __( 'Google Reviews', 'wille-reviews' ),
				'category'        => 'widgets',
				'icon'            => 'star-filled',
				'keywords'        => array( 'google', 'reviews', 'rating', 'testimonials', 'stars' ),
				'editor_script'   => 'willerev-block',
				'style'           => 'willerev',
				'supports'        => array(
					'html'  => false,
					'align' => array( 'wide', 'full' ),
				),
				'attributes'      => array(
					'layout'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'style'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'accent'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'columns'   => array(
						'type'    => 'number',
						'default' => 0,
					),
					'limit'     => array(
						'type'    => 'number',
						'default' => 0,
					),
					'minRating' => array(
						'type'    => 'number',
						'default' => -1,
					),
					'sort'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'header'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'avatars'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'cta'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'alignment' => array(
						'type'    => 'string',
						'default' => '',
					),
					'align'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'className' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}

	/**
	 * Block attributes → shortcode attributes (unset = saved default; pure).
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return array<string,mixed>
	 */
	public static function to_atts( array $attributes ) {
		$atts = array();
		foreach ( array( 'layout', 'style', 'accent', 'sort' ) as $key ) {
			if ( ! empty( $attributes[ $key ] ) ) {
				$atts[ $key ] = (string) $attributes[ $key ];
			}
		}
		foreach ( array( 'columns', 'limit' ) as $key ) {
			if ( ! empty( $attributes[ $key ] ) ) {
				$atts[ $key ] = (int) $attributes[ $key ];
			}
		}
		if ( isset( $attributes['minRating'] ) && (int) $attributes['minRating'] >= 0 ) {
			$atts['min_rating'] = (int) $attributes['minRating'];
		}
		foreach ( array( 'header', 'avatars', 'cta' ) as $key ) {
			if ( isset( $attributes[ $key ] ) && in_array( $attributes[ $key ], array( 'yes', 'no' ), true ) ) {
				$atts[ $key ] = $attributes[ $key ];
			}
		}
		if ( ! empty( $attributes['alignment'] ) ) {
			$atts['align'] = (string) $attributes['alignment'];
		}
		$classes = trim( (string) ( $attributes['className'] ?? '' ) . ( ! empty( $attributes['align'] ) ? ' align' . sanitize_html_class( (string) $attributes['align'] ) : '' ) );
		if ( '' !== $classes ) {
			$atts['class'] = $classes;
		}
		return $atts;
	}

	/**
	 * Server-side render (front end and editor preview).
	 *
	 * @param mixed $attributes Block attributes (whatever the caller passes).
	 * @return string
	 */
	public static function render( $attributes ) {
		$args = WILLEREV_Render::args( self::to_atts( is_array( $attributes ) ? $attributes : array() ), willerev()->settings() );
		$data = WILLEREV_Places::data();
		// In the editor, show the styles with sample data until a place is connected.
		if ( null === $data && wp_is_serving_rest_request() && current_user_can( 'edit_posts' ) ) {
			return WILLEREV_Render::render( $args, WILLEREV_Render::demo_data(), true );
		}
		return WILLEREV_Render::render( $args, $data );
	}
}
