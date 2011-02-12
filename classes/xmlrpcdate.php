<?php
/**
 * @package Habari
 *
 */

/**
 * XMLRPC Date type
 * Used to hold dates for transmission in XMLRPC calls.
 *
 */
class XMLRPCDate
{
	private $rpcdate;
	
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'date':
				if ( is_numeric( $value ) ) {
					$this->rpcdate = $value;
				}
				else {
					$this->rpcdate = strtotime( $value );
				}
		}
	}
	
	public function __get( $name )
	{
		switch ( $name ) {
			case 'date':
				return $this->rpcdate;
		}
	}
	
	public function __construct( $date = null )
	{
		if ( isset( $date ) ) {
			$this->date = $date;
		}
	}
}

?>
