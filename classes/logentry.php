<?php

/**
 * Represents a single logged event entry
 * 
 * @package Habari
 **/  

class LogEntry extends QueryRecord
{
	
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
			'id' => 0,
			'user_id' => '',
			'type_id' => '',
			'severity_id' => '',
			'message' => '',
			'data' => '',
			'timestamp' => date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * constructor for the LogEntry class
	 * 
	 * @param array $paramarray an associative array of initial LogEntry field values
	**/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields );
		
		parent::__construct( $paramarray );
		if ( !isset( $this->fields['module'] ) ) {
			$this->fields['module']= 'habari';
		}
		if ( isset( $this->fields['type'] ) ) {
			$this->fields['type']= 'default';
		}
		if ( isset( $this->fields['severity'] ) ) {
			$this->fields['severity']= 'info';
		}
		$this->exclude_fields( 'id' );
	}
	
	/**
	 * Get an internal cache of log types
	 * @param boolean $force Force the reload of types from the database.	 
	**/
	private function list_logentry_types($force = false)
	{
		if ( $force || empty( self::$types ) ) {
			self::$types= array();
			$res= DB::get_results( 'SELECT `id`, `module`, `type` FROM ' . DB::table( 'log_types' ));
			foreach ( $res as $x ) {
				self::$types[ $x->module ][ $x->type ]= $x->id;
			}
		}
		return self::$types;
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
		self::list_logentry_types();
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
		parent::insert( DB::table( 'log' ) );
	}

	public function get( $paramarray = array() )
	{
		// Defaults
		$defaults= array (
			'fetch_fn' => 'get_row',
		);
		if ( $user = User::identify() ) {
			$defaults['where'][]= array(
				'user_id' => $user->id,
			);
		}
		foreach ( $defaults['where'] as $index => $where ) {
			$defaults['where'][$index]= array_merge( Controller::get_handler()->handler_vars, $where, Utils::get_params( $paramarray ) );
		}
		// make sure we get at most one result
		$defaults['limit']= 1;
		 
		return EventLog::get( $defaults );
	}
	
	public function get_event_type() {
		$type= DB::get_value( 'SELECT type FROM ' . DB::table( 'log_types' ) . ' WHERE id=' . $this->type_id );
		return $type ? $type : _t('Unknown');
	}
	
	public function get_event_module() {
		$module= DB::get_value( 'SELECT module FROM ' . DB::table( 'log_types' ) . ' WHERE id=' . $this->type_id );
		return $module ? $module : _t('Unknown');
	}
	
	public function get_event_severity() {
		return self::severity_name( $this->severity_id );
	}
	
	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value	 
	 **/	 	 
	public function __get( $name )
	{
		$fieldnames = array_merge( array_keys($this->fields), array('module', 'type', 'severity') );
		if( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			preg_match('/^(.*)_([^_]+)$/', $name, $matches);
			list( $junk, $name, $filter ) = $matches;
		}
		else {
			$filter = false;
		}

		switch($name) {
		case 'module':
			$out= $this->get_event_module();
			break;
		case 'type':
			$out = $this->get_event_type();
			break;
		case 'severity':
			$out = $this->get_event_severity();
			break;
		default:
			$out = parent::__get( $name );
			break;
		}
		$out = Plugins::filter( "logentry_{$name}", $out, $this );
		if( $filter ) {
			$out = Plugins::filter( "logentry_{$name}_{$filter}", $out, $this );
		}
		return $out;
	}
	
	/**
	 * function __set
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value	 
	 **/	 	 
	public function __set( $name, $value )
	{
		switch($name) {
		case 'timestamp':
			$value = date( 'Y-m-d H:i:s', strtotime( $value ) );
			break;
		}
		return parent::__set( $name, $value );
	}
	
}

?>
