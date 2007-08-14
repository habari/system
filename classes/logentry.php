<?php

/**
 * Represents a single logged event entry
 * 
 * @package Habari
 **/  

class LogEntry extends QueryRecord
{
	/**
	 * @final
	 */
	private $table= 'log';
	
	/**
	 * Defined event severities
	 * 
	 * @final
	 */
	private static $severities= array(
		'none', // should not be used
		'debug', 'info', 'notice', 'warning', 'err', 'crit', 'alert', 'emerg',
	); 

	/**
	 * Cache for log_types
	 */
	private static $types= array();
	
	/**
	 * Return the defined database columns for an Event
	 * @return array Array of columns in the LogEntry table
	 **/
	public static function default_fields()
	{
		return array(
			'id' => null,
			'user_id' => null,
			'module' => 'habari',
			'type' => 'default',
			'severity' => 'info',
			'message' => '',
			'data' => null,
			'timestamp' => date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * constructor for the LogEntry class
	 * 
	 * @param array $paramarray an associative array of initial LogEntry field values
	**/
	public function __construct( $paramarray= array() )
	{
		$this->fields= array_merge( self::default_fields(), $this->fields );
		parent::__construct( $paramarray );
		$this->exclude_fields( 'id' );

		self::cache_types();		
	}
	
	/**
	 * Get an internal cache of log types
	 * @param boolean $force Force the reload of types from the database.	 
	**/
	private function cache_types($force = false)
	{
		if ( $force || empty( self::$types ) ) {
			self::$types= array();
			$res= DB::get_results( 'SELECT `id`, `module`, `type` FROM ' . DB::table( 'log_types' ));
			foreach ( $res as $x ) {
				self::$types[ $x->module ][ $x->type ]= $x->id;
			}
		}
	}	 	
	
	/**
	 * Get the integer value for the given severity, or <code>false</code>.
	 * @param string $severity The severity name
	 * @return mixed numeric value for the given severity, or <code>false</code>
	 */
	public static function severity( $severity )
	{
		if ( is_numeric( $severity ) && array_key_exists( $severity, self::$severities ) ) {
			return $severity;
		}
		return array_search( $severity, self::$severities );
	}
	
	/**
	 * Get the string representation of teh severity numeric value.
	 * @param integer $severity The severity index.
	 * @return string The string name of the severity, or 'Unknown'.
	 **/
	public static function severity_name( $severity )
	{
		return isset(self::$severities[$severity]) ? self::$severities[$severity] : _t('Unknown');
	}
	
	/**
	 * Get the integer value for the given module/type, or <code>false</code>.
	 * @param string $module the module
	 * @param string $type the type
	 * @return mixed numeric value for the given module/type, or <code>false</code>
	 */
	public static function type( $module, $type )
	{
		self::cache_types();		
		if ( array_key_exists( $module, self::$types ) && array_key_exists( $type, self::$types[$module] ) ) {
			return self::$types[$module][$type];
		}
		return false;
	}

	/**
	 * Insert this LogEntry data into the database
	 */
	public function insert()
	{
		if ( isset( $this->fields['severity'] ) ) {
			$this->severity_id= LogEntry::severity( $this->fields['severity'] );
			unset( $this->fields['severity'] );
		}
		if ( isset( $this->fields['module'] ) && isset( $this->fields['type'] ) ) {
			$this->type_id= LogEntry::type( $this->fields['module'], $this->fields['type'] );
			unset( $this->fields['module'] );
			unset( $this->fields['type'] );
		}
		
		Plugins::filter( 'insert_logentry', $this );
		parent::insert( DB::table( $this->table ) );
	}

	public function get() {
		$logs= DB::get_results( 'SELECT id, user_id, type_id, timestamp, message, severity_id FROM ' . DB::table( 'log' ) . ' ORDER BY timestamp DESC' );
		return $logs;
	}
	
	public function get_event_type( $event_id ) {
		$type= DB::get_row( 'SELECT * FROM ' . DB::table( 'log_types' ) . ' WHERE id=' . $event_id );
		return $type ? $type->type : _t('Unknown');
	}

}

?>
