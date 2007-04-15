<?php

/**
 * Habari RawPHPEngine class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
 * The RawPHPEngine is a subclass of the abstract TemplateEngine class
 * which is intended for those theme designers who choose to use raw PHP
 * to design theme templates.
 */
class RawPHPEngine extends TemplateEngine
{
	// Internal data to be extracted into template symbol table
	private $engine_vars= array();
	private $available_templates= null;

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
		if ( file_exists( $this->template_dir . $template . '.php' ) ) {
			include ( $this->template_dir . $template . '.php' );
		}
	}

	/** 
	 * Returns the existance of the specified template name
	 * 
	 * @param template $template Name of template to detect
	 * @returns boolean True if the template exists, false if not
	 */
	public function template_exists( $template )
	{
		if( empty( $this->available_templates ) ) {
			$this->available_templates= glob( Site::get_dir('theme', TRUE) . '*.*' );
			$this->available_templates= array_map('basename', $this->available_templates, array_fill(1, count($this->available_templates), '.php') );
		}
		return in_array($template, $this->available_templates);
	}

	/** 
	 * A function which returns the content of the transposed
	 * template as a string
	 *
	 * @param template  Name of template to fetch
	 */
	public function fetch( $template )
	{
		ob_start();
		$this->display( $template );
		$contents= ob_get_contents();
		return $contents;
	}

	/** 
	 * Assigns a variable to the template engine for use in 
	 * constructing the template's output.
	 * 
	 * @param key name( s ) of variable
	 * @param value value of variable
	 */
	public function assign( $key, $value= '' )
	{
		$this->engine_vars[$key]= $value;
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
		return isset( $this->engine_vars[$key] );
	}

	/** 
	 * Appends to an existing variable more values
	 * 
	 * @param key name of variable
	 * @param value value of variable
	 */
	public function append( $key, $value='' )
	{
		if ( ! isset( $this->engine_vars[$key] ) ) {
			$this->engine_vars[$key][]= $value;
		}
		else {
			$this->assign( $key, $value );
		}
	} 
}
?>
