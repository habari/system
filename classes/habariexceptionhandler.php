<?php
class HabariExceptionHandler {}

class PDOExceptionHandler extends HabariExceptionHandler {
	public function handle_exception ($exception) {
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

class DBExceptionHandler extends HabariExceptionHandler {}
class PluginExceptionHandler extends HabariExceptionHandler {}
class QueryExceptionHandler extends HabariExceptionHandler {}
class SessionExceptionHandler extends HabariExceptionHandler {}
class ThemeExceptionHandler extends HabariExceptionHandler {}
?>