<?php
/**
 * Test double for the plugin accessor willerev() (defined in wille-reviews.php, which boots every module).
 *
 * @package Wille_Reviews
 */

/**
 * @return WILLEREV_Test_Plugin
 */
function willerev() {
	return new WILLEREV_Test_Plugin();
}
