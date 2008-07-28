<?php

/*
 * Habari MultiByte Class
 *
 * Provides multibyte character set services,
 * a necessity since all of Habari's internal string
 * manipulations are done in UTF-8. Currently
 * this class is a wrapper around mbstring functions.
 * 
 * @package Habari
 */
class MultiByte
{

	/*
	* @var $hab_enc String holding the current encoding the class is using
	*/
	static $hab_enc = 'UTF-8';
	static $use_mbstring = TRUE;

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
	 * Returns the internal encoding. 
	 *
	 * @return string  The current internal encoding.
	*/
	public static function hab_encoding()
	{
		return self::$hab_enc;
	}

	/*
	 * function convert_encoding
	 *
	 * Converts a string's encoding to a new encoding
	 *
	 * @param $str string. The string who's encoding is being changed. 
	 * @param $enc string. The encoding to convert to. If not present,
	 * the internal encoding will be used.
	 *
	 * @return mixed  The  source string in the new encoding or boolean false.
	*/
	public static function convert_encoding( $str, $enc = null )
	{
		$ret = FALSE;

		if( ! isset( $enc ) ) {
			$enc = self::$hab_enc;
		}

		if ( extension_loaded( 'mbstring' ) && self::$use_mbstring == TRUE ) {
			$from_enc = MultiByte::detect_encoding( $str );
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
		$enc = FALSE;

		if ( extension_loaded( 'mbstring' ) && self::$use_mbstring == TRUE ) {
			// get original detection order
			$old_order = mb_detect_order();
//			Utils::debug( $old_order );
			// make sure  ISO-8859-1 is included
			mb_detect_order( array( 'ASCII', 'JIS', 'UTF-8', 'ISO-8859-1', 'EUC-JP', 'SJIS' ) );
			//detect the encoding . the detected encoding may be wrong, but it's better than nothing
			$enc = mb_detect_encoding( $str );
			// reset detection order
			mb_detect_order( $old_order);
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
	 * @param $len integer. How long the returned string should be.
	 *
	 * @return mixed The  section of the source string requested in the encoding requested or false.
	 *  
	*/
	public static function substr( $str, $begin, $len = null )
	{
		$ret = FALSE;

		$enc = self::$hab_enc;

		if ( extension_loaded( 'mbstring' ) && self::$use_mbstring == TRUE ) {
			$ret = mb_convert_encoding( $str, $enc, MultiByte::detect_encoding( $str ) );
			$ret = mb_substr( $ret, $begin, $len, $enc );
		}
		return $ret;
	}

	/*
	 * function strlen
	 *
	 * Gets the length of a string in characters
	 *
	 * @param $str string. The string who's length is being returned. 
	 *
	 * @return integer. The length in characters of the string, or the length in bytes if the mbstring extension isn't loaded.
	*/
	public static function strlen( $str )
	{
		$len = 0;

		$enc = self::$hab_enc;

		if ( extension_loaded( 'mbstring' ) && self::$use_mbstring == TRUE ) {
			$str = mb_convert_encoding( $str, $enc, MultiByte::detect_encoding( $str ) );
			$len = mb_strlen( $str, $enc );
		}
		else {
			$len = strlen( $str );
		}

		return $len;
	}

	/*
	 * function strtolower
	 *
	 * Converts a multibyte string to lowercase. If the mbstring extension isn't loaded, strtolower() will
	 * be used, which can lead to unexpected results.
	 *
	 * @param $str string. The string to lowercase
	 *
	 * @return string. The lowercased string.
	*/
	public static function strtolower( $str )
	{
		$enc = self::$hab_enc;

		if ( extension_loaded( 'mbstring' ) && self::$use_mbstring == TRUE ) {
			$ret = mb_strtolower( MultiByte::convert_encoding( $str ), $enc );
		}
		else {
			$ret = strtolower( $str );
		}

		return $ret;
	}

	/*
	 * function strtoupper
	 *
	 * Converts a multibyte string to uppercase. If the mbstring extension isn't loaded, strtoupper() will
	 * be used, which can lead to unexpected results.
	 *
	 * @param $str string. The string to uppercase
	 *
	 * @return string. The uppercased string.
	*/
	public static function strtoupper( $str )
	{
		$enc = self::$hab_enc;

		if ( extension_loaded( 'mbstring' ) && self::$use_mbstring == TRUE ) {
			$ret = mb_strtoupper( MultiByte::convert_encoding( $str ), $enc );
		}
		else {
			$ret = strtoupper( $str );
		}

		return $ret;
	}
}

?>