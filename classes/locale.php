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
	private static $plural_function;
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
	 * @link http://www.gnu.org/software/gettext/manual/html_node/gettext_136.html GNU Gettext Manual: Description of the MO file format
	 * @param string $domain the domain to load
	 * @return boolean TRUE if data was successfully loaded, FALSE otherwise
	 **/	 	 	 	
	private static function load_domain( $domain )
	{
		$file= HABARI_PATH . '/system/locale/' . self::$locale . '/LC_MESSAGES/' . $domain . '.mo';
		
		return self::load_file( $domain, $file );
	}
	
	/**
	 * Load translations from a given file.
	 * 
	 * @param string $domain the domain to load the data into
	 * @param string $file the file name
	 * @return boolean TRUE if data was successfully loaded, FALSE otherwise
	 */
	private static function load_file( $domain, $file ) {
		if ( ! file_exists( $file ) ) {
			Error::raise( sprintf( 'No translations found for locale %s, domain %s!', self::$locale, $domain ) );
			return FALSE;
		}
		if ( filesize( $file ) < 24 ) {
			Error::raise( sprintf( 'Invalid .MO file for locale %s, domain %s!', self::$locale, $domain ) );
			return FALSE;
		}
		
		$fp= fopen( $file, 'rb' );
		$data= fread( $fp, filesize( $file ) );
		fclose( $fp );
		
		// determine endianness
		$little_endian= TRUE;
		
		$magic= unpack( 'V1', substr( $data, 0, 4 ) );
		$magic= $magic[1];
		switch ( $magic ) {
			case (int)0x950412de:
				$little_endian= TRUE;
				break;
			case (int)0xde120495:
				$little_endian= FALSE;
				break;
			default:
				Error::raise( sprintf( 'Invalid magic number 0x%08x in %s!', $magic, $file ) );
				return FALSE;
		}
		
		$revision= substr( $data, 4, 4 );
		if ( $revision != 0 ) {
			Error::raise( sprintf( 'Unknown revision number %d in %s!', $revision, $file ) );
			return FALSE;
		}
		
		$l= $little_endian ? 'V' : 'N';
		
		if ( $data && strlen( $data ) >= 20 ) {
			$header= substr( $data, 8, 12 );
			$header= unpack( "{$l}1msgcount/{$l}1msgblock/{$l}1transblock", $header );
			
			if ( $header['msgblock'] + ($header['msgcount'] - 1 ) * 8 > filesize( $file ) ) {
				Error::raise( sprintf( 'Message count (%d) out of bounds in %s!', $header['msgcount'], $file ) );
				return FALSE;
			}
			
			$lo= "{$l}1length/{$l}1offset";
		
			for ( $msgindex= 0; $msgindex < $header['msgcount']; $msgindex++ ) {
				$msginfo= unpack( $lo, substr( $data, $header['msgblock'] + $msgindex * 8, 8 ) );
				$msgids= explode( "\0", substr( $data, $msginfo['offset'], $msginfo['length'] ) );
				$transinfo= unpack( $lo, substr( $data, $header['transblock'] + $msgindex * 8, 8 ) );
				$transids= explode( "\0", substr( $data, $transinfo['offset'], $transinfo['length'] ) );
				self::$messages[$domain][$msgids[0]]= array(
					$msgids,
					$transids,
				);
			}
		}
		
		// setup plural functionality
		self::$plural_function= self::get_plural_function( self::$messages[$domain][''][1][0] );

		// only use locale if we actually read something
		return ( count( self::$messages ) > 0 );
	}
	
	private static function get_plural_function( $header )
	{
		if (preg_match('/plural-forms: (.*?)$/i', $header, $matches)) {
			// sanitize
			$plural_forms= preg_replace(
				'@[^a-zA-Z0-9_:;\(\)\?\|\&=!<>+*/\%-]@',
				'',
				$matches[1]
			);
			$body= str_replace(
				array('plural',  'n',  '$n$plurals', ),
				array('$plural', '$n', '$nplurals', ),
				$plural_forms
			);
			
			// add parens
			// important since PHP's ternary evaluates from left to right
			$body.= ';';
			$res= '';
			$p= 0;
			for ($i= 0; $i < strlen($body); $i++) {
				$ch= $body[$i];
				switch ($ch) {
					case '?':
						$res.= ' ? (';
						$p++;
						break;
					case ':':
						$res.= ') : (';
						break;
					case ';':
						$res.= str_repeat( ')', $p) . ';';
						$p= 0;
						break;
					default:
						$res.= $ch;
				}
			}
			
			$body= $res . 'return ($plural>=$nplurals?$nplurals-1:$plural);';
			$fn= create_function(
				'$n',
				$body
			);
		}
		else {
			// default: one plural form for all cases but n==1 (english)
			$fn= create_function(
				'$n',
				'$nplurals=2;$plural=($n==1?0:1);return ($plural>=$nplurals?$nplurals-1:$plural);'
			);
		}
		
		return $fn;
	}
	
	public static function run_plural_test( $header )
	{
		$fn= self::get_plural_function( $header );
		$res= '';
		for ($n= 0; $n < 200; $n++) {
			$res.= $fn($n);
		}
		
		return $res;
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
			// XXX workaround, but direct calling doesn't work
			$fn= self::$plural_function;
			$n= $fn($count);
			if ( isset( self::$messages[$domain][$singular][1][$n] ) ) {
				return self::$messages[$domain][$singular][1][$n];
			}
		}
		// fall-through else for both cases
		return ( $count == 1 ? $singular : $plural );
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
