<?php
/**
 * Habari Utility Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
 
class Utils
{
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
		return rtrim($value, '/') . '/';
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
	 * @return string An FRC-3339 formatted time	 	 
	 **/
	static function atomtime($t)
	{
		if ( ! is_numeric( $t ) ) {
			$t = strtotime( $t );
		}
		$vdate = date( DATE_ATOM, $t );
		// If the date format used for timezone was O instead of P... 
		if ( substr( $vdate, -3, 1 ) != ':' ) {
			$vdate = substr( $vdate, 0, -2) . ':' . substr( $vdate, -2, 2 );
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
	 * @param string A URL, maybe with a querystring	 
	 **/
	static function de_amp($value)
	{
		$url = parse_url( $value );
		$url['query'] = str_replace('&amp;', '&', $url['query']);
		return Utils::glue_url($url);
	}

	/**
	 * function glue_url
	 * Restores a URL separated by a parse_url() call.
	 * $params array The results of a parse_url() call
	 **/	 	 	 
	function glue_url($parsed)
	{
		if (! is_array($parsed)) return false;
		$uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
		$uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
		$uri .= isset($parsed['host']) ? $parsed['host'] : '';
		$uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
		$uri .= isset($parsed['path']) ? $parsed['path'] : '';
		$uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
		$uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
		return $uri;
	}	
	
	/**
	 * function tag_and_list
	 * Formatting function (should be in Format class?)
	 * Turns an array of tag names into an HTML-linked list with command and an "and".
	 * @param array An array of tag names
	 * @param string Text to put between each element
	 * @param string Text to put between the next to last element and the last element
	 * @return string HTML links with specified separators.
	 **/	 	 	 	 	  
	static function tag_and_list($array, $between = ', ', $between_last = ' and ')
	{
		$fn = create_function('$a', 'return "<a href=\\"" . URL::get( "tag", array( "tag" => $a) ) . "\\">" . $a . "</a>";');
		$array = array_map($fn, $array);
		$last = array_pop($array);
		$out = implode($between, $array);
		$out .= ($out == '') ? $last : $between_last . $last;
		return $out;
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
	 * function debug_reveal
	 * Helper function used by debug()
	 * Not for external use.	 
	 **/	 	 	
	static function debug_reveal($show, $hide, $debugid) 
	{
		return "<a href=\"#\" id=\"debugshow-{$debugid}\" onclick=\"debugtoggle('debugshow-{$debugid}');debugtoggle('debughide-{$debugid}');return false;\">$show</a><span style=\"display:none;\" id=\"debughide-{$debugid}\">$hide</span>";
	}
	
	/**
	 * function debug
	 * Outputs a call stack with parameters, and a dump of the parameters passed.
	 * @params mixed Any number of parameters to output in the debug box.
	 **/	 	 	 	 	
	static function debug()
	{
		$debugid = md5(microtime());

		$fooargs = func_get_args();
		echo "<div style=\"background-color:#ffeeee;border:1px solid red;text-align:left;\">";
		if(function_exists('debug_backtrace')) {
			$output = "<script type=\"text/javascript\">
			debuggebi = function(id) {return document.getElementById(id);}
			debugtoggle = function(id) {debuggebi(id).style.display = debuggebi(id).style.display=='none'?'':'none';}
			</script>
			<table style=\"background-color:#fff8f8;\">";
			$backtrace = array_reverse(debug_backtrace(), true);
			foreach($backtrace as $trace) {
				$file = $line = $class = $type = $function = '';
				extract($trace);
				if(isset($class))	$fname = $class . $type . $function; else	$fname = $function;
				if(!isset($file) || $file=='') $file = '[Internal PHP]'; else $file = basename($file);
					
				$output .= "<tr><td style=\"padding-left: 10px;\">{$file} ({$line}):</td><td style=\"padding-left: 20px;white-space: pre;font-family:Courier New,Courier,monospace\">{$fname}(";
				$comma = '';
				foreach((array)$args as $arg) {
					$tracect++; 
					$output .= $comma . Utils::debug_reveal( gettype($arg), htmlentities(print_r($arg,1)), $debugid . $tracect ); 
					$comma = ', '; 
				}
				$output .= ");</td></tr>"; 
			}
			$output .= "</table>";
			echo Utils::debug_reveal('[Show Call Stack]', $output, $debugid);
		}
		echo "<pre>";
		foreach( $fooargs as $arg1 ) {
			echo '<em>' . gettype($arg1) . '</em> ';
			echo htmlentities( print_r( $arg1, 1 ) ) . "<br/>";
		}
		echo "</pre></div>";
	}

}


?>
