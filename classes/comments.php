<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari Comments Class
 *
 *
 * @property-read integer $count The number of comments in this object
 * @property-read Comments $approved A Comments object containing only approved comments from this object
 * @property-read Comments $unapproved A Comments object containing only unapproved comments from this object
 * @property-read Comments $moderated A Comments object containing only moderated comments from this object
 * @property-read Comments $comments A Comments object containing only comment type comments from this object
 * @property-read Comments $pingbacks A Comments object containing only pingback type comments from this object
 * @property-read Comments $trackbacks A Comments object containing only trackback type comments from this object
 * @property-read Comments $spam A Comments object containing only spam comments from this object
 *
 */
class Comments extends \ArrayObject
{
	/**
	 * function get
	 * Returns requested comments
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return array An array of Comment objects, one for each query result
	 *
	 * <code>
	 * $comments = comments::get( array ( "author" => "skippy" ) );
	 * $comments = comments::get( array ( "slug" => "first-post", "status" => "1", "orderby" => "date ASC" ) );
	 * </code>
	 **/
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select = '';
		// what to select -- by default, everything
		foreach ( Comment::default_fields() as $field => $value ) {
			$select .= ( '' == $select )
				? "{comments}.$field as $field"
				: ", {comments}.$field as $field";
		}
		// defaults
		$orderby = 'date DESC';
		$limit = Options::get( 'pagination' );

		// Put incoming parameters into the local scope
		$paramarray = Utils::get_params( $paramarray );

		// let plugins alter the param array before we use it. could be useful for modifying search results, etc.
		$paramarray = Plugins::filter( 'comments_get_paramarray', $paramarray );

		$join_params = array();

		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets = $paramarray['where'];
		}
		else {
			$wheresets = array( array() );
		}

		$wheres = array();
		$joins = array();
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[] = $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// safety mechanism to prevent empty queries
				$where = array( '1=1' );
				$paramset = array_merge( ( array ) $paramarray, ( array ) $paramset );

				if ( isset( $paramset['id'] ) && ( is_numeric( $paramset['id'] ) || is_array( $paramset['id'] ) ) ) {
					if ( is_numeric( $paramset['id'] ) ) {
						$where[] = "{comments}.id= ?";
						$params[] = $paramset['id'];
					}
					else if ( is_array( $paramset['id'] ) && !empty( $paramset['id'] ) ) {
						$id_list = implode( ',', $paramset['id'] );
						// Clean up the id list - remove all non-numeric or comma information
						$id_list = preg_replace( "/[^0-9,]/", "", $id_list );
						// You're paranoid, ringmaster! :P
						$limit = count( $paramset['id'] );
						$where[] = '{comments}.id IN (' . addslashes( $id_list ) . ')';
					}
				}
				if ( isset( $paramset['status'] ) && false !== $paramset['status'] ) {
					if ( is_array( $paramset['status'] ) ) {
						$paramset['status'] = array_diff( $paramset['status'], array( 'any' ) );
						array_walk( $paramset['status'], function(&$a) {$a = Comment::status( $a );} );
						$where[] = "{comments}.status IN (" . Utils::placeholder_string( count( $paramset['status'] ) ) . ")";
						$params = array_merge( $params, $paramset['status'] );
					}
					else {
						$where[] = "{comments}.status= ?";
						$params[] = Comment::status( $paramset['status'] );
					}
				}
				if ( isset( $paramset['type'] ) && false !== $paramset['type'] ) {
					if ( is_array( $paramset['type'] ) ) {
						$paramset['type'] = array_diff( $paramset['type'], array( 'any' ) );
						array_walk( $paramset['type'], function( &$a ) { $a = Comment::type( $a ); } );
						$where[] = "type IN (" . Utils::placeholder_string( count( $paramset['type'] ) ) . ")";
						$params = array_merge( $params, $paramset['type'] );
					}
					else {
						$where[] = "type= ?";
						$params[] = Comment::type( $paramset['type'] );
					}
				}
				if ( isset( $paramset['name'] ) ) {
					$where[] = "LOWER( name ) = ?";
					$params[] = MultiByte::strtolower( $paramset['name'] );
				}
				if ( isset( $paramset['email'] ) ) {
					$where[] = "LOWER( email ) = ?";
					$params[] = MultiByte::strtolower( $paramset['email'] );
				}
				if ( isset( $paramset['url'] ) ) {
					$where[] = "LOWER( url ) = ?";
					$params[] = MultiByte::strtolower( $paramset['url'] );
				}
				if ( isset( $paramset['post_id'] ) ) {
					$where[] = "{comments}.post_id= ?";
					$params[] = $paramset['post_id'];
				}
				if ( isset( $paramset['ip'] ) ) {
					$where[] = "ip= ?";
					$params[] = $paramset['ip'];
				}
				/* do searching */
				if ( isset( $paramset['post_author'] ) ) {
					$joins['posts'] = ' INNER JOIN {posts} ON {comments}.post_id = {posts}.id';
					if ( is_array( $paramset['post_author'] ) ) {
						$where[] = "{posts}.user_id IN (" . implode( ',', array_fill( 0, count( $paramset['post_author'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['post_author'] );
					}
					else {
						$where[] = '{posts}.user_id = ?';
						$params[] = (string) $paramset['post_author'];
					}
				}
				if ( isset( $paramset['criteria'] ) ) {
					if ( isset( $paramset['criteria_fields'] ) ) {
						// Support 'criteria_fields' => 'author,ip' rather than 'criteria_fields' => array( 'author', 'ip' )
						if ( !is_array( $paramset['criteria_fields'] ) && is_string( $paramset['criteria_fields'] ) ) {
							$paramset['criteria_fields'] = explode( ',', $paramset['criteria_fields'] );
						}
					}
					else {
						$paramset['criteria_fields'] = array( 'content' );
					}
					$paramset['criteria_fields'] = array_unique( $paramset['criteria_fields'] );

					preg_match_all( '/(?<=")([\p{L}\p{N}]+[^"]*)(?=")|([\p{L}\p{N}]+)/u', $paramset['criteria'], $matches );
					$where_search = array();
					foreach ( $matches[0] as $word ) {
						foreach ( $paramset['criteria_fields'] as $criteria_field ) {
							$where_search[] .= "( LOWER( {comments}.$criteria_field ) LIKE ? )";
							$params[] = '%' . MultiByte::strtolower( $word ) . '%';
						}
					}
					if ( count( $where_search ) > 0 ) {
						$where[] = '(' . implode( " \nOR\n ", $where_search ).')';
					}
				}

				/*
				 * Build the pubdate
				 * If we've got the day, then get the date.
				 * If we've got the month, but no date, get the month.
				 * If we've only got the year, get the whole year.
				 * @todo Ensure that we've actually got all the needed parts when we query on them
				 * @todo Ensure that the value passed in is valid to insert into a SQL date (ie '04' and not '4')
				 */
				if ( isset( $paramset['day'] ) ) {
					/* Got the full date */
					$where[] = 'date BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], $paramset['day'] );
					$start_date = DateTime::create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 day' )->sql;
				}
				elseif ( isset( $paramset['month'] ) ) {
					$where[] = 'date BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], 1 );
					$start_date = DateTime::create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 month' )->sql;
				}
				elseif ( isset( $paramset['year'] ) ) {
					$where[] = 'date BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], 1, 1 );
					$start_date = DateTime::create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 year' )->sql;
				}

				// Concatenate the WHERE clauses
				if ( count( $where ) > 0 ) {
					$wheres[] = ' (' . implode( ' AND ', $where ) . ') ';
				}
			}
		}

		// Only show comments to which the current user has permission to read the associated post
		if ( isset( $paramset['ignore_permissions'] ) ) {
			$master_perm_where = '';
			// Set up the merge params
			$merge_params = array( $join_params, $params );
			$params = call_user_func_array( 'array_merge', $merge_params );
		}
		else {
			// This set of wheres will be used to generate a list of comment_ids that this user can read
			$perm_where = array();
			$perm_where_denied = array();
			$params_where = array();
			$where = array();

			// every condition here will require a join with the posts table
			$joins['posts'] = 'INNER JOIN {posts} ON {comments}.post_id={posts}.id';

			// Get the tokens that this user is granted or denied access to read
			$read_tokens = isset( $paramset['read_tokens'] ) ? $paramset['read_tokens'] : ACL::user_tokens( User::identify(), 'read', true );
			$deny_tokens = isset( $paramset['deny_tokens'] ) ? $paramset['deny_tokens'] : ACL::user_tokens( User::identify(), 'deny', true );

			// If a user can read his own posts, let him
			if ( User::identify()->can( 'own_posts', 'read' ) ) {
				$perm_where['own_posts_id'] = '{posts}.user_id = ?';
				$params_where[] = User::identify()->id;
			}

			// If a user can read any post type, let him
			if ( User::identify()->can( 'post_any', 'read' ) ) {
				$perm_where = array('post_any' => '(1=1)');
				$params_where = array();
			}
			else {
				// If a user can read specific post types, let him
				$permitted_post_types = array();
				foreach ( Post::list_active_post_types() as $name => $posttype ) {
					if ( User::identify()->can( 'post_' . Utils::slugify( $name ), 'read' ) ) {
						$permitted_post_types[] = $posttype;
					}
				}
				if ( count( $permitted_post_types ) > 0 ) {
					$perm_where[] = '{posts}.content_type IN (' . implode( ',', $permitted_post_types ) . ')';
				}

				// If a user can read posts with specific tokens, let him see comments on those posts
				if ( count( $read_tokens ) > 0 ) {
					$joins['post_tokens__allowed'] = ' LEFT JOIN {post_tokens} pt_allowed ON {posts}.id= pt_allowed.post_id AND pt_allowed.token_id IN ('.implode( ',', $read_tokens ).')';
					$perm_where['perms_join_null'] = 'pt_allowed.post_id IS NOT NULL';
				}

			}

			// If a user is denied access to all posts, do so
			if ( User::identify()->cannot( 'post_any' ) ) {
				$perm_where_denied = array('(0=1)');
			}
			else {
				// If a user is denied read access to specific post types, deny him
				$denied_post_types = array();
				foreach ( Post::list_active_post_types() as $name => $posttype ) {
					if ( User::identify()->cannot( 'post_' . Utils::slugify( $name ) ) ) {
						$denied_post_types[] = $posttype;
					}
				}
				if ( count( $denied_post_types ) > 0 ) {
					$perm_where_denied[] = '{posts}.content_type NOT IN (' . implode( ',', $denied_post_types ) . ')';
				}
			}

			// If there are granted permissions to check, add them to the where clause
			if ( count( $perm_where ) == 0 && !isset( $joins['post_tokens__allowed'] ) ) {
				// You have no grants.  You get no comments.
				$where['perms_granted'] = '(0=1)';
			}
			elseif ( count( $perm_where ) > 0 ) {
				$where['perms_granted'] = '
					(' . implode( ' OR ', $perm_where ) . ')
				';
				$params = array_merge( $params, $params_where );
			}

			if ( count( $deny_tokens ) > 0 ) {
				$joins['post_tokens__denied'] = ' LEFT JOIN {post_tokens} pt_denied ON {posts}.id= pt_denied.post_id AND pt_denied.token_id IN ('.implode( ',', $deny_tokens ).')';
				$perm_where_denied['perms_join_null'] = 'pt_denied.post_id IS NULL';
			}

			// If there are denied permissions to check, add them to the where clause
			if ( count( $perm_where_denied ) > 0 ) {
				$where['perms_denied'] = '
					(' . implode( ' AND ', $perm_where_denied ) . ')
				';
			}

			$master_perm_where = implode( ' AND ', $where );
		}

		// Get any full-query parameters
		$possible = array( 'page', 'fetch_fn', 'count', 'month_cts', 'nolimit', 'limit', 'offset', 'orderby' );
		foreach ( $possible as $varname ) {
			if ( isset( $paramarray[$varname] ) ) {
				$$varname = $paramarray[$varname];
			}
		}

		if ( isset( $page ) && is_numeric( $page ) ) {
			$offset = ( intval( $page ) - 1 ) * intval( $limit );
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
			$select = "COUNT( 1 )";
			$fetch_fn = 'get_value';
			$orderby = '';
		}
		// is a count of comments by month being requested?
		$groupby = '';
		if ( isset( $month_cts ) ) {
			$select = 'MONTH(FROM_UNIXTIME(date)) AS month, YEAR(FROM_UNIXTIME(date)) AS year, COUNT({comments}.id) AS ct';
			$groupby = 'year, month';
			$orderby = 'year, month';
		}
		if ( isset( $limit ) ) {
			$limit = " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit .= " OFFSET $offset";
			}
		}
		if ( isset( $nolimit ) || isset( $month_cts ) ) {
			$limit = '';
		}

		// Build the final SQL statement
		$query = '
			SELECT DISTINCT ' . $select .
			' FROM {comments} ' .
			implode( ' ', $joins );

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE (' . implode( " \nOR\n ", $wheres ) . ')';
			$query .= ($master_perm_where == '') ? '' : ' AND (' . $master_perm_where . ')';
		}
		elseif ( $master_perm_where != '' ) {
			$query .= ' WHERE (' . $master_perm_where . ')';
		}
		$query .= ( $groupby == '' ) ? '' : ' GROUP BY ' . $groupby;
		$query .= ( ( $orderby == '' ) ? '' : ' ORDER BY ' . $orderby ) . $limit;

		DB::set_fetch_mode( \PDO::FETCH_CLASS );
		DB::set_fetch_class( 'Comment' );
		$results = DB::$fetch_fn( $query, $params, 'Comment' );

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
		return false;
	}

	/**
	 * Deletes comments from the database
	 * @param mixed Comments to delete.  An array of or a single ID/Comment object
	*
	 * @return bool True on success, false on failure
	 */
	public static function delete_these( $comments )
	{
		if ( ! is_array( $comments ) && ! $comments instanceOf Comments ) {
			$comments = array( $comments );
		}

		if ( count( $comments ) == 0 ) {
			return true;
		}

		if ( $comments instanceOf Comments ) {
			// Delete all the comments directly
			$result = $comments->delete();
		}
		else if ( is_array( $comments ) ) {
			// We have an array... of something

			if ( $comments[0] instanceOf Comment ) {

				$result = true;
				/** @var Comment $comment */
				foreach ( $comments as $comment ) {
					$comment_result = $comment->delete();

					if ( !$comment_result ) {
						$result = false;
					}
				}

			}
			else if ( is_numeric( $comments[0] ) ) {
				// We were passed an array of ID's. Get their objects and delete them.
				/** @var Comments $comments */
				$comments = self::get( array( 'id' => $comments ) );

				$result = $comments->delete();
			}
			else {
				$result = false;
			}

		}
		else {
			// We were passed a type we could not understand.
			$result = false;
		}

		return $result;
	}

	/**
	 * Changes the status of comments
	 * @param array|Comments $comments Comments to be moderated
	 * @param int|string $status The new status for the provided array of comments, "unapproved" if not provided
	 * @return bool True if all the comments were successfully changed
	 * @internal param \Comment $mixed IDs to moderate.  May be a single ID, or an array of IDs
	 */
	public static function moderate_these( $comments, $status = null )
	{
		if(empty($status)) {
			$status = Comment::status('unapproved');
		}
		// Ensure we have an id, not a string
		$status = Comment::status($status);
		$comments = Utils::single_array($comments);
		if ( count( $comments ) == 0 ) {
			return false;
		}
		if ( $comments[0] instanceOf Comment ) {
			// We were passed an array of comment objects. Use them directly.
			$result = true;
			/** @var Comment $comment */
			foreach ( $comments as $comment ) {
				$comment->status = $status;
				$result &= $comment->update();
				EventLog::log( _t( 'Comment %1$s moderated from %2$s', array( $comment->id, $comment->post->title ) ), 'info', 'comment', 'habari' );
			}
		}
		else if ( is_numeric( $comments[0] ) ) {
			$result = true;
			foreach ( $comments as $commentid ) {
				$result &= DB::update( DB::table( 'comments' ), array( 'status' => $status), array( 'id' => $commentid ) );
				EventLog::log( _t( 'Comment %1$d moderated', array( $commentid ) ), 'info', 'comment', 'habari' );
			}
		}
		else {
			// We were passed a type we could not understand.
			return false;
		}
		return $result;
	}

	/**
	 * selects all comments from a given email address
	 * @param string $email an email address
	 * @return array an array of Comment objects written by that email address
	**/
	public static function by_email( $email = '' )
	{
		if ( ! $email ) {
			return array();
		}
		return self::get( array ( "email" => $email ) );
	}

	/**
	 * selects all comments from a given name
	 * @param string $name a name
	 * @return array an array of Comment objects written by the given name
	**/
	public static function by_name ( $name = '' )
	{
		if ( ! $name ) {
			return array();
		}
		return self::get( array ( "name" => $name ) );
	}

	/**
	 * selects all comments from a given IP address
	 * @param string $ip an IP address
	 * @return array an array of Comment objects written from the given IP
	**/
	public static function by_ip ( $ip = '' )
	{
		if ( ! $ip ) {
			return false;
		}
		return self::get( array ( "ip" => $ip ) );
	}

	/**
	 * select all comments from an author's URL
	 * @param string $url a URL
	 * @return array array an array of Comment objects with the same URL
	**/
	public static function by_url ( $url = '' )
	{
		if ( ! $url ) {
			return false;
		}
		return self::get( array( "url" => $url ) );
	}

	/**
	 * Returns all comments for a supplied post ID
	 * @param integer $post_id The id of a post
	 * @return array  an array of Comment objects for the given post
	 */
	public static function by_post_id( $post_id )
	{
		return self::get( array( 'post_id' => $post_id, 'nolimit' => 1, 'orderby' => 'date ASC' ) );
	}

	/**
	 * select all comments for a given post slug
	 * @param string $slug a post slug
	 * @return array array an array of Comment objects for the given post
	**/
	public static function by_slug ( $slug = '' )
	{
		if ( ! $slug ) {
			return false;
		}
		return self::get( array( 'post_slug' => $slug, 'nolimit' => 1, 'orderby' => 'date ASC' ) );
	}

	/**
	 * select all comments of a given status
	 * @param int $status a status value
	 * @return array an array of Comment objects with the same status
	**/
	public static function by_status ( $status = 0 )
	{
		return self::get( array( 'status' => $status, 'nolimit' => 1, 'orderby' => 'date ASC' ) );
	}

	/**
	 * Returns all of the comments from the current Comments object having the specified index for the specified field
	 * <code>$tb = $comments->only( 'trackbacks' )</code>
	 * @param string $field The field on the comment that is being filtered
	 * @param mixed $index The value of the field that qualifies a comment
	 * @return array an array of Comment objects of the specified type
	 */
	public function only( $field, $index )
	{
		static $results = array();
		if(!isset($results[$field][$index])) {
			$result = array();
			foreach ( $this as $comment ) {
				if($comment->$field == $index) {
					$result[$comment->id] = $comment;
				}
			}
			$results[$field][$index] = new Comments($result);
		}
		return $results[$field][$index];
	}

	/**
	 * Filter Comments using a callback function
	 * @param Callable|array $filter A callback function that returns true if the item passed in should be kept
	 * @return Comments The filtered comments
	 */
	public function filter($filter)
	{
		if(is_callable($filter)) {
			$output = array();
			foreach($this as $comment) {
				if($filter($comment)) {
					$output[] = $comment;
				}
			}
			return new Comments($output);
		}
		elseif(is_array($filter)){
			$filters = $filter;
			$filter = function($item) use($filters) {
				$output = false;
				foreach($filters as $filter) {
					$output = $output || $item->$filter;
				}
				return $output;
			};
			return $this->filter($filter);
		}
		else {
			return $this;
		}
	}

	/**
	 * Implements custom object properties
	 * @param string $name Name of property to return
	 * @return mixed The requested field value
	*/
	public function __get( $name )
	{
		static $moderated = null;

		switch ( $name ) {
			case 'count':
				return count( $this );
			case 'moderated':
				if(empty($moderated)) {
					$moderated_statuses = Plugins::filter('moderated_statuses', array('approved'));
					$moderated_statuses = array_map(function($value){return Comment::status($value);}, $moderated_statuses);
					$moderated = array();
					foreach ( $this as $comment ) {
						if(in_array($comment->status, $moderated_statuses)) {
							$moderated[$comment->id] = $comment;
						}
						if ( isset( $_COOKIE['comment_' . Options::get( 'GUID' )] ) ) {
							list( $commenter, $email, $url ) = explode( '#', $_COOKIE['comment_' . Options::get( 'GUID' )] );
							if ( ( $comment->ip == Utils::get_ip() )
								&& ( $comment->name == $commenter )
								&& ( $comment->email == $email )
								&& ( $comment->url == $url )
							) {
								$moderated[$comment->id] = $comment;
							}
						}
					}
					$moderated = new Comments($moderated);
				}
				return $moderated;
			default:
				if($index = array_search($name, Comment::list_comment_statuses())) {
					return $this->only( 'status', $index );
				}
				if($index = array_search($name, Comment::list_comment_types())) {
					return $this->only( 'type', $index );
				}
				// Dumb check for plurals
				$pluralize = function($s) {
					return $s . 's';
				};
				if($index = array_search($name, array_map($pluralize, Comment::list_comment_statuses()))) {
					return $this->only( 'status', $index );
				}
				if($index = array_search($name, array_map($pluralize, Comment::list_comment_types()))) {
					return $this->only( 'type', $index );
				}
		}
		trigger_error('Property "@name" does not exist.', E_NOTICE);
		return null;
	}

	/**
	 * function delete
	 * Deletes all comments in this object
	 */
	public function delete()
	{
		$result = true;

		/** @var Comment $comment */
		foreach ( $this as $comment ) {
			$result &= $comment->delete();
			EventLog::log( _t( 'Comment %1$s deleted from %2$s', array( $comment->id, $comment->post->title ) ), 'info', 'comment', 'habari' );
		}
		// Clear ourselves.
		$this->exchangeArray( array() );
		return $result;
	}

	/**
	 * returns the number of comments based on the specified status and type
	 * @param integer|string $status A comment status value, or false to not filter on status (default: 'approved')
	 * @param integer|string $type A comment type value, or false to not filter on type (default: 'comment')
	 * @return int a count of the comments based on the specified status and type
	**/
	public static function count_total( $status = 'approved', $type = 'comment' )
	{
		$params = array( 'count' => 1, 'status' => $status, 'type' => $type );
		return self::get( $params );
	}

	/**
	 * returns the number of comments attributed to the specified name
	 * @param string $name a commenter's name
	 * @param integer|string $status A comment status value, or false to not filter on status (default: 'approved')
	 * @return int a count of the comments from the specified name
	**/
	public static function count_by_name( $name = '', $status = 'approved' )
	{
		$params = array ( 'name' => $name, 'count' => 'name' );
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * returns the number of comments attributed ot the specified email
	 * @param string $email an email address
	 * @param integer|string $status A comment status value, or false to not filter on status (default: 'approved')
	 * @return int a count of the comments from the specified email
	**/
	public static function count_by_email( $email = '', $status = 'approved' )
	{
		$params = array( 'email' => $email, 'count' => 'email');
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * returns the number of comments attributed to the specified URL
	 * @param string $url a URL
	 * @param integer|string $status a comment status value, or false to not filter on status (default: 'approved')
	 * @return int a count of the comments from the specified URL
	**/
	public static function count_by_url( $url = '', $status = 'approved' )
	{
		$params = array( 'url' => $url, 'count' => 'url');
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * returns the number of comments from the specified IP address
	 * @param string $ip an IP address
	 * @param integer|string $status A comment status value, or false to not filter on status (default: 'approved')
	 * @return int a count of the comments from the specified IP address
	**/
	public static function count_by_ip( $ip = '', $status = 'approved' )
	{
		$params = array( 'ip' => $ip, 'count' => 'ip');
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * returns the number of comments attached to the specified post
	 * @param string $slug a post slug
	 * @param integer|string $status A comment status value, or false to not filter on status (default: 'approved')
	 * @return int a count of the comments attached to the specified post
	**/
	public static function count_by_slug( $slug = '', $status = 'approved' )
	{
		$params = array( 'post_slug' => $slug, 'count' => 'id');
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * returns the number of comments attached to the specified post
	 * @param int $id a post ID
	 * @param integer|string $status A comment status value, or false to not filter on status(default: 'approved')
	 * @return int a count of the comments attached to the specified post
	**/
	public static function count_by_id( $id = 0, $status = 'approved' )
	{
		$params = array( 'post_id' => $id, 'count' => 'id' );
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * returns the number of comments attached to posts by the specified author
	 * @param int $id a user ID
	 * @param integer|string $status A comment status value, or false to not filter on status(default: 'approved')
	 * @return int a count of the comments attached to the specified post
	**/
	public static function count_by_author( $id = 0, $status = 'approved' )
	{
		$params = array( 'post_author' => $id, 'count' => 'id' );
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * delete all the comments and commentinfo for comments with this status
	 * @param integer|string $status a comment status ID or name
	**/
	public static function delete_by_status( $status )
	{
		$status = Comment::status( $status );
		// first, purge all the comments
		DB::query( 'DELETE FROM {comments} WHERE status=?', array( $status ) );
		// now purge any commentinfo records from those comments
		DB::query( 'DELETE FROM {commentinfo} WHERE comment_id NOT IN ( SELECT id FROM {comments} )' );
	}

	/**
	 * Parses a search string for status, type, author, and tag keywords. Returns
	 * an associative array which can be passed to Comments::get(). If multiple
	 * authors, statuses, or types are specified, we assume an implicit OR
	 * such that (e.g.) any author that matches would be returned.
	 *
	 * @param string $search_string The search string
	 * @return array An associative array which can be passed to Comments::get()
	 */
	public static function search_to_get( $search_string )
	{
		$statuses = array_flip( Comment::list_comment_statuses() );
		$types = array_flip( Comment::list_comment_types() );
		$arguments = array(
						'name' => array(),
						'status' => array(),
						'type' => array()
						);
		$criteria = '';

		$tokens = explode( ' ', $search_string );

		foreach ( $tokens as $token ) {
			// check for a keyword:value pair
			if ( preg_match( '/^\w+:\S+$/u', $token ) ) {
				list( $keyword, $value ) = explode( ':', $token );

				$keyword = strtolower( $keyword );
				$value = MultiByte::strtolower( $value );
				switch ( $keyword ) {
					case 'author':
						$arguments['name'][] = $value;
						break;
					case 'status':
						if ( isset( $statuses[$value] ) ) {
							$arguments['status'][] = (int) $statuses[$value];
						}
						break;
					case 'type':
						if ( isset( $types[$value] ) ) {
							$arguments['type'][] = (int) $types[$value];
						}
						break;
				}
			}
			else {
				$criteria .= $token . ' ';
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
			$arguments['criteria'] = $criteria;
		}

		return $arguments;

	}
}
?>
