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
	 */
	public static function handle_shutdown()
	{
		// @TODO: ob_gzhandler handling
		if ($error = error_get_last()) {
			$uncapturable= ( E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_STRICT );
			if (isset($error['type']) && ($error['type'] & $uncapturable)) {
				// We refuse PHP's reality and substitute it with our own!
				// Note: No output is lost, what has been constructed so far is made available to handlers.
				$out= ob_get_contents();
				ob_end_clean();

	            /* Do we want to send 500?
				if (!headers_sent()) {
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
		// Can't throw exceptions from here, causes:
		// Fatal error: Exception thrown without a stack frame in Unknown on line 0
		$exception = new HabariError($errstr, 0, $errno, $errfile, $errline, $errcontext);
		self::handle_exception( $exception );
	}
}
?>
