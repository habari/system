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

	/**
	 * function set
	 * Sets the locale for the whole system
	 * @param string A locale string such as 'en' or 'en_US'
	 **/	 
	public static function set($locale)
	{
		if ( function_exists( 'bindtextdomain' ) ) {
			self::$uselocale = true;
		}
		if ( self::$uselocale ) {
			putenv( 'LANG=' . $locale );
			putenv( 'LANGUAGE=' . $locale );
			bindtextdomain( 'messages', HABARI_PATH.'/system/locale' );
			textdomain( 'messages' );
		}
	}
	
	/**
	 * function _e
	 * Echo a version of the string translated into the current locale
	 * @param string The text to echo translated
	 **/	 	 	 	
	public static function _e($text)
	{
		echo self::__($text);
	}
	
	/**
	 * function __
	 * Return a version of the string translated into the current locale
	 * @param string The text to translate
	 * @return string The string, translated
	 **/	 	 	 	 	 	
	public static function __($text)
	{
		if ( self::$uselocale ) {
			return gettext($text);
		}
		else {
			return $text;
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


?>
