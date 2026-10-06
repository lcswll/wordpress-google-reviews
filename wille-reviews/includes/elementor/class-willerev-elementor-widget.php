<?php
/**
 * Elementor widget "Google Reviews". Only loaded from WILLEREV_Elementor::register(), i.e. when Elementor is active.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The widget: every shortcode option as a control; empty controls follow the saved design.
 */
class WILLEREV_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Internal name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return WILLEREV_Elementor::WIDGET;
	}

	/**
	 * Title in the widget panel.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Google Reviews', 'wille-reviews' );
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-rating';
	}

	/**
	 * Panel categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array( 'google', 'reviews', 'rating', 'testimonials', 'stars', 'bewertungen' );
	}

	/**
	 * Front-end stylesheet (also loaded in the editor preview).
	 *
	 * @return string[]
	 */
	public function get_style_depends(): array {
		return array( 'willerev' );
	}

	/**
	 * Front-end script ("Read more", slider arrows).
	 *
	 * @return string[]
	 */
	public function get_script_depends(): array {
		return array( 'willerev' );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$layouts = array();
		foreach ( WILLEREV_Render::layouts() as $slug => $layout ) {
			$layouts[ $slug ] = $layout['label'];
		}
		$toggle = WILLEREV_Elementor::with_default(
			array(
				'yes' => __( 'Show', 'wille-reviews' ),
				'no'  => __( 'Hide', 'wille-reviews' ),
			)
		);

		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'wille-reviews' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'defaults_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => sprintf(
					/* translators: %s: link to the design page */
					esc_html__( 'Options left on "Default" follow the %s.', 'wille-reviews' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wille-reviews' ) ) . '" target="_blank">' . esc_html__( 'Wille Reviews design page', 'wille-reviews' ) . '</a>'
				),
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => WILLEREV_Elementor::with_default( $layouts ),
				'default' => '',
			)
		);
		$this->add_control(
			'columns',
			array(
				'label'       => __( 'Columns', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => WILLEREV_Elementor::with_default(
					array(
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
					)
				),
				'default'     => '',
				'description' => __( 'Columns of grid, slider and wall (fewer on small screens automatically).', 'wille-reviews' ),
			)
		);
		$this->add_control(
			'align',
			array(
				'label'       => __( 'Alignment', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::CHOOSE,
				'options'     => array(
					'left'   => array(
						'title' => __( 'Left', 'wille-reviews' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'wille-reviews' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'wille-reviews' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'     => '',
				'description' => __( 'Alignment of badge and social proof.', 'wille-reviews' ),
			)
		);
		$this->add_control(
			'link',
			array(
				'label'       => __( 'Link', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => WILLEREV_Elementor::with_default(
					array(
						'google' => __( 'Google profile', 'wille-reviews' ),
						'none'   => __( 'No link', 'wille-reviews' ),
					)
				),
				'default'     => '',
				'description' => __( 'Target of badge and social proof. auto = your reviews page (settings), else the Google profile.', 'wille-reviews' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'wille-reviews' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'limit',
			array(
				'label'       => __( 'Number of reviews', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => WILLEREV_Render::MAX_LIMIT,
				'default'     => '',
				'description' => __( 'Maximum number of reviews. Google provides up to five per place.', 'wille-reviews' ),
			)
		);
		$this->add_control(
			'min_rating',
			array(
				'label'   => __( 'Minimum stars', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => WILLEREV_Elementor::with_default(
					array(
						'0' => __( 'All reviews', 'wille-reviews' ),
						'3' => '★★★+',
						'4' => '★★★★+',
						'5' => '★★★★★',
					)
				),
				'default' => '',
			)
		);
		$this->add_control(
			'sort',
			array(
				'label'   => __( 'Order', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					''       => __( 'Newest first', 'wille-reviews' ),
					'rating' => __( 'Best first', 'wille-reviews' ),
				),
				'default' => '',
			)
		);
		$this->add_control(
			'lines',
			array(
				'label'       => __( 'Shorten long reviews after', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 30,
				'default'     => '',
				'description' => __( 'Shorten long reviews after this many lines with a "Read more" button (0 = full text).', 'wille-reviews' ),
			)
		);
		$this->add_control(
			'header',
			array(
				'label'   => __( 'Summary header with rating and count', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $toggle,
				'default' => '',
			)
		);
		$this->add_control(
			'cta',
			array(
				'label'   => __( 'Buttons "Write a review" and "See all on Google"', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $toggle,
				'default' => '',
			)
		);
		$this->add_control(
			'avatars',
			array(
				'label'   => __( 'Profile photos', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $toggle,
				'default' => '',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'wille-reviews' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'style',
			array(
				'label'   => __( 'Style', 'wille-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => WILLEREV_Elementor::with_default( WILLEREV_Render::styles() ),
				'default' => '',
			)
		);
		$this->add_control(
			'accent',
			array(
				'label'       => __( 'Accent color', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'alpha'       => false,
				'global'      => array( 'active' => false ),
				'default'     => '',
				'description' => __( 'Accent color for buttons, links and the accent style.', 'wille-reviews' ),
			)
		);
		$this->add_control(
			'count_color',
			array(
				'label'       => __( 'Color of the review count', 'wille-reviews' ),
				'type'        => \Elementor\Controls_Manager::COLOR,
				'alpha'       => false,
				'global'      => array( 'active' => false ),
				'default'     => '',
				'description' => __( 'Highlights the number of reviews, e.g. "283" in "283 reviews on Google".', 'wille-reviews' ),
			)
		);
		$this->add_control(
			'radius',
			array(
				'label'      => __( 'Corner radius', 'wille-reviews' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 32,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => '',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Front end and editor preview (Elementor re-renders on the server after every change).
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$editor   = current_user_can( 'edit_posts' ) && self::in_editor();
		echo WILLEREV_Elementor::render( is_array( $settings ) ? $settings : array(), $editor ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in WILLEREV_Render.
	}

	/**
	 * Whether Elementor renders this for its editor (panel AJAX render or the preview frame).
	 *
	 * @return bool
	 */
	protected static function in_editor() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}
		$elementor = \Elementor\Plugin::$instance;
		return ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() ) || ( isset( $elementor->preview ) && $elementor->preview->is_preview_mode() );
	}
}
