<?php
/*
 * @package Habari
 *
 */

/*
 * Habari MultiByte Class
 *
 * Provides multibyte character set services,
 * a necessity since all of Habari's internal string
 * manipulations are done in UTF-8. Currently
 * this class is a wrapper around mbstring functions.
 *
 */
class MultiByte
{

	const USE_MBSTRING = 0;

	/*
	* @var $hab_enc String holding the current encoding the class is using
	*/
	static $hab_enc = 'UTF-8';
	/*
	* @var $use_library Integer denoting the current multibyte
	* library the class is using
	*/
	private static $use_library = self::USE_MBSTRING;

	/**
	* function __construct
	*
	* An empty constructor since all functions are static
	*/
	private function __construct()
	{
	}

	/*
	* function hab_encoding
	*
	* Sets and returns the internal encoding.
	*
	* @param $use_enc string. The encoding to be used
	*
	* @return string. If $enc is null, returns the current
	* encoding. If $enc is not null, returns the old encoding
	*/
	public static function hab_encoding( $use_enc = null )
	{
		if ( $use_enc === null ) {
			return self::$hab_enc;
		}
		else {
			$old_enc = self::$hab_enc;
			self::$hab_enc = $use_enc;
			return $old_enc;
		}
	}

	/*
	* function library
	*
	* Sets and returns the multibyte library being used internally
	*
	* @param $int The new library to use.
	*
	* @return mixed  If $new_library is null, returns the current library
	* being used. If $new_library has a valid value, returns the old library,
	* else returns false.
	*/
	public static function library( $new_library = null )
	{
		if ( $new_library === null ) {
			return self::$use_library;
		}
		else if ($new_library === self::USE_MBSTRING ) {
			$old_library = self::$use_library;
			self::$use_library = $new_library;
			return $old_library;

		}
		else {
			return false;
		}
	}

	/*
	* function convert_encoding
	*
	* Converts a string's encoding to a new encoding
	*
	* @param $str string. The string who's encoding is being changed.
	* @param $use_enc string. The encoding to convert to. If not set,
	* the internal encoding will be used.
	* @param $from_enc string. encoding before conversion. If not set,
 	* encoding is detected automatically.
	*
	* @return mixed  The  source string in the new encoding or boolean false.
	*/
	public static function convert_encoding( $str, $use_enc = null, $from_enc = null )
	{
		$ret = false;

		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			if ( $from_enc == null ) {
				$from_enc = MultiByte::detect_encoding( $str );
			}
			$ret = mb_convert_encoding( $str, $enc, $from_enc );
		}

		return $ret;
	}

	/*
	* function detect_encoding
	*
	* Detects the encoding being used for a string
	*
	* @param $str string. The string who's encoding is being detected
	*
	* @return mixed The  source string's detected encoding, or boolean false.
	*/
	public static function detect_encoding( $str )
	{
		$enc = false;

		if ( self::$use_library == self::USE_MBSTRING ) {
			// get original detection order
			$old_order = mb_detect_order();
			// make sure  ISO-8859-1 is included
			mb_detect_order( array( 'ASCII', 'JIS', 'UTF-8', 'ISO-8859-1', 'EUC-JP', 'SJIS' ) );
			//detect the encoding . the detected encoding may be wrong, but it's better than guessing
			$enc = mb_detect_encoding( $str );
			// reset detection order
			mb_detect_order( $old_order );
		}

		return $enc;
	}

	/*
	* function substr
	*
	* Get a section of a string
	*
	* @param $str string. The original string
	* @param $begin. integer. The beginning character of the string to return.
	* @param $len integer. How long the returned string should be. If $len is
	* not set, the section of the string from $begin to the end of the string is
	* returned.
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return mixed The  section of the source string requested in the encoding requested or false.
	* If $len is not set, returns substring from $begin to end of string.
	*
	*/
	public static function substr( $str, $begin, $len = null, $use_enc = null )
	{
		$ret = false;

		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			if ( ! isset( $len ) ) {
				$len = MultiByte::strlen( $str ) - $begin;
			}
			$ret = mb_substr( $str, $begin, $len, $enc );
		}
		else {
			$ret = substr( $str, $begin, $len );
		}
		return $ret;
	}

	/*
	* function strlen
	*
	* Gets the length of a string in characters
	*
	* @param $str string. The string who's length is being returned.
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return integer. The length in characters of the string, or the length in bytes if a valid
	* multibyte library isn't loaded.
	*/
	public static function strlen( $str, $use_enc = null )
	{
		$len = 0;

		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$len = mb_strlen( $str, $enc );
		}
		else {
			$len = strlen( $str );
		}

		return $len;
	}

	/*
	* function strpos
	*
	* Find position of first occurrence of string in a string
	*
	* @param $haysack string. The string being checked.
	* @param $needle. string. The position counted from the beginning of haystack .
	* @param $offset integer. The search offset. If it is not specified, 0 is used.
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return mixed The  section of the source string requested in the encoding requested or false.
	* If $len is not set, returns substring from $begin to end of string.
	*
	*/
	public static function strpos( $haysack, $needle, $offset = 0, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$ret = mb_strpos( $haysack, $needle, $offset, $enc );
		}
		else {
			$ret = strpos($haysack, $needle, $offset);
		}
		return $ret;
	}

	/*
	* function stripos
	*
	* Find position of first occurrence of string in a string. Case insensitive.
	*
	* @param $haysack string. The string being checked.
	* @param $needle. string. The position counted from the beginning of haystack .
	* @param $offset integer. The search offset. If it is not specified, 0 is used.
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return mixed The  section of the source string requested in the encoding requested or false.
	* If $len is not set, returns substring from $begin to end of string.
	*
	*/
	public static function stripos( $haysack, $needle, $offset = 0, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$ret = mb_stripos( $haysack, $needle, $offset, $enc );
		}
		else {
			$ret = stripos($haysack, $needle, $offset);
		}
		return $ret;
	}

	/*
	* function strrpos
	*
	* Find position of last occurrence of string in a string.
	*
	* @param $haysack string. The string being checked.
	* @param $needle. string. The position counted from the beginning of haystack .
	* @param $offset integer. The search offset. If it is not specified, 0 is used.
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return mixed The  section of the source string requested in the encoding requested or false.
	* If $len is not set, returns substring from $begin to end of string.
	*
	*/
	public static function strrpos( $haysack, $needle, $offset = 0, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$ret = mb_strrpos( $haysack, $needle, $offset, $enc );
		}
		else {
			$ret = strrpos($haysack, $needle, $offset);
		}
		return $ret;
	}

	/*
	* function strripos
	*
	* Find position of last occurrence of string in a string. Case insensitive.
	*
	* @param $haysack string. The string being checked.
	* @param $needle. string. The position counted from the beginning of haystack .
	* @param $offset integer. The search offset. If it is not specified, 0 is used.
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return mixed The  section of the source string requested in the encoding requested or false.
	* If $len is not set, returns substring from $begin to end of string.
	*
	*/
	public static function strripos( $haysack, $needle, $offset = 0, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$ret = mb_strripos( $haysack, $needle, $offset, $enc );
		}
		else {
			$ret = strripos($haysack, $needle, $offset);
		}
		return $ret;
	}

	/*
	 * function strtolower
	 *
	 * Converts a multibyte string to lowercase. If a valid multibyte library
	* isn't loaded, strtolower() will be used, which can lead to unexpected results.
	 *
	 * @param $str string. The string to lowercase
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	 *
	 * @return string. The lowercased string.
	*/
	public static function strtolower( $str, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$ret = mb_strtolower( $str, $enc );
		}
		else {
			$ret = strtolower( $str );
		}

		return $ret;
	}

	/*
	* function strtoupper
	*
	* Converts a multibyte string to uppercase. If a valid multibyte library
	* isn't loaded, strtoupper() will be used, which can lead to unexpected results.
	*
	* @param $str string. The string to uppercase
	* @param $use_enc string. The encoding to be used. If not set,
	* the internal encoding will be used.
	*
	* @return string. The uppercased string.
	*/
	public static function strtoupper( $str, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}

		if ( self::$use_library == self::USE_MBSTRING ) {
			$ret = mb_strtoupper( $str, $enc );
		}
		else {
			$ret = strtoupper( $str );
		}

		return $ret;
	}

	/**
	 * Determines if the passed string is valid character data (according to mbstring)
	 *
	 * @param string $str the string to check
	 * @return bool
	 */
	public static function valid_data( $str )
	{
		return mb_check_encoding( $str, self::$hab_enc );
	}
	
	/**
	 * Makes a string's first character uppercase
	 * 
	 * @see http://php.net/ucfirst
	 * @param string $str The string to capitalize.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The capitalized string.
	 */
	public static function ucfirst ( $str, $use_enc = null ) {
		
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
			
			// get the first character
			$first = self::substr($str, 0, 1, $enc);
			
			// uppercase it
			$first = self::strtoupper($first, $enc);
			
			// get the rest of the characters
			$last = self::substr($str, 1, null, $enc);
			
			// put them back together
			$ret = $first . $last;
			
		}
		else {
			$ret = ucfirst( $str );
		}
		
		return $ret;
		
	}
	
	/**
	 * Makes a string's first character lowercase
	 * 
	 * @see http://php.net/ucfirst
	 * @param string $str The string to lowercase.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The lowercased string.
	 */
	public static function lcfirst ( $str, $use_enc = null ) {
		
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
			
			// get the first character
			$first = self::substr($str, 0, 1, $enc);
			
			// uppercase it
			$first = self::strtolower($first, $enc);
			
			// get the rest of the characters
			$last = self::substr($str, 1, null, $enc);
			
			// put them back together
			$ret = $first . $last;
			
		}
		else {
			$ret = lcfirst( $str );
		}
		
		return $ret;
		
	}

}

?>
