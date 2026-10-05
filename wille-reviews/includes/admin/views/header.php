<?php
/**
 * Brand bar shown on top of every Wille Reviews screen: logo, navigation between the plugin's pages, author credit.
 *
 * Rendered by WILLEREV_Admin::header(), which records the current page.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$willerev_current = WILLEREV_Admin::current_page();
$willerev_tabs    = array(
	'wille-reviews'          => __( 'Design', 'wille-reviews' ),
	'wille-reviews-settings' => __( 'Settings', 'wille-reviews' ),
);
?>
<header class="willerev-brandbar">
	<a class="willerev-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=wille-reviews' ) ); ?>">
		<svg class="willerev-logo" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
			<path d="M18 14h64a12 12 0 0 1 12 12v40a12 12 0 0 1-12 12H44L26 92V78h-8A12 12 0 0 1 6 66V26a12 12 0 0 1 12-12Z" fill="#fff" fill-opacity="0.16" stroke="#fff" stroke-width="5" stroke-linejoin="round"/>
			<path d="M50 24l7.2 14.6 16.1 2.3-11.6 11.4 2.7 16L50 60.8l-14.4 7.5 2.7-16-11.6-11.4 16.1-2.3Z" fill="#fbbc04" stroke="#fff" stroke-width="3" stroke-linejoin="round"/>
		</svg>
		<span class="willerev-brand-name"><?php esc_html_e( 'Wille Reviews', 'wille-reviews' ); ?></span>
		<span class="willerev-brand-version"><?php echo esc_html( WILLEREV_VERSION ); ?></span>
	</a>
	<nav class="willerev-tabs" aria-label="<?php esc_attr_e( 'Wille Reviews', 'wille-reviews' ); ?>">
		<?php foreach ( $willerev_tabs as $willerev_slug => $willerev_label ) : ?>
			<a class="willerev-tab<?php echo $willerev_slug === $willerev_current ? ' is-current' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $willerev_slug ) ); ?>"<?php echo $willerev_slug === $willerev_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $willerev_label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<a class="willerev-byline" href="<?php echo esc_url( WILLEREV_Admin::AUTHOR_URL ); ?>" target="_blank" rel="noopener">
		<?php
		/* translators: %s: author name */
		echo esc_html( sprintf( __( 'by %s', 'wille-reviews' ), WILLEREV_Admin::AUTHOR ) );
		?>
		<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'wille-reviews' ); ?></span>
	</a>
</header>
