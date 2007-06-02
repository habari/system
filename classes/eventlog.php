<?php

/**
 * Provides access to the event log
 *
 * @package Habari
 */

class EventLog
{

	/**
	 * Adds a logging type to the lookup table
	 * 
	 * @param string $type The type of the error
	 * @param string $module The module of the error
	 */
	public static function register_type( $type= 'default', $module= null )
	{
		DB::query( 'INSERT IGNORE INTO ' . DB::Table('log_types') . ' (module, type) VALUES (?,?)', array( self::get_module($module), $type ) );
	}
	
	/**
	 * Removes a logging type from the lookup table
	 * 
	 * @param string $type The type of the error
	 * @param string $module The module of the error
	 */
	public static function unregister_type( $type= 'default', $module= null )
	{
		DB::query( 'DELETE FROM ' . DB::Table('log_types') . ' WHERE module = ? AND type = ?', array( self::get_module($module), $type ) );
	}
	
	/**
	 * Write an entry to the event log.
	 * 
	 * @param string $message The message
	 * @param string $severity The severity
	 * @param string $type The type
	 * @param string $module The module
	 * @param mixed $data The data
	 * @return class:LogEntry the inserted LogEntry
	 */
	public static function log( $message, $severity= 'info', $type= 'default', $module= null, $data= null )
	{
		$module= self::get_module( $module );
		$log= new LogEntry( array(
			'message' => $message,
			'severity' => $severity,
			'module' => $module,
			'type' => $type,
			'data' => $data,
		) );
		if($user= User::identify()) {
			$log->user_id= $user->id;
		}
		$log->insert();
		return $log;
	}
	
	/**
	 * Get the module in which the logged code was executed
	 * 
	 * @param integer $level How many backtrace calls to go back through the trace
	 * @return The classname or .php module in which the log code was called.
	 */
	public static function get_module( $module= null, $level= 2 )
	{
		if ( is_null( $module ) ) {
			$bt= debug_backtrace();
			$last= $bt[$level];
			$module= isset($last['class']) ? $last['class'] : basename( $last['file'], '.php' );
		}
		return $module;
	}

}

?>