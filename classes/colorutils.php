<?php
/**
 * @package Habari
 *
 */

/**
 * Various color utility functions.
 */
class ColorUtils
{
	/**
	 * Convert RGB args to RGB array.
	 */
	public static function rgb_rgbarr( $r, $g, $b )
	{
		return array_map( 'round', array( 'r' => $r, 'g' => $g, 'b' => $b ) );
	}
	
	/**
	 * Convert HSV args to HSV array.
	 */
	public static function hsv_hsvarr( $h, $s, $v )
	{
		return array_map( 'round', array( 'h' => $h, 's' => $s, 'v' => $v ) );
	}
	
	/**
	 * Converts a HTML style hex string ('#7f6699') to an RGB array.
	 */
	public static function hex_rgb( $hex_string )
	{
		$hex_string = ltrim( $hex_string, '#' );
		if ( ! preg_match( '/^[0-9a-f]+$/i', $hex_string ) ) {
			return Error::raise( _t( 'Not a valid hex color.' ) );
		}
		
		$normalized = '';
		
		switch ( strlen( $hex_string ) ) {
			case 3:
				// 'fed' = 'ffeedd'
				for ( $i = 0; $i < 3; $i++ ) {
					$normalized .= $hex_string{$i} . $hex_string{$i};
				}
				break;
			case 6:
				// already normal
				$normalized = $hex_string;
				break;
			case 2:
			case 4:
				// who uses this anyway!
				$normalized = $hex_string . str_repeat( '0', 6 - strlen( $hex_string ) );
				break;
			default:
				return Error::raise( _t( 'Not a valid color format.' ) );
		}
		
		return self::rgb_rgbarr(
			hexdec( substr( $normalized, 0, 2 ) ), 
			hexdec( substr( $normalized, 2, 2 ) ),
			hexdec( substr( $normalized, 4, 2 ) )
		);
	}
	
	/**
	 * Convert an RGB array to a HTML style hex color.
	 */
	public static function rgb_hex( $rgb_arr )
	{
		$hex_string = sprintf( '%02x%02x%02x', $rgb_arr['r'], $rgb_arr['g'], $rgb_arr['b'] );
		
		return $hex_string;
	}
	
	/**
	 * Convert an RGB array to a HSV array.
	 */
	public static function rgb_hsv( $rgb_arr )
	{
		$min = min( $rgb_arr );
		$max = max( $rgb_arr );
		
		$d = $max - $min;
		
		if ( $max == 0 ) {
			// black
			// we use 0/0/0, even though at black, H and V are undefined
			$h = 0;
			$s = 0;
			$v = 0;
		}
		else {
			if ( $max == $rgb_arr['r'] ) {
				// reddish (YM)
				$h = ( $rgb_arr['g'] - $rgb_arr['b'] ) / $d;
			}
			elseif ( $max == $rgb_arr['g'] ) {
				// greenish (CY)
				$h = 2 + ( $rgb_arr['b'] - $rgb_arr['r'] ) / $d;
			}
			elseif ( $max == $rgb_arr['b'] ) {
				// bluish (MC)
				$h = 4 + ( $rgb_arr['r'] - $rgb_arr['g'] ) / $d;
			}
			else {
				Error::raise( _t( 'Something went terribly wrong here.' ) );
			}
			
			$h*= 60; // convert to deg
			$h = ( $h + 360 ) % 360; // map to 0..359
			
			$s = 100 * $d / $max;
			$v = $max;
		}
	
		return self::hsv_hsvarr( $h, $s, $v );
	}
	
	/**
	 * Convert a HSV array to RGB.
	 */
	public static function hsv_rgb( $hsv_arr )
	{
		if ( $hsv_arr['s'] == 0 ) {
			// grey
			$r = $g = $b = $hsv_arr['v'];
		}
		else {
			$h = $hsv_arr['h'] / 60; // degrees to sectors
			$s = $hsv_arr['s'] / 100; // percent to fraction
			$f = $h - floor( $h );
			
			$p = $hsv_arr['v'] * ( 1 - $s );
			$q = $hsv_arr['v'] * ( 1 - $s * $f );
			$t = $hsv_arr['v'] * ( 1 - $s * ( 1 - $f ) );
			
			switch ( floor( $h ) ) {
				case 0: // first sector
					$r = $hsv_arr['v'];
					$g = $t;
					$b = $p;
					break;
				case 1: // second sector
					$r = $q;
					$g = $hsv_arr['v'];
					$b = $p;
					break;
				case 2: // third sector
					$r = $p;
					$g = $hsv_arr['v'];
					$b = $t;
					break;
				case 3: // fourth sector
					$r = $p;
					$g = $q;
					$b = $hsv_arr['v'];
					break;
				case 4: // fifth sector
					$r = $t;
					$g = $p;
					$b = $hsv_arr['v'];
					break;
				case 5: // sixth sector
					$r = $hsv_arr['v'];
					$g = $p;
					$b = $q;
					break;
			}
		}
	
		return self::rgb_rgbarr( $r, $g, $b );
	}
}

?>
