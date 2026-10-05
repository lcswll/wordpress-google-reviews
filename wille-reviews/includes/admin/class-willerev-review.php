<?php
/**
 * Review request: a quiet card on the plugin's own screens, shown only once the plugin demonstrably works
 * on this site (in use for two weeks and reviews loaded from Google). One click hides it for 30 days or for good.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Review request module.
 */
class WILLEREV_Review {

	/**
	 * Option holding the user's choice: array{state:string,until:int}.
	 */
	const OPTION = 'willerev_review';

	/**
	 * Minimum time the plugin has been active before asking.
	 */
	const MIN_AGE = 14 * DAY_IN_SECONDS;

	/**
	 * How long "Maybe later" hides the request.
	 */
	const SNOOZE = 30 * DAY_IN_SECONDS;

	/**
	 * Hook up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render' ) );
		add_action( 'admin_post_willerev_review', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Decide whether to ask (pure, unit-tested).
	 *
	 * @param array<string,mixed> $choice       Stored choice (state: ''|'later'|'done', until: unix time).
	 * @param int                 $now          Current unix time.
	 * @param int                 $activated_at Unix time of the first activation (0 = unknown).
	 * @param bool                $working      Reviews are loaded from Google.
	 * @return bool
	 */
	public static function should_ask( $choice, $now, $activated_at, $working ) {
		$state = isset( $choice['state'] ) ? (string) $choice['state'] : '';
		if ( 'done' === $state ) {
			return false;
		}
		if ( 'later' === $state && $now < (int) ( $choice['until'] ?? 0 ) ) {
			return false;
		}
		if ( $activated_at <= 0 || $now - $activated_at < self::MIN_AGE ) {
			return false;
		}
		return $working;
	}

	/**
	 * Print the card on the plugin's screens when due.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) || ! WILLEREV_Admin::is_plugin_screen() ) {
			return;
		}
		$choice = get_option( self::OPTION, array() );
		$choice = is_array( $choice ) ? $choice : array();
		$data   = WILLEREV_Places::is_configured() ? WILLEREV_Places::backup() : null;
		if ( ! self::should_ask( $choice, time(), (int) get_option( 'willerev_activated_at', 0 ), null !== $data && (int) $data['count'] > 0 ) ) {
			return;
		}

		$link = static function ( $choice ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=willerev_review&choice=' . $choice ), 'willerev_review' );
		};
		?>
		<div class="notice willerev-review" role="region" aria-label="<?php esc_attr_e( 'Review Wille Reviews', 'wille-reviews' ); ?>">
			<p>
				<strong><?php esc_html_e( 'Your Google reviews have been on your site for two weeks now.', 'wille-reviews' ); ?></strong>
				<?php esc_html_e( 'If Wille Reviews helps you, would you leave a short review on WordPress.org? It takes a minute and helps other site owners find it. Thank you!', 'wille-reviews' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $link( 'rate' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Sure, write a review', 'wille-reviews' ); ?></a>
				<a class="button" href="<?php echo esc_url( $link( 'later' ) ); ?>"><?php esc_html_e( 'Maybe later', 'wille-reviews' ); ?></a>
				<a href="<?php echo esc_url( $link( 'done' ) ); ?>"><?php esc_html_e( 'I already did', 'wille-reviews' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Store the choice and send the user on (to wordpress.org or back to the page).
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'wille-reviews' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'willerev_review' );

		$choice = isset( $_GET['choice'] ) ? sanitize_key( wp_unslash( $_GET['choice'] ) ) : '';
		if ( 'later' === $choice ) {
			update_option(
				self::OPTION,
				array(
					'state' => 'later',
					'until' => time() + self::SNOOZE,
				),
				false
			);
		} elseif ( in_array( $choice, array( 'rate', 'done' ), true ) ) {
			update_option(
				self::OPTION,
				array(
					'state' => 'done',
					'until' => 0,
				),
				false
			);
		}

		if ( 'rate' === $choice ) {
			add_filter(
				'allowed_redirect_hosts',
				static function ( $hosts ) {
					$hosts[] = 'wordpress.org';
					return $hosts;
				}
			);
			wp_safe_redirect( WILLEREV_Admin::REVIEW_URL );
			exit;
		}

		$back = wp_get_referer();
		wp_safe_redirect( $back ? $back : admin_url( 'admin.php?page=wille-reviews' ) );
		exit;
	}
}
