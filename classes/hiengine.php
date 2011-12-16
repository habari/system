<?php
/**
 * @package Habari
 *
 */

/**
 *
 * Habari HiEngine class
 *
 * The HiEngine is a subclass of the RawPHPEngine class
 * which is intended for those theme designers who want to use
 * simple {hi:*} tags four output instead of PHP
 *
 * To use this engine, specify "hiengine" in the theme.xml of a theme.
 *
 * This engine behaves exactly like RawPHPEngine after the template tags are
 * processed, so if an existing RawPHPEngine template is switched to HiEngine,
 * it should still run without issue.
 */
class HiEngine extends RawPHPEngine {
	/**
	 *
	 * Constructor for HiEngine
	 *
	 * Sets up the stream protocol handler
	 */
	public function __construct()
	{
		$streams = stream_get_wrappers();
		if ( ! in_array( 'hi', $streams ) ) {
			stream_wrapper_register( "hi", "HiEngineParser" )
			or die( _t( "Failed to register HiEngine stream protocol" ) );
		}
	}

	/**
	 *
	 * A function which outputs the result of a transposed
	 * template to the output stream
	 * @param template $ Name of template to display
	 */
	public function display( $template )
	{
		extract( $this->engine_vars );
		//Utils::debug($this->engine_vars);die();
		if ( $this->template_exists( $template ) ) {
			$template_file = isset( $this->template_map[$template] ) ? $this->template_map[$template] : null;
			$template_file = Plugins::filter( 'include_template_file', $template_file, $template, __CLASS__ );
			$template_file = 'hi://' . $template_file;
			$fc = file_get_contents( $template_file );
			//echo($fc);
			eval( '?'.'>' . $fc );
			//include $template_file;  // stopped working properly in PHP 5.2.8 
		}
	}
}

/**
 * HiEngineParser - A stream filtering class for the HiEngine
 */
class HiEngineParser
{
	protected $file;
	protected $filename;
	protected $position;
	protected $contexts;
	protected $strings = array();

	/**
	 * Open a HiEngineParser stream
	 *
	 * @param string $path Path of the opened resource, including the protocol specifier
	 * @param string $mode Mode used to open the file
	 * @param integer $options Bitmask options for opening this stream
	 * @param string $opened_path The actual path opened if using relative path, by reference
	 * @return boolean true on success
	 */
	function stream_open( $path, $mode, $options, &$opened_path )
	{
		$this->filename = substr( $path, 5 );
		$this->file = file_get_contents( $this->filename );

		// This processed value should cache and invalidate if a checksum of the template changes!  :)
		$this->file = $this->process( $this->file );

		$this->position = 0;

		return true;
	}

	/**
	 * Read data from a HiEngineParser stream
	 *
	 * @param integer $count Number of characters to read from the current position
	 * @return string Characters read from the stream
	 */
	function stream_read( $count )
	{
		if ( $this->stream_eof() ) {
			return false;
		}
		$ret = substr( $this->file, $this->position, $count );
		$this->position += strlen( $ret );
		return $ret;
	}

	/**
	 * Srite data to a HiEngineParser stream
	 *
	 * @param string $data Data to write
	 * @return boolean false, since this stream type is read-only
	 */
	function stream_write( $data )
	{
		// HiEngineParser streams are read-only
		return false;
	}

	/**
	 * Report the position in the stream
	 *
	 * @return integer the position in the stream
	 */
	function stream_tell()
	{
		return $this->position;
	}

	/**
	 * Report whether the stream is at the end of the file
	 *
	 * @return boolean true if the file pointer is at or beyond the end of the file
	 */
	function stream_eof()
	{
		return $this->position >= strlen( $this->file );
	}

	/**
	 * Seek to a specific position within the stream
	 *
	 * @param integer $offset The offset from the specified position
	 * @param integer $whence The position to seek from
	 * @return boolean true if seek was successful
	 */
	function stream_seek( $offset, $whence )
	{
		switch ( $whence ) {
			case SEEK_SET:
				if ( $offset < strlen( $this->file ) && $offset >= 0 ) {
					$this->position = $offset;
					return true;
				}
				else {
					return false;
				}
				break;

			case SEEK_CUR:
				if ( $offset >= 0 ) {
					$this->position += $offset;
					return true;
				}
				else {
					return false;
				}
				break;

			case SEEK_END:
				if ( strlen( $this->file ) + $offset >= 0 ) {
					$this->position = strlen( $this->file ) + $offset;
					return true;
				}
				else {
					return false;
				}
				break;

			default:
				return false;
		}
		
	}

	/**
	 * Return fstat() info as required when calling stats on the stream
	 * @return array An array of stat info
	 */
	function stream_stat()
	{
		return array();
	}

	/**
	 * Process the template file for template tags
	 *
	 * @param string $template The template file contents
	 * @return string The processed template
	 */
	function process( $template )
	{
		$template = preg_replace_callback( '/\{hi:(".+?")((?:\s*[\w\.]+){0,2})\s*\}/smu', array( $this, 'hi_quote' ), $template );
		$template = preg_replace_callback( '%\{hi:([^\?]+?)\}(.+?)\{/hi:\1\}%ism', array( $this, 'hi_loop' ), $template );
		$template = preg_replace_callback( '%\{hi:\?\s*(.+?)\}(.+?)\{/hi:\?\}%ismu', array( $this, 'hi_if' ), $template );
		$template = preg_replace_callback( '%\{hi:([^:}]+?:.+?)\}%i', array( $this, 'hi_command' ), $template );
		$template = preg_replace_callback( '%\{hi:(.+?)\}%i', array( $this, 'hi_var' ), $template );
		return $template;
	}
	
	/**
	 * Replace a single function template tag with its PHP counterpart
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the function template tag
	 */
	function hi_command( $matches )
	{
		$cmd = trim( $matches[1] );

		// Catch tags in the format {hi:command:parameter}
		if ( preg_match( '/^(\w+):(.+)$/u', $cmd, $cmd_matches ) ) {
			switch ( strtolower( $cmd_matches[1] ) ) {
				case 'area':
					return '<?php echo $theme->area(\'' . $cmd_matches[2] . '\'); ?>';
				case 'display':
					return '<?php $theme->display(\'' . $cmd_matches[2] . '\'); ?>';
				case 'option':
				case 'options':
					return '<?php Options::out(\'' . $cmd_matches[2] . '\'); ?>';
				case 'siteurl':
					return '<?php Site::out_url( \'' . $cmd_matches[2] . '\' ); ?>';
				case 'url':
					return '<?php URL::out( \'' . $cmd_matches[2] . '\' ); ?>';
				case 'session':
					switch ( $cmd_matches[2] ) {
						case 'messages':
							return '<?php if (Session::has_messages()){Session::messages_out();} ?>';
						case 'errors':
							return '<?php if (Session::has_errors()){Session::messages_out();} ?>';
					}
				// this is an internal match
				case 'context':
					return $this->hi_to_var( $cmd_matches[2] );
				case 'escape':
					return '<?php echo Utils::htmlspecialchars( ' . $this->hi_to_var( $cmd_matches[2] ) . ' ); ?>';
			}
		}
		
		return $matches[0];
	}

	/**
	 * Replace a single template tag with its PHP counterpart
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the template tag
	 */
	function hi_var( $matches )
	{
		$cmd = trim( $matches[1] );
		$params = array();
		$returnval = false;

		if ( preg_match_all( '/(?<=\s)(?P<name>[@a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*)\s*=\s*(?P<value>(?P<quot>["\']).+?\3|[^"\'\s]+)/i', $cmd, $foundparams, PREG_SET_ORDER ) ) {
			foreach ( $foundparams as $p ) {
				$params[$p['name']] = trim( $p['value'], $p['quot'] );
			}
		}

		// Straight variable or property output, ala {hi:variable_name} or {hi:post.title}
		if ( preg_match( '%(^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*)%i', $cmd, $cmdmatch ) ) {
			$cmdmatch = str_replace( '.', '->', $cmdmatch[1] );
			if ( count( $this->contexts ) ) {
				// Build a conditional that checks for the most specific, then the least
				// eg.- $a->b->c->d->x, then $a->b->c->x, down to just $x
				$ctx = $this->contexts;
				$prefixes = array();
				foreach ( $ctx as $void ) {
					$prefixes[] = implode( '->', $this->contexts );
					array_pop( $ctx );
				}
				$output = '';
				foreach ( $prefixes as $prefix ) {
					$output .= '(is_object($' . $prefix . ') && !'.'is_null($' . $prefix . '->' . $cmdmatch . ')) ? $' . $prefix . '->' . $cmdmatch . ' : ';
				}
				$output .= '$' . $cmdmatch;
				$returnval = $output;
			}
			else {
				$returnval = '$'. $cmdmatch;
			}
		}
		if ( $returnval !== false ) {
			$returnval = $this->apply_parameters( $returnval, $params );
			return '<?php echo '. $returnval . '; ?>';
		}

		// Use tags in the format {hi:@foo} as theme functions, ala $theme->foo();
		if ( $cmd[0] == '@' ) {
			return '<?php $theme->' . substr( $cmd, 1 ) . '(); ?>';
		}

		// Didn't match anything we support so far
		return $matches[0];
	}
	
	/**
	 * Take the found paramters on a variable tag and apply them to the output
	 *
	 * @param array $returnval The expression to be output
	 * @param array $params An associative array of parameters
	 * @return string The PHP expression with the paramters applied
	 */
	function apply_parameters( $returnval, $params )
	{
		foreach ( $params as $k => $v ) {
			if ( $k[0] == '@' ) {
				$returnval = '$theme->' . substr( $cmd, 1 ) . '(' . $returnval . ", '" . $v . "')";
			}
			switch ( $k ) {
				case 'dateformat':
					$returnval = "call_user_func(array(" . $returnval . ", 'format'), '" . addslashes( $v ) . "')";
					break; 
			}
		}
		return $returnval;
	}

	/**
	 * Replace a loop tag section with its PHP counterpart, and add the context to the stack
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the template tag
	 */
	function hi_loop( $matches )
	{
		$hivar = $matches[1];
		$phpvar = $this->hi_to_var( $hivar );
		$iterator = strpos( $hivar, '.' ) ? substr( $hivar, strrpos( $hivar, '.' ) + 1 ) : $hivar;
		$output = '<?php foreach(' . $phpvar . ' as $' . $iterator . '_index => $' . $iterator . '_1): ?>';
		$this->contexts[] = "{$iterator}_1";
		$output .= $this->process( $matches[2] );
		$output .= '<?php endforeach; ?>';
		array_pop( $this->contexts );
		return $output;
	}

	/**
	 * Replace variables in the hiengine syntax with PHP varaibles
	 * @param array $matches The match array found in hi_if ()
	 * @returns string A PHP variable string to use as the replacement
	 */
	function var_replace( $matches )
	{
		$var = $matches[1];

		if ( is_callable( $var ) ) {
			return $var;
		}
		if ( preg_match( '/true|false|null|isset|empty/i', $var ) ) {
			return $var;
		}

		$var = $this->hi_to_var( $var );

		return $var;
	}

	function hi_to_var( $hisyntax )
	{
		$var = str_replace( '.', '->', $hisyntax );
		if ( count( $this->contexts ) ) {
			// Build a conditional that checks for the most specific, then the least
			// eg.- $a->b->c->d->x, then $a->b->c->x, down to just $x
			$ctx = $this->contexts;
			$prefixes = array();
			foreach ( $ctx as $void ) {
				$prefixes[] = implode( '->', $this->contexts );
				array_pop( $ctx );
			}
			$output = '';
			foreach ( $prefixes as $prefix ) {
				$output .= '(!is_null($' . $prefix . '->' . $var . ') ? $' . $prefix . '->' . $var . ' : ';
			}
			$output .= '$' . $var . ')';
			return $output;
		}
		else {
			return '$'. $var;
		}
	}

	/**
	 * Creates a table of static strings in hiengine expressions to be replaced in later
	 * @param array $matches The match found in hi_if ()
	 * @returns string An uncommon string index for the stored static string.
	 */
	function string_stack( $matches )
	{
		$key = chr( 0 ) . count( $this->strings ) . chr( 1 );
		$this->strings[$key] = $matches[0];
		return $key;
	}

	/**
	 * Replace an if tag section with its PHP counterpart
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the template tag
	 */
	function hi_if ( $matches )
	{
		list( $void, $eval, $context ) = $matches;

		$eval = preg_replace_callback( '/([\'"]).*?(?<!\\\\)\1/i', array( $this, 'string_stack' ), $eval );
		$eval = preg_replace_callback( '/\b((?<!::)[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*(?!::))\b/i', array( $this, 'var_replace' ), $eval );
		$eval = preg_replace( '/(?<!=)=(?!=)/i', '==', $eval );
		$eval = str_replace( array_keys( $this->strings ), $this->strings, $eval );

		$context = preg_replace( '/\{hi:\?else\?\}/i', '<?php else: ?>', $context );

		$output = '<?php if (' . $eval . '): ?>';
		$output .= $this->process( $context );
		$output .= '<?php endif; ?>';
		return $output;
	}

	/**
	* Prepare strings for translation
	* @param array $matches Matches in HiEngineParser::process()
	* @param string The PHP replacement for the template tag
	*/
	function hi_quote( $matches )
	{
		$args = preg_split( '/\s+/u', trim( $matches[2] ) );

		preg_match_all( '/"(.+?)(?<!\\\\)"/', $matches[1], $quotes );
		$count = 0;
		$all_vars = array();
		foreach ( $quotes[1] as $index => $quote ) {
			preg_match_all( '/{hi:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*)}/', $quote, $vars, PREG_SET_ORDER );
			foreach ( $vars as $var ) {
				$count++;
				$quote = str_replace( $var[0], '%'.$count.'\$s', $quote );
				$all_vars[] = '{hi:context:' . $var[1] . '}';  //$this->hi_to_var($var[1]);
			}
			$quotes[1][$index] = $quote;
		}
		if ( count( $quotes[1] ) > 1 ) {
			$output = '<?php printf(_n("'.$quotes[1][0].'", "'.$quotes[1][1].'", {hi:context:'.$args[0].'})';
			// Add vars
			if ( count( $all_vars ) > 0 ) {
				$output .= ', ' . implode( ', ', $all_vars );
			}
			array_shift( $args );
		}
		else {
			$output = '<?php _e("'.$quotes[1][0].'"';
			// Add vars
			if ( count( $all_vars ) > 0 ) {
				$output .= ', array(' . implode( ', ', $all_vars ) . ')';
			}
		}

		// Add the domain, if any
		if ( isset( $args[0] ) ) {
			$output .= ', "'.$args[0].'"';
		}

		// Close the tag
		$output .= '); ?>';

		return $output;
	}
}

?>
