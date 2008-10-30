<?php
class HabariErrorHandler {
	
	public static function handle_exception($exception) {
		$message = sprintf( _t( '%s in %s:%s' ), $exception->getMessage(), $exception->getFile(), $exception->getLine() );
		EventLog::log( $message, $exception->getSeverity(), get_class( $exception ), null, serialize($exception->getTrace()) );
	}
	
}

/*
To be removed one day...

class DBErrorHandler extends HabariErrorHandler {}
class PluginErrorHandler extends HabariErrorHandler {}
class QueryErrorHandler extends HabariErrorHandler {}
class SessionErrorHandler extends HabariErrorHandler {}
class ThemeErrorHandler extends HabariErrorHandler {}
*/
?>