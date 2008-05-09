<?php
/**
 * Habari Utility Class
 *
 * @package Habari
 */

class Utils
{
    public static $debug_defined = false;

	/**
	 * Utils constructor
	 * This class should not be instantiated.
	 **/
	private function __construct()
	{
	}

	/**
	 * function get_params
	 * Returns an associative array of parameters, whether the input value is
	 * a querystring or an associative array.
	 * @param mixed An associative array or querystring parameter list
	 * @return array An associative array of parameters
	 **/
	public static function get_params( $params )
	{
		if( is_array( $params ) ) return $params;
		$paramarray= array();
		parse_str( $params, $paramarray );
		return $paramarray;
	}

	/**
	 * function end_in_slash
	 * Forces a string to end in a single slash
	 * @param string A string, usually a path
	 * @return string The string with the slash added or extra slashes removed, but with one slash only
	 **/
	public static function end_in_slash( $value )
	{
		return rtrim($value, '\\/') . '/';
	}

	/**
	 * function redirect
	 * Redirects the request to a new URL
	 * @param string $url The URL to redirect to, or omit to redirect to the current url
	 **/
	public static function redirect( $url = '' )
	{
		if($url == '') {
			$url = Controller::get_full_url() . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
		}
		header('Location: ' . $url, true, 302);
	}

	/**
	 * function atomtime
	 * Returns RFC-3339 time from a time string or integer timestamp
	 * @param mixed A string of time or integer timestamp
	 * @return string An RFC-3339 formatted time
	 **/
	public static function atomtime($t)
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
	 **/
	public static function nonce()
	{
		return sprintf('%06x', rand(0, 16776960)) . sprintf('%06x', rand(0, 16776960));
	}

	/**
	 * function WSSE
	 * returns an array of tokens used for WSSE authentication
	 *    http://www.xml.com/pub/a/2003/12/17/dive.html
	 *    http://www.sixapart.com/developers/atom/protocol/atom_authentication.html
	 * @param String a nonce
	 * @param String a timestamp
	 * @return Array an array of WSSE authentication elements
	**/
	public static function WSSE( $nonce = '', $timestamp = '' )
	{
		if ( '' === $nonce )
		{
			$nonce= Utils::crypt( Options::get('GUID') . Utils::nonce() );
		}
		if ( '' === $timestamp )
		{
			$timestamp= date('c');
		}
		$user= User::identify();
		$wsse= array(
			'nonce' => $nonce,
			'timestamp' => $timestamp,
			'digest' => base64_encode(pack('H*', sha1($nonce . $timestamp .  $user->password)))
			);
		return $wsse;
	}

	/**
	 * function stripslashes
	 * Removes slashes from escaped strings, including strings in arrays
	 **/
	public static function stripslashes($value)
	{
		if ( is_array($value) ) {
			$value = array_map( array('Utils', 'stripslashes') , $value );
		}	elseif ( !empty($value) && is_string($value) ) {
			$value = stripslashes($value);
		}
		return $value;
	}

	/**
	 * function de_amp
	 * Returns &amp; entities in a URL querystring to their previous & glory, for use in redirects
	 * @param string $value A URL, maybe with a querystring
	 **/
	public static function de_amp($value)
	{
		$url = InputFilter::parse_url( $value );
		$url['query'] = str_replace('&amp;', '&', $url['query']);
		return InputFilter::glue_url($url);
	}

	/**
	 * Restore a URL separated by a parse_url() call.
	 * @param $parsed array An array as returned by parse_url()
	 **/
	public static function glue_url($parsed)
	{
		if ( ! is_array( $parsed ) ) {
			return false;
		}
		$uri= isset( $parsed['scheme'] )
			? $parsed['scheme'] . ':' . ( ( strtolower( $parsed['scheme'] ) == 'mailto' ) ? '' : '//' )
			: '';
		$uri.= isset( $parsed['user'] )
			? $parsed['user'].( $parsed['pass'] ? ':' . $parsed['pass'] : '' ) . '@'
			: '';
		$uri.= isset( $parsed['host'] ) ? $parsed['host'] : '';
		$uri.= isset( $parsed['port'] ) ? ':'.$parsed['port'] : '';
		$uri.= isset( $parsed['path'] ) ? $parsed['path'] : '';
		$uri.= isset( $parsed['query'] ) ? '?'.$parsed['query'] : '';
		$uri.= isset( $parsed['fragment'] ) ? '#'.$parsed['fragment'] : '';

		return $uri;
	}

	/**
	 * function revert_magic_quotes_gpc
	 * Reverts magicquotes_gpc behavior
	 **/
	public static function revert_magic_quotes_gpc()
	{
	    /* We should only revert the magic quotes once per page hit */
	    static $revert = true;
	    if ( get_magic_quotes_gpc() && $revert) {
		$_GET = self::stripslashes($_GET);
		$_POST = self::stripslashes($_POST);
		$_COOKIE = self::stripslashes($_COOKIE);
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
		return (strpos($value, ' ') === false) ? $value : '"' . $value . '"';
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
		if ( ! is_array( $values ) )
		{
			$values = array();
		}
		$values = array_map(array('Utils', 'quote_spaced'), $values);
		return implode( $separator, $values );
	}

	/**
	 * function archive_pages
	 * Returns the number of pages in an archive using the number of items per page set in options
	 * @param integer Number of items in the archive
	 * @returns integer Number of pages based on pagination option.
	 **/
	public static function archive_pages($item_total)
	{
		return ceil($item_total / Options::get('pagination'));
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
	public static function map_array($value, $prefix = '{$', $postfix = '}')
	{
		return $prefix . $value . $postfix;
	}

	/**
	 * Helper function used by debug()
	 * Not for external use.
	 **/
	public static function debug_reveal($show, $hide, $debugid, $close = false)
	{
		$reshow = $restyle = $restyle2 = '';
		if($close) {
			$reshow = "onclick=\"debugtoggle('debugshow-{$debugid}');debugtoggle('debughide-{$debugid}');return false;\"";
			$restyle = "<span class=\"utils__block\">";
			$restyle2 = "</span>";
		}
		return "<span class=\"utils__arg\"><a href=\"#\" id=\"debugshow-{$debugid}\" onclick=\"debugtoggle('debugshow-{$debugid}');debugtoggle('debughide-{$debugid}');return false;\">$show</a><span style=\"display:none;\" id=\"debughide-{$debugid}\" {$reshow} >{$restyle}$hide{$restyle2}</span></span>";
	}

	/**
	 * Outputs a call stack with parameters, and a dump of the parameters passed.
	 * @params mixed Any number of parameters to output in the debug box.
	 **/
	public static function debug()
	{
		$debugid= md5(microtime());
		$tracect= 0;

		$fooargs = func_get_args();
		echo "<div class=\"utils__debugger\">";
		if(!self::$debug_defined) {
			$output = "<script type=\"text/javascript\">
				debuggebi = function(id) {return document.getElementById(id);}
				debugtoggle = function(id) {debuggebi(id).style.display = debuggebi(id).style.display=='none'?'inline':'none';}
				</script>
				<style type=\"text/css\">
				.utils__debugger{background-color:#550000;border:1px solid red;text-align:left;}
				.utils__debugger pre{margin:5px;background-color:#000}
				.utils__debugger pre em{color:#dddddd;}
				.utils__debugger table{background-color:#770000;color:white;width:100%;}
				.utils__debugger tr{background-color:#000000;}
				.utils__debugger td{padding-left: 10px;vertical-align:top;white-space: pre;font-family:Courier New,Courier,monospace;}
				.utils__debugger .utils__odd{background:#880000;}
				.utils__debugger .utils__arg a{color:#ff3333;}
				.utils__debugger .utils__arg span{display:none;}
				.utils__debugger .utils__arg span span{display:inline;}
				.utils__debugger .utils__arg span .utils__block{display:block;background:#990000;margin:0px 2em;-moz-border-radius:10px;padding:5px;}
				</style>
			";
			echo $output;
			self::$debug_defined = true;
		}
		if(function_exists('debug_backtrace')) {
			$output = "<table>";
			$backtrace = array_reverse(debug_backtrace(), true);
			$odd = '';
			$tracect = 0;
			foreach($backtrace as $trace) {
				$file = $line = $class = $type = $function = '';
				$args= array();
				extract($trace);
				if(isset($class))	$fname = $class . $type . $function; else	$fname = $function;
				if(!isset($file) || $file=='') $file = '[Internal PHP]'; else $file = basename($file);
				$odd = $odd == '' ? 'class="utils__odd"' : '';
				$output .= "<tr {$odd}><td>{$file} ({$line}):</td><td>{$fname}(";
				$comma = '';
				foreach((array)$args as $arg) {
					$tracect++;
					$argout = print_r($arg,1);
					$output .= $comma . Utils::debug_reveal( gettype($arg), htmlentities($argout), $debugid . $tracect, true );
					$comma = ', ';
				}
				$output .= ");</td></tr>";
			}
			$output .= "</table>";
			echo Utils::debug_reveal('<small>Call Stack</small>', $output, $debugid);
		}
		echo "<pre style=\"color:white;\">";
		foreach( $fooargs as $arg1 ) {
			echo '<em>' . gettype($arg1) . '</em> ';
			echo htmlentities( print_r( $arg1, TRUE ) ) . "<br>";
		}
		echo "</pre></div>";
	}

	/**
	 * Outputs debug information like ::debug() but using Firebug's Console.
	 * @params mixed Any number of parameters to output in the debug box.
	 **/
	public static function firedebug()
	{
		$fooargs = func_get_args();
		$output = "<script type=\"text/javascript\">\nif(window.console){\n";
		$backtrace = array_reverse(debug_backtrace(), true);
		$output .= Utils::firebacktrace($backtrace);

		foreach( $fooargs as $arg1 ) {
			$output .= "console.info(\"%s:  %s\", \"" . gettype($arg1) . "\"";
			$output .= ", \"" . str_replace("\n", '\n', addslashes(print_r($arg1,1))) . "\");\n";
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
	public static function firebacktrace($backtrace)
	{
		$output = '';
		extract(end($backtrace));
		if(isset($class))	$fname = $class . $type . $function; else	$fname = $function;
		if(!isset($file) || $file=='') $file = '[Internal PHP]'; else $file = basename($file);
		$output .= "console.group(\"%s(%s):  %s(...)\", \"".basename($file)."\", \"{$line}\", \"{$fname}\");\n";
		foreach($backtrace as $trace) {
			$file = $line = $class = $type = $function = '';
			$args= array();
			extract($trace);
			if(isset($class))	$fname = $class . $type . $function; else	$fname = $function;
			if(!isset($file) || $file=='') $file = '[Internal PHP]'; else $file = basename($file);

			$output .= "console.group(\"%s(%s):  %s(%s)\", \"{$file}\", \"{$line}\", \"{$fname}\", \"";

			$output2 = $comma = $argtypes = '';
			foreach((array)$args as $arg) {
				$argout = str_replace("\n", '\n', addslashes(print_r($arg,1)));
				//$output .= $comma . Utils::debug_reveal( gettype($arg), htmlentities($argout), $debugid . $tracect, true );
				$argtypes .= $comma . gettype($arg);
				$output2 .= "console.log(\"$argout\");\n";
				$comma = ', ';
			}
			$argtypes = trim($argtypes);
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
	public static function crypt( $password, $hash= NULL )
	{
		if ( $hash == NULL ) {
			// encrypt
			if ( function_exists( 'hash' ) ) { // PHP >= 5.1.2
				return self::ssha512( $password, $hash );
			}
			else {
				return self::ssha( $password, $hash );
			}
		}
		elseif ( strlen( $hash ) > 3 ) { // need at least {, } and a char :p
			// verify
			if ( $hash{0} == '{' ) {
				// new hash from the block
				$algo= strtolower( substr( $hash, 1, strpos( $hash, '}', 1 ) - 1 ) );
				switch ( $algo ) {
					case 'sha1':
					case 'ssha':
					case 'ssha512':
					case 'md5':
						return self::$algo( $password, $hash );
					default:
						Error::raise( sprintf(_t('Unsupported digest algorithm "%s"'), $algo) );
						return FALSE;
				}
			}
			else {
				// legacy sha1
				return ( sha1( $password ) == $hash );
			}
		}
		else {
			Error::raise( 'Invalid hash' );
		}
	}

	/**
	 * Crypt or verify a given password using SHA.
	 *
	 * Passwords should not be stored using this method, but legacy systems might require it.
	 */
	public static function sha1( $password, $hash= NULL ) {
		$marker= '{SHA1}';
		if ( $hash == NULL ) {
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
	public static function md5( $password, $hash= NULL ) {
		$marker= '{MD5}';
		if ( $hash == NULL ) {
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
	public static function ssha( $password, $hash= NULL )
	{
		$marker= '{SSHA}';
		if ( $hash == NULL ) { // encrypt
			// create salt (4 byte)
			$salt= '';
			for ( $i= 0; $i < 4; $i++ ) {
				$salt.= chr( mt_rand( 0, 255 ) );
			}
			// get digest
			$digest= sha1( $password . $salt, TRUE );
			// b64 for storage
			return $marker . base64_encode( $digest . $salt );
		}
		else { // verify
			// is this a SSHA hash?
			if ( ! substr( $hash, 0, strlen( $marker ) ) == $marker ) {
				Error::raise( 'Invalid hash' );
				return FALSE;
			}
			// cut off {SSHA} marker
			$hash= substr( $hash, strlen( $marker ) );
			// b64 decode
			$hash= base64_decode( $hash );
			// split up
			$digest= substr( $hash, 0, 20 );
			$salt= substr( $hash, 20 );
			// compare
			return ( sha1( $password . $salt, TRUE ) == $digest );
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
	public static function ssha512( $password, $hash= NULL )
	{
		$marker= '{SSHA512}';
		if ( $hash == NULL ) { // encrypt
			$salt= '';
			for ( $i= 0; $i < 4; $i++ ) {
				$salt.= chr( mt_rand( 0, 255 ) );
			}
			$digest= hash( 'sha512', $password . $salt, TRUE );
			return $marker . base64_encode( $digest . $salt );
		}
		else { // verify
			if ( ! substr( $hash, 0, strlen( $marker ) ) == $marker ) {
				Error::raise( 'Invalid hash' );
				return FALSE;
			}
			$hash= substr( $hash, strlen( $marker ) );
			$hash= base64_decode( $hash );
			$digest= substr( $hash, 0, 64 );
			$salt= substr( $hash, 64 );
			return ( hash( 'sha512', $password . $salt, TRUE ) == $digest );
		}
	}

	/**
	 * Return an array of date information
	 * Just like getdate() but also returns 0-padded versions of day and month in mday0 and mon0
	 * @param integer $timestamp A unix timestamp
	 * @return array An array of date data
	 */
	public static function getdate($timestamp)
	{
		$info= getdate($timestamp);
		$info['mon0']= substr('0' . $info['mon'], -2, 2);
		$info['mday0']= substr('0' . $info['mday'], -2, 2);
		return $info;
	}

	/**
	 * Return a formatted date/time trying to use strftime() AND date()
	 * @param string $format The format for the date.  If it contains non-escaped percent signs, it uses strftime(),	otherwise date()
	 * @param integer $timestamp The unix timestamp of the time to format
	 * @return string The formatted time
	 **/
	public static function locale_date($format, $timestamp)
	{
		$matches= preg_split( '/((?<!\\\\)%[a-z]\\s*)/i', $format, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$output= '';
		foreach( $matches as $match ) {
			if( $match{0} == '%' ) {
				$output.= strftime($match, $timestamp);
			}
			else {
				$output.= date($match, $timestamp);
			}
		}
		return $output;
	}

	/**
	 * Return a sanitized slug, replacing non-alphanumeric characters to dashes
	 * @param string $string The string to sanitize. Non-alphanumeric characters will be replaced by dashes
	 * @return string The sanitized slug
	 */
	public static function slugify( $string )
	{
		// Replace non-alphanumeric characters to dashes. Exceptions: %, _, -
		// Note that multiple separators are collapsed automatically by the preg_replace.
		// Convert all characters to lowercase.
		// Trim spaces on both sides.
		$slug= rtrim( strtolower( preg_replace( '/[^a-z0-9%_\-]+/i', '-', $string ) ), '-' );

		// Let people change the behavior.
		$slug= Plugins::filter('slugify', $slug, $string);

		return $slug;
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
	public static function html_select( $name, $options, $current = null, $properties = array())
	{
		$output= '<select id="' . $name . '" name="' . $name . '"';
		foreach($properties as $key => $value) {
			$output.= " {$key}=\"{$value}\"";
		}
		$output.= ">\n";
		foreach($options as $value => $text){
			$output.= '<option value="'.$value.'"';
			if($current == (string)$value) {
				$output.= ' selected';
			}
			$output.= '>' . $text . "</option>\n";
		}
		$output.= '</select>';
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
	**/
	public static function html_checkboxes( $name, $options )
	{
		$output= '';
		$multi= false;
		if ( count( $options > 1 ) ) {
			$multi= true;
		}
		foreach ( $options as $option ) {
			$output.= '<input type="checkbox" id="' . $option['name'] . '" name="' . $option['name'];
			if ( $multi ) {
				$output.= '[]';
			}
			$output.= '" value="' . $option['value'] . '"';
			if ( isset($option['checked']) )
			{
				$output.= ' checked';
			}
			$output.= '>';
		}
		return $output;
	}

	/**
	 * Trims longer phrases to shorter ones with elipsis in the middle
	 * @param string The string to truncate
	 * @param integer The length of the returned string
	 * @param bool Whether to place the ellipsis in the middle (true) or
	 *	at the end (false)
	 * @return string The truncated string
	**/
	public static function truncate($str, $len=10, $middle=true)
	{
	        // make sure $len is a positive integer
	        if ( ! is_numeric($len) || ( 0 > $len ) ) {
	                return $str;
	        }
	        // if the string is less than the length specified, bail out
	        if ( iconv_strlen($str) <= $len ) {
	                return $str;
	        }

	        // okay.  Shuold we place the ellipse in the middle?
	        if ($middle) {
	                // yes, so compute the size of each half of the string
	                $len = round(($len-3)/2);
	                // and place an ellipse in between the pieces
	                return iconv_substr($str, 0, $len) . '...' . substr($str, -$len);
	        } else {
	                // no, the ellipse goes at the end
	                $len= $len-3;
	                return iconv_substr($str, 0, $len ) . '...';
	        }
	}

	/**
	 * Check the PHP syntax of the specified code.
	 * Performs a syntax (lint) check on the specified code testing for scripting errors.
	 *
	 * @param string $code The code string to be evaluated. It does not have to contain PHP opening tags.
	 * @return bool Returns TRUE if the lint check passed, and FALSE if the link check failed.
	 */
	public static function php_check_syntax( $code, &$error = null )
	{
		$b= 0;

		foreach ( token_get_all( $code ) as $token ) {
			if ( is_array( $token ) ) {
				$token= token_name( $token[0] );
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
			$error = _t('Unbalanced braces.');
			return false; // Unbalanced braces would break the eval below
		}
		else {
			ob_start(); // Catch potential parse error messages
			$display_errors= ini_set( 'display_errors', 'on' ); // Make sure we have something to catch
			$error_reporting= error_reporting( E_ALL ^ E_NOTICE );
			$code = eval( ' if(0){' . $code . '}' ); // Put $code in a dead code sandbox to prevent its execution
			ini_set( 'display_errors', $display_errors ); // be a good citizen
			error_reporting($error_reporting);
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
		$code= ' ?>' . file_get_contents( $file ) . '<?php ';

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
			$results= glob( $pattern, $flags );
		}
		elseif ( ! preg_match_all( '/\{.*?\}/', $pattern, $m ) ) {
			// GLOB_BRACE used, but this pattern doesn't even use braces
			$results= glob( $pattern, $flags ^ GLOB_BRACE );
		}
		else {
			// pattern uses braces, but platform doesn't support GLOB_BRACE
			$braces= array();
			foreach ( $m[0] as $raw_brace ) {
				$braces[ preg_quote( $raw_brace ) ] = '(?:' . str_replace( ',', '|', preg_quote( substr( $raw_brace, 1, -1 ), '/' ) ) . ')';
			}
			$new_pattern= preg_replace( '/\{.*?\}/', '*', $pattern );
			$pattern= preg_quote( $pattern, '/' );
			$pattern= str_replace( '\\*', '.*', $pattern );
			$pattern= str_replace( '\\?', '.', $pattern );
			$regex= '/' . str_replace( array_keys( $braces ), array_values( $braces ), $pattern ) . '/';
			$results= preg_grep( $regex, Utils::glob( $new_pattern, $flags ^ GLOB_BRACE) );
		}

		if ( $results === false ) $results= array();
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
		$max_tick = count($sizes) - 1;
		while($bytesize > 1024 && $tick < $max_tick) {
			$tick++;
			$bytesize /= 1024;
		}

		return sprintf('%0.2f%s', $bytesize, $sizes[$tick]);
	}

	public static function truncate_log() {
		// Truncate the log table
		return DB::exec('DELETE FROM {log} WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL 14 DAY)');
	}

	/**
	 * Convert a single non-array variable into an array with that one element
	 *
	 * @param mixed $element Some value, either an array or not
	 * @return array Either the original array value, or the passed value as the single element of an array
	 */
	public static function single_array( $element )
	{
		if(!is_array($element)) {
			return array($element);
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
		if(function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
		}
		else if(function_exists('mime_content_type')) {
			$mimetype = mime_content_type( $filename );
		}
		if( empty( $mimetype ) ) {
			$pi = pathinfo($filename);
			switch(strtolower($pi['extension'])) {
				// hacky, hacky, kludge, kludge...
				case 'jpg': $mimetype = 'image/jpeg'; break;
				case 'gif': $mimetype = 'image/gif'; break;
				case 'png': $mimetype = 'image/png'; break;
				case 'mp3': $mimetype = 'audio/mpeg3'; break;
				case 'wav': $mimetype = 'audio/wav'; break;
				case 'mpg': $mimetype = 'video/mpeg'; break;
				case 'swf': $mimetype = 'application/x-shockwave-flash'; break;
			}
		}
		$mimetype = Plugins::filter('get_mime_type', $mimetype, $filename);
		return $mimetype;
	}

}
?>
