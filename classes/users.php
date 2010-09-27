<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Users Class
 *
 */
class Users extends ArrayObject
{
	protected $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
	 * Returns a user or users based on supplied parameters.
	 * @todo This class should cache query results!
	 *
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return array An array of User objects, or a single User object, depending on request
	 **/
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select = '';
		// what to select -- by default, everything
		foreach ( User::default_fields() as $field => $value ) {
			$select .= ( '' == $select )
				? "{users}.$field"
				: ", {users}.$field";
		}
		// defaults
		$orderby = 'id ASC';
		$nolimit = TRUE;

		// Put incoming parameters into the local scope
		$paramarray = Utils::get_params( $paramarray );

		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets = $paramarray['where'];
		}
		else {
			$wheresets = array( array() );
		}

		$wheres = array();
		$join = '';
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[] = $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// safety mechanism to prevent empty queries
				$where = array();
				$paramset = array_merge((array) $paramarray, (array) $paramset);

				$default_fields = User::default_fields();
				foreach ( User::default_fields() as $field => $scrap ) {
					if ( !isset( $paramset[$field] ) ) {
						continue;
					}
					switch ( $field ) {
						case 'id':
							// ilo, allow searching for groups, passing a list of group member ids
							if ( is_array($paramset[$field])) {
								$where[] = "{$field} IN (". implode( ', ', $paramset[$field]) .")";
							}
							if ( !is_numeric( $paramset[$field] ) ) {
								continue;
							}
						default:
							$where[] = "{$field} = ?";
							$params[] = $paramset[$field];
					}
				}

				if ( isset( $paramset['info'] ) && is_array( $paramset['info'] ) ) {
					$join .= 'INNER JOIN {userinfo} ON {users}.id = {userinfo}.user_id';
					foreach ( $paramset['info'] as $info_name => $info_value ) {
						$where[] = '{userinfo}.name = ? AND {userinfo}.value = ?';
						$params[] = $info_name;
						$params[] = $info_value;
					}
				}

				if ( count($where) > 0 ) {
					$wheres[] = ' (' . implode( ' AND ', $where ) . ') ';
				}
			}
		}

		// Get any full-query parameters
		$possible = array( 'fetch_fn', 'count', 'nolimit', 'limit', 'offset' );
		foreach ( $possible as $varname ) {
			if ( isset( $paramarray[$varname] ) ) {
				$$varname = $paramarray[$varname];
			}
		}

		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn = $fns[0];
			}
		}
		else {
			$fetch_fn = $fns[0];
		}

		// is a count being request?
		if ( isset( $count ) ) {
			$select = "COUNT($count)";
			$fetch_fn = 'get_value';
			$orderby = '';
		}
		if ( isset( $limit ) ) {
			$limit = " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit .= " OFFSET $offset";
			}
		}
		if ( isset( $nolimit ) ) {
			$limit = '';
		}

		$query = '
			SELECT ' . $select
			. ' FROM {users} '
			. $join;

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query .= ( ($orderby == '') ? '' : ' ORDER BY ' . $orderby ) . $limit;
		//Utils::debug($paramarray, $fetch_fn, $query, $params);

		DB::set_fetch_mode(PDO::FETCH_CLASS);
		DB::set_fetch_class('User');
		$results = DB::$fetch_fn( $query, $params, 'User' );

		if ( 'get_results' != $fetch_fn ) {
			// return the results
			return $results;
		}
		elseif ( is_array( $results ) ) {
			$c = __CLASS__;
			$return_value = new $c( $results );
			$return_value->get_param_cache = $paramarray;
			return $return_value;
		}
	}

	/**
	 * Select a user from the database by userinfo
	 *
	 * @param string|array $key
	 * @param string|null $value
	 * @return Users|bool
	 */
	public static function get_by_info( $key, $value = NULL )
	{
		// If no value was specified, check if several info were passed
		if ( NULL === $value ) {
			if ( is_array( $key ) ) {
				$params['info'] = $key;
			}
			else {
				// We need a value to compare to
				return false;
			}
		}
		else {
			$params['info'] = array( $key => $value );
		}

		return self::get( $params );
	}

	/**
	 * Select all users from the database
	 *
	 * @return Users
	 */
	public static function get_all()
	{
		$params = array(
			'orderby' => 'username ASC'
		);

		return self::get( $params );
	}

	/**
	 * Parses a search string for info, and group. Returns an associative array
	 * which can be passed to Users::get(). If multiple info or groups are
	 * specified, we assume an implicit OR such that (e.g.) any user that matches
	 * would be returned.
	 *
	 * Currently it handles group:groupname
	 *
	 * @param string $search_string The search string
	 * @return array An associative array which can be passed to Users::get()
	 */
	public static function search_to_get( $search_string )
	{
		// if adding to this array, make sure you update the consequences of a search on this below in the switch.
		$keywords = array( 'group' => 1, 'info' => 1, );

		// Get Group information
		$allgroups = UserGroups::get_all();
		$groups = array();
		foreach ($allgroups as $group) {
			$groups[strtolower($group->name)] = $group->id;
		}

		$arguments = array(
			'info' => array(),
			'group' => array()
		);
		$criteria = '';

		// this says, find stuff that has the keyword at the start, and then some term straight after.
		// the terms should have no whitespace, or if it does, be ' delimited.
		// ie info:foo or info:'foo bar'
		$flag_regex = '/(?P<flag>' . implode( '|', array_keys( $keywords ) ) . '):(?P<value>[^\'"][^\s]*(?:\s|$)|([\'"]+)(?P<quotedvalue>[^\3]+)(?<!\\\)\3)/Uui';

		// now do some matching.
		preg_match_all( $flag_regex , $search_string, $matches, PREG_SET_ORDER );

		// now we remove those terms from the search string, otherwise the keyword search below has issues. It will pick up things like
		// from tag:'pair of' -> matches of'
		$criteria = trim(preg_replace( $flag_regex, '', $search_string));

		// go through flagged things.
		foreach ($matches as $match) {
			// switch on the type match. ie status, type et al.
			// also, trim out the quote marks that have been matched.
			if( isset($match['quotedvalue']) && $match['quotedvalue'] ) {
				$value = stripslashes($match['quotedvalue']);
			}
			else {
				$value = $match['value'];
			}
			$value = trim( $value );

			switch( strtolower($match['flag']) )  {
				// ilo: support search users by group
				case 'group':
					if ( isset( $groups[strtolower($value)] ) ) {
						$member_ids = DB::get_column( 'SELECT user_id FROM {users_groups} WHERE group_id= ?', array( $groups[strtolower($value) ] ) );
						if ( is_array( $member_ids ) && count( $member_ids ) ) {
						  $arguments['id'][] = $member_ids;
						}
					}
					break;
				case 'info':
					if( strpos($value, ':') !== FALSE ) {
						list( $infokey, $infovalue ) = explode( ':', $value, 2 );
						$arguments['info'][] = array($infokey=>$infovalue);
					}
					break;
			}
		}

		// flatten keys that have single-element or no-element arrays
		foreach ( $arguments as $key => $arg ) {
			switch ( count( $arg ) ) {
				case 0:
					unset( $arguments[$key] );
					break;
				case 1:
					$arguments[$key] = $arg[0];
					break;
			}
		}

		if ( $criteria != '' ) {
			$arguments['username'] = $criteria;
		}

		return $arguments;
	}


}
?>
