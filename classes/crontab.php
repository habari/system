<?php

/**
 * Static class to build and read cron entries
 *
 * @package Habari
 * @todo Document this class
 */

class CronTab extends ActionHandler
{
	/**
	 * Executes all cron jobs in the DB.
	 * @param boolean $async If true, allows execution to continue by making an asynchronous request to a cron URL
	 * @todo Change the query for crons to use a stored value of when the next cron should happen, rather than executing a non-cacheable query on every pass.
	 */
	static function run_cron($async = false)
	{
		if($async) {
			$poll_time = doubleval(Options::get('poll_cron'));
			if($poll_time > microtime(true)) {
				return;
			}
			//Wait 30 second between trial requests
			$poll_time = microtime(true) + 30;
			Options::set('poll_cron', $poll_time);
			// Timeout is really low so that it doesn't wait for the request to finish
			$cronurl = URL::get('cron', array('time' => sprintf('%.6F', $poll_time)));
			$request= new RemoteRequest($cronurl, 'GET', 1);
			$request->execute();
		}
		else {
			$crons = DB::get_results( 'SELECT * FROM ' . DB::table('crontab') . ' WHERE start_time <= ? AND next_run <= ?', array( time(), time() ), 'CronJob' );
			if ( $crons ) {
				foreach( $crons as $cron ) {
					$cron->execute();
				}
			}
			Options::set('poll_cron', 0);
		}
	}

	/**
	 * Get a Cron Job by name from the Database.
	 * @param string $name Document the functions.
	 * @return obj the CronJob retreived from the DB
	 */
	static function get_cronjob( $name )
	{
		$cron = DB::get_row( 'SELECT * FROM ' . DB::table('crontab') . ' WHERE name = ?', array( $name ), 'CronJob' );
		// return $cron ? $cron : new Error( 'No Cron Job named ' . $name );
		return $cron;
	}

	/**
	 * function delete_cronjob
	 * Delete a Cron Job by name from the Database.
	 * @param string $name Document the functions.
	 * @return bool wheather or not the delete was successfull
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
	 * @param array $paramarray A paramarray of cron job feilds.
	 */
	static function add_cron( $paramarray )
	{
		$cron = new CronJob( $paramarray );
		return $cron->insert();
	}

	/**
	 * function add_single_cron
	 * Add a new cron job to the DB, that runs only once.
	 * @param string $name The name of the cron job.
	 * @param string $callback The callback function for the cron job to execute.
	 * @param string $run_time The time (PHP timestamp) to execute the cron.
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
	 * @param string $name The name of the cron job.
	 * @param string $callback The callback function for the cron job to execute.
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
	 * @param string $name The name of the cron job.
	 * @param string $callback The callback function for the cron job to execute.
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
	 * @param string $name The name of the cron job.
	 * @param string $callback The callback function for the cron job to execute.
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
	 * @param string $name The name of the cron job.
	 * @param string $callback The callback function for the cron job to execute.
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

	function act_poll_cron()
	{
		$time = $this->handler_vars['time'];
		if($time != Options::get('poll_cron')) {
			return;
		}
		$crons = DB::get_results( 'SELECT * FROM ' . DB::table('crontab') . ' WHERE start_time <= ? AND next_run <= ?', array( time(), time() ), 'CronJob' );
		if ( $crons ) {
			foreach( $crons as $cron ) {
				$cron->execute();
			}
		}
		Options::set('poll_cron', 0);
	}
}

?>
