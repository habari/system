<?php
/**
 * @package Habari
 *
 */

/**
 * IsContent Interface for Habari
 * Defines an interface for classes to return the type of content they are
 *
 * @version $Id$
 * @copyright 2008
 */
interface IsContent
{

	/**
	 * Returns the content type of the object instance
	 *
	 * @return array An array of content types that this object represents, starting with the most specific
	 */
	function content_type();

}

?>
