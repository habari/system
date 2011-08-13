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

	const USE_MBSTRING = 1;

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
	* @param $int The new library to use. One of the self::USE_* constants, null to simply return, or false to disable and use native non-multibyte-safe PHP methods.
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
		else if ( $new_library === self::USE_MBSTRING ) {
			$old_library = self::$use_library;
			self::$use_library = $new_library;
			return $old_library;

		}
		else if ( $new_library === false ) {
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
	* @param $str string. The string whose encoding is being detected
	*
	* @return mixed The source string's detected encoding, or boolean false.
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
	* function detect_bom_encoding
	*
	* Detects the encoding being used for a string using the existence
	* of a byte order mark
	*
	* @param $str string. The string whose encoding is being detected
	*
	* @return mixed The source string's detected encoding, or boolean false.
	*/
	public static function detect_bom_encoding( $str )
	{
		$ret = false;
		if ( "\xFE\xFF" == substr( 0, 2, $source_contents ) ) {
			$ret = 'UTF-16BE';
		}
		else if ( "\xFF\xFE" == substr( 0, 2, $source_contents ) ) {
			$ret = 'UTF-16LE';
		}
		else if ( "\xEF\xBB\xBF" == substr( 0, 3, $source_contents ) ) {
			$ret = 'UTF-8';
		}

		return $ret;
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
			$ret = strpos( $haysack, $needle, $offset );
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
			$ret = stripos( $haysack, $needle, $offset );
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
			$ret = strrpos( $haysack, $needle, $offset );
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
			$ret = strripos( $haysack, $needle, $offset );
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
	public static function valid_data( $str, $use_enc = null )
	{
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
			return mb_check_encoding( $str, $enc );
		}
		
		return true;
	}
	
	/**
	 * Makes a string's first character uppercase
	 * 
	 * @see http://php.net/ucfirst
	 * @param string $str The string to capitalize.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The capitalized string.
	 */
	public static function ucfirst ( $str, $use_enc = null )
	{
		
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
			
			// get the first character
			$first = self::substr( $str, 0, 1, $enc );
			
			// uppercase it
			$first = self::strtoupper( $first, $enc );
			
			// get the rest of the characters
			$last = self::substr( $str, 1, null, $enc );
			
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
	public static function lcfirst ( $str, $use_enc = null )
	{
		
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
			
			// get the first character
			$first = self::substr( $str, 0, 1, $enc );
			
			// lowercase it
			$first = self::strtolower( $first, $enc );
			
			// get the rest of the characters
			$last = self::substr( $str, 1, null, $enc );
			
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
	 * @see http://php.net/str_replace
	 * @param mixed $search A string or an array of strings to search for.
	 * @param mixed $replace A string or an array of strings to replace search values with.
	 * @param string $subject The string to perform the search and replace on.
	 * @param int $count If passed, this value will hold the number of matched and replaced needles.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The subject with replaced values.
	 */
	public static function str_replace ( $search, $replace, $subject, &$count = 0, $use_enc = null )
	{
		
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
			
			// if subject is an array, recursively call ourselves on each element of it
			if ( is_array( $subject ) ) {
				foreach ( $subject as $k => $v ) {
					$subject[ $k ] = self::str_replace( $search, $replace, $v, $count, $use_enc );
				}
				
				return $subject;
			}
						
			
			
			// now we've got an array of characters and arrays of search / replace characters with the same values - loop and replace them!
			$search_count = count( $search );	// we modify $search, so we can't include it in the condition next
			for ( $i = 0; $i < $search_count; $i++ ) {
				
				// the values we'll match
				$s = array_shift( $search );
				$r = array_shift( $replace );
				
				// to avoid an infinite loop if you're replacing with a value that contains the subject we get the position of each instance first
				$positions = array();
				
				$offset = 0;
				while ( self::strpos( $subject, $s, $offset, $enc ) !== false ) {
					
					// get the position
					$pos = self::strpos( $subject, $s, $offset, $enc );
					
					// add it to the list
					$positions[] = $pos;
					
					// and set the offset to skip over this value
					$offset = $pos + self::strlen( $s, $enc );
					
				}
				
				// if we pick through from the beginning, our positions will change if the replacement string is longer
				// instead, we pick through from the last place
				$positions = array_reverse( $positions );
				
				// now that we've got the position of each one, just loop through that and replace them
				foreach ( $positions as $pos ) {
					
					// pull out the part before the string
					$before = self::substr( $subject, 0, $pos, $enc );
					
					// pull out the part after
					$after = self::substr( $subject, $pos + self::strlen( $s, $enc ), null, $enc );
					
					// now we have the string in two parts without the string we're searching for
					// put it back together with the replacement
					$subject = $before . $r . $after;
					
					// increment our count, a replacement was made
					$count++;
					
				}
				
			}
			
		}
		else {
			
			$subject = str_replace( $search, $replace, $subject, $count );
			
		}
		
		return $subject;
		
	}
	
	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * 
	 * @see http://php.net/str_ireplace
	 * @param mixed $search A string or an array of strings to search for.
	 * @param mixed $replace A string or an array of strings to replace search values with.
	 * @param string $subject The string to perform the search and replace on.
	 * @param int $count If passed, this value will hold the number of matched and replaced needles.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The subject with replaced values.
	 */
	public static function str_ireplace( $search, $replace, $subject, &$count = 0, $use_enc = null )
	{
		
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
			
			// if subject is an array, recursively call ourselves on each element of it
			if ( is_array( $subject ) ) {
				foreach ( $subject as $k => $v ) {
					$subject[ $k ] = self::str_ireplace( $search, $replace, $v, $count, $use_enc );
				}
				
				return $subject;
			}
						
			
			
			$search_count = count( $search );	// we modify $search, so we can't include it in the condition next
			for ( $i = 0; $i < $search_count; $i++ ) {
				
				// the values we'll match
				$s = array_shift( $search );
				$r = array_shift( $replace );
				
				
				// to avoid an infinite loop if you're replacing with a value that contains the subject we get the position of each instance first
				$positions = array();
				
				$offset = 0;
				while ( self::stripos( $subject, $s, $offset, $enc ) !== false ) {
					
					// get the position
					$pos = self::stripos( $subject, $s, $offset, $enc );
					
					// add it to the list
					$positions[] = $pos;
					
					// and set the offset to skip over this value
					$offset = $pos + self::strlen( $s, $enc );
					
				}
				
				// if we pick through from the beginning, our positions will change if the replacement string is longer
				// instead, we pick through from the last place
				$positions = array_reverse( $positions );
				
				// now that we've got the position of each one, just loop through that and replace them
				foreach ( $positions as $pos ) {
					
					// pull out the part before the string
					$before = self::substr( $subject, 0, $pos, $enc );
					
					// pull out the part after
					$after = self::substr( $subject, $pos + self::strlen( $s, $enc ), null, $enc );
					
					// now we have the string in two parts without the string we're searching for
					// put it back together with the replacement
					$subject = $before . $r . $after;
					
					// increment our count, a replacement was made
					$count++;
					
				}
				
			}
			
		}
		else {
			
			$subject = str_ireplace( $search, $replace, $subject, $count );
			
		}
		
		return $subject;
		
	}
	
	/**
	 * Uppercase the first character of each word in a string.
	 * 
	 * From php.net/ucwords:
	 * 	The definition of a word is any string of characters that is immediately after a whitespace
	 * 	(These are: space, form-feed, newline, carriage return, horizontal tab, and vertical tab).
	 * 
	 * @see http://php.net/ucwords
	 * @param string $str The input string.
	 * @param string $use_enc The encoding to be used. If null, the internal encoding will be used.
	 * @return string The modified string.
	 */
	public static function ucwords ( $str, $use_enc = null )
	{
		
		$enc = self::$hab_enc;
		if ( $use_enc !== null ) {
			$enc = $use_enc;
		}
		
		if ( self::$use_library == self::USE_MBSTRING ) {
		
			$delimiters = array(
				chr( 32 ),	// space
				chr( 12 ),	// form-feed
				chr( 10 ),	// newline
				chr( 13 ),	// carriage return
				chr( 9 ),	// horizontal tab
				chr( 11 ),	// vertical tab
			);
			
			// loop through the delimiters and explode the string by each one
			foreach ( $delimiters as $d ) {
				
				$pieces = explode( $d, $str );
				
				for ( $i = 0; $i < count( $pieces ); $i++ ) {
					
					// capitalize each word
					$pieces[ $i ] = self::ucfirst( $pieces[ $i ], $enc );
					
				}
				
				// put the string back together
				$str = implode( $d, $pieces );
				
			}
		
		}
		else {
			$str = ucwords( $str );
		}
		
		
		return $str;
	}

}

?>