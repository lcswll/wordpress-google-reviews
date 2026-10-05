<?php
/**
 * Settings page: connection to Google, caching, privacy, floating badge, status.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$willerev_settings = willerev()->settings();
$willerev_name     = 'willerev_settings';
$willerev_status   = WILLEREV_Places::status();
$willerev_data     = WILLEREV_Places::is_configured() ? WILLEREV_Places::backup() : null;
$willerev_next     = wp_next_scheduled( WILLEREV_Places::CRON );
$willerev_format   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only flags set by our own redirects.
$willerev_refreshed = isset( $_GET['willerev-refreshed'] ) ? sanitize_key( wp_unslash( $_GET['willerev-refreshed'] ) ) : '';
$willerev_flushed   = isset( $_GET['willerev-flushed'] );
$willerev_saved     = isset( $_GET['settings-updated'] );
// phpcs:enable
?>
<div class="wrap willerev-wrap">
	<?php WILLEREV_Admin::header( 'wille-reviews-settings' ); ?>
	<h1><?php esc_html_e( 'Settings', 'wille-reviews' ); ?></h1>

	<?php if ( $willerev_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'wille-reviews' ); ?></p></div>
	<?php endif; ?>
	<?php if ( 'ok' === $willerev_refreshed ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Connection works – the reviews were loaded from Google.', 'wille-reviews' ); ?></p></div>
	<?php elseif ( 'error' === $willerev_refreshed ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Google did not return any data. See the error under "Status" below.', 'wille-reviews' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $willerev_flushed ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cache cleared. The next page view loads the reviews from Google again.', 'wille-reviews' ); ?></p></div>
	<?php endif; ?>

	<div class="willerev-columns">
		<form method="post" action="options.php" class="willerev-settings">
			<?php settings_fields( 'willerev_settings_group' ); ?>
			<input type="hidden" name="<?php echo esc_attr( $willerev_name ); ?>[_section]" value="connection" />

			<div class="willerev-panel">
				<h2><?php esc_html_e( 'Connect your Google business', 'wille-reviews' ); ?></h2>
				<p class="willerev-benefit"><?php esc_html_e( 'The plugin loads rating, review count and the latest reviews from the Google Places API on your server. Your visitors never connect to Google.', 'wille-reviews' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="willerev-api-key"><?php esc_html_e( 'API key', 'wille-reviews' ); ?></label></th>
						<td>
							<input type="password" id="willerev-api-key" class="regular-text code" name="<?php echo esc_attr( $willerev_name ); ?>[api_key]" value="<?php echo esc_attr( (string) $willerev_settings['api_key'] ); ?>" autocomplete="off" spellcheck="false" />
							<p class="description"><?php esc_html_e( 'A Google Cloud API key with the "Places API (New)" enabled.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="willerev-place-id"><?php esc_html_e( 'Place ID', 'wille-reviews' ); ?></label></th>
						<td>
							<input type="text" id="willerev-place-id" class="regular-text code" name="<?php echo esc_attr( $willerev_name ); ?>[place_id]" value="<?php echo esc_attr( (string) $willerev_settings['place_id'] ); ?>" placeholder="ChIJ…" spellcheck="false" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: link to Google's Place ID Finder */
									esc_html__( 'The ID of your Google Business Profile. Find it with Google\'s %s.', 'wille-reviews' ),
									'<a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" rel="noopener">' . esc_html__( 'Place ID Finder', 'wille-reviews' ) . '</a>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>
				<details class="willerev-details">
					<summary><?php esc_html_e( 'How do I get an API key? (5 minutes)', 'wille-reviews' ); ?></summary>
					<ol>
						<li>
							<?php
							printf(
								/* translators: %s: link to the Google Cloud Console */
								esc_html__( 'Open the %s and create a project (or pick an existing one). Google asks for a billing account – the plugin\'s few requests normally stay within the free monthly usage.', 'wille-reviews' ),
								'<a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>'
							);
							?>
						</li>
						<li><?php esc_html_e( 'Under "APIs & Services → Library", enable "Places API (New)".', 'wille-reviews' ); ?></li>
						<li><?php esc_html_e( 'Under "APIs & Services → Credentials", create an API key.', 'wille-reviews' ); ?></li>
						<li><?php esc_html_e( 'Recommended: restrict the key to "Places API (New)" and, if your host has a fixed IP address, to that IP.', 'wille-reviews' ); ?></li>
						<li><?php esc_html_e( 'Paste key and Place ID here, save, then click "Test connection & load reviews".', 'wille-reviews' ); ?></li>
					</ol>
					<p>
						<?php
						printf(
							/* translators: %s: link to Google Maps Platform pricing */
							esc_html__( 'With the default refresh every 12 hours the plugin makes about 60 requests a month, no matter how many visitors you have. Current prices and free usage: %s.', 'wille-reviews' ),
							'<a href="https://developers.google.com/maps/billing-and-pricing/pricing" target="_blank" rel="noopener">' . esc_html__( 'Google Maps Platform pricing', 'wille-reviews' ) . '</a>'
						);
						?>
					</p>
				</details>
			</div>

			<div class="willerev-panel">
				<h2><?php esc_html_e( 'Reviews', 'wille-reviews' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="willerev-language"><?php esc_html_e( 'Language', 'wille-reviews' ); ?></label></th>
						<td>
							<input type="text" id="willerev-language" class="small-text" name="<?php echo esc_attr( $willerev_name ); ?>[language]" value="<?php echo esc_attr( (string) $willerev_settings['language'] ); ?>" placeholder="<?php echo esc_attr( WILLEREV_Places::language_code( '', get_locale() ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Language code such as de or en. Empty = the language of your site.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Review text', 'wille-reviews' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[original_text]" value="1" <?php checked( 1, (int) $willerev_settings['original_text'] ); ?> /> <?php esc_html_e( 'Show reviews in the language they were written in', 'wille-reviews' ); ?></label>
							<p class="description"><?php esc_html_e( 'Off = Google\'s automatic translation into the language above.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="willerev-reviews-url"><?php esc_html_e( 'Your reviews page', 'wille-reviews' ); ?></label></th>
						<td>
							<input type="url" id="willerev-reviews-url" class="regular-text" name="<?php echo esc_attr( $willerev_name ); ?>[reviews_url]" value="<?php echo esc_attr( (string) $willerev_settings['reviews_url'] ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Page that shows your reviews. Badges and social proof link there (the anchor #google-reviews is added automatically). Empty = they link to your Google profile.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="willerev-cache-hours"><?php esc_html_e( 'Refresh every', 'wille-reviews' ); ?></label></th>
						<td>
							<input type="number" id="willerev-cache-hours" class="small-text" min="1" max="168" name="<?php echo esc_attr( $willerev_name ); ?>[cache_hours]" value="<?php echo esc_attr( (string) (int) $willerev_settings['cache_hours'] ); ?>" /> <?php esc_html_e( 'hours', 'wille-reviews' ); ?>
							<p class="description"><?php esc_html_e( 'New reviews appear after at most this long. Shorter means more API requests.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="willerev-panel">
				<h2><?php esc_html_e( 'Floating badge', 'wille-reviews' ); ?></h2>
				<p class="willerev-benefit"><?php esc_html_e( 'A small rating badge in a corner of every page – trust at the moment a visitor decides. Visitors can close it for their session.', 'wille-reviews' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Show', 'wille-reviews' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[floating]" value="1" <?php checked( 1, (int) $willerev_settings['floating'] ); ?> /> <?php esc_html_e( 'Show the floating badge on all front-end pages', 'wille-reviews' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="willerev-floating-position"><?php esc_html_e( 'Position', 'wille-reviews' ); ?></label></th>
						<td>
							<select id="willerev-floating-position" name="<?php echo esc_attr( $willerev_name ); ?>[floating_position]">
								<option value="right" <?php selected( $willerev_settings['floating_position'], 'right' ); ?>><?php esc_html_e( 'Bottom right', 'wille-reviews' ); ?></option>
								<option value="left" <?php selected( $willerev_settings['floating_position'], 'left' ); ?>><?php esc_html_e( 'Bottom left', 'wille-reviews' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Style and accent color follow the default design.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="willerev-panel">
				<h2><?php esc_html_e( 'Privacy & data', 'wille-reviews' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Profile photos', 'wille-reviews' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[hide_avatars]" value="1" <?php checked( 1, (int) $willerev_settings['hide_avatars'] ); ?> /> <?php esc_html_e( 'Do not download or show reviewers\' profile photos', 'wille-reviews' ); ?></label>
							<p class="description"><?php esc_html_e( 'Photos are otherwise copied to your uploads folder once, so visitors\' browsers never load images from Google. Initials are shown instead when this is on.', 'wille-reviews' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Uninstall', 'wille-reviews' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[delete_on_uninstall]" value="1" <?php checked( 1, (int) $willerev_settings['delete_on_uninstall'] ); ?> /> <?php esc_html_e( 'Delete all settings, cached reviews and photos when the plugin is deleted', 'wille-reviews' ); ?></label></td>
					</tr>
				</table>
			</div>

			<?php submit_button( __( 'Save settings', 'wille-reviews' ) ); ?>
		</form>

		<aside class="willerev-side">
			<div class="willerev-panel">
				<h2><?php esc_html_e( 'Status', 'wille-reviews' ); ?></h2>
				<?php if ( ! WILLEREV_Places::is_configured() ) : ?>
					<p class="willerev-state willerev-state--warn"><?php esc_html_e( 'Not connected – enter API key and Place ID.', 'wille-reviews' ); ?></p>
				<?php elseif ( null === $willerev_status ) : ?>
					<p class="willerev-state willerev-state--warn"><?php esc_html_e( 'No request yet. Test the connection.', 'wille-reviews' ); ?></p>
				<?php else : ?>
					<p class="willerev-state willerev-state--<?php echo 'ok' === $willerev_status['state'] ? 'ok' : 'bad'; ?>">
						<?php echo 'ok' === $willerev_status['state'] ? esc_html__( 'Last request successful', 'wille-reviews' ) : esc_html__( 'Last request failed', 'wille-reviews' ); ?>
					</p>
					<p><code class="willerev-status-message"><?php echo esc_html( $willerev_status['message'] ); ?></code></p>
					<p class="description"><?php echo esc_html( (string) wp_date( $willerev_format, $willerev_status['time'] ) ); // wp_date() is false only for invalid timestamps. ?></p>
				<?php endif; ?>

				<?php if ( null !== $willerev_data ) : ?>
					<p class="willerev-status-place">
						<strong><?php echo esc_html( (string) $willerev_data['name'] ); ?></strong><br />
						<?php echo WILLEREV_Render::stars( (float) $willerev_data['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in stars(). ?>
						<?php echo esc_html( number_format_i18n( (float) $willerev_data['rating'], 1 ) ); ?> ·
						<?php
						/* translators: %s: number of reviews */
						echo esc_html( sprintf( _n( '%s review', '%s reviews', (int) $willerev_data['count'], 'wille-reviews' ), number_format_i18n( (int) $willerev_data['count'] ) ) );
						?>
					</p>
				<?php endif; ?>
				<?php if ( false !== $willerev_next ) : ?>
					<p class="description">
						<?php
						/* translators: %s: date and time */
						echo esc_html( sprintf( __( 'Next automatic refresh: %s', 'wille-reviews' ), wp_date( $willerev_format, $willerev_next ) ) );
						?>
					</p>
				<?php endif; ?>

				<?php if ( WILLEREV_Places::is_configured() ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'willerev_refresh' ); ?>
						<input type="hidden" name="action" value="willerev_refresh" />
						<?php submit_button( __( 'Test connection & load reviews', 'wille-reviews' ), 'primary', 'submit', false ); ?>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="willerev-flush">
						<?php wp_nonce_field( 'willerev_flush' ); ?>
						<input type="hidden" name="action" value="willerev_flush" />
						<?php submit_button( __( 'Clear cache', 'wille-reviews' ), 'secondary', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			</div>

			<div class="willerev-panel">
				<h2><?php esc_html_e( 'Use it', 'wille-reviews' ); ?></h2>
				<ul class="willerev-usage">
					<li><?php esc_html_e( 'Block editor: add the block "Google Reviews".', 'wille-reviews' ); ?></li>
					<li><?php esc_html_e( 'Anywhere else: the shortcode', 'wille-reviews' ); ?> <code>[wille_reviews]</code></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wille-reviews' ) ); ?>"><?php esc_html_e( 'Choose layout and style', 'wille-reviews' ); ?></a></li>
				</ul>
			</div>
		</aside>
	</div>
</div>
