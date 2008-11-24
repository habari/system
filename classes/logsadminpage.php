<?php

class LogsAdminPage extends AdminPage
{
		/**
	 * Handle GET requests for /admin/logs to display the logs
	 */
	public function act_request_get()
	{
		$this->act_request_post();
	}

	/**
	 * Handle POST requests for /admin/logs to display the logs
	 */
	public function act_request_post()
	{
		$this->fetch_logs();
		$this->display( 'logs' );
	}

	/**
	 * Assign values needed to display the logs page to the theme based on handlervars and parameters
	 *
	 */
	private function fetch_logs($params = NULL)
	{
		$locals = array(
			'do_delete' => false,
			'log_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'change' => '',
			'limit' => 20,
			'user' => 0,
			'date' => 'any',
			'module' => '0',
			'type' => '0',
			'severity' => 'any',
			'address' => '0',
			'search' => '',
			'do_search' => false,
			'index' => 1,
		);

		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : $default;
			$this->theme->{$varname}= $$varname;
		}

		if ( $do_delete && isset( $log_ids ) ) {
			$okay = true;

			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay = false;
			}

			$wsse = Utils::WSSE( $nonce, $timestamp );

			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay = false;
			}

			if ( $okay ) {
				foreach ( $log_ids as $id ) {
					$ids[] = array( 'id' => $id );
				}
				$to_delete = EventLog::get( array( 'nolimit' => 1 ) );
				$logstatus = array( 'Deleted %d logs' => 0 );
				foreach ( $to_delete as $log ) {
					$log->delete();
					$logstatus['Deleted %d logs']+= 1;
				}
				foreach ( $logstatus as $key => $value ) {
					if ( $value ) {
						Session::notice( sprintf( _t( $key ), $value ) );
					}
				}
			}

			Utils::redirect();
		}

		$this->theme->severities = LogEntry::list_severities();
		$any = array( '0' => 'Any' );

		$modulelist = LogEntry::list_logentry_types();
		$modules = array();
		$types = array();
		$addresses = $any;
		$ips = DB::get_column( 'SELECT DISTINCT(ip) FROM ' . DB::table( 'log' ) );
		foreach ( $ips as $ip ) {
			$addresses[$ip] = long2ip( $ip );
		}
		$this->theme->addresses = $addresses;
		foreach ( $modulelist as $modulename => $typearray ) {
			$modules['0,'.implode( ',', $typearray )] = $modulename;
			foreach ( $typearray as $typename => $typevalue ) {
				if ( !isset( $types[$typename] ) ) {
					$types[$typename] = '0';
				}
				$types[$typename] .= ',' . $typevalue;
			}
		}
		$types = array_flip( $types );
		$this->theme->types = array_merge( $any, $types );
		$this->theme->modules = array_merge( $any, $modules );

		// set up the users
		$users_temp = DB::get_results( 'SELECT DISTINCT username, user_id FROM {users} JOIN {log} ON {users}.id = {log}.user_id ORDER BY username ASC' );
		array_unshift( $users_temp, new QueryRecord( array( 'username' => 'All', 'user_id' => 0 ) ) );
		foreach ( $users_temp as $user_temp ) {
			$users[$user_temp->user_id] = $user_temp->username;
		}
		$this->theme->users = $users;

		// set up dates.
		$dates = DB::get_column( 'SELECT timestamp FROM {log} ORDER BY timestamp DESC' );
		$dates = array_map( create_function( '$date', 'return HabariDateTime::date_create( $date )->get(\'Y-m\');' ), $dates );
		array_unshift( $dates, 'Any' );
		$dates = array_combine( $dates, $dates );
		$this->theme->dates = $dates;

		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();

		$arguments = array(
			'severity' => LogEntry::severity( $severity ),
			'limit' => $limit,
			'offset' => ( $index - 1) * $limit,
		);

		// deduce type_id from module and type
		$r_type = explode( ',', substr( $type, 2 ) );
		$r_module = explode( ',', substr( $module, 2 ) );
		if( $type != '0' && $module != '0' ) {
			$arguments['type_id'] = array_intersect( $r_type, $r_module );
		}
		elseif( $type == '0' ) {
			$arguments['type_id'] = $r_module;
		}
		elseif( $module == '0' ) {
			$arguments['type_id'] = $r_type;
		}

		if ( '0' != $address ) {
			$arguments['ip'] = $address;
		}

		if ( 'any' != strtolower( $date ) ) {
			list( $arguments['year'], $arguments['month'] ) = explode( '-', $date );
		}
		if ( '' != $search ) {
			$arguments['criteria'] = $search;
		}
		if ( '0' != $user ) {
			$arguments['user_id'] = $user;
		}

		if(is_array($params)) {
			$arguments = array_merge($arguments, $params);
		}

		$this->theme->logs = EventLog::get( $arguments );

		$monthcts = EventLog::get( array_merge( $arguments, array( 'month_cts' => true ) ) );
		foreach( $monthcts as $month ) {

			if ( isset($years[$month->year]) ) {
				$years[$month->year][] = $month;
			}
			else {
				$years[$month->year] = array( $month );
			}

		}

		if ( isset($years) ) {
			$this->theme->years = $years;
		}
		else {
			$this->theme->years = array();
		}

	}

	/**
	 * Handles ajax requests from the logs page
	 */
	public function act_ajax_get()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params = $_POST;

		$this->fetch_logs( $params );
		$items = $this->theme->fetch( 'logs_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach($this->theme->logs as $log) {
			$item_ids['p' . $log->id]= 1;
		}

		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}
	
	/**
	 * handles AJAX from /logs
	 * used to delete logs
	 */
	public function act_ajax_post($handler_vars)
	{
		$count = 0;

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		foreach($_POST as $id => $delete) {
			// skip POST elements which are not log ids
			if ( preg_match( '/^p\d+/', $id ) && $delete ) {
				$id = substr($id, 1);

				$ids[]= array( 'id' => $id );

			}
		}

		$to_delete = EventLog::get( array( 'date' => 'any', 'where' => $ids, 'nolimit' => 1 ) );

		$logstatus = array( 'Deleted %d logs' => 0 );
		foreach ( $to_delete as $log ) {
			$log->delete();
			$count++;
		}
		foreach ( $logstatus as $key => $value ) {
			if ( $value ) {
				Session::notice( sprintf( _t( $key ), $value ) );
			}
		}

		Session::notice( sprintf( _t('Deleted %d logs.'), $count ) );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}
}

?>