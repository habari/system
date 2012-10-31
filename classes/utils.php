<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Utility Class
 *
 */
class Utils
{
	public static $debug_defined = false;

	/**
	 * Utils constructor
	 * This class should not be instantiated.
	 */
	private function __construct()
	{
	}

	/**
	 * function get_params
	 * Returns an associative array of parameters, whether the input value is
	 * a querystring or an associative array.
	 * @param mixed An associative array or querystring parameter list
	 * @return array An associative array of parameters
	 */
	public static function get_params( $params )
	{
		if ( is_array( $params ) || $params instanceof Traversable ) {
			return $params;
		}
		$paramarray = array();
		parse_str( $params, $paramarray );
		return $paramarray;
	}

	/**
	 * function end_in_slash
	 * Forces a string to end in a single slash
	 * @param string A string, usually a path
	 * @return string The string with the slash added or extra slashes removed, but with one slash only
	 */
	public static function end_in_slash( $value )
	{
		return rtrim( $value, '\\/' ) . '/';
	}

	/**
	 * function redirect
	 * Redirects the request to a new URL
	 * @param string $url The URL to redirect to, or omit to redirect to the current url
	 * @param boolean $continue Whether to continue processing the script (default false for security reasons, cf. #749)
	 */
	public static function redirect( $url = '', $continue = false )
	{
		if ( $url == '' ) {
			$url = Controller::get_full_url();
		}
		header( 'Location: ' . $url, true, 302 );

		if ( ! $continue ) exit;
	}

	/**
	 * function atomtime
	 * Returns RFC-3339 time from a time string or integer timestamp
	 * @param mixed A string of time or integer timestamp
	 * @return string An RFC-3339 formatted time
	 */
	public static function atomtime( $t )
	{
		if ( ! is_numeric( $t ) ) {
			$t = strtotime( $t );
		}
		$vdate = date( DATE_ATOM, $t );
		// If the date format used for timezone was O instead of P...
		if ( substr( $vdate, -3, 1 ) != ':' ) {
			$vdate = substr( $vdate, 0, -2 ) . ':' . substr( $vdate, -2, 2 );
		}
		return $vdate;
	}

	/**
	 * function nonce
	 * Returns a random 12-digit hex number
	 */
	public static function nonce()
	{
		return sprintf( '%06x', rand( 0, 16776960 ) ) . sprintf( '%06x', rand( 0, 16776960 ) );
	}

	/**
	 * Returns an array of tokens used for WSSE authentication
	 *    http://www.xml.com/pub/a/2003/12/17/dive.html
	 *    http://www.sixapart.com/developers/atom/protocol/atom_authentication.html
	 * @param string|array $nonce a string nonce or an existing array to add nonce parameters to
	 * @param string $timestamp a timestamp
	 * @return array an array of WSSE authentication elements
	 */
	public static function WSSE( $nonce = '', $timestamp = '' )
	{
		$wsse = array();
		if (is_array($nonce)) {
			$wsse = $nonce;
			$nonce = '';
		}
		if ( '' === $nonce ) {
			$nonce = Utils::crypt( Options::get( 'GUID' ) . Utils::nonce() );
		}
		if ( '' === $timestamp ) {
			$timestamp = date( 'c' );
		}
		$user = User::identify();
		$wsse = array_merge(
			$wsse,
			array(
				'nonce' => $nonce,
				'timestamp' => $timestamp,
				'digest' => base64_encode( pack( 'H*', sha1( $nonce . $timestamp . $user->password ) ) )
			)
		);
		return $wsse;
	}

	/**
	 * function stripslashes
	 * Removes slashes from escaped strings, including strings in arrays
	 */
	public static function stripslashes( $value )
	{
		if ( is_array( $value ) ) {
			$value = array_map( array( 'Utils', 'stripslashes' ), $value );
		}
		elseif ( !empty( $value ) && is_string( $value ) ) {
			$value = stripslashes( $value );
		}
		return $value;
	}

	/**
	 * function addslashes
	 * Adds slashes to escape strings, including strings in arrays
	 */
	public static function addslashes( $value )
	{
		if ( is_array( $value ) ) {
			$value = array_map( array( 'Utils', 'addslashes' ), $value );
		}
		else if ( !empty( $value ) && is_string( $value ) ) {
			$value = addslashes( $value );
		}
		return $value;
	}

	/**
	 * function de_amp
	 * Returns &amp; entities in a URL querystring to their previous & glory, for use in redirects
	 * @param string $value A URL, maybe with a querystring
	 */
	public static function de_amp( $value )
	{
		$url = InputFilter::parse_url( $value );
		$url[ 'query' ] = str_replace( '&amp;', '&', $url[ 'query' ] );
		return InputFilter::glue_url( $url );
	}

	/**
	 * function revert_magic_quotes_gpc
	 * Reverts magicquotes_gpc behavior
	 */
	public static function revert_magic_quotes_gpc()
	{
		/* We should only revert the magic quotes once per page hit */
		static $revert = true;
		if ( get_magic_quotes_gpc() && $revert ) {
			$_GET = self::stripslashes( $_GET );
			$_POST = self::stripslashes( $_POST );
			$_COOKIE = self::stripslashes( $_COOKIE );
			$revert = false;
		}
	}

	/**
	 * function quote_spaced
	 * Adds quotes around values that have spaces in them
	 * @param string A string value that might have spaces
	 * @return string The string value, quoted if it has spaces
	 */
	public static function quote_spaced( $value )
	{
		return ( strpos( $value, ' ' ) === false ) ? $value : '"' . $value . '"';
	}

	/**
	 * function implode_quoted
	 * Behaves like the implode() function, except it quotes values that contain spaces
	 * @param string A separator between each value
	 * @param	array An array of values to separate
	 * @return string The concatenated string
	 */
	public static function implode_quoted( $separator, $values )
	{
		if ( ! is_array( $values ) ) {
			$values = array();
		}
		$values = array_map( array( 'Utils', 'quote_spaced' ), $values );
		return implode( $separator, $values );
	}

	/**
	 * Returns a string of question mark parameter
	 * placeholders.
	 *
	 * Useful when building, for instance, an IN() list for SQL
	 *
	 * @param		count		Number of placeholders to put in the string
	 * @return	string	Placeholder string
	 */
	public static function placeholder_string( $count )
	{
		if ( Utils::is_traversable( $count ) ) {
			$count = count( $count );
		}
		return rtrim( str_repeat( '?,', $count ), ',' );
	}

	/**
	 * function archive_pages
	 * Returns the number of pages in an archive using the number of items per page set in options
	 * @param integer Number of items in the archive
	 * @param integer Number of items per page
	 * @returns integer Number of pages based on pagination option.
	 */
	public static function archive_pages( $item_total, $items_per_page = null )
	{
		if ( $items_per_page ) {
			return ceil( $item_total / $items_per_page );
		}
		return ceil( $item_total / Options::get( 'pagination' ) );
	}

	/**
	* Used with array_map to create an array of PHP stringvar-style search/replace strings using optional pre/postfixes
	* <code>
	* $mapped_values= array_map(array('Utils', 'map_array'), $values);
	* </code>
	* @param string $value The value to wrap
	* @param string $prefix The prefix for the returned value
	* @param string $postfix The postfix for the returned value
	* @return string The wrapped value
	*/
	public static function map_array( $value, $prefix = '{$', $postfix = '}' )
	{
		return $prefix . $value . $postfix;
	}

	/**
	 * Helper function used by debug()
	 * Not for external use.
	 */
	public static function debug_reveal( $show, $hide, $debugid, $close = false )
	{
		$reshow = $restyle = $restyle2 = '';
		if ( $close ) {
			$reshow = "onclick=\"debugtoggle('debugshow-{$debugid}');debugtoggle('debughide-{$debugid}');return false;\"";
			$restyle = "<span class=\"utils__block\">";
			$restyle2 = "</span>";
		}
		return "<span class=\"utils__arg\"><a href=\"#\" id=\"debugshow-{$debugid}\" onclick=\"debugtoggle('debugshow-{$debugid}');debugtoggle('debughide-{$debugid}');return false;\">$show</a><span style=\"display:none;\" id=\"debughide-{$debugid}\" {$reshow} >{$restyle}$hide{$restyle2}</span></span>";
	}

	/**
	 * Outputs a call stack with parameters, and a dump of the parameters passed.
	 * @params mixed Any number of parameters to output in the debug box.
	 */
	public static function debug()
	{
		$debugid = md5( microtime() );
		$tracect = 0;

		$fooargs = func_get_args();
		echo "<div class=\"utils__debugger\">";
		if ( !self::$debug_defined ) {
			$output = "<script type=\"text/javascript\">
				debuggebi = function(id) {return document.getElementById(id);}
				debugtoggle = function(id) {debuggebi(id).style.display = debuggebi(id).style.display=='none'?'inline':'none';}
				</script>
				<style type=\"text/css\">
				.utils__debugger{background-color:#550000;border:1px solid red;text-align:left;}
				.utils__debugger pre{margin:5px;background-color:#000;overflow-x:scroll}
				.utils__debugger pre em{color:#dddddd;}
				.utils__debugger table{background-color:#770000;color:white;width:100%;}
				.utils__debugger tr{background-color:#000000;}
				.utils__debugger td{padding-left: 10px;vertical-align:top;white-space: pre;font-family:Courier New,Courier,monospace;}
				.utils__debugger .utils__odd{background:#880000;}
				.utils__debugger .utils__arg a{color:#ff3333;}
				.utils__debugger .utils__arg span{display:none;}
				.utils__debugger .utils__arg span span{display:inline;}
				.utils__debugger .utils__arg span .utils__block{display:block;background:#990000;margin:0px 2em;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:9px;padding:5px;}
				</style>
			";
			echo $output;
			self::$debug_defined = true;
		}
		if ( function_exists( 'debug_backtrace' ) ) {
			$output = "<table>";
			$backtrace = array_reverse( debug_backtrace(), true );
			$odd = '';
			$tracect = 0;
			foreach ( $backtrace as $trace ) {
				$file = $line = $class = $type = $function = '';
				$args = array();
				extract( $trace );
				if ( isset( $class ) ) $fname = $class . $type . $function; else $fname = $function;
				if ( !isset( $file ) || $file=='' ) $file = '[Internal PHP]'; else $file = basename( $file );
				$odd = $odd == '' ? 'class="utils__odd"' : '';
				$output .= "<tr {$odd}><td>{$file} ({$line}):</td><td>{$fname}(";
				$comma = '';
				foreach ( (array)$args as $arg ) {
					$tracect++;
					$argout = print_r( $arg, 1 );
					$output .= $comma . Utils::debug_reveal( gettype( $arg ), htmlentities( $argout ), $debugid . $tracect, true );
					$comma = ', ';
				}
				$output .= ");</td></tr>";
			}
			$output .= "</table>";
			echo Utils::debug_reveal( '<small>Call Stack</small>', $output, $debugid );
		}
		echo "<pre style=\"color:white;\">";
		foreach ( $fooargs as $arg1 ) {
			echo '<em>' . gettype( $arg1 ) . '</em> ';
			if ( gettype( $arg1 ) == 'boolean' ) {
				echo htmlentities( var_export( $arg1 ) ) . '<br>';
			}
			else {
				echo htmlentities( print_r( $arg1, true ) ) . "<br>";
			}
		}
		echo "</pre></div>";
	}

	/**
	 * Outputs debug information like ::debug() but using Firebug's Console.
	 * @params mixed Any number of parameters to output in the debug box.
	 */
	public static function firedebug()
	{
		$fooargs = func_get_args();
		$output = "<script type=\"text/javascript\">\nif (window.console){\n";
		$backtrace = array_reverse( debug_backtrace(), true );
		$output .= Utils::firebacktrace( $backtrace );

		foreach ( $fooargs as $arg1 ) {
			$output .= "console.info(\"%s:  %s\", \"" . gettype( $arg1 ) . "\"";
			$output .= ", \"" . str_replace( "\n", '\n', addslashes( print_r( $arg1, 1 ) ) ) . "\");\n";
		}
		$output .= "console.groupEnd();\n}\n</script>";
		echo $output;
	}

	/**
	 * Utils::firebacktrace()
	 *
	 * @param array $backtrace An array of backtrace details from debug_backtrace()
	 * @return string Javascript output that will display the backtrace in the Firebug console.
	 */
	public static function firebacktrace( $backtrace )
	{
		$output = '';
		extract( end( $backtrace ) );
		if ( isset( $class ) ) $fname = $class . $type . $function; else $fname = $function;
		if ( !isset( $file ) || $file=='' ) $file = '[Internal PHP]'; else $file = basename( $file );
		$output .= "console.group(\"%s(%s):  %s(&hellip;)\", \"" . basename( $file ) . "\", \"{$line}\", \"{$fname}\");\n";
		foreach ( $backtrace as $trace ) {
			$file = $line = $class = $type = $function = '';
			$args = array();
			extract( $trace );
			if ( isset( $class ) ) $fname = $class . $type . $function; else $fname = $function;
			if ( !isset( $file ) || $file=='' ) $file = '[Internal PHP]'; else $file = basename( $file );

			$output .= "console.group(\"%s(%s):  %s(%s)\", \"{$file}\", \"{$line}\", \"{$fname}\", \"";

			$output2 = $comma = $argtypes = '';
			foreach ( (array)$args as $arg ) {
				$argout = str_replace( "\n", '\n', addslashes( print_r( $arg, 1 ) ) );
				//$output .= $comma . Utils::debug_reveal( gettype($arg), htmlentities($argout), $debugid . $tracect, true );
				$argtypes .= $comma . gettype( $arg );
				$output2 .= "console.log(\"$argout\");\n";
				$comma = ', ';
			}
			$argtypes = trim( $argtypes );
			$output .= "{$argtypes}\");\n{$output2}";
			$output .= "console.groupEnd();\n";
			//$output .= ");</td></tr>";
		}
		return $output;
	}

	/**
	 * Crypt a given password, or verify a given password against a given hash.
	 *
	 * @todo Enable best algo selection after DB schema change.
	 *
	 * @param string $password the password to crypt or verify
	 * @param string $hash (optional) if given, verify $password against $hash
	 * @return crypted password, or boolean for verification
	 */
	public static function crypt( $password, $hash = null )
	{
		if ( $hash == null ) {
			return self::ssha512( $password, $hash );
		}
		elseif ( strlen( $hash ) > 3 ) { // need at least {, } and a char :p
			// verify
			if ( $hash{0} == '{' ) {
				// new hash from the block
				$algo = strtolower( substr( $hash, 1, strpos( $hash, '}', 1 ) - 1 ) );
				switch ( $algo ) {
					case 'sha1':
					case 'ssha':
					case 'ssha512':
					case 'md5':
						return self::$algo( $password, $hash );
					default:
						Error::raise( _t( 'Unsupported digest algorithm "%s"', array( $algo ) ) );
						return false;
				}
			}
			else {
				// legacy sha1
				return ( sha1( $password ) == $hash );
			}
		}
		else {
			Error::raise( _t( 'Invalid hash' ) );
		}
	}

	/**
	 * Crypt or verify a given password using SHA.
	 *
	 * Passwords should not be stored using this method, but legacy systems might require it.
	 */
	public static function sha1( $password, $hash = null )
	{
		$marker = '{SHA1}';
		if ( $hash == null ) {
			return $marker . sha1( $password );
		}
		else {
			return ( sha1( $password ) == substr( $hash, strlen( $marker ) ) );
		}
	}

	/**
	 * Crypt or verify a given password using MD5.
	 *
	 * Passwords should not be stored using this method, but legacy systems might require it.
	 */
	public static function md5( $password, $hash = null )
	{
		$marker = '{MD5}';
		if ( $hash == null ) {
			return $marker . md5( $password );
		}
		else {
			return ( md5( $password ) == substr( $hash, strlen( $marker ) ) );
		}
	}

	/**
	 * Crypt or verify a given password using SSHA.
	 * Implements the {Seeded,Salted}-SHA algorithm as per RfC 2307.
	 *
	 * @param string $password the password to crypt or verify
	 * @param string $hash (optional) if given, verify $password against $hash
	 * @return crypted password, or boolean for verification
	 */
	public static function ssha( $password, $hash = null )
	{
		$marker = '{SSHA}';
		if ( $hash == null ) { // encrypt
			// create salt (4 byte)
			$salt = '';
			for ( $i = 0; $i < 4; $i++ ) {
				$salt .= chr( mt_rand( 0, 255 ) );
			}
			// get digest
			$digest = sha1( $password . $salt, true );
			// b64 for storage
			return $marker . base64_encode( $digest . $salt );
		}
		else { // verify
			// is this a SSHA hash?
			if ( ! substr( $hash, 0, strlen( $marker ) ) == $marker ) {
				Error::raise( _t( 'Invalid hash' ) );
				return false;
			}
			// cut off {SSHA} marker
			$hash = substr( $hash, strlen( $marker ) );
			// b64 decode
			$hash = base64_decode( $hash );
			// split up
			$digest = substr( $hash, 0, 20 );
			$salt = substr( $hash, 20 );
			// compare
			return ( sha1( $password . $salt, true ) == $digest );
		}
	}

	/**
	 * Crypt or verify a given password using SSHA512.
	 * Implements a modified version of the {Seeded,Salted}-SHA algorithm
	 * from RfC 2307, using SHA-512 instead of SHA-1.
	 *
	 * Requires the new hash*() functions.
	 *
	 * @param string $password the password to crypt or verify
	 * @param string $hash (optional) if given, verify $password against $hash
	 * @return crypted password, or boolean for verification
	 */
	public static function ssha512( $password, $hash = null )
	{
		$marker = '{SSHA512}';
		if ( $hash == null ) { // encrypt
			$salt = '';
			for ( $i = 0; $i < 4; $i++ ) {
				$salt .= chr( mt_rand( 0, 255 ) );
			}
			$digest = hash( 'sha512', $password . $salt, true );
			return $marker . base64_encode( $digest . $salt );
		}
		else { // verify
			if ( ! substr( $hash, 0, strlen( $marker ) ) == $marker ) {
				Error::raise( _t( 'Invalid hash' ) );
				return false;
			}
			$hash = substr( $hash, strlen( $marker ) );
			$hash = base64_decode( $hash );
			$digest = substr( $hash, 0, 64 );
			$salt = substr( $hash, 64 );
			return ( hash( 'sha512', $password . $salt, true ) == $digest );
		}
	}

	/**
	 * Return an array of date information
	 * Just like getdate() but also returns 0-padded versions of day and month in mday0 and mon0
	 * @param integer $timestamp A unix timestamp
	 * @return array An array of date data
	 */
	public static function getdate( $timestamp )
	{
		$info = getdate( $timestamp );
		$info[ 'mon0' ] = substr( '0' . $info[ 'mon' ], -2, 2 );
		$info[ 'mday0' ] = substr( '0' . $info[ 'mday' ], -2, 2 );
		return $info;
	}

	/**
	 * Return a formatted date/time trying to use strftime() AND date()
	 * @param string $format The format for the date.  If it contains non-escaped percent signs, it uses strftime(),	otherwise date()
	 * @param integer $timestamp The unix timestamp of the time to format
	 * @return string The formatted time
	 */
	public static function locale_date( $format, $timestamp )
	{
		$matches = preg_split( '/((?<!\\\\)%[a-z]\\s*)/iu', $format, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$output = '';
		foreach ( $matches as $match ) {
			if ( $match{0} == '%' ) {
				$output .= strftime( $match, $timestamp );
			}
			else {
				$output .= date( $match, $timestamp );
			}
		}
		return $output;
	}

	/**
	 * Return a sanitized slug, replacing non-alphanumeric characters to dashes
	 * @param string $string The string to sanitize. Non-alphanumeric characters will be replaced by dashes
	 * @param string $separator The slug separator, '-' by default
	 * @return string The sanitized slug
	 */
	public static function slugify( $string, $separator = '-' )
	{
		// Decode HTML entities
		// Replace non-alphanumeric characters to dashes. Exceptions: %, _, -
		// Note that multiple separators are collapsed automatically by the preg_replace.
		// Convert all characters to lowercase.
		// Trim spaces on both sides.
		$slug = rtrim( MultiByte::strtolower( preg_replace( '/[^\p{L}\p{N}_]+/u', $separator, preg_replace( '/\p{Po}/u', '', html_entity_decode( $string ) ) ) ), $separator );
		// Let people change the behavior.
		$slug = Plugins::filter( 'slugify', $slug, $string );

		return $slug;
	}

	/**
	 * Creates one or more HTML inputs
	 * @param string The name of the input element.
	 * @param array An array of input options.  Each element should be
	 *	an array containing "name", "value" and "type".
	 * @return string The HTML of the inputs
	 */
	public static function html_inputs( $options )
	{
		$output = '';
		foreach ( $options as $option ) {
			$output .= '<input type="' . $option['type'] . '" id="' . $option[ 'name' ] . '" name="' . $option[ 'name' ];
			$output .= '" value="' . $option[ 'value' ] . '"';
			$output .= '>';
		}
		
		return $output;
	}

	/**
	 * Create an HTML select tag with options and a current value
	 *
	 * @param string $name The name and id of the select control
	 * @param array $options An associative array of values to use as the select options
	 * @param string $current The value of the currently selected option
	 * @param array $properties An associative array of additional properties to assign to the select control
	 * @return string The select control markup
	 */
	public static function html_select( $name, $options, $current = null, $properties = array() )
	{
		$id = isset($properties['id']) ? $properties['id'] : $name;
		$output = '<select id="' . $id . '" name="' . $name . '"';
		foreach ( $properties as $key => $value ) {
			$output .= " {$key}=\"{$value}\"";
		}
		$output .= ">\n";
		foreach ( $options as $value => $text ) {
			$output .= '<option value="' . $value . '"';
			if ( $current == (string)$value ) {
				$output .= ' selected="selected"';
			}
			$output .= '>' . $text . "</option>\n";
		}
		$output .= '</select>';
		return $output;
	}

	/**
	 * Creates one or more HTML checkboxes
	 * @param string The name of the checkbox element.  If there are
	 *	multiple checkboxes for the same name, this method will
	 *	automatically apply "[]" at the end of the name
	 * @param array An array of checkbox options.  Each element should be
	 *	an array containing "name" and "value".  If the checkbox
	 *	should be checked, it should have a "checked" element.
	 * @return string The HTML of the checkboxes
	 */
	public static function html_checkboxes( $name, $options )
	{
		$output = '';
		$multi = false;
		if ( count( $options > 1 ) ) {
			$multi = true;
		}
		foreach ( $options as $option ) {
			$output .= '<input type="checkbox" id="' . $option[ 'name' ] . '" name="' . $option[ 'name' ];
			if ( $multi ) {
				$output .= '[]';
			}
			$output .= '" value="' . $option[ 'value' ] . '"';
			if ( isset( $option[ 'checked' ] ) ) {
				$output .= ' checked';
			}
			$output .= '>';
		}
		return $output;
	}

	/**
	 * Trims longer phrases to shorter ones with elipsis in the middle
	 * @param string The string to truncate
	 * @param integer The length of the returned string
	 * @param bool Whether to place the ellipsis in the middle (true) or
	 * at the end (false)
	 * @return string The truncated string
	 */
	public static function truncate( $str, $len = 10, $middle = true )
	{
		// make sure $len is a positive integer
		if ( ! is_numeric( $len ) || ( 0 > $len ) ) {
			return $str;
		}
		// if the string is less than the length specified, bail out
		if ( MultiByte::strlen( $str ) <= $len ) {
			return $str;
		}

		// okay.  Shuold we place the ellipse in the middle?
		if ( $middle ) {
			// yes, so compute the size of each half of the string
			$len = round( ( $len - 3 ) / 2 );
			// and place an ellipse in between the pieces
			return MultiByte::substr( $str, 0, $len ) . '&hellip;' . MultiByte::substr( $str, -$len );
		}
		else {
			// no, the ellipse goes at the end
			$len = $len - 3;
			return MultiByte::substr( $str, 0, $len ) . '&hellip;';
		}
	}

	/**
	 * Check the PHP syntax of the specified code.
	 * Performs a syntax (lint) check on the specified code testing for scripting errors.
	 *
	 * @param string $code The code string to be evaluated. It does not have to contain PHP opening tags.
	 * @return bool Returns true if the lint check passed, and false if the link check failed.
	 */
	public static function php_check_syntax( $code, &$error = null )
	{
		$b = 0;

		foreach ( token_get_all( $code ) as $token ) {
			if ( is_array( $token ) ) {
				$token = token_name( $token[0] );
			}
			switch ( $token ) {
				case 'T_CURLY_OPEN':
				case 'T_DOLLAR_OPEN_CURLY_BRACES':
				case 'T_CURLY_OPENT_VARIABLE': // This is not documented in the manual. (11.05.07)
				case '{':
					++$b;
					break;
				case '}':
					--$b;
					break;
			}
		}

		if ( $b ) {
			$error = _t( 'Unbalanced braces.' );
			return false; // Unbalanced braces would break the eval below
		}
		else {
			ob_start(); // Catch potential parse error messages
			$display_errors = ini_set( 'display_errors', 'on' ); // Make sure we have something to catch
			$error_reporting = error_reporting( E_ALL ^ E_NOTICE );
			$code = eval( ' if (0){' . $code . '}' ); // Put $code in a dead code sandbox to prevent its execution
			ini_set( 'display_errors', $display_errors ); // be a good citizen
			error_reporting( $error_reporting );
			$error = ob_get_clean();

			return false !== $code;
		}
	}

	/**
	 * Check the PHP syntax of (and execute) the specified file.
	 *
	 * @see Utils::php_check_syntax()
	 */
	public static function php_check_file_syntax( $file, &$error = null )
	{
		// Prepend and append PHP opening tags to prevent eval() failures.
		$code = ' ?>' . file_get_contents( $file ) . '<?php ';

		return self::php_check_syntax( $code, $error );
	}

	/**
	 * Replacement for system glob that returns an empty array if there are no results
	 *
	 * @param string $pattern The glob() file search pattern
	 * @param integer $flags Standard glob() flags
	 * @return array An array of result files, or an empty array if no results found
	 */
	public static function glob( $pattern, $flags = 0 )
	{
		if ( ! defined( 'GLOB_NOBRACE' ) || ! ( ( $flags & GLOB_BRACE ) == GLOB_BRACE ) ) {
			// this platform supports GLOB_BRACE out of the box or GLOB_BRACE wasn't requested
			$results = glob( $pattern, $flags );
		}
		elseif ( ! preg_match_all( '/\{.*?\}/', $pattern, $m ) ) {
			// GLOB_BRACE used, but this pattern doesn't even use braces
			$results = glob( $pattern, $flags ^ GLOB_BRACE );
		}
		else {
			// pattern uses braces, but platform doesn't support GLOB_BRACE
			$braces = array();
			foreach ( $m[0] as $raw_brace ) {
				$braces[ preg_quote( $raw_brace ) ] = '(?:' . str_replace( ',', '|', preg_quote( substr( $raw_brace, 1, -1 ), '/' ) ) . ')';
			}
			$new_pattern = preg_replace( '/\{.*?\}/', '*', $pattern );
			$pattern = preg_quote( $pattern, '/' );
			$pattern = str_replace( '\\*', '.*', $pattern );
			$pattern = str_replace( '\\?', '.', $pattern );
			$regex = '/' . str_replace( array_keys( $braces ), array_values( $braces ), $pattern ) . '/';
			$results = preg_grep( $regex, Utils::glob( $new_pattern, $flags ^ GLOB_BRACE ) );
		}

		if ( $results === false ) $results = array();
		return $results;
	}

	/**
	 * Produces a human-readable size string.
	 * For example, converts 12345 into 12.34KB
	 *
	 * @param integer $bytesize Number of bytes
	 * @return string Human-readable string
	 */
	public static function human_size( $bytesize )
	{
		$sizes = array(
			' bytes',
			'KiB',
			'MiB',
			'GiB',
			'TiB',
			'PiB'
			);
		$tick = 0;
		$max_tick = count( $sizes ) - 1;
		while ( $bytesize > 1024 && $tick < $max_tick ) {
			$tick++;
			$bytesize /= 1024;
		}

		return sprintf( '%0.2f%s', $bytesize, $sizes[ $tick ] );
	}

	/**
	 * Convert a single non-array variable into an array with that one element
	 *
	 * @param mixed $element Some value, either an array or not
	 * @return array Either the original array value, or the passed value as the single element of an array
	 */
	public static function single_array( $element )
	{
		if ( !is_array( $element ) && !$element instanceof Traversable ) {
			return array( $element );
		}
		return $element;
	}

	/**
	 * Return the mimetype of a file
	 *
	 * @param string $filename the path of a file
	 * @return string The mimetype of the file.
	 */
	public static function mimetype( $filename )
	{
		$mimetype = null;
		if ( function_exists( 'finfo_open' ) && file_exists($filename) ) {
			$finfo = finfo_open( FILEINFO_MIME );
			$mimetype = finfo_file( $finfo, $filename );
			/* FILEINFO_MIME Returns the mime type and mime encoding as defined by RFC 2045.
			 * So only return the mime type, not the encoding.
			 */
			if ( ( $pos = strpos( $mimetype, ';' ) ) !== false ) {
				$mimetype = substr( $mimetype, 0, $pos );
			}
			finfo_close( $finfo );
		}

		if ( empty( $mimetype ) ) {
			$pi = pathinfo( $filename );
			switch ( strtolower( $pi[ 'extension' ] ) ) {
				// hacky, hacky, kludge, kludge...
				case 'jpg':
				case 'jpeg':
					$mimetype = 'image/jpeg';
					break;
				case 'gif':
					$mimetype = 'image/gif';
					break;
				case 'png':
					$mimetype = 'image/png';
					break;
				case 'mp3':
					$mimetype = 'audio/mpeg3';
					break;
				case 'wav':
					$mimetype = 'audio/wav';
					break;
				case 'mpg':
				case 'mpeg':
					$mimetype = 'video/mpeg';
					break;
				case 'swf':
					$mimetype = 'application/x-shockwave-flash';
					break;
				case 'htm':
				case 'html':
				$mimetype = 'text/html';
				break;
			}
		}
		$mimetype = Plugins::filter( 'get_mime_type', $mimetype, $filename );
		return $mimetype;
	}

	/**
	 * Returns a trailing slash or a string, depending on the value passed in
	 *
	 * @param mixed $value A trailing string value
	 * @return string A slash if true, the value if value passed, emptystring if false
	 */
	public static function trail( $value = false )
	{
		if ( $value === true ) {
			return '/';
		}
		elseif ( $value ) {
			return $value;
		}
		return '';
	}

	/**
	 * Send email
	 *
	 * @param string $to The destination address
	 * @param string $subject The subject of the message
	 * @param string $message The message itself
	 * @param array $headers An array of key=>value pairs for additional email headers
	 * @param string $parameters Additional parameters to mail()
	 * @return boolean True if sending the message succeeded
	 */
	public static function mail( $to, $subject, $message, $headers = array(), $parameters = '' )
	{
		$mail = array(
			'to' => $to,
			'subject' => $subject,
			'message' => $message,
			'headers' => $headers,
			'parameters' => $parameters,
		);
		$mail = Plugins::filter( 'mail', $mail );

		$handled = false;
		$handled = Plugins::filter( 'send_mail', $handled, $mail );
		if ( $handled ) {
			return true;
		}
		else {
			$additional_headers = array();
			foreach ( $headers as $header_key => $header_value ) {
				$header_key = trim( $header_key );
				$header_value = trim( $header_value );
				if ( strpos( $header_key . $header_value, "\n" ) === false ) {
					$additional_headers[] = "{$header_key}: {$header_value}";
				}
			}
			$additional_headers = implode( "\r\n", $additional_headers );
		}
		return mail( $to, $subject, $message, $additional_headers, $parameters );
	}

	/**
	 * Create a random password of a specific length
	 *
	 * @param integer $length Length of the password, if not provded, 10
	 * @return string A random password
	 */
	public static function random_password( $length = 10 )
	{
		$password = '';
		$character_set = '1234567890!@#$^*qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVNBM';
		$data = str_split( $character_set );
		for ( $i = 0; $i < $length; $i++ ) {
			$password .= $data[rand( 1, strlen( $character_set ) ) - 1];
		}
		return $password;
	}

	/**
	 * Does a bitwise OR of all the numbers in an array
	 * @param array $input An array of integers
	 * @return int The bitwise OR of the input array
	 */
	public static function array_or( $input )
	{
		return array_reduce( $input, array( 'Utils', 'ror' ), 0 );
	}

	/**
	 * Helper function for array_or
	 */
	public static function ror( $v, $w )
	{
		return $v |= $w;
	}

	/**
	 * Checks whether the correct HTTP method was used for the request
	 *
	 * @param array $expected Expected HTTP methods for the request
	 */
	public static function check_request_method( $expected )
	{
		if ( !in_array( $_SERVER['REQUEST_METHOD'], $expected ) ) {
			if ( in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD', 'POST', 'PUT', 'DELETE' ) ) ) {
				header( 'HTTP/1.1 405 Method Not Allowed', true, 405 );
			}
			else {
				header( 'HTTP/1.1 501 Method Not Implemented', true, 501 );
			}
			header( 'Allow: ' . implode( ',', $expected ) );
			exit;
		}
	}

	/**
	 * Returns a regex pattern equivalent to the given glob pattern
	 *
	 * @return string regex pattern with '/' delimiter
	 */
	public static function glob_to_regex( $glob )
	{
		$pattern = $glob;
		// braces need more work
		$braces = array();
		if ( preg_match_all( '/\{.*?\}/', $pattern, $m ) ) {
			foreach ( $m[0] as $raw_brace ) {
				$braces[ preg_quote( $raw_brace ) ] = '(?:' . str_replace( ',', '|', preg_quote( substr( $raw_brace, 1, -1 ), '/' ) ) . ')';
			}
		}
		$pattern = preg_quote( $pattern, '/' );
		$pattern = str_replace( '\\*', '.*', $pattern );
		$pattern = str_replace( '\\?', '.', $pattern );
		$pattern = str_replace( array_keys( $braces ), array_values( $braces ), $pattern );
		return '/' . $pattern . '/';
	}

	/**
	 * Return the port used for a specific URL scheme
	 *
	 * @param string $scheme The scheme in question
	 * @return integer the port used for the scheme
	 */
	public static function scheme_ports( $scheme = null )
	{
		$scheme_ports = array(
			'ftp' => 21,
			'ssh' => 22,
			'telnet' => 23,
			'http' => 80,
			'pop3' => 110,
			'nntp' => 119,
			'news' => 119,
			'irc' => 194,
			'imap3' => 220,
			'https' => 443,
			'nntps' => 563,
			'imaps' => 993,
			'pop3s' => 995,
		);
		if ( is_null( $scheme ) ) {
			return $scheme_ports;
		}
		return $scheme_ports[ $scheme ];
	}

	/**
	 * determines if the given that is travesable in foreach
	 *
	 * @param mixed $data
	 * @return bool
	 */
	public static function is_traversable( $data )
	{
		return ( is_array( $data ) || ( $data instanceof Traversable && $data instanceof Countable ) );
	}

	/**
	* Get the remote IP address, but try and take into account users who are
	* behind proxies, whether they know it or not.
	* @return The client's IP address.
	*/
	public static function get_ip( $default = '0.0.0.0' )
	{
		// @todo in particular HTTP_X_FORWARDED_FOR could be a comma-separated list of IPs that have handled it, the client being the left-most. we should handle that...
		$keys = array( 'HTTP_CLIENT_IP', 'HTTP_FORWARDED', 'HTTP_X_FORWARDED', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR' );
		
		$return = '';
		foreach ( $keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$return = $_SERVER[ $key ];
			}
		}
		
		// make sure whatever IP we got was valid
		$valid = filter_var( $return, FILTER_VALIDATE_IP );
		
		if ( $valid === false ) {
			return $default;
		}
		else {
			return $return;
		}
		
	}

	/**
	* Call htmlspecialchars() with the correct flags and encoding,
	*  without double escaping strings.
	* See http://php.net/manual/en/function.htmlspecialchars.php for details on the parameters
	* and purpose of the function.
	*
	* @todo Should htmlspecialchars_decode() be used instead of html_entity_decode()?
	*
	* @param string|array $string The string or array of strings to escape
	* @param integer $quote_flag Sets what quotes and doublequotes are escaped
	* @param string $encoding The encoding of the passed string
	* @param boolean $decode Whether or not to unescape any html entities first
	* @param boolean $double_encode Whether or not to double escape any html entities
	*
	* @return The escaped string
	*/
	public static function htmlspecialchars( $string, $quote_flag = ENT_COMPAT, $encoding = 'UTF-8', $decode = true, $double_encode = true )
	{
		if(is_array($string)) {
			if( $decode ) {
				return array_map(
					function($v) use($quote_flag, $encoding, $decode, $double_encode) {
						return self::htmlspecialchars($v, $quote_flag, $encoding, $decode, $double_encode);
					},
					$string
				);
			}
		}
		else {
			if( $decode ) {
				$string = html_entity_decode($string, ENT_QUOTES, $encoding );
			}
			return htmlspecialchars( $string, $quote_flag, $encoding, $double_encode );
		}
	}

	/**
	* Convenience function to find a usable PCRE regular expression
	* delimiter for a particular string.  (I.e., some character that
	* *isn't* found in the string.)
	*
	* @param $string. string. The string for which to find a delimiter.
	* @param $choices. string. Delimiters from which to choose one.
	* @param $encoding. string. The encoding of the passed string
	*
	* @return A valid regex delimiter, or null if none of the choices work.
	*/
	public static function regexdelim( $string, $choices = null )
	{
		/*
		 * Supply some default possibilities for delimiters if we
		 * weren't given an explicit list.
		 */
		if ( ! isset( $choices ) ) {
			$choices = sprintf( '%c%c%c%c%c%c%c',
				167, /* § */
				164, /* ¤ */
				165, /* ¥ */
				ord( '`' ),
				ord( '~' ),
				ord( '%' ),
				ord( '#' )
			);
		}
		$a_delims = str_split( $choices );
		/*
		 * Default condition is 'we didn't find one.'
		 */
		$delim = null;
		/*
		 * Check for each possibility by scanning the text for it.
		 * If it isn't found, it's a valid choice, so break out of the
		 * loop.
		 */
		foreach ( $a_delims as $tdelim ) {
			if ( ! strstr( $string, $tdelim ) ) {
				$delim = $tdelim;
				break;
			}
		}
		return $delim;
	}

	/**
	 * Create a list of html element attributes from an associative array
	 * 
	 * @param array $attrs An associative array of parameters
	 * @return string The parameters turned into a string of tag attributes
	 */
	public static function html_attr($attrs)
	{
		$out = '';
		foreach($attrs as $key => $value) {
			$value = is_array($value) ? implode(' ', $value) : $value;
			if($value != '') {
				$out .= ($out == '' ? '' : ' ') . $key . '="' . Utils::htmlspecialchars($value) . '"';
			}
		}
		return $out;
	}

	/**
	 * Get a list of the PHP ini settings relevant to Habari
	 *
	 * @return Array The relevant PHP ini settings as array of strings
	 */
	public static function get_ini_settings()
	{
		$settings = array();
		$keys = array(
			'safe_mode',
			'open_basedir',
			'display_errors',
			'session.gc_probability',
			'session.gc_maxlifetime',
			'error_reporting',
			'memory_limit',
		);
		foreach($keys as $key ) {
			$val = ini_get( $key );
			if ( $val === false ) {
				$settings[] = $key . ': ' . _t( 'Not set' );
			}
			else {
				$settings[] = $key . ': ' . ( strlen( $val ) ? $val : '0' );
			}
		}
		return $settings;
	}

	/**
	 * Are we in a testing environment?
	 * @static
	 * @param string $key The querystring key that can specify the environment to use
	 * @return bool True if this is a test environment.
	 */
	public static function env_test($key = '_useenv')
	{
		return Utils::env_is('test', $key);
	}

	/**
	 * Are we in a specific environment?
	 * @static
	 * @param string $env The environment to test for
	 * @param string $key The querystring key that can specify the environment to use
	 * @return bool True if this is a test environment.
	 */
	public static function env_is($env, $key = '_useenv')
	{
		if(defined('UNIT_TEST')) {
			$_GET[$key] = is_string(UNIT_TEST) ? UNIT_TEST : 'test';
		}
		if(
			(isset($_GET[$key]) && $_GET[$key] == $env) ||
			(isset($_COOKIE[$key]) && $_COOKIE[$key] == $env && (!isset($_GET[$key]) || $_GET[$key] == $env))
		) {
			setcookie($key, $env);
			return true;
		}
		else {
			setcookie($key, false);
			return false;
		}
	}

	/**
	 * Given an array of arrays, return an array that contains the value of a particular common field
	 * Example:
	 * $a = array(
	 *   array('foo'=>1, 'bar'=>2),
	 *   array('foo'=>3, 'bar'=>4),
	 * );
	 * $b = Utils::array_map_field($a, 'foo'); // $b = array(1, 3);
	 *
	 * @param Traversable $array An array of arrays or objects with similar keys or properties
	 * @param string $field The name of a common field within each array/object
	 * @return array An array of the values of the specified field within each array/object
	 */
	public static function array_map_field($array, $field, $key = null)
	{
		if(count($array) == 0) {
			return $array;
		}
		if(is_null($key)) {
			if($array instanceof ArrayObject) {
				$array = $array->getArrayCopy();
			}
			return array_map( function( $element ) use ($field) {
				return is_array($element) ? $element[$field] : (is_object($element) ? $element->$field : null);
			}, $array);
		}
		else {
			return array_combine(
				Utils::array_map_field($array, $key),
				Utils::array_map_field($array, $field)
			);
		}
	}

	/**
	 * Replace shortcodes in content with shortcode output
	 * @static
	 * @param string $content The content within which to replace shortcodes
	 * @param Object $obj_context The object context in which the content was found
	 * @return string The content with shortcodes replaced
	 */
	public static function replace_shortcodes($content, $obj_context)
	{
		$regex = '%\[(\w+?)(?:\s+([^\]]+?))?/\]|\[(\w+?)(?:\s+(.+?))?(?<!/)](?:(.*?)\[/\3])%si';
		if(preg_match_all($regex, $content, $match_set, PREG_SET_ORDER)) {
			foreach($match_set as $matches) {
				$matches = array_pad($matches, 6, '');
				$code = $matches[1] . $matches[3];
				$attrs = $matches[2] . $matches[4];
				$code_contents = $matches[5];
				if(preg_match($regex, $code_contents)) {
					$code_contents = self::replace_shortcodes($code_contents, $obj_context);
				}
				preg_match_all('#(\w+)\s*=\s*(?:(["\'])?(.*?)\2|(\S+))#i', $attrs, $attr_match, PREG_SET_ORDER);
				$attrs = array();
				foreach($attr_match as $attr) {
					$attr = array_pad($attr, 5, '');
					$attrs[$attr[1]] = $attr[3] . $attr[4];
				}
				$replacement = Plugins::filter('shortcode_' . $code, $matches[0], $code, $attrs, $code_contents, $obj_context);
				$content = str_replace($matches[0], $replacement, $content);
			}
		}
		return $content;
	}

	public static function setup_wsse() {
		$wsse = self::WSSE();
		$inputs = array();
		$inputs[] = array('type' => 'hidden', 'value' => $wsse['nonce'], 'name' => 'nonce', 'id' => 'nonce');
		$inputs[] = array('type' => 'hidden', 'value' => $wsse['digest'], 'name' => 'digest', 'id' => 'digest');
		$inputs[] = array('type' => 'hidden', 'value' => $wsse['timestamp'], 'name' => 'timestamp', 'id' => 'timestamp');
		return self::html_inputs( $inputs );
	}

	/**
	 * Verify WSSE values passed in.
	 * @static
	 * @param array $data payload from a given request, needs to include 'nonce', 'timestamp', and 'digest' as generated by Utils::WSSE()
	 * @param bool $anyverb If true, act on any request verb, not just POST.
	 * @return bool True if the WSSE values passed are valid
	 */
	public static function verify_wsse($data, $anyverb = false) {
		$pass = true;
		if( $anyverb || $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if($data instanceof ActionHandler) {
				$extract = $data->handler_vars->filter_keys( 'nonce', 'timestamp', 'digest' );
			}
			elseif($data instanceof SuperGlobal) {
				$extract = $data->filter_keys( 'nonce', 'timestamp', 'digest' );
			}
			elseif(is_array($data)) {
				$extract = array_intersect_key($data, array( 'nonce' => 1, 'timestamp' => 1, 'digest' => 1 ));
			}
		
			foreach ( $extract as $key => $value ) {
				$$key = $value;
			}
	
			if ( empty( $nonce ) || empty( $timestamp ) || empty( $digest ) ) {
				$pass = false;
			}
	
			if( $pass == true ) {
				$check = self::WSSE( $nonce, $timestamp );
				if ( $digest != $check['digest'] ) {
					$pass = false;
				}
			}
		}
		return $pass;
	}
}
?>
