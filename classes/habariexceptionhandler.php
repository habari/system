<?php
class HabariExceptionHandler {
	
	public static function handle_exception($exception) {
		$message = sprintf( _t( '%s in %s:%s' ), $exception->getMessage(), $exception->getFile(), $exception->getLine() );
		EventLog::log( $message, 'E_USER_ERROR', get_class( $exception ), null, serialize($exception->getTrace()) );
	}
	
}

// You can't handle the truth!
// We need to wrap default Exception and ErrorException because of third party libraries.
class ExceptionHandler extends HabariExceptionHandler {}
class ErrorExceptionHandler extends HabariExceptionHandler {}

class PDOExceptionHandler extends HabariExceptionHandler {
	public static function handle_exception($exception) {
		list($engine) = explode(':', $GLOBALS['db_connection']['connection_string']);
		
		if (!class_exists($engine.'exceptionhandler')) {
			include_once( HABARI_PATH . "/system/schema/{$engine}/{$engine}exceptionhandler.php" );
		}
		
		if (class_exists($engine.'exceptionhandler')) {
			if (method_exists($engine.'exceptionhandler', 'handle_exception')) {
				call_user_func( array($engine.'exceptionhandler', 'handle_exception'), $exception );
			}
		}
		else {
			// Hide the exception, since we can't confirm if it will or not contain sensitive information?
		}
	}
}

class UpdateExceptionHandler extends HabariExceptionHandler {
	public static function handle_exception($exception) {
		
	}
}

class LocaleExceptionHandler extends HabariExceptionHandler {}
class DBExceptionHandler extends HabariExceptionHandler {}
class PluginExceptionHandler extends HabariExceptionHandler {}
class QueryExceptionHandler extends HabariExceptionHandler {}
class SessionExceptionHandler extends HabariExceptionHandler {}
class ThemeExceptionHandler extends HabariExceptionHandler {}
?>