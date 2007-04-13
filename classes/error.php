<?php
/**
 * class Error
 *  
 * Contains error-related functions and
 *  
 * @package Habari
 **/  
class Error
{
	private $message = '';

	/**
	 * function __construct
	 * 
	 * Constructor for the Error class, used to initialize the instance.
	 * @param string The message that describes the error	 
	 */
	public function __construct( $error_message, $severity = E_USER_ERROR)
	{
		$this->message = $error_message;
		//$this->out();
		if( $severity == E_USER_ERROR ) {
			$this->out();
			die();
		}
	}
	
	/**
	 * function handle_errors
	 * 
	 * Configures the Error class to handle all errors.
	 */	 	 	 	
	static public function handle_errors()
	{
		set_error_handler(array('Error', 'error_handler'));
	}
	
	/**
	 * function error_handler
	 * 
	 * Used to handle all PHP errors after Error::handle_errors() is called.
	 */
	static public function error_handler( $errno, $errstr, $errfile, $errline, $errcontext )
	{
		if( $errno != 0 ) {
			$error = new Error( sprintf('<p class="error"><b>%s:%d:</b> %s</p>', basename( $errfile ), $errline, $errstr), $errno );
		}
	}
	
	/**
	 * function out
	 * 
	 * Outputs the error message in plain text
	 */
	public function out()
	{
		if (is_scalar($this->message)) {
			echo $this->message . "\n";
		}
		else {
			echo var_export($this->message, TRUE) . "\n";
		}
	}
	
	/**
	 * function get
	 * 
	 * Returns the error message in plain text
	 */
	public function get()
	{
		return $this->message;
	}
	
	/**
	 * function raise
	 * 
	 * Convenience method to create and return a new Error object
	 */
	public static function raise( $error_message, $severity = E_USER_ERROR )
	{
		return new Error( $error_message, $severity );
	}
	
	/**
	 * function is_error
	 * 
	 * Returns true if the argument is an Error instance
	 */
	public static function is_error($obj)
	{
		return ($obj instanceof Error);
	}	 	 	 		 	

}

?>
