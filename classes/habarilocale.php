<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Locale Class
 *
 * Provides translation services.
 *
 */
class HabariLocale
{
	private static $uselocale = false;
	private static $messages = array();
	private static $plural_function;
	private static $locale;

	/**
	 * Sets the locale for Habari.
	 *
	 * @param string $locale A language code like 'en' or 'en-us' or 'x-klingon', will be lowercased
	 */
	public static function set( $locale = null )
	{
		if ( $locale == null ) {
			return;
		}

		self::$locale = strtolower( $locale );
		self::$uselocale = self::load_domain( 'habari' );
	}

	/**
	 * Set system locale.
	 *
	 * The problem is that every platform has its own way to designate a locale,
	 * so for German you could have 'de', 'de_DE', 'de_DE.UTF-8', 'de_DE.UTF-8@euro'
	 * (Linux) or 'DEU' (Windows), etc.
	 *
	 * @todo: This setting should probably be stored in the language files.
	 *
	 * @param string... $locale The locale(s) to set. They will be tried in order.
	 * @return string the locale that was picked, or false if an error occurred
	 */
	public static function set_system_locale()
	{
		if ( func_num_args() == 0 ) return;
		$args = func_get_args();
		array_unshift( $args, LC_ALL );

		return call_user_func_array( 'setlocale', $args );
	}

	/**
	 * Load translations for a given domain and base directory for a pluggable object.
	 * Translations are stored in gettext-style .mo files.
	 * The internal workings of the file format are not entirely meant to be understood.
	 *
	 * @link http://www.gnu.org/software/gettext/manual/html_node/gettext_136.html GNU Gettext Manual: Description of the MO file format
	 * @param string $domain the domain to load
	 * @param string $base_dir the base directory in which to find the translation files
	 * @return boolean true if data was successfully loaded, false otherwise
	 */
	public static function load_pluggable_domain( $domain, $base_dir )
	{
		$file = $base_dir . '/locale/' . self::$locale . '/LC_MESSAGES/' . $domain . '.mo';
		return self::load_file( $domain, $file );
	}

	/**
	 * Load translations for a given domain.
	 * Translations are stored in gettext-style .mo files.
	 * The internal workings of the file format are not entirely meant to be understood.
	 *
	 * @link http://www.gnu.org/software/gettext/manual/html_node/gettext_136.html GNU Gettext Manual: Description of the MO file format
	 * @param string $domain the domain to load
	 * @return boolean true if data was successfully loaded, false otherwise
	 */
	private static function load_domain( $domain )
	{
		$file_end = self::$locale . '/LC_MESSAGES/' . $domain . '.mo';

		if ( file_exists( Site::get_dir( 'config' ) . '/locale/' . $file_end ) ) {
			$file = Site::get_dir( 'config' ) . '/locale/' . $file_end;
		}
		else if ( file_exists( HABARI_PATH . '/user/locale/' . $file_end ) ) {
			$file = HABARI_PATH . '/user/locale/' . $file_end;
		}
		else if ( file_exists( HABARI_PATH . '/3rdparty/locale/' . $file_end ) ) {
			$file = HABARI_PATH . '/3rdparty/locale/' . $file_end;
		}
		else {
			$file = HABARI_PATH . '/system/locale/' . $file_end;
		}

		return self::load_file( $domain, $file );
	}

	/**
	 * function list_all
	 * Retrieves an array of the Habari locales that are installed
	 *
	 * @return array. An array of Habari locales in the installation
	 */
	public static function list_all()
	{
		$localedirs = array( HABARI_PATH . '/system/locale/', HABARI_PATH . '/3rdparty/locale/', HABARI_PATH . '/user/locale/' );
		if ( Site::CONFIG_LOCAL != Site::$config_type ) {
			// include site-specific locales
			$localedirs[] = Site::get_dir( 'config' ) . '/locale/';
		}

		$dirs = array();
		foreach ( $localedirs as $localedir ) {
			if ( file_exists( $localedir ) ) {
				$dirs = array_merge( $dirs, Utils::glob( $localedir . '*', GLOB_ONLYDIR | GLOB_MARK ) );
			}
		}
		$dirs = array_filter( $dirs, create_function( '$a', 'return file_exists($a . "LC_MESSAGES/habari.mo");' ) );

		$locales = array_map( 'basename', $dirs );
		ksort( $locales );
		return $locales;
	}

	/**
	 * Load translations from a given file.
	 *
	 * @param string $domain the domain to load the data into
	 * @param string $file the file name
	 * @return boolean true if data was successfully loaded, false otherwise
	 */
	private static function load_file( $domain, $file )
	{
		if ( ! file_exists( $file ) ) {
			Error::raise( sprintf( _t( 'No translations found for locale %s, domain %s!' ), self::$locale, $domain ) );
			return false;
		}
		if ( filesize( $file ) < 24 ) {
			Error::raise( sprintf( _t( 'Invalid .MO file for locale %s, domain %s!' ), self::$locale, $domain ) );
			return false;
		}

		$fp = fopen( $file, 'rb' );
		$data = fread( $fp, filesize( $file ) );
		fclose( $fp );

		// determine endianness
		$little_endian = true;

		list(,$magic) = unpack( 'V1', substr( $data, 0, 4 ) );
		switch ( $magic & 0xFFFFFFFF ) {
			case (int)0x950412de:
				$little_endian = true;
				break;
			case (int)0xde120495:
				$little_endian = false;
				break;
			default:
				Error::raise( sprintf( _t( 'Invalid magic number 0x%08x in %s!' ), $magic, $file ) );
				return false;
		}

		$revision = substr( $data, 4, 4 );
		if ( $revision != 0 ) {
			Error::raise( sprintf( _t( 'Unknown revision number %d in %s!' ), $revision, $file ) );
			return false;
		}

		$l = $little_endian ? 'V' : 'N';

		if ( $data && strlen( $data ) >= 20 ) {
			$header = substr( $data, 8, 12 );
			$header = unpack( "{$l}1msgcount/{$l}1msgblock/{$l}1transblock", $header );

			if ( $header['msgblock'] + ($header['msgcount'] - 1 ) * 8 > filesize( $file ) ) {
				Error::raise( sprintf( _t( 'Message count (%d) out of bounds in %s!' ), $header['msgcount'], $file ) );
				return false;
			}

			$lo = "{$l}1length/{$l}1offset";

			for ( $msgindex = 0; $msgindex < $header['msgcount']; $msgindex++ ) {
				$msginfo = unpack( $lo, substr( $data, $header['msgblock'] + $msgindex * 8, 8 ) );
				$msgids = explode( "\0", substr( $data, $msginfo['offset'], $msginfo['length'] ) );
				$transinfo = unpack( $lo, substr( $data, $header['transblock'] + $msgindex * 8, 8 ) );
				$transids = explode( "\0", substr( $data, $transinfo['offset'], $transinfo['length'] ) );
				self::$messages[$domain][$msgids[0]] = array(
					$msgids,
					$transids,
				);
			}
		}

		// setup plural functionality
		self::$plural_function = self::get_plural_function( self::$messages[$domain][''][1][0] );

		// only use locale if we actually read something
		return ( count( self::$messages ) > 0 );
	}

	private static function get_plural_function( $header )
	{
		if ( preg_match( '/plural-forms: (.*?)$/i', $header, $matches ) && preg_match( '/^\s*nplurals\s*=\s*(\d+)\s*;\s*plural=(.*)$/u', $matches[1], $matches ) ) {
			// sanitize
			$nplurals = preg_replace( '/[^0-9]/', '', $matches[1] );
			$plural = preg_replace( '#[^n0-9:\(\)\?\|\&=!<>+*/\%-]#', '', $matches[2] );

			$body = str_replace(
				array( 'plural',  'n',  '$n$plurals', ),
				array( '$plural', '$n', '$nplurals', ),
				'nplurals='. $nplurals . '; plural=' . $plural
			);

			// add parens
			// important since PHP's ternary evaluates from left to right
			$body .= ';';
			$res = '';
			$p = 0;
			for ( $i = 0; $i < strlen( $body ); $i++ ) {
				$ch = $body[$i];
				switch ( $ch ) {
					case '?':
						$res .= ' ? (';
						$p++;
						break;
					case ':':
						$res .= ') : (';
						break;
					case ';':
						$res .= str_repeat( ')', $p ) . ';';
						$p = 0;
						break;
					default:
						$res .= $ch;
				}
			}

			$body = $res . 'return ($plural>=$nplurals?$nplurals-1:$plural);';
			$fn = create_function(
				'$n',
				$body
			);
		}
		else {
			// default: one plural form for all cases but n==1 (english)
			$fn = create_function(
				'$n',
				'$nplurals=2;$plural=($n==1?0:1);return ($plural>=$nplurals?$nplurals-1:$plural);'
			);
		}

		return $fn;
	}

	/**
	 * DO NOT USE THIS FUNCTION.
	 * This function is only to be used by the test case for the Locale class!
	 */
	public static function __run_plural_test( $header )
	{
		$fn = self::get_plural_function( $header );
		$res = '';
		for ( $n = 0; $n < 200; $n++ ) {
			$res .= $fn( $n );
		}

		return $res;
	}

	/**
	 * DO NOT USE THIS FUNCTION.
	 * This function is only to be used by the test case for the Locale class!
	 */
	public static function __run_loadfile_test( $filename )
	{
		return self::load_file( 'test', $filename );
	}

	/**
	 * Echo a version of the string translated into the current locale
	 * @param string $text The text to echo translated
	 * @param string $domain (optional) The domain to search for the message
	 */
	public static function _e()
	{
		$args = func_get_args();
		echo call_user_func_array( array( 'HabariLocale', '_t' ), $args );
	}

	/**
	 * Return a version of the string translated into the current locale
	 *
	 * @param string $text The text to echo translated
	 * @param string $domain (optional) The domain to search for the message
	 * @return string The translated string
	 */
	public static function _t( $text, $args = array(), $domain = 'habari' )
	{
		if ( is_string( $args ) ) {
			$domain = $args;
		}

		if ( isset( self::$messages[$domain][$text] ) ) {
			$t = self::$messages[$domain][$text][1][0];
		}
		else {
			$t = $text;
		}

		if ( !empty( $args ) && is_array( $args ) ) {
			array_unshift( $args, $t );
			$t = call_user_func_array( 'sprintf', $args );
		}

		return $t;
	}

	/**
	 * Given a string translated into the current locale, return the untranslated string.
	 *
	 * @param string $text The translated string
	 * @param string $domain (optional) The domain to search for the message
	 * @return string The untranslated string
	 */
	public static function _u( $text, $domain = 'habari' )
	{
		$t = $text;
		foreach ( self::$messages[$domain] as $msg ) {
			if ( $text == $msg[1][0] ) {
				$t = $msg[0][0];
				break;
			}
		}

		return $t;
	}
	/**
	 * Echo singular or plural version of the string, translated into the current locale, based on the count provided
	 *
	 * @param string $singular The singular form
	 * @param string $plural The plural form
	 * @param string $count The count
	 * @param string $domain (optional) The domain to search for the message
	 */
	public static function _ne( $singular, $plural, $count, $domain = 'habari' )
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
	 */
	public static function _n( $singular, $plural, $count, $domain = 'habari' )
	{
		if ( isset( self::$messages[$domain][$singular] ) ) {
			// XXX workaround, but direct calling doesn't work
			$fn = self::$plural_function;
			$n = $fn( $count );
			if ( isset( self::$messages[$domain][$singular][1][$n] ) ) {
				return self::$messages[$domain][$singular][1][$n];
			}
		}
		// fall-through else for both cases
		return ( $count == 1 ? $singular : $plural );
	}
}

/**
 * Echo a version of the string translated into the current locale, alias for HabariLocale::_e()
 *
 * @param string $text The text to translate
 */
function _e( $text, $args = array(), $domain = 'habari' )
{
	return HabariLocale::_e( $text, $args, $domain );
}

/**
 * function _ne
 * Echo singular or plural version of the string, translated into the current locale, based on the count provided,
 * alias for HabariLocale::_ne()
 * @param string $singular The singular form
 * @param string $plural The plural form
 * @param string $count The count
 */
function _ne( $singular, $plural, $count, $domain = 'habari' )
{
	return HabariLocale::_ne( $singular, $plural, $count, $domain );
}

/**
 * Return a version of the string translated into the current locale, alias for HabariLocale::_t()
 *
 * @param string $text The text to translate
 * @return string The translated string
 */
function _t( $text, $args = array(), $domain = 'habari' )
{
	return HabariLocale::_t( $text, $args, $domain );
}

/**
 * Return a singular or plural string translated into the current locale based on the count provided
 *
 * @param string $singular The singular form
 * @param string $plural The plural form
 * @param string $count The count
 * @return string The appropriately translated string
 */
function _n( $singular, $plural, $count, $domain = 'habari' )
{
	return HabariLocale::_n( $singular, $plural, $count, $domain );
}

/**
 * Given a string translated into the current locale, return the untranslated version of the string.
 * Alias for HabariLocale::_u()
 *
 * @param string $text The translated string
 * @param string $domain (optional) The domain to search for the message
 * @return string The untranslated string
 */
function _u( $text, $domain = 'habari' )
{
	return HabariLocale::_u( $text, $domain );
}

?>
