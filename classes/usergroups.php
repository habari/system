<?php
/**
 * @package Habari
 *
 */

/**
 * Habari UserGroups Class
 *
 */
class UserGroups extends ArrayObject
{
	protected $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
	 * Returns a group or grops based on supplied parameters.
	 * @todo This class should cache query results!
	 *
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return array An array of UserGroup objects, or a single UserGroup object, depending on request
	 */
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select = '';
		// what to select -- by default, everything
		foreach ( UserGroup::default_fields() as $field => $value ) {
			$select .= ( '' == $select )
				? "{groups}.$field"
				: ", {groups}.$field";
		}
		// defaults
		$orderby = 'id ASC';
		$nolimit = true;

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
				$paramset = array_merge( (array) $paramarray, (array) $paramset );

				$default_fields = UserGroup::default_fields();
				foreach ( UserGroup::default_fields() as $field => $scrap ) {
					if ( !isset( $paramset[$field] ) ) {
						continue;
					}
					switch ( $field ) {
						case 'id':
							if ( !is_numeric( $paramset[$field] ) ) {
								continue;
							}
						default:
							$where[] = "{$field} = ?";
							$params[] = $paramset[$field];
					}
				}

				if ( count( $where ) > 0 ) {
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
		// Are we asking for a single UserGroup or possibly multiple UserGroups
		$single = false;
		if ( isset( $limit ) ) {
			$single = ($limit == 1);
			$limit = " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit .= " OFFSET $offset";
			}
		}
		if ( isset( $nolimit ) ) {
			$limit = '';
		}

		$query = ' SELECT ' . $select . ' FROM {groups} ' . $join;

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query .= ( ($orderby == '') ? '' : ' ORDER BY ' . $orderby ) . $limit;

		DB::set_fetch_mode( PDO::FETCH_CLASS );

		$results = DB::$fetch_fn( $query, $params, 'UserGroup' );

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
	 * Select all groups from the database
	 *
	 * @return Groups
	 */
	public static function get_all()
	{

		$params = array(
			'orderby' => 'name ASC'
			);

		return self::get( $params );
	}

}
?>
