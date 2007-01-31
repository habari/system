<?php

/**
 * Habari Locale Class
 *
 * Provides translation services.
 * 
 * @package Habari
 */
class Locale
{
	private static $uselocale= FALSE;
	private static $messages= array();
	private static $locale;

	/**
	 * Sets the locale for Habari.
	 * 
	 * @param string $locale A language code like 'en' or 'en-us' or 'x-klingon', will be lowercased
	 **/	 
	public static function set( $locale= NULL )
	{
		if ( $locale == NULL ) {
			return;
		}
		
		self::$locale= strtolower( $locale );
		self::$uselocale= self::load_domain( 'habari' );
	}
	
	/**
	 * Load translations for a given domain.
	 * Translations are stored in gettext-style .mo files.
	 * The internal workings of the file format are not entirely meant to be understood.
	 * 	 
	 * @param string $domain The domain to load
	 **/	 	 	 	
	private static function load_domain( $domain )
	{
		$file= HABARI_PATH . '/system/locale/' . self::$locale . '/LC_MESSAGES/' . $domain . '.mo';
		if ( file_exists( $file ) ) { 
			$fp= fopen( $file, 'rb' );
			$data= fread( $fp, filesize( $file ) );
			fclose( $fp );
			
			// @todo TODO check magic number
			if ( $data && strlen( $data ) >= 20 ) {
				$header= substr( $data, 8, 12 );
				$header= unpack( 'L1msgcount/L1msgblock/L1transblock', $header );
			
				for ( $msgindex= 0; $msgindex < $header['msgcount']; $msgindex++ ) {
					$msginfo= unpack( 'L1length/L1offset', substr( $data, $header['msgblock'] + $msgindex * 8, 8 ) );
					// list( $msgid, $msgid2 )= explode( "\0", substr( $data, $msginfo['offset'], $msginfo['length'] ) );
					// FIXME: temporary fix to stop the offset notice
					$msgids= explode( "\0", substr( $data, $msginfo['offset'], $msginfo['length'] ) );
					$transinfo= unpack( 'L1length/L1offset', substr( $data, $header['transblock'] + $msgindex * 8, 8 ) );
					// list( $trans, $trans2 )= explode( "\0", substr( $data, $transinfo['offset'], $transinfo['length'] ) );
					// FIXME: temporary fix to stop the offset notice
					$transids= explode( "\0", substr( $data, $transinfo['offset'], $transinfo['length'] ) );
					self::$messages[$domain][$msgids[0]]= array(
						$msgids,
						$transids
					);
				}
			}
			// only use locale if we actually read something
			return ( count( self::$messages ) > 0 );
		}
		
		return FALSE;
	}
	
	/**
	 * Echo a version of the string translated into the current locale
	 * @param string $text The text to echo translated
	 * @param string $domain (optional) The domain to search for the message	 
	 **/	 	 	 	
	public static function _e( $text, $domain= 'habari' )
	{
		echo self::_t( $text );
	}
	
	/**
	 * Return a version of the string translated into the current locale
	 * 
	 * @param string $text The text to echo translated
	 * @param string $domain (optional) The domain to search for the message	 
	 * @return string The translated string
	 **/	 	 	 	 	 	
	public static function _t( $text, $domain= 'habari' )
	{
		if ( isset( self::$messages[$domain][$text] ) ) {
			return self::$messages[$domain][$text][1][0];
		}
		else {
			return $text;
		}
	}
	
	/**
	 * Echo singular or plural version of the string, translated into the current locale, based on the count provided
	 * 
	 * @param string $singular The singular form
	 * @param string $plural The plural form
	 * @param string $count The count
	 * @param string $domain (optional) The domain to search for the message	 
	 **/	 	 	 	
	public static function _ne( $singular, $plural, $count, $domain= 'habari' )
	{
		echo self::_n( $singular, $plural, $count, $domain );
	}
	
	/**
	 * Return a singular or plural string translated into the current locale based on the count provided
	 * 
	 * @param string $singular The singular form
	 * @param string $plural The plural form
	 * @param string $count The count
	 * @param string $domain (optional) The domain to search for the message	 
	 * @return string The appropriately translated string
	 **/       
	public static function _n($singular, $plural, $count, $domain= 'habari')
	{
		if ( isset( self::$messages[$domain][$singular] ) ) {
			return ( $count == 1 ? self::$messages[$domain][$singular][1][0] : self::$messages[$domain][$singular][1][1] );
		}
		else {
			return ( $count == 1 ? $singular : $plural );
		}
	}
}

/**
 * Echo a version of the string translated into the current locale, alias for Locale::_e() 
 * 
 * @param string $text The text to translate
 **/	 	 	 	
function _e( $text )
{
	return Locale::_e( $text );
}

/**
 * function _ne
 * Echo singular or plural version of the string, translated into the current locale, based on the count provided,
 * alias for Locale::_ne()
 * @param string $singular The singular form
 * @param string $plural The plural form
 * @param string $count The count
 **/	 	
function _ne( $singular, $plural, $count )
{
	return Locale::_ne( $singular, $plural, $count );
}

/**
 * Return a version of the string translated into the current locale, alias for Locale::_t()
 *  
 * @param string $text The text to translate
 * @return string The translated string
 **/	 	 	 	 	 	
function _t( $text )
{
	return Locale::_t( $text );
}

/**
 * Return a singular or plural string translated into the current locale based on the count provided
 * 
 * @param string $singular The singular form
 * @param string $plural The plural form
 * @param string $count The count
 * @return string The appropriately translated string
 **/       
function _n( $singular, $plural, $count )
{
	return Locale::_n( $singular, $plural, $count );
}
?>