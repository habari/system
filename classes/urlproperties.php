<?php
/**
 * @package Habari
 *
 */

/**
 * Habari URLProperties interface
 *
 */
interface URLProperties
{
	// must return an associative array of
	// named args/values for URL building.
	public function get_url_args();
}

?>
