<?php
/**
 * Seeds WordPress for the browser tests and the directory screenshots: the plugin connected to the fake Google
 * place (tests/e2e/fake-google.php), the reviews loaded once, and pages showing every layout × a style,
 * one of them as a block. The floating badge is on.
 *
 * Writes /e2e-out/seeded last (tests/e2e/wait-for-wordpress.js waits for it).
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.WP.AlternativeFunctions
 *
 * @package Wille_Reviews
 */

require '/wordpress/wp-load.php';

$settings = array_merge(
	WILLEREV_Install::defaults(),
	array(
		'api_key'     => 'TEST-KEY',
		'place_id'    => 'ChIJtest',
		'language'    => 'de',
		'floating'    => 1,
		'reviews_url' => home_url( '/google-reviews/' ),
	)
);
update_option( 'willerev_settings', $settings );
willerev()->flush_settings_cache();
WILLEREV_Places::refresh();

$pages = array(
	'google-reviews' => array( 'Google Reviews', '<!-- wp:shortcode -->[wille_reviews]<!-- /wp:shortcode -->' ),
	'styles'         => array(
		'All layouts',
		'<!-- wp:heading --><h2 class="wp-block-heading">Grid / light</h2><!-- /wp:heading -->'
		. '<!-- wp:shortcode -->[wille_reviews layout="grid" style="light" id="grid"]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Slider / dark</h2><!-- /wp:heading -->'
		. '<!-- wp:shortcode -->[wille_reviews layout="carousel" style="dark" columns="2" id="slider"]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">List / minimal</h2><!-- /wp:heading -->'
		. '<!-- wp:shortcode -->[wille_reviews layout="list" style="minimal" min_rating="4" header="no" id="list"]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Wall / bubble</h2><!-- /wp:heading -->'
		. '<!-- wp:shortcode -->[wille_reviews layout="masonry" style="bubble" id="wall"]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Badge / accent</h2><!-- /wp:heading -->'
		. '<!-- wp:shortcode -->[wille_reviews layout="badge" style="accent" accent="#0f766e" align="center"]<!-- /wp:shortcode -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Social proof</h2><!-- /wp:heading -->'
		. '<!-- wp:shortcode -->[wille_reviews layout="social" link="none"]<!-- /wp:shortcode -->',
	),
	'block-page'     => array( 'Block', '<!-- wp:wille-reviews/reviews {"layout":"carousel","style":"accent","accent":"#7c3aed","limit":4} /-->' ),
);
foreach ( $pages as $slug => $page ) {
	wp_insert_post(
		array(
			'post_title'   => $page[0],
			'post_name'    => $slug,
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => $page[1],
		)
	);
}

file_put_contents( '/e2e-out/seeded', gmdate( 'c' ) );
