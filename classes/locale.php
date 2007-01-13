<?php
/**
 * Habari Locale Class
 *
 * Provides translation services.
 * @package Habari
 */

class Locale
{
	static $uselocale = false;
	static $messages = array();
	static $locale;

	/**
	 * function set
	 * Sets the locale for the whole system
	 * @param string A locale string such as 'en' or 'en_US'
	 **/	 
	public static function set($locale)
	{
		self::$locale = $locale;
		//self::load_domain('habari');
	}
	
	/**
	 * function load_domain
	 * Loads a translation domain from a locale .mo file into the Locale class.
	 * This function reads a cryptic but common translation file format.  Its 
	 * internal workings are not entirely meant to be understood.	 
	 * @param string The domain string to load
	 **/	 	 	 	
	private static function load_domain($domain)
	{
		$file = HABARI_PATH . '/system/locale/' . self::$locale . '/LC_MESSAGES/' . $domain . '.mo';
		if ( file_exists( $file ) ) { 
			$fp = fopen($file, 'rb');
			$data = fread($fp, filesize($file));
			fclose($fp);
			
			if ( $data ) {
				$header = substr( $data, 8, 12 );
				$header = unpack( 'L1msgcount/L1msgblock/L1transblock', $header );
			
				for ( $msgindex = 0; $msgindex < $header['msgcount']; $msgindex++ ) {
					$msginfo = unpack( 'L1length/L1offset', substr( $data, $header['msgblock'] + $msgindex * 8, 8 ) );
					list( $msgid, $msgid2 ) = explode( "\0", substr( $data, $msginfo['offset'], $msginfo['length'] ) );
					$transinfo = unpack( 'L1length/L1offset', substr( $data, $header['transblock'] + $msgindex * 8, 8 ) );
					list( $trans, $trans2 ) = explode( "\0", substr( $data, $transinfo['offset'], $transinfo['length'] ) );
					self::$messages[$domain][$msgid] = array(
						array( $msgid, $msgid2 ),
						array( $trans, $trans2 )
					);
				}
			}
		}
	}
	
	/**
	 * function _e
	 * Echo a version of the string translated into the current locale
	 * @param string The text to echo translated
	 * @param string The domain to search for the message	 
	 **/	 	 	 	
	public static function _e($text, $domain = 'habari')
	{
		echo self::__($text);
	}
	
	/**
	 * function __
	 * Return a version of the string translated into the current locale
	 * @param string The text to translate
	 * @param string The domain to search for the message	 
	 * @return string The string, translated
	 **/	 	 	 	 	 	
	public static function __($text, $domain = 'habari')
	{
		if ( isset( self::$messages[$domain][$text] ) ) {
			return self::$messages[$domain][$text][1][0];
		}
		else {
			return $text;
		}
	}
	
	/**
	 * function _n
	 * Return a singular or plural string translated into the current 
	 * locale based on the count provided.
	 * @param string The singular form
	 * @param string The plural form
	 * @param string The count
	 * @param string The domain to search for the message	 
	 * @return string The appropriately translated string
	 **/       
	public static function _n( $singular, $plural, $count, $domain= 'habari' )
	{
		if ( isset( self::$messages[$domain][$singular] ) ) {
			return ( $count == 1 ? self::$messages[$domain][$singular][1][0] : self::$messages[$domain][$singular][1][1] );
		}
		else {
			return ( count == 1 ? $singular : $plural );
		}
	}
}

/**
 * function _e
 * Echo a version of the string translated into the current locale
 * Alias for Locale::_e() 
 * @param string The text to echo translated
 **/	 	 	 	
function _e($text)
{
	return Locale::_e($text);
}

/**
 * function __
 * Return a version of the string translated into the current locale
 * Alias for Locale::__() 
 * @param string The text to translate
 * @return string The string, translated
 **/	 	 	 	 	 	
function __($text)
{
	return Locale::__($text);
}

/**
 * function _n
 * Return a singular or plural string translated into the current 
 * locale based on the count provided.
 * @param string The singular form
 * @param string The plural form
 * @param string The count
 * @return string The appropriately translated string
 **/       
function _n($singular, $plural, $count)
{
	return Locale::_n($singular, $plural, $count);
}


?>
