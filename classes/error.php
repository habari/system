<?php
/**
 * Contains error handling system.
 *
 * @package Habari
 **/
class Error
{
	
	protected static $error_severities = array(
		E_ERROR => 'E_ERROR',
		E_WARNING => 'E_WARNING',
		E_PARSE => 'E_PARSE',
		E_NOTICE => 'E_NOTICE',
		E_CORE_ERROR => 'E_CORE_ERROR',
		E_CORE_WARNING => 'E_CORE_WARNING',
		E_COMPILE_ERROR => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR => 'E_USER_ERROR',
		E_USER_WARNING => 'E_USER_WARNING',
		E_USER_NOTICE => 'E_USER_NOTICE',
		E_STRICT => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_ALL => 'E_ALL',
		E_DEPRECATED => 'E_DEPRECATED',
		E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		E_DEBUG => 'E_DEBUG',
		);

	/**
	 * Configures the Error class to handle all errors and exceptions.
	 */
	public static function prepare_handlers()
	{
		set_error_handler( array( 'Error', 'handle_error' ) );
		set_exception_handler( array( 'Error', 'handle_exception' ) );
		
		// Preload default handlers
		spl_autoload_call('habarierror');
		spl_autoload_call('habariexception');
		spl_autoload_call('habarierrorhandler');
		spl_autoload_call('habariexceptionhandler');
	}
	
	/**
	 * Capture and handle errors `set_error_handler()` can't capture.
	 *
	 * The following error types cannot be handled with a user defined function:
	 * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, and
	 * most of E_STRICT raised in the file where set_error_handler() is called.
	 *
	 * Shortcomings of this method:
	 *
	 * E_PARSE errors on the include that builds out the shutdown structure will not be caught,
	 * as the interpreter can’t get beyond the parse error to put it into place.
	 *
	 * E_CORE_ERROR errors? These are environmental errors caught when PHP itself is
	 * bootstrapping — pre-script interpretation. No dice.
	 *
	 * When using output buffering in combination with the ob_gzhandler, take care when cleaning the buffer.
	 * Chances are the Content-Encoding: gzip header was already set and you will need to follow suit.
 	 *
	 * If your output buffer has already been flushed before hitting a fatal error and the shutdown function,
	 * your content to that point is already gone. Check ob_get_status to see if that is the case.
	 */
	public static function handle_shutdown()
	{
		if ($error = error_get_last()) {			
			$uncapturable= array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT);
			if (isset($error['type']) && (in_array($error['type'],$uncapturable))) {
				// We refuse PHP's reality and substitute it with our own!
				// Note: No output is lost, what has been constructed so far is made available to handlers.
				$out= ob_get_contents();
				ob_end_clean();

	            /* if (!headers_sent()) {
	                header('HTTP/1.1 500 Internal Server Error');
	            } */
				EventLog::log( $error['message'] . ' in ' . $error['file'] . ':' . $error['line'], self::$error_severities[$error['type']], 'default' );
	        }
		}
	}

	/**
	 * Handle all exceptions and dispatch to available handlers
	 */
	public static function handle_exception( $exception )
	{
		if (class_exists(get_class($exception) . 'Handler')) {
			if (method_exists(get_class($exception) . 'Handler', 'handle_exception')) {
				call_user_func( array( get_class($exception) . 'Handler', 'handle_exception' ), $exception );				
			}
		}
	}
	
	/**
	 * Convert errors to HabariErrors
	 * Errors we can't handle here will be caught by Error::handle_shutdown()
	 *
	 * @see HabariError
	 */
	public static function handle_error( $errno, $errstr, $errfile, $errline, $errcontext )
	{
		// Sorted in descending order of integer value
		switch ($errno) {
			case E_ERROR: // Fatal and can't be handled here
			case E_WARNING:
			case E_PARSE: // Fatal and can't be handled here
			case E_NOTICE:
			case E_CORE_ERROR: // Fatal and can't be handled here
			case E_CORE_WARNING:
			case E_COMPILE_ERROR: // Fatal and can't be handled here
			case E_COMPILE_WARNING: // Can't be handled here
			case E_USER_ERROR:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			case E_STRICT:
			case E_RECOVERABLE_ERROR: // Fatal
			case E_ALL:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			case E_DEBUG:
			default:
			break;
		}
		
		// Can't throw exceptions from here, causes:
		// Fatal error: Exception thrown without a stack frame in Unknown on line 0
		$exception = new HabariError($errstr, 0, $errno, $errfile, $errline, $errcontext);
		self::handle_exception( $exception );
	}
	
}
?>
