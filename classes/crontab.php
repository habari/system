<?php
/**
 * @package Habari
 *
 */

/**
 * Static class to build and read cron entries
 *
 */
class CronTab extends ActionHandler
{
	/**
	 * Executes all cron jobs in the DB if there are any to run.
	 *
	 * @param boolean $async If true, allows execution to continue by making an asynchronous request to a cron URL
	 */
	static function run_cron( $async = false )
	{
		// check if it's time to run crons, and if crons are already running.
		$next_cron = HabariDateTime::date_create( Options::get( 'next_cron', 1 ) );
		$time = HabariDateTime::date_create();
		if ( ( $next_cron->int > $time->int )
			|| ( Options::get( 'cron_running' ) && Options::get( 'cron_running' ) > microtime( true ) )
			) {
			return;
		}

		// cron_running will timeout in 10 minutes
		// round cron_running to 4 decimals
		$run_time = microtime( true ) + 600;
		$run_time = sprintf( "%.4f", $run_time );
		Options::set( 'cron_running', $run_time );

		if ( $async ) {
			// Timeout is really low so that it doesn't wait for the request to finish
			$cronurl = URL::get( 'cron',
				array(
					'time' => $run_time,
					'asyncronous' => Utils::crypt( Options::get( 'GUID' ) ) )
				);
			$request = new RemoteRequest( $cronurl, 'GET', 1 );
			
			try {
				$request->execute();
			}
			catch ( RemoteRequest_Timeout $e ) {
				// the request timed out - we knew that would happen
			}
			catch ( Exception $e ) {
				// some other error occurred. log it.
				 EventLog::log( $e->getMessage(), 'err', 'crontab', 'habari', $e );
			}
		}
		else {
			// @todo why do we usleep() and why don't we just call act_poll_cron()?
			usleep( 5000 );
			if ( Options::get( 'cron_running' ) != $run_time ) {
				return;
			}

			$time = HabariDateTime::date_create();
			$crons = DB::get_results(
				'SELECT * FROM {crontab} WHERE start_time <= ? AND next_run <= ? AND active != ?',
				array( $time->sql, $time->sql, 0 ),
				'CronJob'
				);
			if ( $crons ) {
				foreach ( $crons as $cron ) {
					$cron->execute();
				}
			}
			
			EventLog::log( _t( 'CronTab run completed.' ), 'debug', 'crontab', 'habari', $crons );

			// set the next run time to the lowest next_run OR a max of one day.
			$next_cron = DB::get_value( 'SELECT next_run FROM {crontab} ORDER BY next_run ASC LIMIT 1', array() );
			Options::set( 'next_cron', min( intval( $next_cron ), $time->modify( '+1 day' )->int ) );
			Options::set( 'cron_running', false );
		}
	}

	/**
	 * Handles asyncronous cron calls.
	 *
	 * @todo next_cron should be the actual next run time and update it when new
	 * crons are added instead of just maxing out at one day..
	 */
	function act_poll_cron()
	{
		Utils::check_request_method( array( 'GET', 'HEAD', 'POST' ) );
		
		$time = doubleval( $this->handler_vars['time'] );
		if ( $time != Options::get( 'cron_running' ) ) {
			return;
		}

		// allow script to run for 10 minutes. This only works on host with safe mode DISABLED
		if ( !ini_get( 'safe_mode' ) ) {
			set_time_limit( 600 );
		}
		$time = HabariDateTime::date_create();
		$crons = DB::get_results(
			'SELECT * FROM {crontab} WHERE start_time <= ? AND next_run <= ? AND active != ?',
			array( $time->sql, $time->sql, 0 ),
			'CronJob'
			);

		if ( $crons ) {
			foreach ( $crons as $cron ) {
				$cron->execute();
			}
		}

		// set the next run time to the lowest next_run OR a max of one day.
		$next_cron = DB::get_value( 'SELECT next_run FROM {crontab} ORDER BY next_run ASC LIMIT 1', array() );
		Options::set( 'next_cron', min( intval( $next_cron ), $time->modify( '+1 day' )->int ) );
		Options::set( 'cron_running', false );
	}

	/**
	 * Get a Cron Job by name or id from the Database.
	 *
	 * @param mixed $name The name or id of the cron job to retreive.
	 * @return CronJob The cron job retreived from the DB
	 */
	static function get_cronjob( $name )
	{
		if ( is_int( $name ) ) {
			$cron = DB::get_row( 'SELECT * FROM {crontab} WHERE cron_id = ?', array( $name ), 'CronJob' );
		}
		else {
			$cron = DB::get_row( 'SELECT * FROM {crontab} WHERE name = ?', array( $name ), 'CronJob' );
		}
		return $cron;
	}

	/**
	 * Delete a Cron Job by name or id from the Database.
	 *
	 * @param mixed $name The name or id of the cron job to delete.
	 * @return bool Wheather or not the delete was successfull
	 */
	static function delete_cronjob( $name )
	{
		$cron = self::get_cronjob( $name );
		if ( $cron ) {
			return $cron->delete();
		}
		return false;
	}

	/**
	 * Add a new cron job to the DB.
	 *
	 * @see CronJob
	 * @param array $paramarray A paramarray of cron job feilds.
	 */
	static function add_cron( $paramarray )
	{
		$cron = new CronJob( $paramarray );
		$result = $cron->insert();

		//If the new cron should run earlier than the others, rest next_cron to its strat time.
		$next_cron = DB::get_value( 'SELECT next_run FROM {crontab} ORDER BY next_run ASC LIMIT 1', array() );
		if ( intval( Options::get( 'next_cron' ) ) > intval( $next_cron ) ) {
			Options::set( 'next_cron', $next_cron );
		}
		return $result;
	}

	/**
	 * Add a new cron job to the DB, that runs only once.
	 *
	 * @param string $name The name of the cron job.
	 * @param mixed $callback The callback function or plugin action for the cron job to execute.
	 * @param HabariDateTime $run_time The time to execute the cron.
	 * @param string $description The description of the cron job.
	 */
	static function add_single_cron( $name, $callback, $run_time, $description = '' )
	{
		$paramarray = array(
			'name' => $name,
			'callback' => $callback,
			'start_time' => $run_time,
			'end_time' => $run_time, // only run once
			'description' => $description
		);
		return self::add_cron( $paramarray );
	}

	/**
	 * Add a new cron job to the DB, that runs hourly.
	 *
	 * @param string $name The name of the cron job.
	 * @param mixed $callback The callback function or plugin action for the cron job to execute.
	 * @param string $description The description of the cron job.
	 */
	static function add_hourly_cron( $name, $callback, $description = '' )
	{
		$paramarray = array(
			'name' => $name,
			'callback' => $callback,
			'increment' => 3600, // one hour
			'description' => $description
		);
		return self::add_cron( $paramarray );
	}

	/**
	 * Add a new cron job to the DB, that runs daily.
	 *
	 * @param string $name The name of the cron job.
	 * @param mixed $callback The callback function or plugin action for the cron job to execute.
	 * @param string $description The description of the cron job.
	 */
	static function add_daily_cron( $name, $callback, $description = '' )
	{
		$paramarray = array(
			'name' => $name,
			'callback' => $callback,
			'increment' => 86400, // one day
			'description' => $description
		);
		return self::add_cron( $paramarray );
	}

	/**
	 * Add a new cron job to the DB, that runs weekly.
	 *
	 * @param string $name The name of the cron job.
	 * @param mixed $callback The callback function or plugin action for the cron job to execute.
	 * @param string $description The description of the cron job.
	 */
	static function add_weekly_cron( $name, $callback, $description = '' )
	{
		$paramarray = array(
			'name' => $name,
			'callback' => $callback,
			'increment' => 604800, // one week (7 days)
			'description' => $description
		);
		return self::add_cron( $paramarray );
	}

	/**
	 * Add a new cron job to the DB, that runs monthly.
	 *
	 * @param string $name The name of the cron job.
	 * @param mixed $callback The callback function or plugin action for the cron job to execute.
	 * @param string $description The description of the cron job.
	 */
	static function add_monthly_cron( $name, $callback, $description = '' )
	{
		$paramarray = array(
			'name' => $name,
			'callback' => $callback,
			'increment' => 2592000, // one month (30 days)
			'description' => $description
		);
		return self::add_cron( $paramarray );
	}
}

?>
