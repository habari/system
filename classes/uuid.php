<?php
/**
 * @package Habari
 *
 */

/**
 * Class to create and format UUIDs.
 *
 */
class UUID
{
	private $uuid = array();
	
	/**
	 * Create a UUID (Universally Unique IDentifier) as per RfC 4122.
	 *
	 * Currently, only version 4 UUIDs are supported (Section 4.4,
	 * "Algorithms for Creating a UUID from Truly Random or Pseudo-Random Numbers").
	 *
	 * @param int $version UUID version to generate (currently, only version 4 is supported)
	 */
	public function __construct( $version = 4 )
	{
		$uuid = array();
		for ( $i = 0; $i < 16; $i++ ) {
			$uuid[] = mt_rand( 0, 255 );
		}
		// variant (bit 6 = 1, bit 7 = 0)
		$uuid[8] = ( $uuid[8] & 0x3f ) | 0x80;
		/* // weird byte orders make my head hurt!
		// version (bits 4-7 = 0100);
		$uuid[7] = ( $uuid[7] & 0x0f ) | 0x40;
		*/
		// version (bits 12-15 = 0100)
		$uuid[6] = ( $uuid[6] & 0x0f ) | 0x40;
		
		$this->uuid = $uuid;
	}
	
	/**
	 * @return a string representation of this object.
	 */
	public function __toString()
	{
		return $this->get_hex();
	}
	
	/**
	 * @return the generated UUID as an array of bytes
	 */
	public function get_array()
	{
		return $this->uuid;
	}
	
	/**
	 * @return the generated UUID as a string of bytes
	 */
	public function get_raw()
	{
		return implode( '', array_map( 'chr', $this->uuid ) );
	}
	
	/**
	 * @return the canonical hexadecimal representation of the generated UUID
	 */
	public function get_hex()
	{
		$uuid_hex = '';
		for ( $i = 0; $i < 16; $i++ ) {
			if ( 4==$i || 6==$i || 8==$i || 10==$i ) {
				$uuid_hex.= '-';
			}
			$uuid_hex.= sprintf( '%02x', $this->uuid[$i] );
		}
		
		return $uuid_hex;
	}
	
	/**
	 * Create a UUID and return its canonical hexadecimal representation.
	 *
	 * @return the canonical hexadecimal representation of the generated UUID
	 */
	public static function get()
	{
		$uuid = new UUID();
		return $uuid->get_hex();
	}
	
}

?>
