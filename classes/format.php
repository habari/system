<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Format Class
 *
 * Provides formatting functions for use in themes.  Extendable.
 *
 */
class Format
{
	private static $formatters = null;

	/**
	 * Called to register a format function to a plugin hook, only passing the hook's first parameter to the Format function.
	 * @param string $format A function name that exists in a Format class
	 * @param string $onwhat A plugin hook to apply that Format function to as a filter
	 */
	public static function apply( $format, $onwhat )
	{
		if ( self::$formatters == null ) {
			self::load_all();
		}

		$priority = 8;
		if(preg_match('#^(.+)_(\d+)$#', $onwhat, $matches)) {
			$priority = intval($matches[2]);
			$onwhat = $matches[1];
		}

		$method = false;
		if (is_callable($format)) {
			$method = $format;
		}
		else {
			foreach ( self::$formatters as $formatobj ) {
				if ( method_exists( $formatobj, $format ) ) {
					$method = array($formatobj, $format);
					break;
				}
			}
		}

		if($method) {
			$args = func_get_args();
			$args = array_slice($args, 2);
			$lambda = function() use ($args, $method) {
				$filterargs = func_get_args();
				$filterargs = array_slice($filterargs, 0, 1);
				foreach($args as $arg) {
					$filterargs[] = $arg;
				}
				return call_user_func_array($method, $filterargs);
			};
			Plugins::register( $lambda, 'filter', $onwhat, $priority );
		}
	}

	/**
	 * Called to register a format function to a plugin hook, and passes all of the hook's parameters to the Format function.
	 * @param string $format A function name that exists in a Format class
	 * @param string $onwhat A plugin hook to apply that Format function to as a filter
	 */
	public static function apply_with_hook_params( $format, $onwhat )
	{
		if ( self::$formatters == null ) {
			self::load_all();
		}

		$priority = 8;
		if(preg_match('#^(.+)_(\d+)$#', $onwhat, $matches)) {
			$priority = intval($matches[2]);
			$onwhat = $matches[1];
		}

		$method = false;
		if (is_callable($format)) {
			$method = $format;
		}
		else {
			foreach ( self::$formatters as $formatobj ) {
				if ( method_exists( $formatobj, $format ) ) {
					$method = array($formatobj, $format);
					break;
				}
			}
		}

		if($method) {
			$args = func_get_args();
			$args = array_slice($args, 2);
			$lambda = function() use ($args, $method) {
				$filterargs = func_get_args();
				//$filterargs = array_slice($filterargs, 0, 1);
				foreach($args as $arg) {
					$filterargs[] = $arg;
				}
				return call_user_func_array($method, $filterargs);
			};
			Plugins::register( $lambda, 'filter', $onwhat );
		}
	}

	/**
	 * function by_index
	 * Returns an indexed formatter object, for use by lambda functions created
	 * to supply additional parameters to plugin filters.
	 * @param integer $index The index of the formatter object to return.
	 * @return Format The formatter object requested
	 */
	public static function by_index( $index )
	{
		return self::$formatters[$index];
	}

	/**
	 * function load_all
	 * Loads and stores an instance of all declared Format classes for future use
	 */
	public static function load_all()
	{
		self::$formatters = array();
		$classes = get_declared_classes();
		foreach ( $classes as $class ) {
			if ( ( get_parent_class( $class ) == 'Format' ) || ( $class == 'Format' ) ) {
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
	 *
	 * If you make changes to this, PLEASE add test cases here:
	 *   http://svn.habariproject.org/habari/trunk/tests/data/autop/
	 *
	 * @param string $value The string to apply the formatting
	 * @returns string The formatted string
	 */
	public static function autop( $value )
	{
		$value = str_replace( "\r\n", "\n", $value );
		$value = trim( $value );
		$ht = new HtmlTokenizer( $value, false );
		$set = $ht->parse();
		$value = '';

		// should never autop ANY content in these items
		$no_auto_p = array(
			'pre','code','ul','h1','h2','h3','h4','h5','h6',
			'object','applet','embed',
			'table','ul','ol','li','i','b','em','strong','script', 'dl', 'dt', 'dd'
		);

		$block_elements = array(
			'address','blockquote','center','dir','div','dl','fieldset','form',
			'h1','h2','h3','h4','h5','h6','hr','isindex','menu','noframes',
			'object','applet','embed',
			'noscript','ol','p','pre','table','ul','figure','figcaption'
		);

		$token = $set->current();

		// There are no tokens in the text being formatted
		if ( $token === false ) {
			return $value;
		}

		$open_p = false;
		do {

			if ( $open_p ) {
				if ( ( $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_EMPTY || $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN || $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE ) && in_array( strtolower( $token['name'] ), $block_elements ) ) {
					if ( strtolower( $token['name'] ) != 'p' || $token['type'] != HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE ) {
						$value .= '</p>';
					}
					$open_p = false;
				}
			}

			if ( ( $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN || $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_EMPTY ) && !in_array( strtolower( $token['name'] ), $block_elements ) && !$open_p ) {
				// first element, is not a block element
				$value .= '<p>';
				$open_p = true;
			}

			// no-autop, pass them through verbatim
			if ( $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN && in_array( strtolower( $token['name'] ), $no_auto_p ) ) {
				$nested_token = $token;
				do {
					$value .= HtmlTokenSet::token_to_string( $nested_token, false );
					if (
						( $nested_token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE
							&& strtolower( $nested_token['name'] ) == strtolower( $token['name'] ) ) // found closing element
					) {
						break;
					}
				} while ( $nested_token = $set->next() );
				continue;
			}

			// anything that's not a text node should get passed through
			if ( $token['type'] != HTMLTokenizer::NODE_TYPE_TEXT ) {
				$value .= HtmlTokenSet::token_to_string( $token, true );
				// If the token itself is p, we need to set $open_p
				if ( strtolower( $token['name'] ) == 'p' && $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN ) {
					$open_p = true;
				}
				continue;
			}

			// if we get this far, token type is text
			$local_value = $token['value'];
			if ( MultiByte::strlen( $local_value ) ) {
				if ( !$open_p ) {
					$local_value = '<p>' . ltrim( $local_value );
					$open_p = true;
				}

				$local_value = preg_replace( '/\s*(\n\s*){2,}/u', "</p><p>", $local_value ); // at least two \n in a row (allow whitespace in between)
				$local_value = str_replace( "\n", "<br>", $local_value ); // nl2br
			}
			$value .= $local_value;
		} while ( $token = $set->next() );

		$value = preg_replace( '#\s*<p></p>\s*#u', '', $value ); // replace <p></p>
		$value = preg_replace( '/<p><!--(.*?)--><\/p>/', "<!--\\1-->", $value ); // replace <p></p> around comments
		if ( $open_p ) {
			$value .= '</p>';
		}

		return $value;
	}

	/**
	 * function and_list
	 * Turns an array of strings into a friendly delimited string separated by commas and an "and"
	 * @param array $array An array of strings
	 * @param string $between Text to put between each element
	 * @param string $between_last Text to put between the next-to-last element and the last element
	 * @reutrn string The constructed string
	 */
	public static function and_list( $array, $between = ', ', $between_last = null )
	{
		if ( ! is_array( $array ) ) {
			$array = array( $array );
		}

		if ( $between_last === null ) {
			// @locale The default string used between the last two items in a series (one, two, three *and* four).
			$between_last = _t( ' and ' );
		}

		$last = array_pop( $array );
		$out = implode( $between, $array );
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
	 * @param boolean $sort_alphabetical Should the tags be sorted alphabetically by `term` first?
	 * @return string HTML links with specified separators.
	 */
	public static function tag_and_list( $terms, $between = ', ', $between_last = null, $sort_alphabetical = false )
	{
		$array = array();
		if ( !$terms instanceof Terms ) {
			$terms = new Terms( $terms );
		}

		foreach ( $terms as $term ) {
			$array[$term->term] = $term->term_display;
		}

		if ( $sort_alphabetical ) {
			ksort( $array );
		}

		if ( $between_last === null ) {
			// @locale The default string used between the last two items in a series of tags (one, two, three *and* four).
			$between_last = _t( ' and ' );
		}

		$fn = function($a, $b) {
			return "<a href=\"" . URL::get("display_entries_by_tag", array( "tag" => $b) ) . "\" rel=\"tag\">" . $a . "</a>";
		};
		$array = array_map( $fn, $array, array_keys( $array ) );
		$last = array_pop( $array );
		$out = implode( $between, $array );
		$out .= ( $out == '' ) ? $last : $between_last . $last;
		return $out;

	}

	/**
	 * Format a date using a specially formatted string
	 * Useful for using a single string to format multiple date components.
	 * Example:
	 *  If $dt is a HabariDateTime for December 10, 2008...
	 *  echo $dt->format_date('<div><span class="month">{F}</span> {j}, {Y}</div>');
	 *  // Output: <div><span class="month">December</span> 10, 2008</div>
	 *
	 * @param HabariDateTime $date The date to format
	 * @param string $format A string with date()-like letters within braces to replace with date components
	 * @return string The formatted string
	 */
	public static function format_date( $date, $format )
	{
		if ( !( $date instanceOf HabariDateTime ) ) {
			$date = HabariDateTime::date_create( $date );
		}
		return $date->text_format( $format );
	}

	/**
	 * function nice_date
	 * Formats a date using a date format string
	 * @param HabariDateTime $date A date as a HabariDateTime object
	 * @param string $dateformat A date format string
	 * @returns string The date formatted as a string
	 */
	public static function nice_date( $date, $dateformat = 'F j, Y' )
	{
		if ( !( $date instanceOf HabariDateTime ) ) {
			$date = HabariDateTime::date_create( $date );
		}
		return $date->format( $dateformat );
	}

	/**
	 * function nice_time
	 * Formats a time using a date format string
	 * @param HabariDateTime $date A date as a HabariDateTime object
	 * @param string $dateformat A date format string
	 * @returns string The time formatted as a string
	 */
	public static function nice_time( $date, $dateformat = 'H:i:s' )
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
	 * @param integer $max_paragraphs Maximum paragraphs to display [1]
	 * @return string The string, shortened
	 */
	public static function summarize( $text, $count = 100, $max_paragraphs = 1 )
	{
		$ellipsis = '&hellip;';

		$showmore = false;

		$ht = new HtmlTokenizer($text, false);
		$set = $ht->parse();

		$stack = array();
		$para = 0;
		$token = $set->current();
		$summary = new HTMLTokenSet();
		$set->rewind();
		$remaining_words = $count;
		// $bail lets the loop end naturally and close all open elements without adding new ones.
		$bail = false;
		for ( $token = $set->current(); $set->valid(); $token = $set->next() ) {
			if ( !$bail && $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN ) {
				$stack[] = $token;
			}
			if ( !$bail ) {
				switch ( $token['type'] ) {
					case HTMLTokenizer::NODE_TYPE_TEXT:
						$words = preg_split( '/(\\s+)/u', $token['value'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
						// word count is doubled because spaces between words are captured as their own array elements via PREG_SPLIT_DELIM_CAPTURE
						$words = array_slice( $words, 0, $remaining_words * 2 );
						$remaining_words -= count( $words ) / 2;
						$token['value'] = implode( '', $words );
						if ( $remaining_words <= 0 ) {
							$token['value'] .= $ellipsis;
							$summary[] = $token;
							$bail = true;
						}
						else {
							$summary[] = $token;
						}
						break;
					case HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE;
						// don't handle this case here
						break;
					default:
						$summary[] = $token;
						break;
				}
			}
			if ( $token['type'] == HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE ) {
				do {
					$end = array_pop( $stack );
					$end['type'] = HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE;
					$end['attrs'] = null;
					$end['value'] = null;
					$summary[] = $end;
				} while ( ( $bail || $end['name'] != $token['name'] ) && count( $stack ) > 0 );
				if ( count( $stack ) == 0 ) {
					$para++;
				}
				if ( $bail || $para >= $max_paragraphs ) {
					break;
				}
			}
		}

		return (string) $summary;
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
	 * @param boolean $inside_last Should the link be placed inside the last element, or not? Default: true
	 * @return string The post content, suitable for display
	 */
	public static function more( $content, $post, $properties = array() )
	{
		// If the post requested is the post under consideration, always return the full post
		if ( $post->slug == Controller::get_var( 'slug' ) ) {
			return $content;
		}
		elseif ( is_string( $properties ) ) {
			$args = func_get_args();
			$more_text = $properties;
			$max_words = ( isset( $args[3] ) ? $args[3] : null );
			$max_paragraphs = ( isset( $args[4] ) ? $args[4] : null );
			$inside_last = ( isset( $args[5] ) ? $args[5] : true );
			$paramstring = "";
		}
		else {
			$paramstring = "";
			$paramarray = Utils::get_params( $properties );

			$more_text = ( isset( $paramarray['more_text'] ) ? $paramarray['more_text'] : 'Read More' );
			$max_words = ( isset( $paramarray['max_words'] ) ? $paramarray['max_words'] : null );
			$max_paragraphs = ( isset( $paramarray['max_paragraphs'] ) ? $paramarray['max_paragraphs'] : null );
			$inside_last = ( isset( $paramarray['inside_last'] ) ? $paramarray['inside_last'] : true );

			if ( isset( $paramarray['title:before'] ) || isset( $paramarray['title'] ) || isset( $paramarray['title:after'] ) ) {
				$paramstring .= 'title="';

				if ( isset( $paramarray['title:before'] ) ) {
					$paramstring .= $paramarray['title:before'];
				}
				if ( isset( $paramarray['title'] ) ) {
					$paramstring .= $post->title;
				}
				if ( isset( $paramarray['title:after'] ) ) {
					$paramstring .= $paramarray['title:after'];
				}
				$paramstring .= '" ';
			}
			if ( isset( $paramarray['class'] ) ) {
				$paramstring .= 'class="' . $paramarray['class'] . '" ';
			}

		}

		$link_text = '<a ' . $paramstring . ' href="' . $post->permalink . '">' . $more_text . '</a>';

		// if we want it inside the last element, make sure there's a space before the link
		if ( $inside_last ) {
			$link_text = ' ' . $link_text;
		}

		// check for a <!--more--> link, which sets exactly where we should split
		$matches = preg_split( '/<!--\s*more\s*-->/isu', $content, 2, PREG_SPLIT_NO_EMPTY );
		if ( count( $matches ) > 1 ) {
			$summary = reset( $matches );
		}
		else {
			// otherwise, we need to summarize it automagically
			$max_words = empty( $max_words ) ? 9999999 : intval( $max_words );
			$max_paragraphs = empty( $max_paragraphs ) ? 9999999 : intval( $max_paragraphs );
			$summary = Format::summarize( $content, $max_words, $max_paragraphs );
		}

		// if the summary is equal to the length of the content (or somehow greater??), there's no need to add a link, just return the content
		if ( MultiByte::strlen( $summary ) >= MultiByte::strlen( $content ) ) {
			return $content;
		}
		else {
			// make sure there's actually text to append before we waste our time
			if ( strlen( $more_text ) ) {
				// parse out the summary and stick in our linky goodness

				// tokenize the summary
				$ht = new HTMLTokenizer( $summary );
				$summary_set = $ht->parse();

				// tokenize the link we're adding
				$ht = new HTMLTokenizer( $link_text );
				$link_set = $ht->parse();

				// find out where to put the link by bumping the iterator to the last element
				$end = $summary_set->end();
				// and what index is that?
				$key = $summary_set->key();

				// if we want it inside the last element, we're good to go - if we want it outside, we need to add it as the *next* element
				if ( $inside_last == false ) {
					$key++;
				}

				// if the element is a text node, there were no tags; probably not autop'ed yet, just add link as new line
				if($end['type'] == HTMLTokenizer::NODE_TYPE_TEXT) {
					$summary_set->insert( $link_set, $key + 1 );
				}
				else {
				// inject it, whereever we decided it should go
					$summary_set->insert( $link_set, $key );
				}


				// and return a stringified version
				return (string)$summary_set;
			}
			else {
				// no text to append? just return the summary
				return $summary;
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
	 */
	public static function html_messages( $notices, $errors )
	{
		$output = '';
		if ( count( $errors ) ) {
			$output.= '<ul class="messages error">';
			foreach ( $errors as $error ) {
				$output.= '<li>' . $error . '</li>';
			}
			$output.= '</ul>';
		}
		if ( count( $notices ) ) {
			$output.= '<ul class="messages success">';
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
	 */
	public static function humane_messages( $notices, $errors )
	{
		$output = '';
		if ( count( $errors ) ) {
			foreach ( $errors as $error ) {
				$error = addslashes( $error );
				$output .= "human_msg.display_msg(\"{$error}\");";
			}
		}
		if ( count( $notices ) ) {
			foreach ( $notices as $notice ) {
				$notice = addslashes( $notice );
				$output .= "human_msg.display_msg(\"{$notice}\");";
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
	 */
	public static function json_messages( $notices, $errors )
	{
		$messages = array_merge( $errors, $notices );
		return json_encode( $messages );
	}

	/**
	 * function term_tree
	 * Create nested HTML lists from a hierarchical vocabulary.
	 *
	 * Turns Terms or an array of terms from a hierarchical vocabulary into a ordered HTML list with list items for each term.
	 * @param mixed $terms An array of Term objects or a Terms object.
	 * @param string $tree_name The name of the tree, used for unique node id's
	 * @param array $config an array of values to use to configure the output of this function
	 * @return string The transformed vocabulary.
	 */
	public static function term_tree( $terms, $tree_name, $config = array() )
	{
		$defaults = array(
			'treestart' => '<ol %s>',
			'treeattr' => array('class' => 'tree', 'id' => Utils::slugify('tree_' . $tree_name)),
			'treeend' => '</ol>',
			'liststart' => '<ol %s>',
			'listattr' => array(),
			'listend' => '</ol>',
			'itemstart' => '<li %s>',
			'itemattr' => array('class' => 'treeitem'),
			'itemend' => '</li>',
			'wrapper' => '<div>%s</div>',
			'linkcallback' => null,
			'itemcallback' => null,
			'listcallback' => null,
		);
		$config = array_merge($defaults, $config);

		$out = sprintf($config['treestart'], Utils::html_attr($config['treeattr']));
		$stack = array();
		$tree_name = Utils::slugify($tree_name);

		if ( !$terms instanceof Terms ) {
			$terms = new Terms( $terms );
		}

		foreach ( $terms as $term ) {
			if(count($stack)) {
				if($term->mptt_left - end($stack)->mptt_left == 1) {
					if(isset($config['listcallback'])) {
						$config = call_user_func($config['listcallback'], $term, $config);
					}
					$out .= sprintf($config['liststart'], Utils::html_attr($config['listattr']));
				}
				while(count($stack) && $term->mptt_left > end($stack)->mptt_right) {
					$out .= $config['listend'] . $config['itemend'] . "\n";
					array_pop($stack);
				}
			}

			$config['itemattr']['id'] = $tree_name . '_' . $term->id;
			if(isset($config['itemcallback'])) {
				$config = call_user_func($config['itemcallback'], $term, $config);
			}
			$out .= sprintf($config['itemstart'], Utils::html_attr($config['itemattr']));
			if(isset($config['linkcallback'])) {
				$display = call_user_func($config['linkcallback'], $term, $config);
			}
			else {
				$display = $term->term_display;
			}
			$out .= sprintf( $config['wrapper'], $display );
			if($term->mptt_right - $term->mptt_left > 1) {
				$stack[] = $term;
			}
			else {
				$out .= $config['itemend'] ."\n";
			}
		}
		while(count($stack)) {
			$out .= $config['listend'] . $config['itemend'] . "\n";
			array_pop($stack);
		}

		$out .= $config['treeend'];
		return $out;
	}
}
?>
