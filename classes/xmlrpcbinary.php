<?php
/**
 * @package Habari
 *
 */

/**
 * XMLRPC Binary type
 * Used to hold binary data for transmission in XMLRPC calls.
 *
 */
class XMLRPCBinary
{
	public $data;
	
	public function load_from_file( $filename )
	{
		$this->data = file_get_contents( $filename );
	}
	
	public function __construct( $data = null )
	{
		if ( isset( $data ) ) {
			$this->data = $data;
		}
	}
}

?>
