<?php

if ( ! defined( 'DEBUG' ) )
	define( 'DEBUG', true );

/**
 * Contains error-related functions and Habari's error handler.
 *  
 * @package Habari
 **/  
class Error extends Exception
{
	protected $message= '';

	/**
	 * function __construct
	 * 
	 * Constructor for the Error class, used to initialize the instance.
	 * @param string The message that describes the error	 
	 */
	public function __construct( $error_message, $severity = E_USER_ERROR)
	{
		$this->message= $error_message;
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
	public static function handle_errors()
	{
		set_error_handler( array( 'Error', 'error_handler' ) );
		set_exception_handler( array( 'Error', 'exception_handler' ) );
	}
	
	/**
	 * Used to handle all uncaught exceptions.
	 */
	public static function exception_handler( $exception )
	{
		printf( "<pre>\n<b>Uncaught Exception:</b> <i>%s: %s</i>\n</pre>\n",
			get_class( $exception ), $exception->getMessage() );
		if ( DEBUG ) {
			self::print_backtrace( $exception->getTrace() );
		}
	}
	
	/**
	 * Used to handle all PHP errors after Error::handle_errors() is called.
	 */
	public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext )
	{
		if ( ( $errno & error_reporting() ) === 0 ) {
			return;
		}
		
		// Don't be fooled, we can't actually handle most of these.
		$error_names= array(
			E_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_PARSE => 'Parse Error',
			E_NOTICE => 'Notice',
			E_CORE_ERROR => 'Core Error',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_ERROR => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Strict Notice',
			E_RECOVERABLE_ERROR => 'Recoverable Error',
		);
		
		if ( strpos( $errfile, HABARI_PATH ) === 0 ) {
			$errfile= substr( $errfile, strlen( HABARI_PATH ) + 1 );
		}
		
		throw new Exception($errstr);
		
		/*
		printf( "<pre class=\"error\">\n<b>%s:</b> <i>%s</i>\n",
			( array_key_exists( $errno, $error_names ) ? $error_names[$errno] : 'Unknown error: '.$errno ),
			$errstr );

		if ( DEBUG ) {
			self::print_backtrace();
		}
		else {
			// don't display detailed backtrace
			printf( "  in <b>%s</b>:<b>%d</b>\n<", $errfile, $errline );
		}
		
		print "</pre>";
		*/
		
		// die on all errors except for NOTICE, STRICT, and WARNING
		if ( $errno & ( E_ALL ^ E_NOTICE ^ E_STRICT ^ E_WARNING ) ) {
			die();
		}
	}
	
	private function print_backtrace( $trace= null )
	{
		if ( !isset($trace) )
			$trace= debug_backtrace();
		// remove this call
		//array_shift( $trace );
		// remove error handler call
		//array_shift( $trace );
		print "<pre class=\"backtrace\">\n";
		foreach ( $trace as $n => $a ) {
			if ( ! isset( $a['file'] ) ) { $a['file']= '[core]'; }
			if ( ! isset( $a['line'] ) ) { $a['line']= '(eval)'; }
			if ( ! isset( $a['class'] ) ) { $a['class']= ''; }
			if ( ! isset( $a['type'] ) ) { $a['type']= ''; }
			if ( ! is_array( $a['args'] ) ) { $a['args'] = array(); }
			if ( strpos( $a['file'], HABARI_PATH ) === 0 ) {
				$a['file']= substr( $a['file'], strlen( HABARI_PATH ) + 1 );
			}
		
			$args= array();
			foreach ( $a['args'] as $arg ) {
				$args[]= htmlentities( str_replace(
					array( "\n", "\r" ),
					array( "\n    ", '' ),
					"\n".var_export( $arg, true )
				) );
			}
			$argstr= implode( ",    ", $args );
			if ( !empty( $argstr) ) $argstr.= "\n  ";
			if ( strlen( $argstr ) > 1024 ) {
				$argstr= substr( $argstr, 0, 1021 ) . '...';
			}
				
			printf("#%d in <b>%s</b>:<b>%d</b>:\n  <b>%s</b>(%s)\n",
				$n, $a['file'], $a['line'], $a['class'].$a['type'].$a['function'],
				$argstr
			);
		}
		print "</pre>\n";
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
