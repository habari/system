<?php

/**
 * Habari SmartyEngine template engine subclass
 *
 * @package Habari
 *
 * The SmartyEngine is a subclass of the abstract TemplateEngine class
 * which uses the Smarty templating system to handle display of template files
 *   
 */

require( HABARI_PATH . '/3rdparty/smarty/libs/Smarty.class.php' );
if ( !defined( 'DEBUG' ) )
	define( 'DEBUG', true ); 

class SmartyEngine extends TemplateEngine
{
	// Actual Smarty template processor
	private $smarty= null;

	/**
	 * Constructor for SmartyEngine
	 * 
	 * Sets up default values for required settings.
	 */
	public function __construct()
	{
		$this->smarty= new Smarty();
		$this->smarty->compile_dir= HABARI_PATH . '/3rdparty/smarty/templates_c/';
		$this->smarty->cache_dir= HABARI_PATH . '/3rdparty/smarty/cached/';
		$this->smarty->plugins_dir= HABARI_PATH . '/3rdparty/smarty/libs/plugins/';
		$this->smarty->force_compile= DEBUG;
		$this->smarty->compile_check= DEBUG;
		$this->smarty->caching= !DEBUG;
	}
	
	
	/**
	 * Tries to retrieve a variable from the internal array engine_vars.
	 * Method returns the value if succesful, returns false otherwise.
	 *
	 * @param name name of variable
	 */
	public function __get( $key )
	{
		return ( !empty( $this->smarty->get_template_vars( $key ) ) ) ? $this->smarty->get_template_vars( $key ) : null;
	}
	
	/** 
	 * Assigns a variable to the template engine for use in 
	 * constructing the template's output.
	 * 
	 * @param name name of variable
	 * @param value value of variable
	 */
	public function __set( $key, $value )
	{
		$this->smarty->assign( $key, $value );
	}
	
	/** 
	 * Unassigns a variable to the template engine.
	 * 
	 * @param name name of variable
	 */
	public function __unset( $key )
	{
		$this->smarty->clear_assign( $key );
	}
	
	/** 
	 * Detects if a variable is assigned to the template engine for use in 
	 * constructing the template's output.
	 * 
	 * @param string $key name of variable
	 * @returns boolean true if name is set, false if not set
	 */
	public function __isset( $key )
	{
		return ( !empty( $this->smarty->get_template_vars( $key ) ) ) ? true : false;
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
		// Set directory now to allow theme to load theme directory after constructor.
		$this->smarty->template_dir= $this->template_dir; 
		$this->smarty->display( $template . '.tpl' );
	} 

	/** 
	 * A function which returns the content of the transposed
	 * template as a string
	 *
	 * @param template  Name of template to fetch
	 */
	public function fetch( $template )
	{
		// Set directory now to allow theme to load theme directory after contructor.
		$this->smarty->template_dir= $this->template_dir; 
		$this->smarty->fetch( $template ); 
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
		if ( ! is_array( $key ) ) {
			$this->smarty->assign( $key, $value );
		}
		else {
			$this->smarty->assign( $key );
		}
	} 

	/** 
	 * Appends to an existing variable more values
	 * 
	 * @param key name of variable
	 * @param value value of variable
	 */
	public function append( $key, $value= '' )
	{
		if ( ! is_array( $key ) ) {
			$this->smarty->assign( $key, $value );
		}
		else {
			$this->smarty->assign( $key );
		}
	} 
}

?>
