<?php
/**
 * Habari Format Class
 *
 * Provides formatting functions for use in themes.  Extendable. 
 * @package Habari
 */
 
class Format 
{
	static private $formatters = null;
	
	/**
	 * function use
	 * Called to register a format function to a plugin hook.
	 * @param string A function name that exists in a Format class
	 * @param string A plugin hook to apply that Format function to as a filter
	 **/	 	 	 	 	
	static function apply($format, $onwhat)
	{
		if( self::$formatters == null ) {
			self::load_all();
		}
	
		foreach(self::$formatters as $formatobj) {
			if( method_exists($formatobj, $format) ) {
				if( func_num_args() > 2 ) {
					$index = array_search($formatobj, self::$formatters);
					$func = '$o = Format::by_index(' . $index . ');return $o->' . $format . '($a, ';
					$args = func_get_args();
					$args = array_map(create_function('$a', 'return "\'{$a}\'";'), array_slice($args, 2));
					$func .= implode(', ', $args) . ');';
					$lambda = create_function('$a', $func);
					Plugins::register( $lambda, 'filter', $onwhat);
				}
				else {
					Plugins::register( array($formatobj, $format), 'filter', $onwhat);
				}
				break;
			}
		}
	}
	
	/**
	 * function by_index
	 * Returns an indexed formatter object, for use by lambda functions created
	 * to supply additional parameters to plugin filters.
	 **/
	static function by_index($index)
	{
		return self::$formatters[$index];
	}	 	  	 	

	/**
	 * function load_all
	 * Loads and stores an instance of all declared Format classes for future use
	 **/	 	 	
	static function load_all()
	{
		self::$formatters = array();
		$classes = get_declared_classes();
		foreach( $classes as $class ) {
			if( ( get_parent_class($class) == 'Format' ) || ( $class == 'Format' ) ) {
				self::$formatters[] = new $class();
			}
		}
		self::$formatters = array_reverse(self::$formatters, true);
	}

	/** DEFAULT FORMAT FUNCTIONS **/
	
	/**
	 * function autop
	 * Converts non-HTML paragraphs separated with 2 line breaks into HTML paragraphs 
	 * while preserving any internal HTML
	 * @param string The string to apply the formatting
	 * @returns string The formatted string
	 **/	 	 	  	 	
	public function autop($value)
	{
		$regex = '/(<\\s*(address|blockquote|del|div|h[1-6]|hr|ins|p|pre|ul|ol|dl|table)[^>]*?'.'>.*?<\\s*\/\\s*\\2\\s*>)/sm';
		$target = str_replace("\r\n", "\n", $value);
		$target = preg_replace('/<\\s*br\\s*\/\\s*>(\s*)/m', "\n", $target);
		
		$cz = preg_split($regex, $target);
		preg_match_all($regex, $target, $cd, PREG_SET_ORDER);
		
		$output = '';
		for($z = 0; $z < count($cz); $z++) {
			$pblock = preg_replace('/\n{2,}/', "<!--pbreak-->", trim($cz[$z]));
			$pblock = str_replace("\n", "<br />\n", $pblock);
			$pblock = str_replace("<!--pbreak-->", "</p>\n<p>", $pblock);
			$pblock = ($pblock == '') ? '' : "<p>{$pblock}</p>\n";
			$tblock = isset($cd[$z]) ? $cd[$z][0] . "\n" : '';
			$output .= $pblock . $tblock;
		} 
		return trim($output);
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
	public function tag_and_list($array, $between = ', ', $between_last = ' and ')
	{
		if ( ! is_array( $array ) )
		{
			$array = array ( $array );
		}
		$fn = create_function('$a', 'return "<a href=\\"" . URL::get( "tag", array( "tag" => $a), false, true ) . "\\">" . $a . "</a>";');
		$array = array_map($fn, $array);
		$last = array_pop($array);
		$out = implode($between, $array);
		$out .= ($out == '') ? $last : $between_last . $last;
		return $out;
	}

	/**
	 * function nice_date
	 * Formats a date using a date format string
	 * @param mixed A date as a string or a timestamp
	 * @param string A date format string
	 * @returns string The date formatted as a string
	 **/	 	 	 	 	 		
	public function nice_date($date, $dateformat = 'F j, Y')
	{
		if ( is_numeric($date) ) return date($dateformat, $date);
		return date($dateformat, strtotime($date));
	}

	/**
	 * function nice_date
	 * Formats a time using a date format string
	 * @param mixed A date as a string or a timestamp
	 * @param string A date format string
	 * @returns string The time formatted as a string
	 **/	 	 	 	 	 		
	public function nice_time($date, $dateformat = 'H:i:s')
	{
		if ( is_numeric($date) ) return date($dateformat, $date);
		return date($dateformat, strtotime($date));
	}}
 
 
?>
