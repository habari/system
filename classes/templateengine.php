<?php
/**
 * @package Habari
 *
 */

/**
 * Habari TemplateEngine abstract base class
 *
 *
 * The TemplateEngine is an abstract base class to allow any template
 * engine to supply templates for the UI.  For an example
 * implementation, see RawPHPEngine or SmartyEngine
 *
 */
abstract class TemplateEngine
{
	// directory where the template resides
	protected $template_dir = null;
	
	abstract function __construct(); // virtual - implement in derived class

	/**
	 * A function which outputs the result of a transposed
	 * template to the output stream
	 *
	 * @param template  Name of template to display
	 */
	abstract function display( $template ); // virtual - implement in derived class

	/**
	 * Returns the existance of the specified template name
	 *
	 * @param template $template Name of template to detect
	 * @returns boolean True if the template exists, false if not
	 */
	abstract function template_exists( $template ); // virtual - implement in derived class

	/**
	 * A function which returns the content of the transposed
	 * template as a string
	 *
	 * @param template  Name of template to fetch
	 */
	abstract function fetch( $template ); // virtual - implement in derived class

	/**
	 * Tries to retrieve a variable from the internal array engine_vars.
	 * Method returns the value if succesful, returns false otherwise.
	 *
	 * @param key name of variable
	 */
	abstract function __get( $key ); // virtual - implement in derived class

	/**
	 * Assigns a variable to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name of variable
	 * @param value value of variable
	 */
	abstract function __set( $key, $value ); // virtual - implement in derived class

	/**
	 * Unassigns a variable to the template engine.
	 *
	 * @param key name of variable
	 */
	abstract function __unset( $key );

	/**
	 * Detects if a variable is assigned to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name of variable
	 * @returns boolean true if key is set, false if not set
	 */
	abstract function __isset( $key ); // virtual - implement in derived class

	/**
	 * Assigns a variable to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name of variable
	 * @param value value of variable
	 */
	abstract function assign( $key, $value = '' ); // virtual - implement in derived class

	/**
	 * Detects if a variable is assigned to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param string $key name of variable
	 * @returns boolean true if key is set, false if not set
	 */
	abstract function assigned( $key ); // virtual - implement in derived class

	
	/**
	 * Clear all of the assigned values
	 */
	abstract function clear(); // vitrual - implement in derived class
	
	/**
	 * Appends to an existing variable more values
	 *
	 * @param key name of variable
	 * @param value value of variable
	 */
	abstract function append( $key, $value = '' ); // virtual - implement in derived class

	/**
	 * Add a template to the list of available templates
	 * @abstract
	 * @param string $name Name of the new template
	 * @param string $file File of the template to add
	 * @param boolean $replace If true, replace any existing template with this name
	 */
	abstract function add_template( $name, $file, $replace = false ); // virtual - implement in derived class

	/**
	 * Sets the directory for the engine to find templates
	 *
	 * @param mixed Directory path as string or array.  Earlier elements override later elements.
	 */
	public function set_template_dir( $dir )
	{
		$this->template_dir = $dir;
	}
}

?>
