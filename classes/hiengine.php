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
	 * Process the template file for template tags
	 *
	 * @param string $template The template file contents
	 * @return string The processed template
	 */
	function process( $template )
	{
		return preg_replace_callback('%\{hi:(.+?)\}%i', array($this, 'hi_command'), $template);
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
			return '<?php echo $'. $cmd . '; ?>';
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
}

?>
