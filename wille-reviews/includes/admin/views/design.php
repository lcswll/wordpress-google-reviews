<?php
/**
 * Design page: pick layout and style with a live preview, copy the matching shortcode, save the defaults.
 *
 * The preview renders every layout once (with all reviews); switching layout, style, color, columns, count or
 * toggles happens in the browser (assets/js/willerev-admin.js) – styles are pure CSS, so nothing reloads.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$willerev_settings = willerev()->settings();
$willerev_data     = WILLEREV_Places::data();
$willerev_is_demo  = null === $willerev_data;
if ( $willerev_is_demo ) {
	$willerev_data = WILLEREV_Render::demo_data();
}
$willerev_layouts = WILLEREV_Render::layouts();
$willerev_styles  = WILLEREV_Render::styles();
$willerev_name    = 'willerev_settings';

// Layout pictograms (simple wireframes).
$willerev_icons = array(
	'grid'     => '<rect x="2" y="4" width="11" height="16" rx="2"/><rect x="15" y="4" width="11" height="16" rx="2"/><rect x="28" y="4" width="11" height="16" rx="2"/>',
	'carousel' => '<rect x="7" y="4" width="12" height="16" rx="2"/><rect x="21" y="4" width="12" height="16" rx="2"/><path d="M3 12l2-2v4zM37 12l-2-2v4z"/>',
	'list'     => '<rect x="2" y="3" width="37" height="5" rx="1.5"/><rect x="2" y="10" width="37" height="5" rx="1.5"/><rect x="2" y="17" width="37" height="5" rx="1.5"/>',
	'masonry'  => '<rect x="2" y="2" width="11" height="10" rx="2"/><rect x="2" y="14" width="11" height="8" rx="2"/><rect x="15" y="2" width="11" height="6" rx="2"/><rect x="15" y="10" width="11" height="12" rx="2"/><rect x="28" y="2" width="11" height="13" rx="2"/><rect x="28" y="17" width="11" height="5" rx="2"/>',
	'badge'    => '<rect x="6" y="6" width="29" height="12" rx="4"/>',
	'social'   => '<circle cx="8" cy="12" r="4"/><circle cx="14" cy="12" r="4"/><circle cx="20" cy="12" r="4"/><rect x="26" y="8" width="13" height="3" rx="1.5"/><rect x="26" y="13" width="9" height="3" rx="1.5"/>',
);
?>
<div class="wrap willerev-wrap">
	<?php WILLEREV_Admin::header( 'wille-reviews' ); ?>
	<h1><?php esc_html_e( 'Design & shortcode', 'wille-reviews' ); ?></h1>
	<p class="willerev-intro"><?php esc_html_e( 'Pick a layout and a style – the preview updates instantly. Save your choice as the default, or copy the shortcode to use a different design on a single page.', 'wille-reviews' ); ?></p>

	<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Design saved as default. Shortcodes and blocks without their own options use it now.', 'wille-reviews' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $willerev_is_demo ) : ?>
		<div class="notice notice-info inline willerev-demo-note">
			<p>
				<?php esc_html_e( 'The preview shows sample reviews.', 'wille-reviews' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wille-reviews-settings' ) ); ?>"><?php esc_html_e( 'Connect your Google business to see your own.', 'wille-reviews' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php" class="willerev-designer" id="willerev-designer">
		<?php settings_fields( 'willerev_settings_group' ); ?>
		<input type="hidden" name="<?php echo esc_attr( $willerev_name ); ?>[_section]" value="design" />

		<div class="willerev-designer__controls">
			<fieldset class="willerev-panel">
				<legend class="willerev-panel__title"><?php esc_html_e( 'Layout', 'wille-reviews' ); ?></legend>
				<div class="willerev-choices willerev-choices--layouts">
					<?php foreach ( $willerev_layouts as $willerev_slug => $willerev_layout ) : ?>
						<label class="willerev-choice">
							<input type="radio" name="<?php echo esc_attr( $willerev_name ); ?>[layout]" value="<?php echo esc_attr( $willerev_slug ); ?>" <?php checked( $willerev_settings['layout'], $willerev_slug ); ?> />
							<span class="willerev-choice__card">
								<svg viewBox="0 0 41 24" aria-hidden="true" focusable="false"><?php echo $willerev_icons[ $willerev_slug ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG shapes defined above. ?></svg>
								<span class="willerev-choice__label"><?php echo esc_html( $willerev_layout['label'] ); ?></span>
								<span class="willerev-choice__desc"><?php echo esc_html( $willerev_layout['description'] ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="willerev-panel">
				<legend class="willerev-panel__title"><?php esc_html_e( 'Style', 'wille-reviews' ); ?></legend>
				<div class="willerev-choices willerev-choices--styles">
					<?php foreach ( $willerev_styles as $willerev_slug => $willerev_label ) : ?>
						<label class="willerev-choice">
							<input type="radio" name="<?php echo esc_attr( $willerev_name ); ?>[style]" value="<?php echo esc_attr( $willerev_slug ); ?>" <?php checked( $willerev_settings['style'], $willerev_slug ); ?> />
							<span class="willerev-choice__card">
								<span class="willerev-swatch willerev-swatch--<?php echo esc_attr( $willerev_slug ); ?>" aria-hidden="true"><span></span></span>
								<span class="willerev-choice__label"><?php echo esc_html( $willerev_label ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
				<div class="willerev-fields">
					<label class="willerev-field">
						<span><?php esc_html_e( 'Accent color', 'wille-reviews' ); ?></span>
						<input type="color" name="<?php echo esc_attr( $willerev_name ); ?>[accent]" value="<?php echo esc_attr( (string) $willerev_settings['accent'] ); ?>" data-willerev="accent" />
					</label>
					<label class="willerev-field">
						<span><?php esc_html_e( 'Corner radius', 'wille-reviews' ); ?> <output data-willerev-output="radius"><?php echo esc_html( (string) (int) $willerev_settings['radius'] ); ?></output> px</span>
						<input type="range" min="0" max="32" name="<?php echo esc_attr( $willerev_name ); ?>[radius]" value="<?php echo esc_attr( (string) (int) $willerev_settings['radius'] ); ?>" data-willerev="radius" />
					</label>
				</div>
			</fieldset>

			<fieldset class="willerev-panel">
				<legend class="willerev-panel__title"><?php esc_html_e( 'Content', 'wille-reviews' ); ?></legend>
				<div class="willerev-fields">
					<label class="willerev-field" data-willerev-for="cards">
						<span><?php esc_html_e( 'Columns', 'wille-reviews' ); ?> <output data-willerev-output="columns"><?php echo esc_html( (string) (int) $willerev_settings['columns'] ); ?></output></span>
						<input type="range" min="1" max="4" name="<?php echo esc_attr( $willerev_name ); ?>[columns]" value="<?php echo esc_attr( (string) (int) $willerev_settings['columns'] ); ?>" data-willerev="columns" />
					</label>
					<label class="willerev-field" data-willerev-for="cards">
						<span><?php esc_html_e( 'Number of reviews', 'wille-reviews' ); ?> <output data-willerev-output="limit"><?php echo esc_html( (string) (int) $willerev_settings['limit'] ); ?></output></span>
						<input type="range" min="1" max="<?php echo esc_attr( (string) WILLEREV_Render::MAX_LIMIT ); ?>" name="<?php echo esc_attr( $willerev_name ); ?>[limit]" value="<?php echo esc_attr( (string) (int) $willerev_settings['limit'] ); ?>" data-willerev="limit" />
					</label>
					<label class="willerev-field" data-willerev-for="cards">
						<span><?php esc_html_e( 'Minimum stars', 'wille-reviews' ); ?></span>
						<select name="<?php echo esc_attr( $willerev_name ); ?>[min_rating]" data-willerev="min_rating">
							<option value="0" <?php selected( (int) $willerev_settings['min_rating'], 0 ); ?>><?php esc_html_e( 'All reviews', 'wille-reviews' ); ?></option>
							<option value="3" <?php selected( (int) $willerev_settings['min_rating'], 3 ); ?>>★★★+</option>
							<option value="4" <?php selected( (int) $willerev_settings['min_rating'], 4 ); ?>>★★★★+</option>
							<option value="5" <?php selected( (int) $willerev_settings['min_rating'], 5 ); ?>>★★★★★</option>
						</select>
					</label>
					<label class="willerev-field" data-willerev-for="cards">
						<span><?php esc_html_e( 'Shorten long reviews after', 'wille-reviews' ); ?></span>
						<select name="<?php echo esc_attr( $willerev_name ); ?>[lines]" data-willerev="lines">
							<?php foreach ( array( 3, 4, 5, 6, 8, 0 ) as $willerev_lines ) : ?>
								<option value="<?php echo esc_attr( (string) $willerev_lines ); ?>" <?php selected( (int) $willerev_settings['lines'], $willerev_lines ); ?>>
									<?php
									echo esc_html(
										0 === $willerev_lines
											? __( 'Never (full text)', 'wille-reviews' )
											/* translators: %d: number of lines */
											: sprintf( _n( '%d line', '%d lines', $willerev_lines, 'wille-reviews' ), $willerev_lines )
									);
									?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
				<div class="willerev-toggles">
					<label data-willerev-for="cards"><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[show_header]" value="1" data-willerev="show_header" <?php checked( 1, (int) $willerev_settings['show_header'] ); ?> /> <?php esc_html_e( 'Summary header with rating and count', 'wille-reviews' ); ?></label>
					<label data-willerev-for="cards"><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[show_cta]" value="1" data-willerev="show_cta" <?php checked( 1, (int) $willerev_settings['show_cta'] ); ?> /> <?php esc_html_e( 'Buttons "Write a review" and "See all on Google"', 'wille-reviews' ); ?></label>
					<label><input type="checkbox" name="<?php echo esc_attr( $willerev_name ); ?>[show_avatars]" value="1" data-willerev="show_avatars" <?php checked( 1, (int) $willerev_settings['show_avatars'] ); ?> /> <?php esc_html_e( 'Profile photos', 'wille-reviews' ); ?></label>
				</div>
			</fieldset>

			<div class="willerev-panel willerev-shortcode">
				<p class="willerev-panel__title"><?php esc_html_e( 'Shortcode for this design', 'wille-reviews' ); ?></p>
				<div class="willerev-shortcode__row">
					<code id="willerev-shortcode" data-willerev-shortcode>[wille_reviews]</code>
					<button type="button" class="button" data-willerev-copy><?php esc_html_e( 'Copy', 'wille-reviews' ); ?></button>
				</div>
				<p class="description"><?php esc_html_e( 'Paste it into any page, post or widget. In the block editor you can also add the block "Google Reviews" and pick the design in its sidebar.', 'wille-reviews' ); ?></p>
				<?php submit_button( __( 'Save as default design', 'wille-reviews' ), 'primary', 'submit', false ); ?>
			</div>
		</div>

		<div class="willerev-designer__preview">
			<div class="willerev-preview-bar">
				<span class="willerev-preview-bar__title"><?php esc_html_e( 'Preview', 'wille-reviews' ); ?></span>
				<span class="willerev-preview-bar__bg" role="group" aria-label="<?php esc_attr_e( 'Page background', 'wille-reviews' ); ?>">
					<button type="button" class="is-current" data-willerev-bg="light" aria-pressed="true"><?php esc_html_e( 'Light page', 'wille-reviews' ); ?></button>
					<button type="button" data-willerev-bg="dark" aria-pressed="false"><?php esc_html_e( 'Dark page', 'wille-reviews' ); ?></button>
				</span>
			</div>
			<div class="willerev-preview" data-willerev-preview>
				<?php
				foreach ( WILLEREV_Render::LAYOUTS as $willerev_layout_slug ) {
					$willerev_args = WILLEREV_Render::args(
						array(
							'layout'     => $willerev_layout_slug,
							'limit'      => WILLEREV_Render::MAX_LIMIT,
							'min_rating' => 0,
							'header'     => 'yes',
							'cta'        => 'yes',
							'avatars'    => 'yes',
							'link'       => 'none',
							'id'         => 'willerev-preview-' . $willerev_layout_slug,
						),
						$willerev_settings
					);
					?>
					<div class="willerev-preview__item" data-layout="<?php echo esc_attr( $willerev_layout_slug ); ?>"<?php echo $willerev_layout_slug === $willerev_settings['layout'] ? '' : ' hidden'; ?>>
						<?php echo WILLEREV_Render::render( $willerev_args, $willerev_data, $willerev_is_demo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the renderer. ?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</form>

	<details class="willerev-panel willerev-reference">
		<summary><?php esc_html_e( 'All shortcode options', 'wille-reviews' ); ?></summary>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Option', 'wille-reviews' ); ?></th><th><?php esc_html_e( 'Values', 'wille-reviews' ); ?></th><th><?php esc_html_e( 'Effect', 'wille-reviews' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>layout</code></td><td><code>grid</code> <code>carousel</code> <code>list</code> <code>masonry</code> <code>badge</code> <code>social</code></td><td><?php esc_html_e( 'Structure of the widget.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>style</code></td><td><code>light</code> <code>dark</code> <code>minimal</code> <code>quote</code> <code>accent</code></td><td><?php esc_html_e( 'Visual style.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>accent</code></td><td><code>#1a73e8</code></td><td><?php esc_html_e( 'Accent color for buttons, links and the accent style.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>radius</code></td><td>0–32</td><td><?php esc_html_e( 'Corner radius in pixels.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>columns</code></td><td>1–4</td><td><?php esc_html_e( 'Columns of grid, slider and wall (fewer on small screens automatically).', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>limit</code></td><td>1–10</td><td><?php esc_html_e( 'Maximum number of reviews. Google provides up to five per place.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>min_rating</code></td><td>0–5</td><td><?php esc_html_e( 'Only show reviews with at least this many stars.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>sort</code></td><td><code>newest</code> <code>rating</code></td><td><?php esc_html_e( 'Order of the reviews.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>lines</code></td><td>0–30</td><td><?php esc_html_e( 'Shorten long reviews after this many lines with a "Read more" button (0 = full text).', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>header</code> · <code>cta</code> · <code>avatars</code></td><td><code>yes</code> <code>no</code></td><td><?php esc_html_e( 'Summary header, buttons to Google, profile photos.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>link</code></td><td><code>auto</code> <code>google</code> <code>none</code> URL</td><td><?php esc_html_e( 'Target of badge and social proof. auto = your reviews page (settings), else the Google profile.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>align</code></td><td><code>left</code> <code>center</code> <code>right</code></td><td><?php esc_html_e( 'Alignment of badge and social proof.', 'wille-reviews' ); ?></td></tr>
				<tr><td><code>id</code> · <code>class</code></td><td>&nbsp;</td><td><?php esc_html_e( 'Anchor and extra CSS classes. The first reviews section on a page gets the anchor #google-reviews automatically.', 'wille-reviews' ); ?></td></tr>
			</tbody>
		</table>
	</details>
</div>
