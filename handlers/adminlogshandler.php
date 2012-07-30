<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminLogsHandler Class
 * Handles log-related actions in the admin
 *
 */
class AdminLogsHandler extends AdminHandler
{
	/**
	 * Handle GET requests for /admin/logs to display the logs.
	 */
	public function get_logs()
	{
		$this->post_logs();
	}

	/**
	 * Handle POST requests for /admin/logs to display the logs.
	 */
	public function post_logs()
	{
		$this->fetch_logs();
		$this->display( 'logs' );
	}

	private function fetch_logs()
	{
		// load all the values for our filter drop-downs
		$dates = $this->fetch_log_dates();
		$users = $this->fetch_log_users();
		$ips = $this->fetch_log_ips();
		extract( $this->fetch_log_modules_types() ); // $modules and $types
		$severities = LogEntry::list_severities();

		// parse out the arguments we'll fetch logs for

		// the initial arguments
		$arguments = array(
			'limit' => Controller::get_var( 'limit', 20 ),
			'offset' => Controller::get_var( 'offset', 0 ),
		);

		// filter for the search field
		$search = Controller::get_var( 'search', '' );

		if ( $search != '' ) {
			$arguments['criteria'] = $search;
		}

		// filter by date
		$date = Controller::get_var( 'date', 'any' );

		if ( $date != 'any' ) {
			$d = HabariDateTime::date_create( $date );	// ! means fill any non-specified pieces with default Unix Epoch ones
			$arguments['year'] = $d->format( 'Y' );
			$arguments['month'] = $d->format( 'm' );
		}


		// filter by user
		$user = Controller::get_var( 'user', 'any' );

		if ( $user != 'any' ) {
			$arguments['user_id'] = $user;
		}

		// filter by ip
		$ip = Controller::get_var( 'address', 'any' );

		if ( $ip != 'any' ) {
			$arguments['ip'] = $ip;
		}

		// filter modules and types
		// @todo get events of a specific type in a specific module, instead of either of the two
		// the interface doesn't currently make any link between module and type, so we won't worry about it for now

		$module = Controller::get_var( 'module', 'any' );
		$type = Controller::get_var( 'type', 'any' );

		if ( $module != 'any' ) {
			// we get a slugified key back, get the actual module name
			$arguments['module'] = $modules[ $module ];
		}

		if ( $type != 'any' ) {
			// we get a slugified key back, get the actual type name
			$arguments['type'] = $types[ $type ];
		}

		// filter by severity
		$severity = Controller::get_var( 'severity', 0 );
		if ( $severity != 0) {
			$arguments['severity'] = $severity;
		}

		// get the logs!
		$logs = EventLog::get( $arguments );

		// last, but not least, generate the list of years used for the timeline
		$months = EventLog::get( array_merge( $arguments, array( 'month_cts' => true ) ) );

		$years = array();
		foreach ( $months as $m ) {

			$years[ $m->year ][] = $m;

		}

		// assign all our theme values in one spot

		// first the filter options
		$this->theme->dates = $dates;
		$this->theme->users = $users;
		$this->theme->addresses = $ips;
		$this->theme->modules = $modules;
		$this->theme->types = $types;
		$this->theme->severities = $severities;

		// next the filter criteria we used
		$this->theme->search_args = $search;
		$this->theme->date = $date;
		$this->theme->user = $user;
		$this->theme->address = $ip;
		$this->theme->module = $module;
		$this->theme->type = $type;
		$this->theme->severity = $severity;

		$this->theme->logs = $logs;

		$this->theme->years = $years;

		$this->theme->wsse = Utils::WSSE(); // prepare a WSSE token for any ajax calls

	}

	private function fetch_log_dates()
	{
		$db_dates = DB::get_column( 'SELECT timestamp FROM {log} ORDER BY timestamp DESC' );
		$dates = array(
			'any' => 'Any'
		);

		foreach ( $db_dates as $db_date ) {
			$date = HabariDateTime::date_create( $db_date )->format( 'Y-m' );
			$dates[ $date ] = $date;
		}
		return $dates;
	}

	private function fetch_log_users()
	{
		$db_users = DB::get_results( 'SELECT DISTINCT username, user_id FROM {users} JOIN {log} ON {users}.id = {log}.user_id ORDER BY username ASC' );
		$users = array(
			'any' => 'Any'
		);
		foreach ( $db_users as $db_user ) {
			$users[ $db_user->user_id ] = $db_user->username;
		}
		return $users;
	}

	private function fetch_log_ips()
	{
		$db_ips = DB::get_column( 'SELECT DISTINCT(ip) FROM {log}' );
		$ips = array(
			'any' => 'Any'
		);

		foreach ( $db_ips as $db_ip ) {
			$ips[ $db_ip ] = $db_ip;
		}
		return $ips;
	}

	private function fetch_log_modules_types()
	{
		$module_list = LogEntry::list_logentry_types();
		$modules = $types = array(
			'any' => 'Any',
		);

		foreach ( $module_list as $module_name => $type_array ) {
			// Utils::slugify() gives us a safe key to use - this is what will be handed to the filter after a POST as well
			$modules[ Utils::slugify( $module_name ) ] = $module_name;
			foreach ( $type_array as $type_name => $type_value ) {
				$types[ Utils::slugify( $type_name ) ] = $type_name;
			}
		}
		return array( 'modules' => $modules, 'types' => $types );
	}

	/**
	 * Handles AJAX requests from the logs page.
	 */
	public function ajax_logs()
	{
		Utils::check_request_method( array( 'GET', 'HEAD' ) );

		$this->create_theme();

		$this->fetch_logs();
		$items = $this->theme->fetch( 'logs_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach ( $this->theme->logs as $log ) {
			$item_ids['p' . $log->id] = 1;
		}

		$ar = new AjaxResponse();
		$ar->data = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		$ar->out();
	}

	/**
	 * Handles AJAX from /logs.
	 * Used to delete logs.
	 */
	public function ajax_delete_logs( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$count = 0;

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t( 'WSSE authentication failed.' ) );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}
		foreach ( $_POST as $id => $delete ) {
			// skip POST elements which are not log ids
			if ( preg_match( '/^p\d+$/', $id ) && $delete ) {
				$id = (int) substr( $id, 1 );
				$ids[] = array( 'id' => $id );
			}
		}

		if ( ( ! isset( $ids ) || empty( $ids ) ) && $handler_vars['action'] != 'purge' ) {
			Session::notice( _t( 'No logs selected.' ) );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		switch ( $handler_vars['action'] ) {
			case 'delete':
				$to_delete = EventLog::get( array( 'date' => 'any', 'where' => $ids, 'nolimit' => 1 ) );
				foreach ( $to_delete as $log ) {
					$log->delete();
					$count++;
				}
				Session::notice( _t( 'Deleted %d logs.', array( $count ) ) );
				break;
			case 'purge':
				$result = EventLog::purge();
				Session::notice( _t( 'Logs purged.' ) );
				break;
		}

		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

}
?>
