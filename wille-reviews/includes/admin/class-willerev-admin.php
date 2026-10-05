<?php
/**
 * Admin: menu, settings, assets, refresh/flush actions, dashboard widget.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin module.
 */
class WILLEREV_Admin {

	/**
	 * Author credit shown in the brand bar and the plugin list.
	 */
	const AUTHOR = 'Lucas Wille';

	/**
	 * Author website.
	 */
	const AUTHOR_URL = 'https://lucaswille.de/';

	/**
	 * Where a review is written on wordpress.org.
	 */
	const REVIEW_URL = 'https://wordpress.org/support/plugin/wille-reviews/reviews/#new-post';

	/**
	 * Hook suffixes of our admin pages (WordPress derives them from the translated menu title, so they are
	 * captured instead of hardcoded).
	 *
	 * @var string[]
	 */
	protected static $page_hooks = array();

	/**
	 * Menu slug of the page currently rendering its brand bar.
	 *
	 * @var string
	 */
	protected static $current_page = '';

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'welcome_notice' ) );
		add_action( 'admin_post_willerev_refresh', array( __CLASS__, 'handle_refresh' ) );
		add_action( 'admin_post_willerev_flush', array( __CLASS__, 'handle_flush' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_setup' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WILLEREV_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
	}

	/**
	 * Whether the current admin screen is one of the plugin's own pages.
	 *
	 * @return bool
	 */
	public static function is_plugin_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen instanceof WP_Screen && in_array( $screen->id, self::$page_hooks, true );
	}

	/**
	 * Brand bar with navigation and author credit (top of every plugin page).
	 *
	 * @param string $current Menu slug of the current page.
	 * @return void
	 */
	public static function header( $current ) {
		self::$current_page = $current;
		require WILLEREV_DIR . 'includes/admin/views/header.php';
	}

	/**
	 * Menu slug of the page being rendered (for views/header.php).
	 *
	 * @return string
	 */
	public static function current_page() {
		return self::$current_page;
	}

	/**
	 * Admin menu: Design (default page) and Settings.
	 *
	 * @return void
	 */
	public static function menu() {
		$hooks            = array();
		$hooks[]          = add_menu_page(
			__( 'Wille Reviews', 'wille-reviews' ),
			__( 'Google Reviews', 'wille-reviews' ),
			'manage_options',
			'wille-reviews',
			array( __CLASS__, 'render_design' ),
			'dashicons-star-filled',
			59
		);
		$hooks[]          = add_submenu_page(
			'wille-reviews',
			__( 'Design & shortcode', 'wille-reviews' ),
			__( 'Design', 'wille-reviews' ),
			'manage_options',
			'wille-reviews',
			array( __CLASS__, 'render_design' )
		);
		$hooks[]          = add_submenu_page(
			'wille-reviews',
			__( 'Google Reviews settings', 'wille-reviews' ),
			__( 'Settings', 'wille-reviews' ),
			'manage_options',
			'wille-reviews-settings',
			array( __CLASS__, 'render_settings' )
		);
		self::$page_hooks = array_values( array_filter( $hooks ) );
	}

	/**
	 * Links in the plugin's row on the plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=wille-reviews' ) ) . '">' . esc_html__( 'Design', 'wille-reviews' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=wille-reviews-settings' ) ) . '">' . esc_html__( 'Settings', 'wille-reviews' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Review link in the plugin's row meta.
	 *
	 * @param string[] $links Row meta links.
	 * @param string   $file  Plugin basename of the row.
	 * @return string[]
	 */
	public static function row_meta( $links, $file ) {
		if ( plugin_basename( WILLEREV_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( self::REVIEW_URL ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'Rate Wille Reviews on WordPress.org (opens in a new tab)', 'wille-reviews' ) . '">' . esc_html__( 'Rate ★★★★★', 'wille-reviews' ) . '</a>';
		return $links;
	}

	/**
	 * Footer line on the plugin's own pages only.
	 *
	 * @param string $text Default footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		if ( ! self::is_plugin_screen() ) {
			return $text;
		}
		return sprintf(
			/* translators: 1: author link, 2: review link with five stars */
			esc_html__( 'Wille Reviews is made by %1$s. Does it help you? A %2$s review on WordPress.org helps others find it – thank you!', 'wille-reviews' ),
			'<a href="' . esc_url( self::AUTHOR_URL ) . '" target="_blank" rel="noopener">' . esc_html( self::AUTHOR ) . '</a>',
			'<a class="willerev-footer-stars" href="' . esc_url( self::REVIEW_URL ) . '" target="_blank" rel="noopener">★★★★★</a>'
		);
	}

	/**
	 * One-time notice after activation, on the Plugins screen only.
	 *
	 * @return void
	 */
	public static function welcome_notice() {
		if ( ! get_transient( 'willerev_welcome_notice' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		if ( self::is_plugin_screen() ) {
			delete_transient( 'willerev_welcome_notice' );
			return;
		}
		if ( 'plugins' !== $screen->id ) {
			return;
		}
		delete_transient( 'willerev_welcome_notice' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Wille Reviews is active.', 'wille-reviews' ); ?></strong>
				<?php esc_html_e( 'Connect your Google business in two minutes, then pick a layout and a style.', 'wille-reviews' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wille-reviews-settings' ) ); ?>"><?php esc_html_e( 'Connect now', 'wille-reviews' ); ?></a>
				· <a href="<?php echo esc_url( admin_url( 'admin.php?page=wille-reviews' ) ); ?>"><?php esc_html_e( 'Browse the styles', 'wille-reviews' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Register the single settings option.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'willerev_settings_group',
			'willerev_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize a save. The design page and the settings page each submit their own section ("_section"); the
	 * other section's values are kept, so a checkbox missing from the form only means "off" for its own page.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( $input ) {
		$defaults = WILLEREV_Install::defaults();
		$input    = is_array( $input ) ? $input : array();
		$saved    = get_option( 'willerev_settings', array() );
		$clean    = array_intersect_key( wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults ), $defaults );
		$section  = isset( $input['_section'] ) ? sanitize_key( (string) $input['_section'] ) : '';

		// Programmatic update_option() with a full array (WP-CLI, tests): validate every key.
		if ( '' === $section ) {
			$section = 'all';
		}

		if ( in_array( $section, array( 'connection', 'all' ), true ) ) {
			$clean['api_key']           = isset( $input['api_key'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $input['api_key'] ) : $clean['api_key'];
			$clean['place_id']          = isset( $input['place_id'] ) ? self::sanitize_place_id( (string) $input['place_id'] ) : $clean['place_id'];
			$language                   = isset( $input['language'] ) ? trim( (string) $input['language'] ) : (string) $clean['language'];
			$clean['language']          = preg_match( '/^[a-zA-Z]{2,3}(?:[-_][a-zA-Z]{2,4})?$/', $language ) ? $language : '';
			$clean['cache_hours']       = isset( $input['cache_hours'] ) ? min( 168, max( 1, absint( $input['cache_hours'] ) ) ) : $clean['cache_hours'];
			$clean['reviews_url']       = isset( $input['reviews_url'] ) ? esc_url_raw( trim( (string) $input['reviews_url'] ) ) : $clean['reviews_url'];
			$clean['floating_position'] = isset( $input['floating_position'] ) && 'left' === $input['floating_position'] ? 'left' : 'right';
			foreach ( array( 'original_text', 'hide_avatars', 'floating', 'delete_on_uninstall' ) as $flag ) {
				$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
			}
		}

		if ( in_array( $section, array( 'design', 'all' ), true ) ) {
			$layout              = isset( $input['layout'] ) ? sanitize_key( (string) $input['layout'] ) : '';
			$style               = isset( $input['style'] ) ? sanitize_key( (string) $input['style'] ) : '';
			$clean['layout']     = in_array( $layout, WILLEREV_Render::LAYOUTS, true ) ? $layout : $defaults['layout'];
			$clean['style']      = in_array( $style, WILLEREV_Render::STYLES, true ) ? $style : $defaults['style'];
			$clean['accent']     = WILLEREV_Render::hex_color( isset( $input['accent'] ) ? (string) $input['accent'] : '', $defaults['accent'] );
			$clean['radius']     = isset( $input['radius'] ) ? min( 32, max( 0, (int) $input['radius'] ) ) : $defaults['radius'];
			$clean['columns']    = isset( $input['columns'] ) ? min( 4, max( 1, (int) $input['columns'] ) ) : $defaults['columns'];
			$clean['limit']      = isset( $input['limit'] ) ? min( WILLEREV_Render::MAX_LIMIT, max( 1, (int) $input['limit'] ) ) : $defaults['limit'];
			$clean['min_rating'] = isset( $input['min_rating'] ) ? min( 5, max( 0, (int) $input['min_rating'] ) ) : $defaults['min_rating'];
			$clean['lines']      = isset( $input['lines'] ) ? min( 30, max( 0, (int) $input['lines'] ) ) : $defaults['lines'];
			foreach ( array( 'show_header', 'show_avatars', 'show_cta' ) as $flag ) {
				$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
			}
		}

		return $clean;
	}

	/**
	 * Place ID as pasted (also "places/ChIJ…" from the API or with stray spaces; pure).
	 *
	 * @param string $value Input.
	 * @return string
	 */
	public static function sanitize_place_id( $value ) {
		$value = trim( $value );
		if ( 0 === strpos( $value, 'places/' ) ) {
			$value = substr( $value, 7 );
		}
		return (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', $value );
	}

	/**
	 * Admin assets on the plugin's pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( ! in_array( $hook, self::$page_hooks, true ) ) {
			return;
		}
		wp_enqueue_style( 'willerev-admin', WILLEREV_URL . 'assets/css/willerev-admin.css', array( 'willerev' ), WILLEREV_VERSION );
		wp_enqueue_script( 'willerev-admin', WILLEREV_URL . 'assets/js/willerev-admin.js', array( 'willerev' ), WILLEREV_VERSION, true );

		// The shortcode builder only writes options that differ from what [wille_reviews] renders right now.
		$settings = willerev()->settings();
		$defaults = array( 'accent' => (string) $settings['accent'] );
		foreach ( array( 'radius', 'columns', 'limit', 'min_rating', 'lines', 'show_header', 'show_avatars', 'show_cta' ) as $key ) {
			$defaults[ $key ] = (int) $settings[ $key ];
		}
		wp_localize_script(
			'willerev-admin',
			'willerevAdmin',
			array(
				'defaults' => $defaults,
				'i18n'     => array(
					'copied' => __( 'Copied!', 'wille-reviews' ),
					'copy'   => __( 'Copy', 'wille-reviews' ),
				),
			)
		);
	}

	/**
	 * Design page.
	 *
	 * @return void
	 */
	public static function render_design() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WILLEREV_DIR . 'includes/admin/views/design.php';
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WILLEREV_DIR . 'includes/admin/views/settings.php';
	}

	/**
	 * "Test connection & refresh now".
	 *
	 * @return void
	 */
	public static function handle_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'wille-reviews' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'willerev_refresh' );
		delete_transient( WILLEREV_Places::LOCK );
		$data = WILLEREV_Places::refresh();
		wp_safe_redirect( add_query_arg( 'willerev-refreshed', null !== $data ? 'ok' : 'error', admin_url( 'admin.php?page=wille-reviews-settings' ) ) );
		exit;
	}

	/**
	 * "Clear cache".
	 *
	 * @return void
	 */
	public static function handle_flush() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'wille-reviews' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'willerev_flush' );
		WILLEREV_Places::flush();
		wp_safe_redirect( add_query_arg( 'willerev-flushed', '1', admin_url( 'admin.php?page=wille-reviews-settings' ) ) );
		exit;
	}

	/**
	 * Dashboard widget for administrators.
	 *
	 * @return void
	 */
	public static function dashboard_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'willerev_dashboard', __( 'Google Reviews', 'wille-reviews' ), array( __CLASS__, 'render_dashboard_widget' ) );
		wp_add_inline_style(
			'dashboard',
			'.willerev-dash__score{display:flex;align-items:baseline;gap:.5em;flex-wrap:wrap;margin-top:0}.willerev-dash__score strong{font-size:1.9em;line-height:1}.willerev-dash__stars{color:#dba617;letter-spacing:1px}.willerev-dash li{margin-bottom:.7em}'
		);
	}

	/**
	 * Dashboard widget: rating, count and the latest reviews.
	 *
	 * @return void
	 */
	public static function render_dashboard_widget() {
		$settings_url = admin_url( 'admin.php?page=wille-reviews-settings' );
		if ( ! WILLEREV_Places::is_configured() ) {
			printf(
				'<p>%s</p><p><a class="button button-primary" href="%s">%s</a></p>',
				esc_html__( 'Not connected yet – add your API key and place ID to show your Google reviews.', 'wille-reviews' ),
				esc_url( $settings_url ),
				esc_html__( 'Connect now', 'wille-reviews' )
			);
			return;
		}
		$data = WILLEREV_Places::data();
		if ( null === $data ) {
			$status = WILLEREV_Places::status();
			printf(
				'<p>%s</p><p><code>%s</code></p><p><a href="%s">%s</a></p>',
				esc_html__( 'No data available – the last request to Google failed.', 'wille-reviews' ),
				esc_html( null !== $status ? $status['message'] : '' ),
				esc_url( $settings_url ),
				esc_html__( 'Open settings', 'wille-reviews' )
			);
			return;
		}
		?>
		<div class="willerev-dash">
			<p class="willerev-dash__score">
				<strong><?php echo esc_html( number_format_i18n( (float) $data['rating'], 1 ) ); ?></strong>
				<span class="willerev-dash__stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', (int) round( (float) $data['rating'] ) ) ); ?></span>
				<span>
					<?php
					/* translators: %s: number of reviews */
					echo esc_html( sprintf( _n( '%s review on Google', '%s reviews on Google', (int) $data['count'], 'wille-reviews' ), number_format_i18n( (int) $data['count'] ) ) );
					?>
				</span>
			</p>
			<?php if ( ! empty( $data['reviews'] ) ) : ?>
				<ul>
					<?php foreach ( array_slice( (array) $data['reviews'], 0, 3 ) as $review ) : ?>
						<li>
							<strong><?php echo esc_html( (string) $review['author'] ); ?></strong>
							<span class="willerev-dash__stars"><?php echo esc_html( str_repeat( '★', (int) $review['rating'] ) ); ?></span>
							<?php if ( '' !== (string) $review['text'] ) : ?>
								<br /><span><?php echo esc_html( wp_trim_words( (string) $review['text'], 18, '…' ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p>
				<?php if ( '' !== (string) $data['url'] ) : ?>
					<a href="<?php echo esc_url( (string) $data['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See all on Google', 'wille-reviews' ); ?></a> ·
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wille-reviews' ) ); ?>"><?php esc_html_e( 'Design', 'wille-reviews' ); ?></a>
			</p>
		</div>
		<?php
	}
}
