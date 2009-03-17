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
		if ( ! preg_match('/^[0-9a-f]+$/i', $hex_string ) ) {
			return Error::raise( _t('Not a valid hex color.') );
		}
		
		$normalized = '';
		
		switch ( strlen( $hex_string ) ) {
			case 3:
				// 'fed' = 'ffeedd'
				for ( $i = 0; $i < 3; $i++ ) {
					$normalized.= $hex_string{$i} . $hex_string{$i};
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
				return Error::raise( _t('Not a valid color format.') );
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
			$H = 0;
			$S = 0;
			$V = 0;
		}
		else {
			if ( $max == $rgb_arr['r'] ) {
				// reddish (YM)
				$H = ( $rgb_arr['g'] - $rgb_arr['b'] ) / $d;
			}
			elseif ( $max == $rgb_arr['g'] ) {
				// greenish (CY)
				$H = 2 + ( $rgb_arr['b'] - $rgb_arr['r'] ) / $d;
			}
			elseif ( $max == $rgb_arr['b'] ) {
				// bluish (MC)
				$H = 4 + ( $rgb_arr['r'] - $rgb_arr['g'] ) / $d;
			}
			else {
				Error::raise( _t('Something went terribly wrong here.') );
			}
			
			$H*= 60; // convert to deg
			$H = ( $H + 360 ) % 360; // map to 0..359
			
			$S = 100 * $d / $max;
			$V = $max;
		}
	
		return self::hsv_hsvarr( $H, $S, $V );
	}
	
	/**
	 * Convert a HSV array to RGB.
	 */
	public static function hsv_rgb( $hsv_arr )
	{
		if ( $hsv_arr['s'] == 0 ) {
			// grey
			$R = $G = $B = $hsv_arr['v'];
		}
		else {
			$H = $hsv_arr['h'] / 60; // degrees to sectors
			$S = $hsv_arr['s'] / 100; // percent to fraction
			$f = $H - floor( $H );
			
			$p = $hsv_arr['v'] * ( 1 - $S );
			$q = $hsv_arr['v'] * ( 1 - $S * $f );
			$t = $hsv_arr['v'] * ( 1 - $S * ( 1 - $f ) );
			
			switch ( floor( $H ) ) {
				case 0: // first sector
					$R = $hsv_arr['v'];
					$G = $t;
					$B = $p;
					break;
				case 1: // second sector
					$R = $q;
					$G = $hsv_arr['v'];
					$B = $p;
					break;
				case 2: // third sector
					$R = $p;
					$G = $hsv_arr['v'];
					$B = $t;
					break;
				case 3: // fourth sector
					$R = $p;
					$G = $q;
					$B = $hsv_arr['v'];
					break;
				case 4: // fifth sector
					$R = $t;
					$G = $p;
					$B = $hsv_arr['v'];
					break;
				case 5: // sixth sector
					$R = $hsv_arr['v'];
					$G = $p;
					$B = $q;
					break;
			}
		}
	
		return self::rgb_rgbarr( $R, $G, $B );
	}
}

?>
