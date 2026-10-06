<?php
/**
 * Minimal Elementor stubs for PHPStan: only the API the widget uses (Elementor is not a Composer dependency).
 *
 * @package Wille_Reviews
 */

namespace Elementor;

/**
 * Control types and tabs.
 */
class Controls_Manager {
	const TAB_CONTENT = 'content';
	const TAB_STYLE   = 'style';
	const RAW_HTML    = 'raw_html';
	const SELECT      = 'select';
	const CHOOSE      = 'choose';
	const NUMBER      = 'number';
	const COLOR       = 'color';
	const SLIDER      = 'slider';
}

/**
 * Base class of all widgets.
 */
abstract class Widget_Base {
	/**
	 * @param string              $id   Section ID.
	 * @param array<string,mixed> $args Section arguments.
	 * @return void
	 */
	public function start_controls_section( $id, array $args = array() ) {}

	/**
	 * @return void
	 */
	public function end_controls_section() {}

	/**
	 * @param string              $id   Control ID.
	 * @param array<string,mixed> $args Control arguments.
	 * @param array<string,mixed> $options Options.
	 * @return bool
	 */
	public function add_control( $id, array $args, $options = array() ) {
		return true;
	}

	/**
	 * @param string|null $setting_key Key.
	 * @return mixed
	 */
	public function get_settings_for_display( $setting_key = null ) {
		return array();
	}
}

/**
 * Editor state.
 */
class Editor {
	/**
	 * @return bool
	 */
	public function is_edit_mode() {
		return false;
	}
}

/**
 * Preview state.
 */
class Preview {
	/**
	 * @return bool
	 */
	public function is_preview_mode() {
		return false;
	}
}

/**
 * Plugin singleton.
 */
class Plugin {
	/**
	 * @var Plugin|null
	 */
	public static $instance;

	/**
	 * @var Editor|null
	 */
	public $editor;

	/**
	 * @var Preview|null
	 */
	public $preview;
}
