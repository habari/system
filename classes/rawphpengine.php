<?php
/**
 * @package Habari
 *
 */

/**
 * Habari RawPHPEngine class
 *
 *
 * The RawPHPEngine is a subclass of the abstract TemplateEngine class
 * which is intended for those theme designers who choose to use raw PHP
 * to design theme templates.
 */
class RawPHPEngine extends TemplateEngine
{
	// Internal data to be extracted into template symbol table
	protected $engine_vars = array();
	protected $available_templates = array();
	protected $template_map =array();
	protected $var_stack = array();
	protected $loaded_templates = false;

	/**
	 * Constructor for RawPHPEngine
	 *
	 * Sets up default values for required settings.
	 */
	public function __construct()
	{
		// Nothing to do here...
	}

	/**
	 * Tries to retrieve a variable from the internal array engine_vars.
	 * Method returns the value if succesful, returns false otherwise.
	 *
	 * @param key name of variable
	 */
	public function __get( $key )
	{
		return isset( $this->engine_vars[$key] ) ? $this->engine_vars[$key] : null;
	}

	/**
	 * Assigns a variable to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name of variable
	 * @param value value of variable
	 */
	public function __set( $key, $value )
	{
		$this->engine_vars[$key] = $value;
	}

	/**
	 * Unassigns a variable to the template engine.
	 *
	 * @param key name of variable
	 */
	public function __unset( $key )
	{
		unset( $this->engine_vars[$key] );
	}

	/**
	 * Detects if a variable is assigned to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name of variable
	 * @returns boolean true if name is set, false if not set
	 */
	public function __isset( $key )
	{
		return isset( $this->engine_vars[$key] );
	}

	/**
	 * A function which outputs the result of a transposed
	 * template to the output stream
	 *
	 * @param template  Name of template to display
	 */
	public function display( $template )
	{
		/**
		 * @todo  Here would be a good place to notify observers of output.
		 *        For instance, having sessions/headers output before
		 *        the template content...
		 */
		extract( $this->engine_vars );
		if ( $this->template_exists( $template ) ) {
			//$template_file= Plugins::filter( 'include_template_file', $this->template_dir . $template . '.php', $template, __CLASS__ );
			$template_file = isset( $this->template_map[$template] ) ? $this->template_map[$template] : null;
			$template_file = Plugins::filter( 'include_template_file', $template_file, $template, __CLASS__ );
			include ( $template_file );
		}
	}

	/**
	 * Returns the existance of the specified template name
	 *
	 * @param string $template Name of template to detect
	 * @returns boolean True if the template exists, false if not
	 */
	public function template_exists( $template )
	{
		if ( !$this->loaded_templates ) {
			if ( !is_array( $this->template_dir ) ) {
				$this->template_dir = array( $this->template_dir );
			}
			$alltemplates = array();
			$dirs = array_reverse( $this->template_dir );
			foreach ( $dirs as $dir ) {
				$templates = Utils::glob( $dir . '*.*' );
				$alltemplates = array_merge( $alltemplates, $templates );
			}
			$available_templates = array_map( 'basename', $alltemplates, array_fill( 1, count( $alltemplates ), '.php' ) );
			$template_map = array_combine( $available_templates, $alltemplates );
			$this->template_map = array_merge($this->template_map, $template_map);
			// Workaround for the 404 template key being merged into the 0 integer index
			unset($this->template_map[0]);
			if(isset($template_map[404])) {
				$this->template_map['404'] = $template_map[404];
			}
			array_unique( $available_templates );
			$available_templates = Plugins::filter( 'available_templates', $available_templates, __CLASS__ );
			$this->available_templates = array_merge($this->available_templates, $available_templates);
			$this->loaded_templates = true;
		}
		return in_array( $template, $this->available_templates );
	}

	/**
	 * A function which returns the content of the transposed
	 * template as a string
	 *
	 * @param string $template Name of template to fetch
	 */
	public function fetch( $template )
	{
		ob_start();
		$this->display( $template );
		$contents = ob_get_clean();
		return $contents;
	}

	/**
	 * Assigns a variable to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name( s ) of variable
	 * @param value value of variable
	 */
	public function assign( $key, $value = '' )
	{
		$this->$key = $value;
	}

	/**
	 * Detects if a variable is assigned to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param string $key name of variable
	 * @returns boolean true if key is set, false if not set
	 */
	public function assigned( $key )
	{
		return isset( $this->$key );
	}

	/**
	 * Clear all of the assigned template variables
	 */
	public function clear()
	{
		$this->engine_vars = array();
	}
	
	/**
	 * Appends to an existing variable more values
	 *
	 * @param key name of variable
	 * @param value value of variable
	 */
	public function append( $key, $value = '' )
	{
		if ( ! isset( $this->engine_vars[$key] ) ) {
			$this->engine_vars[$key][] = $value;
		}
		else {
			$this->engine_vars[$key] = $value;
		}
	}

	public function add_template($name, $file, $replace = false)
	{
		if($replace || !in_array($name, $this->available_templates)) {
			$this->available_templates[] = $name;
			$this->template_map[$name] = $file;
		}
	}
}
?>
