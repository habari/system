<?php

/**
 * Contains error-related functions and Habari's error handler.
 *
 * @package Habari
 **/
class Error extends Exception
{
	protected $message= '';

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
		printf(
			"<pre class=\"error\">\n<b>%s:</b> %s\n",
			get_class( $exception ),
			$exception->getMessage()
		);
		$trace= $exception->getTrace();
		do {
			$details= current( $trace );
			if( !isset( $details['class'] ) || ( isset( $details['class'] ) && $details['class'] != 'Error' ) ) {
				$details = current($trace);
				break;
			}
		} while( next( $trace ) );
		if( isset( $details ) ) {
			printf(
				_t('%s : Line %s'),
				$details['file'],
				$details['line']
			);
		}
		print "</pre>\n";
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

		throw new Error($errstr);
	}

	private static function print_backtrace( $trace= null )
	{
		if ( !isset($trace) ) {
			$trace= debug_backtrace();
		}
		print "<pre class=\"backtrace\">\n";
		foreach ( $trace as $n => $a ) {
			if ( ! isset( $a['file'] ) ) { $a['file']= '[core]'; }
			if ( ! isset( $a['line'] ) ) { $a['line']= '(eval)'; }
			if ( ! isset( $a['class'] ) ) { $a['class']= ''; }
			if ( ! isset( $a['type'] ) ) { $a['type']= ''; }
			if ( ! is_array( $a['args'] ) ) { $a['args']= array(); }
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

			printf( _t('#%d in <b>%s</b>:<b>%d</b>:\n  <b>%s</b>(%s)\n'),
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
