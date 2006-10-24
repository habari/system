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
		if(is_array($params)) return $params;
		parse_str($params, $paramarray);
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
			echo htmlentities( print_r( $arg1, 1 ) ) . "<br/>";
		}
		echo "</pre></div>";
	}

}


?>
