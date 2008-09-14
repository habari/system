<?php
/**
 * Habari Format Class
 *
 * Provides formatting functions for use in themes.  Extendable.
 * @package Habari
 */

class Format
{
	private static $formatters = null;

	/**
	 * Called to register a format function to a plugin hook, only passing the hook's first parameter to the Format function.
	 * @param string $format A function name that exists in a Format class
	 * @param string $onwhat A plugin hook to apply that Format function to as a filter
	 **/
	public static function apply($format, $onwhat)
	{
		if( self::$formatters == null ) {
			self::load_all();
		}

		foreach(self::$formatters as $formatobj) {
			if( method_exists($formatobj, $format) ) {
				$index = array_search($formatobj, self::$formatters);
				$func = '$o = Format::by_index(' . $index . ');return $o->' . $format . '($a';
				$args = func_get_args();
				if( count($args) > 2) {
					$func.= ', ';
					$args = array_map(create_function('$a', 'return "\'{$a}\'";'), array_slice($args, 2));
					$func .= implode(', ', $args);
				}
				$func .= ');';
				$lambda = create_function('$a', $func);
				Plugins::register( $lambda, 'filter', $onwhat);
				break;  // We only look for one matching format function to apply.
			}
		}
	}

	/**
	 * Called to register a format function to a plugin hook, and passes all of the hook's parameters to the Format function.
	 * @param string $format A function name that exists in a Format class
	 * @param string $onwhat A plugin hook to apply that Format function to as a filter
	 **/
	public static function apply_with_hook_params($format, $onwhat)
	{
		if( self::$formatters == null ) {
			self::load_all();
		}

		foreach(self::$formatters as $formatobj) {
			if( method_exists($formatobj, $format) ) {
				$index = array_search($formatobj, self::$formatters);
				$func = '$o= Format::by_index(' . $index . '); $args= func_get_args(); return call_user_func_array(array($o, "' . $format . '"), array_merge($args';
				$args = func_get_args();
				if( count($args) > 2) {
					$func.= ', array( ';
					$args = array_map(create_function('$a', 'return "\'{$a}\'";'), array_slice($args, 2));
					$func .= implode(', ', $args) . ')';
				}
				$func .= '));';
				$lambda = create_function('$a', $func);
				Plugins::register( $lambda, 'filter', $onwhat);
				break;  // We only look for one matching format function to apply.
			}
		}
	}

	/**
	 * function by_index
	 * Returns an indexed formatter object, for use by lambda functions created
	 * to supply additional parameters to plugin filters.
	 * @param integer $index The index of the formatter object to return.
	 * @return Format The formatter object requested
	 **/
	public static function by_index($index)
	{
		return self::$formatters[$index];
	}

	/**
	 * function load_all
	 * Loads and stores an instance of all declared Format classes for future use
	 **/
	public static function load_all()
	{
		self::$formatters = array();
		$classes = get_declared_classes();
		foreach( $classes as $class ) {
			if( ( get_parent_class($class) == 'Format' ) || ( $class == 'Format' ) ) {
				self::$formatters[] = new $class();
			}
		}
		self::$formatters = array_merge( self::$formatters, Plugins::get_by_interface( 'FormatPlugin' ) );
		self::$formatters = array_reverse( self::$formatters, true );
	}

	/** DEFAULT FORMAT FUNCTIONS **/

	/**
	 * function autop
	 * Converts non-HTML paragraphs separated with 2 or more new lines into HTML paragraphs 
	 * while preserving any internal HTML.
	 * New lines within the text of block elements are converted to linebreaks.
	 * New lines before and after tags are stripped.
	 * @param string $value The string to apply the formatting
	 * @returns string The formatted string
	 **/
	public static function autop($value)
	{
		$regex = '/(<\s*(address|blockquote|div|h[1-6]|hr|p|pre|ul|ol|dl|table)[^>]*?'.'>.*?<\s*\/\s*\2\s*>)/ism';

		// First, clean out any windows line endings 
		$target = str_replace( "\r\n", "\n", $value );

		// Then, find any of the approved tags above, and split the content on them
		$cz = preg_split( $regex, $target );
		preg_match_all( $regex, $target, $cd, PREG_SET_ORDER );

		/**
		 * Loop through each content block, turning two newlines in a row into </p><p>'s and 
		 * single newlines into <br>'s
		 **/
		$output = '';
		for($z = 0; $z < count($cz); $z++) {
			$pblock = preg_replace( '/\n{2,}/', "</p><p>", trim( $cz[$z] ) );
			$pblock = ( $pblock == '' ) ? '' : "<p>{$pblock}</p>";

			$tblock = isset( $cd[$z] ) ? $cd[$z][0] : '';
			$output .= $pblock . $tblock;
		}


		$output = preg_replace( '%>\s*\n%i', '>', $output );
		$output = preg_replace( '%\n\s*<%i', '<', $output );
		$output = preg_replace( '%\n%i', '<br />', $output );


		/**
		 * Now filter out any erroneous paragraph or break tags, like ones that would occur in nested lists.
		 * There may very well be more cases for this: table td's, etc? 
		 **/
		$cleanNestedRegex = '<\/?\s*(ul|ol|li|dl|dt|dd)[^>]*>';

		// Filter out paragraph tags
		$output = preg_replace( "/<p>\s*($cleanNestedRegex)/", "$1", $output );
		$output = preg_replace( "/($cleanNestedRegex)\s*<\/p>/", "$1", $output );

		// Filter out br tags
		$output = preg_replace( "/($cleanNestedRegex)\s*<br>/", "$1", $output );
		$output = preg_replace( "/<br>\s*($cleanNestedRegex)/", '$1', $output );

		// Finally, move misplaced p tags to after hr tags
		$output= preg_replace( '%<p><hr\s*/*\s*>%', "<hr/><p>", $output );

		return trim( $output );

	}

	/**
	 * function and_list
	 * Turns an array of strings into a friendly delimited string separated by commas and an "and"
	 * @param array $array An array of strings
	 * @param string $between Text to put between each element
	 * @param string $between_last Text to put between the next-to-last element and the last element
	 * @reutrn string The constructed string
	**/
	public static function and_list( $array, $between = ', ', $between_last = NULL )
	{
		if ( ! is_array( $array ) ) {
			$array = array( $array );
		}

		if( $between_last === NULL ) {
			$between_last = _t( ' and ' );
		}

		$last= array_pop( $array );
		$out = implode(', ', $array );
		$out .= ($out == '') ? $last : $between_last . $last;
		return $out;
	}

	/**
	 * function tag_and_list
	 * Formatting function (should be in Format class?)
	 * Turns an array of tag names into an HTML-linked list with commas and an "and".
	 * @param array $array An array of tag names
	 * @param string $between Text to put between each element
	 * @param string $between_last Text to put between the next to last element and the last element
	 * @return string HTML links with specified separators.
	 **/
	public static function tag_and_list($array, $between = ', ', $between_last = NULL )
	{
		if ( ! is_array( $array ) ) {
			$array = array ( $array );
		}

		if ( $between_last === NULL ) {
			$between_last = _t(' and ');
		}

		$fn = create_function('$a,$b', 'return "<a href=\\"" . URL::get("display_entries_by_tag", array( "tag" => $b) ) . "\\" rel=\\"tag\\">" . $a . "</a>";');
		$array = array_map($fn, $array, array_keys($array));
		$last = array_pop($array);
		$out = implode($between, $array);
		$out .= ($out == '') ? $last : $between_last . $last;
		return $out;
	}

	/**
	 * function nice_date
	 * Formats a date using a date format string
	 * @param HabariDateTime A date as a HabariDateTime object
	 * @param string A date format string
	 * @returns string The date formatted as a string
	 **/
	public static function nice_date($date, $dateformat = 'F j, Y')
	{
		if ( !( $date instanceOf HabariDateTime ) ) {
			$date = HabariDateTime::date_create( $date );
		}
		return $date->format( $dateformat );
	}

	/**
	 * function nice_time
	 * Formats a time using a date format string
	 * @param HabariDateTime A date as a HabariDateTime object
	 * @param string A date format string
	 * @returns string The time formatted as a string
	 **/
	public static function nice_time($date, $dateformat = 'H:i:s')
	{
		if ( !( $date instanceOf HabariDateTime ) ) {
			$date = HabariDateTime::date_create( $date );
		}
		return $date->format( $dateformat );
	}

	/**
	 * Returns a shortened version of whatever is passed in.
	 * @param string $value A string to shorten
	 * @param integer $count Maximum words to display [100]
	 * @param integer $maxparagraphs Maximum paragraphs to display [1]
	 * @return string The string, shortened
	 **/
	public static function summarize( $text, $count= 100, $maxparagraphs= 1 )
	{
		preg_match_all( '/<script.*?<\/script.*?>/', $text, $scripts );
		preg_replace( '/<script.*?<\/script.*?>/', '', $text );

		$words = preg_split( '/(<(?:\\s|".*?"|[^>])+>|\\s+)/', $text, $count + 1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$ellipsis= '';
		if( count( $words ) > $count * 2 ) {
			array_pop( $words );
			$ellipsis= '...';
		}
		$output= '';

		$paragraphs= 0;

		$stack= array();
		foreach( $words as $word ) {
			if ( preg_match( '/<.*\/\\s*>$/', $word ) ) {
				// If the tag self-closes, do nothing.
				$output.= $word;
			}
			elseif( preg_match( '/<[\\s\/]+/', $word )) {
				// If the tag ends, pop one off the stack (cheatingly assuming well-formed!)
				array_pop( $stack );
				preg_match( '/<\s*\/\s*(\\w+)/', $word, $tagn );
				switch( $tagn[1] ) {
				case 'br':
				case 'p':
				case 'div':
				case 'ol':
				case 'ul':
					$paragraphs++;
					if( $paragraphs >= $maxparagraphs ) {
						$output.= '...' . $word;
						$ellipsis= '';
						break 2;
					}
				}
				$output.= $word;
			}
			elseif( $word[0] == '<' ) {
				// If the tag begins, push it on the stack
				$stack[]= $word;
				$output.= $word;
			}
			else {
				$output.= $word;
			}
		}
		$output.= $ellipsis;

		if ( count( $stack ) > 0 ) {
			preg_match( '/<(\\w+)/', $stack[0], $tagn );
			$stack= array_reverse( $stack );
			foreach ( $stack as $tag ) {
				preg_match( '/<(\\w+)/', $tag, $tagn );
				$output.= '</' . $tagn[1] . '>';
			}
		}
		foreach( $scripts[0] as $script ) {
			$output.= $script;
		}

		return $output;
	}

	/**
	 * Returns a truncated version of post content when the post isn't being displayed on its own.
	 * Posts are split either at the comment <!--more--> or at the specified maximums.
	 * Use only after applying autop or other paragrpah styling methods.
	 * Apply to posts using:
	 * <code>Format::apply_with_hook_params( 'more', 'post_content_out' );</code>
	 * @param string $content The post content
	 * @param Post $post The Post object of the post
	 * @param string $more_text The text to use in the "read more" link.
	 * @param integer $max_words null or the maximum number of words to use before showing the more link
	 * @param integer $max_paragraphs null or the maximum number of paragraphs to use before showing the more link
	 * @return string The post content, suitable for display
	 **/
	public static function more($content, $post, $more_text = 'Read More &raquo;', $max_words = null, $max_paragraphs = null)
	{
		// If the post requested is the post under consideration, always return the full post
		if( $post->slug == Controller::get_var('slug') ) {
			return $content;
		}
		else {
			$matches= preg_split( '/<!--\s*more\s*-->/is', $content, 2, PREG_SPLIT_NO_EMPTY );
			if(count($matches) > 1) {
				return reset($matches) . ' <a href="' . $post->permalink . '">' . $more_text . '</a>';
			}
			elseif (isset($max_words) || isset($max_paragraphs)) {
				$max_words = empty($max_words) ? 9999999 : intval($max_words);
				$max_paragraphs = empty($max_paragraphs) ? 9999999 : intval($max_paragraphs);
				$summary = Format::summarize($content, $max_words, $max_paragraphs);
				if(strlen($summary) >= strlen($content)) {
					return $content;
				}
				else {
					return $summary . ' <a href="' . $post->permalink . '">' . $more_text . '</a>';
				}
			}
    }
    return $content;
	}
	
	/**
	 * html_messages
	 * Creates an HTML unordered list of an array of messages
	 * @param array $notices a list of success messages
	 * @param array $errors a list of error messages
	 * @return string HTML output
	 **/
	public static function html_messages( $notices, $errors )
	{
		$output = '';
		if ( count( $errors ) ) {
			$output.= '<ul class="error">';
			foreach ( $errors as $error ) {
				$output.= '<li>' . $error . '</li>';
			}
			$output.= '</ul>';
		}
		if ( count( $notices ) ) {
			$output.= '<ul class="success">';
			foreach ( $notices as $notice ) {
				$output.= '<li>' . $notice . '</li>';
			}
			$output.= '</ul>';
		}
		
		return $output;
	}

	/**
	 * humane_messages
	 * Creates JS calls to display session messages
	 * @param array $notices a list of success messages
	 * @param array $errors a list of error messages
	 * @return string JS output
	 **/
	public static function humane_messages( $notices, $errors )
	{
		$output = '';
		if ( count( $errors ) ) {
			foreach ( $errors as $error ) {
				$error= addslashes($error);
				$output.= "humanMsg.displayMsg('{$error}');";
			}
		}
		if ( count( $notices ) ) {
			foreach ( $notices as $notice ) {
				$notice= addslashes($notice);
				$output.= "humanMsg.displayMsg('{$notice}');";
			}
		}
		
		return $output;
	}

	/**
	 * json_messages
	 * Creates a JSON list of session messages
	 * @param array $notices a list of success messages
	 * @param array $errors a list of error messages
	 * @return string JS output
	 **/
	public static function json_messages( $notices, $errors )
	{
		$messages = array_merge( $errors, $notices );
		return json_encode( $messages );
	}
}
?>
