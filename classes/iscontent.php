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
	 * @return string The content type of the object instance
	 */
	function content_type();

}

?>
