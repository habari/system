<?php

/**
 *
 * Habari HiEngine class
 * @package Habari
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
		stream_wrapper_register("hi", "HiEngineParser")
		or die(_t("Failed to register HiEngine stream protocol"));
	}

	/**
	 *
	 * A function which outputs the result of a transposed
	 * template to the output stream
	 * @param template $ Name of template to display
	 */
	public function display($template)
	{
		extract($this->engine_vars);
		if ($this->template_exists($template)) {
			$template_file = isset($this->template_map[$template]) ? $this->template_map[$template] : null;
			$template_file = Plugins::filter('include_template_file', $template_file, $template, __CLASS__);
			$template_file = 'hi://' . $template_file;
			include ($template_file);
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
	function stream_open($path, $mode, $options, &$opened_path)
	{
		$this->filename = substr($path, 5);
		$this->file = file_get_contents($this->filename);

		// This processed value should cache and invalidate if a checksum of the template changes!  :)
		$this->file = $this->process($this->file);

		$this->position = 0;

		return true;
	}

	/**
	 * Read data from a HiEngineParser stream
	 *
	 * @param integer $count Number of characters to read from the current position
	 * @return string Characters read from the stream
	 */
	function stream_read($count)
	{
		$ret = substr($this->file, $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	/**
	 * Srite data to a HiEngineParser stream
	 *
	 * @param string $data Data to write
	 * @return boolean false, since this stream type is read-only
	 */
	function stream_write($data)
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
		return $this->position >= strlen($this->file);
	}

	/**
	 * Seek to a specific position within the stream
	 *
	 * @param integer $offset The offset from the specified position
	 * @param integer $whence The position to seek from
	 * @return boolean true if seek was successful
	 */
	function stream_seek($offset, $whence)
	{
		switch ($whence) {
			case SEEK_SET:
				if ($offset < strlen($this->file) && $offset >= 0) {
					$this->position = $offset;
					return true;
				} else {
					return false;
				}
				break;

			case SEEK_CUR:
				if ($offset >= 0) {
					$this->position += $offset;
					return true;
				} else {
					return false;
				}
				break;

			case SEEK_END:
				if (strlen($this->file) + $offset >= 0) {
					$this->position = strlen($this->file) + $offset;
					return true;
				} else {
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
		$template = preg_replace_callback('%\{hi:([^\?]+?)\}(.+?){/hi:\1}%ism', array($this, 'hi_loop'), $template);
		$template = preg_replace_callback('%\{hi:\?\s*(.+?)\}(.+?){/hi:\?}%ism', array($this, 'hi_if'), $template);
		$template = preg_replace_callback('%\{hi:(.+?)\}%i', array($this, 'hi_command'), $template);
		return $template;
	}

	/**
	 * Replace a single template tag with its PHP counterpart
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the template tag
	 */
	function hi_command($matches)
	{
		$cmd = trim($matches[1]);

		// Straight variable or property output, ala {hi:variable_name} or {hi:post.title}
		if(preg_match('%^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*$%i', $cmd)) {
			$cmd = str_replace('.', '->', $cmd);
			if(count($this->contexts)) {
				// Build a conditional that checks for the most specific, then the least
				// eg.- $a->b->c->d->x, then $a->b->c->x, down to just $x 
				$ctx = $this->contexts;
				$prefixes = array();
				foreach($ctx as $void) {
					$prefixes[] = implode('->', $this->contexts);
					array_pop($ctx);
				}
				$output = '<?php echo ';
				foreach($prefixes as $prefix) {
					$output .= '!is_null($' . $prefix . '->' . $cmd . ') ? $' . $prefix . '->' . $cmd . ' : ';
				}
				$output .= '$' . $cmd . '; ?>';
				return $output;
			}
			else {
				return '<?php echo $'. $cmd . '; ?>';
			}
		}

		// Catch tags in the format {hi:command:parameter}
		if(preg_match('%^(\w+):(.+)$%', $cmd, $cmd_matches)) {
			switch(strtolower($cmd_matches[1])) {
				case 'display':
					return '<?php $theme->display(\'' . $cmd_matches[2] . '\'); ?>';
				case 'option':
				case 'options':
					return '<?php Options::out(\'' . $cmd_matches[2] . '\'); ?>';
				case 'siteurl':
					return '<?php Site::out_url( \'' . $cmd_matches[2] . '\' ); ?>';
				case 'url':
					return '<?php URL::out( \'' . $cmd_matches[2] . '\' ); ?>';
			}
		}

		// Use tags in the format {hi:@foo} as theme functions, ala $theme->foo();
		if($cmd[0] == '@') {
			return '<?php $theme->' . substr($cmd, 1) . '(); ?>';
		}

		// Didn't match anything we support so far
		return $matches[0];
	}
	
	/**
	 * Replace a loop tag section with its PHP counterpart, and add the context to the stack
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the template tag
	 */
	function hi_loop($matches)
	{
		$output = '<?php foreach($' . $matches[1] . ' as $' . $matches[1] . '_index => $' . $matches[1] . '_1): ?>';
		$this->contexts[] = "{$matches[1]}_1";
		$output .= $this->process($matches[2]);
		$output .= '<?php endforeach; ?>';
		array_pop($this->contexts);
		return $output;
	}
	
	/**
	 * Replace variables in the hiengine syntax with PHP varaibles
	 * @param array $matches The match array found in hi_if()
	 * @returns string A PHP variable string to use as the replacement
	 */	 	 	 
	function var_replace($matches)
	{
		$var = $matches[0];
		if(is_callable($var)) {
			return $var;
		}
	
		$var = str_replace('.', '->', $var);
		if(count($this->contexts)) {
			// Build a conditional that checks for the most specific, then the least
			// eg.- $a->b->c->d->x, then $a->b->c->x, down to just $x 
			$ctx = $this->contexts;
			$prefixes = array();
			foreach($ctx as $void) {
				$prefixes[] = implode('->', $this->contexts);
				array_pop($ctx);
			}
			$output = '';
			foreach($prefixes as $prefix) {
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
	 * @param array $matches The match found in hi_if()
	 * @returns string An uncommon string index for the stored static string.
	 */	 	 	 	
	function string_stack($matches)
	{
		$key = chr(0) . count($this->strings) . chr(1);
		$this->strings[$key] = $matches[0];
		return $key;
	}
	
	/**
	 * Replace an if tag section with its PHP counterpart
	 *
	 * @param array $matches The match array found in HiEngineParser::process()
	 * @return string The PHP replacement for the template tag
	 */
	function hi_if($matches)
	{
		list($void, $eval, $context) = $matches;

		$eval = preg_replace_callback('/([\'"]).*?(?<!\\\\)\1/i', array($this, 'string_stack'), $eval);
		$eval = preg_replace_callback('/\b(?<!::)[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*(?!::)\b/i', array($this, 'var_replace'), $eval);
		$eval = preg_replace('/(?<!=)=(?!=)/i', '==', $eval);
		$eval = str_replace(array_keys($this->strings), $this->strings, $eval);
		
		$context = preg_replace('/\{hi:\?else\?\}/i', '<?php else: ?>', $context);

		$output = '<?php if(' . $eval . '): ?>';
		$output .= $this->process($context);
		$output .= '<?php endif; ?>';
		return $output;
	}
}

?>
