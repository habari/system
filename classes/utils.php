<?php
/**
 * Habari Utility Class
 *
 * @package Habari
 */

class Utils
{
	static $debug_defined = false;

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
	static function get_params( $params )
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
	static function end_in_slash( $value )
	{
		return rtrim($value, '\\/') . '/';
	}

	/**
	 * function redirect
	 * Redirects the request to a new URL
	 * @param string The URL to redirect to
	 **/
	static function redirect( $url )
	{
		header('Location: ' . $url, true, 302);
	}

	/**
	 * function atomtime
	 * Returns RFC-3339 time from a time string or integer timestamp
	 * @param mixed A string of time or integer timestamp
	 * @return string An RFC-3339 formatted time
	 **/
	static function atomtime($t)
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
	static function nonce()
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
	static function WSSE( $nonce = '', $timestamp = '' )
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
	static function stripslashes($value)
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
	static function de_amp($value)
	{
		$url = parse_url( $value );
		$url['query'] = str_replace('&amp;', '&', $url['query']);
		return Utils::glue_url($url);
	}

	/**
	 * Restore a URL separated by a parse_url() call.
	 * @param $parsed array An array as returned by parse_url()
	 **/
	static function glue_url($parsed)
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
	static function revert_magic_quotes_gpc()
	{
		if ( get_magic_quotes_gpc() ) {
			$_GET = self::stripslashes($_GET);
			$_POST = self::stripslashes($_POST);
			$_COOKIE = self::stripslashes($_COOKIE);
		}
	}

	/**
	 * function quote_spaced
	 * Adds quotes around values that have spaces in them
	 * @param string A string value that might have spaces
	 * @return string The string value, quoted if it has spaces
	 */
	static function quote_spaced( $value )
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
	static function implode_quoted( $separator, $values )
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
	static function archive_pages($item_total)
	{
		return ceil($item_total / Options::get('pagination'));
	}

	/**
	 * Build a collection of paginated URLs to be used for pagination.
	 *
	 * @param integer Current page
	 * @param integer Total pages
	 * @param string The RewriteRule name used to build the links.
	 * @param array Various settings used by the method and the RewriteRule.
	 * @return string Collection of paginated URLs built by the RewriteRule.
	 **/
	static function page_selector( $current, $total, $rr_name= NULL, $settings = array() )
	{
		// If RewriteRule name is not supplied, use the current matched rule.
		// Else retrieve the RewriteRule matching the supplied name.
		$rr= ( $rr_name == '' ) ? URL::get_matched_rule() : reset( RewriteRules::by_name( $rr_name ) );
		
		// Retrieve arguments name the RewriteRule can use to build a URL.
		$rr_named_args= $rr->named_args;
		$rr_args= array_merge( $rr_named_args['required'], $rr_named_args['optional']  );
		// For each argument, check if the handler_vars array has that argument and if it does, use it.
		$rr_args_values= array();
		foreach ( $rr_args as $rr_arg ) {
			if ( !isset( $settings[$rr_arg] ) ) {
				$rr_arg_value= Controller::get_var( $rr_arg );
				if ( $rr_arg_value != '' ) {
					$settings[$rr_arg]= $rr_arg_value;
				}
			}
		}
		
		// Make sure the current page is valid
		if ( $current > $total ) {
			$current= $total;
		}
		else if ( $current < 1 ) {
			$current= 1;
		}
		
		// Number of pages to display on each side of the current page.
		$leftSide= isset( $settings['leftSide'] ) ? $settings['leftSide'] : 1;
		$rightSide= isset( $settings['rightSide'] ) ? $settings['rightSide'] : 1;
		
		// Add the page '1'.
		$pages[]= 1;
		
		// Add the pages to display on each side of the current page, based on $leftSide and $rightSide.
		for ( $i= max( $current - $leftSide, 2 ); $i < $total && $i <= $current + $rightSide; $i++ ) {
			$pages[]= $i;
		}
		
		// Add the last page if there is more than one page.
		if ( $total > 1 ) {
			$pages[]= (int) $total;
		}
		
		// Sort the array by natural order.
		natsort( $pages );
		
		// This variable is used to know the last page processed by the foreach().
		$prevpage= 0;
		// Create the output variable.
		$out= '';

		foreach ( $pages as $page ) {
			$settings['page']= $page;
			
			// Add ... if the gap between the previous page is higher than 1.
			if ( ($page - $prevpage) > 1 ) {
				$out.= '&hellip;';
			}
			// Wrap the current page number with square brackets.
			$caption= ( $page == $current ) ? '[' . $current . ']' : $page;
			// Build the URL using the supplied $settings and the found RewriteRules arguments.
			$url= Site::get_url( 'habari', true ) . $rr->build( $settings , false );
			// Build the HTML link.
			$out.= '<a href="' . $url . '" ' . ( ( $page == $current ) ? 'class="current-page"' : '' ) . '>' . $caption . '</a>';
			
			$prevpage= $page;
		}
		
		return $out;
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
	static function map_array($value, $prefix = '{$', $postfix = '}')
	{
		return $prefix . $value . $postfix;
	}

	/**
	 * Helper function used by debug()
	 * Not for external use.
	 **/
	static function debug_reveal($show, $hide, $debugid, $close = false)
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
	static function debug()
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
	static function firedebug()
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
	static function firebacktrace($backtrace)
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
						return self::$algo( $password, $hash );
					default:
						Error::raise( 'Unsupported digest algorithm "' . $algo . '"' );
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
	 * @deprecated Use any of the salted methods instead.
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
		$slug= rtrim( strtolower( preg_replace( '/[^a-z0-9%_\-]+/i', '-', $string ) ), '-' );
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
			if($current == $value) {
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
	function truncate($str, $len=10, $middle=true)
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

}
?>
