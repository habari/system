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
			
			// lowercase it
			$first = self::strtolower($first, $enc);
			
			// get the rest of the characters
			$last = self::substr($str, 1, null, $enc);
			
			// put them back together
			$ret = $first . $last;
			
		}
		else {
			
			// lcfirst() is php 5.3+ so we'll emulate it
			$first = substr( $str, 0, 1 );
			$first = strtolower( $first );
			
			$last = substr( $str, 1 );
			
			$ret = $first . $last;
			
		}
		
		return $ret;
		
	}
	

	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * 
	 * @todo Allow an array to be passed to $subject (and then return an array).
	 * 
	 * @see http://php.net/str_replace
	 * @param mixed $search A string or an array of strings to search for.
	 * @param mixed $replace A string or an array of strings to replace search values with.
	 * @param string $subject The string to perform the search and replace on.
	 * @param int $count If passed, this value will hold the number of matched and replaced needles.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @param boolean $case_insensitive Should the search and replace be case insenstive?
	 * @return string The subject with replaced values.
	 */
	public static function str_replace ( $search, $replace, $subject, &$count = 0, $use_enc = null, $case_insensitive = false ) {
		
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
		
			// if search is an array and replace is not, we need to make replace an array and pad it to the same number of values as search
			if ( is_array( $search ) && !is_array( $replace ) ) {
				$replace = array_fill( 0, count( $search ), $replace );
			}
			
			// if search is an array and replace is as well, we need to make sure replace has the same number of values - pad it with empty strings
			if ( is_array( $search ) && is_array( $replace ) ) {
				$replace = array_pad( $replace, count( $search ), '' );
			}
			
			// if search is not an array, make it one
			if ( !is_array( $search ) ) {
				$search = array( $search );
			}
			
			// if replace is not an array, make it one
			if ( !is_array( $replace ) ) {
				$replace = array( $replace );
			}
			
			
			// pick apart the string into an array of characters
			$chars = array();
			for ( $i = 0; $i < self::strlen( $subject, $enc ); $i++ ) {
				
				// get the char
				$char = self::substr( $subject, $i, 1, $enc );
				
				// add it to the stack
				$chars[] = $char;
				
			}
						
			
			
			// now we've got an array of characters and arrays of search / replace characters with the same values - loop and replace them!
			$search_count = count( $search );	// we modify $search, so we can't include it in the condition next
			for ( $i = 0; $i < $search_count; $i++ ) {
				
				// the values we'll match
				$s = array_shift( $search );
				$r = array_shift( $replace );
				
				// loop through each character
				for ( $j = 0; $j < count( $chars ); $j++ ) {
					
					$char = $chars[ $j ];
					
					// replace it if necessary
					if ( $char == $s || ( $case_insensitive == true && self::strtolower( $char, $enc ) == self::strtolower( $s, $enc ) ) ) {
						$char = $r;
						
						// increment $count
						$count++;
					}
					
					// add it back
					$chars[ $j ] = $char;
					
				}
				
			}
			
			// reassemble the characters
			$subject = implode('', $chars);
			
		}
		else {
			
			if ( $case_insensitive == true ) {
				$subject = str_ireplace( $search, $replace, $subject, $count );
			}
			else {
				$subject = str_replace( $search, $replace, $subject, $count );
			}
			
		}
		
		return $subject;
		
	}
	
	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * 
	 * @todo Allow an array to be passed to $subject (and then return an array).
	 * 
	 * @see http://php.net/str_ireplace
	 * @param mixed $search A string or an array of strings to search for.
	 * @param mixed $replace A string or an array of strings to replace search values with.
	 * @param string $subject The string to perform the search and replace on.
	 * @param int $count If passed, this value will hold the number of matched and replaced needles.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The subject with replaced values.
	 */
	public static function str_ireplace( $search, $replace, $subject, &$count = 0, $use_enc = null ) {
		
		return self::str_replace( $search, $replace, $subject, $count, $use_enc, true );
		
	}

}

?>