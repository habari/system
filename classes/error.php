<?php

/**
 * Contains error-related functions and Habari's error handler.
 *
 * @package Habari
 **/
class Error extends Exception
{
	protected $message= '';
	protected $is_error = false;
	
	/**
	 * Constructor for the Error class
	 * 
	 * @param string $message Exception to display
	 * @param integer $code Code of the exception
	 * @param boolean $is_error true if the exception represents an error handled by the Error class 
	 */
	public function __construct($message = 'Generic Habari Error', $code = 0, $is_error = false)
	{
		parent::__construct($message, $code);
		$this->is_error = $is_error;
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
		if(isset($exception->is_error) && $exception->is_error) {
			return;
		}
		printf(
			"<pre class=\"error\">\n<b>%s:</b> %s in %s line %s\n</pre>",
			get_class( $exception ),
			$exception->getMessage(),
			$exception->file,
			$exception->line
		);

		if ( DEBUG ) {
			self::print_backtrace( $exception->getTrace() );
		}
	}
	
	/**
	 * Get the error text, file, and line number from the backtrace, which is more accurate
	 *
	 * @return string The contructed error string
	 */
	public function humane_error()
	{
		$trace= $this->getTrace();
		$trace1= reset($trace);
		
		$file= isset( $trace1['file'] ) ? $trace1['file'] : $this->getFile();
		$line= isset( $trace1['line'] ) ? $trace1['line'] : $this->getLine();
		
		return sprintf(_t('%1$s in %2$s line %3$s on request of "%4$s"'), $this->getMessage(), $file, $line, $_SERVER['REQUEST_URI']);
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

		printf(
			"<pre class=\"error\">\n<b>%s:</b> %s in %s line %s\n</pre>",
			$error_names[$errno],
			$errstr,
			$errfile,
			$errline
		);
		if( DEBUG ) {
			Error::print_backtrace();
		}
		
		throw new Error($errstr, 0, true);
	}

	/**
	 * Print a backtrace in a format that looks reasonable in both rendered HTML and text
	 * 
	 * @param array $trace An optional array of trace data
	 */
	private static function print_backtrace( $trace= null )
	{
		if ( !isset($trace) ) {
			$trace= debug_backtrace();
		}
		print "<pre class=\"backtrace\">\n";
		$defaults = array(
			'file' => '[core]',
			'line' => '(eval)',
			'class' => '',
			'type' => '',
			'args' => array(),
		);

		foreach ( $trace as $n => $a ) {
			$a = array_merge($defaults, $a);
			
			if($a['class'] == 'Error') {
				continue;
			}
		
			if ( strpos( $a['file'], HABARI_PATH ) === 0 ) {
				$a['file']= substr( $a['file'], strlen( HABARI_PATH ) + 1 );
			}

			if(defined('DEBUG_ARGS')) {
				$args= array();
				foreach ( $a['args'] as $arg ) {
					$args[]= htmlentities( str_replace(
						array( "\n", "\r" ),
						array( "\n   ", '' ),
						var_export( $arg, true )
					) );
				}
				$args= implode( ",    ", $args );
				if ( strlen( $args ) > 1024 ) {
					$args= substr( $args, 0, 1021 ) . '...';
				}
			}
			else {
				$args = count($a['args']) == 0 ? ' ' : sprintf(_n(' ...%d arg... ', ' ...%d args... ', count($a['args'])), $a['args']);
			}

			printf( 
				_t("%s line %d:\n  %s(%s)\n"),
				$a['file'], 
				$a['line'], 
				$a['class'].$a['type'].$a['function'],
				$args
			);
		}
		print "\n</pre>\n";
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
