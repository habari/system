<?php

/**
 * CronJob is a single cron task
 *
 * @package Habari
 */

class CronJob extends QueryRecord
{
	const CRON_SYSTEM = 1;
	const CRON_THEME = 2;
	const CRON_PLUGIN = 4;
	const CRON_CUSTOM = 8;
	
	private $now;
	
	
	/**
	 * Returns the defined database columns for a cronjob.
	 * @return array Array of columns in the crontab table
	 */
	public static function default_fields()
	{
		return array(
			'cron_id' => 0,
			'name' => '',
			'callback' => '',
			'last_run' => '',
			'next_run' => time(),
			'increment' => 86400, // one day
			'start_time' => time(),
			'end_time' => '',
			'result' => '',
			'cron_class' => self::CRON_CUSTOM,
			'description' => ''
		);
	}
	
	/**
	 * Constructor for the CronJob class.
	 * @param array $paramarray an associative array or querystring of initial field values
	 */
	public function __construct( $paramarray = array() )
	{
		$this->now = time();
		
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields 
		);
		
		// maybe serialize the callback
		$paramarray = Utils::get_params( $paramarray );
		if ( isset($paramarray['callback']) 
			&& (
			 	is_array( $paramarray['callback'] ) 
				|| is_object( $paramarray['callback'] ) 
			) 
		) {
			$paramarray['callback'] = serialize( $paramarray['callback'] );
		}
		
		parent::__construct( $paramarray );
		$this->exclude_fields( 'cron_id' );
	}
	
	/**
	 * Runs this job.
	 *
	 * Executes the Cron Job callback. Deletes the Cron Job if end_time is reached
	 * or if it failed to execute the last # consecutive attempts. Also sends notification
	 * by email to specified address.
	 * Note: end_time can be null, ie. "The Never Ending Cron Job".
	 *
	 * Callback is passed a param_array of the Cron Job feilds and the exection time
	 * as the 'now' feild. The 'result' feild contains the result of the last execution; either
	 * 'executed' or 'failed'.
	 *
	 * @todo delete job after # failed attempts.
	 * @todo send notification of execution/failure.
	 */
	public function execute()
	{
		$paramarray = array_merge( array( 'now' => $this->now ), $this->to_array() );
		
		if ( is_callable( $this->callback ) ) {
			$result = @call_user_func( $this->callback, $paramarray );
		}
		else {
			$result = true;
			$result = Plugins::filter( $this->callback, $result, $paramarray );
		}
		
		if ( $result === false ) {
			$this->result = 'failed';
		}
		else {
			$this->result = 'executed';
			
			// it ran successfully, so check if it's time to delete it.
			if ( $this->end_time && ( $this->now >= $this->end_time ) ) {
				$this->delete();
				return;
			}
		}
		
		$this->last_run = $this->now;
		$this->next_run = $this->last_run + $this->increment;
		$this->update();
	}
	
	/**
	 * Magic property setter
	 * Serializes the callback if needed.
	 */
	public function __set( $name, $value )
	{
		if ( $name == 'callback' && ( is_array($value) || is_object($value) ) ) {
			$value = serialize( $value );
		}
		return parent::__set( $name, $value );
	}
	
	/**
	 * Magic property getter
	 * Unserializes the callback if called.
	 */
	public function __get( $name )
	{
		if ( $name == 'callback' ) {
			if ( false !== $res = @ unserialize( parent::__get($name) ) ) {
				return $res;
			}
		}
		return parent::__get( $name );
	}
	
	
	/**
	 * Saves a new cron job to the crontab table
	 */
	public function insert()
	{
		return parent::insertRecord( DB::table('crontab') );
	}
	
	/**
	 * Updates an existing cron job to the crontab table
	 */
	public function update()
	{
		return parent::updateRecord( DB::table('crontab'), array('cron_id'=>$this->cron_id) );
	}
	
	/**
	 * Deletes an existing cron job
	 */
	public function delete()
	{
		return parent::deleteRecord( DB::table('crontab'), array('cron_id'=>$this->cron_id) );
	}
}

?>
