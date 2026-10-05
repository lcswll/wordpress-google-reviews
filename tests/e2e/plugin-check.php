<?php
/**
 * Runs the static (non-PHPCS) checks of the official Plugin Check plugin inside Playground
 * and writes the findings to /e2e-out/plugin-check.json.
 *
 * The PHPCS-based Plugin Check rules run outside Playground (scripts/plugin-check.mjs) because
 * php-wasm cannot take the file locks PHPCS uses for its temp reports.
 *
 * @package Wille_Reviews
 */

require '/wordpress/wp-load.php';

$willerev_checks = array(
	'code_obfuscation',
	'plugin_content',
	'file_type',
	'plugin_header_fields',
	'plugin_updater',
	'plugin_uninstall',
	'plugin_readme',
	'no_unfiltered_uploads',
	'trademarks',
	'direct_file_access',
	'external_admin_menu_links',
	'wp_functions_compatibility',
);

$willerev_runner = new WordPress\Plugin_Check\Checker\AJAX_Runner();
$willerev_runner->set_plugin( 'wille-reviews/wille-reviews.php' );
$willerev_runner->set_check_slugs( $willerev_checks );
$willerev_runner->set_experimental_flag( true );
$willerev_cleanup = $willerev_runner->prepare();
$willerev_result  = $willerev_runner->run();
$willerev_cleanup();

$willerev_findings = array();
foreach ( array(
	'ERROR'   => $willerev_result->get_errors(),
	'WARNING' => $willerev_result->get_warnings(),
) as $willerev_type => $willerev_files ) {
	foreach ( $willerev_files as $willerev_file => $willerev_lines ) {
		foreach ( $willerev_lines as $willerev_line => $willerev_columns ) {
			foreach ( $willerev_columns as $willerev_messages ) {
				foreach ( $willerev_messages as $willerev_message ) {
					$willerev_findings[] = array(
						'type'     => $willerev_type,
						'file'     => $willerev_file,
						'line'     => $willerev_line,
						'code'     => $willerev_message['code'],
						'message'  => wp_strip_all_tags( $willerev_message['message'] ),
						'severity' => $willerev_message['severity'] ?? 5,
					);
				}
			}
		}
	}
}

file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test harness output.
	'/e2e-out/plugin-check.json',
	wp_json_encode(
		array(
			'checks'   => $willerev_checks,
			'findings' => $willerev_findings,
		),
		JSON_PRETTY_PRINT
	)
);
