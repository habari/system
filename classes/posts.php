<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Posts Class
 *
 * class Posts
 * This class provides two key features.
 * 1: Posts contains static method get() that returns the
 * requested posts based on the passed criteria.  Depending on the type of
 * request, different types are returned. See the function for details
 * 2: An instance of Posts functions as an array (by extending ArrayObject) and
 * is returned by Posts::get() as the results of a query.  This allows the
 * result of Posts::get() to be iterated (for example, in a foreach construct)
 * and to have properties that can be accessed that describe the results
 * (for example, $posts->onepost).
 */
class Posts extends ArrayObject implements IsContent
{
	public $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
	 * function __get
	 * Returns properties of a Posts object.
	 * This is the function that returns information about the set of posts that
	 * was requested.  This function should offer property names that are identical
	 * to properties of instances of the URL class.  A call to Posts::get()
	 * without parameters should return mostly the same property values as the
	 * global $url object for the request.  The difference would occur when
	 * the data returned doesn't necessarily match the request, such as when
	 * several posts are requested, but only one is available to return.
	 * @param string The name of the property to return.
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'onepost':
				return ( count( $this ) == 1 );
		}

		return false;
	}

	/**
	 * Returns a post or posts based on supplied parameters.
	 * @todo <b>THIS CLASS SHOULD CACHE QUERY RESULTS!</b>
	 *
	 * @param array $paramarray An associative array of parameters, or a querystring.
	 * The following keys are supported:
	 * - id => a post id or array of post ids
	 * - not:id => a post id or array of post ids to exclude
	 * - slug => a post slug or array of post slugs
	 * - not:slug => a post slug or array of post slugs to exclude
	 * - user_id => an author id or array of author ids
	 * - content_type => a post content type or array post content types
	 * - not:content_type => a post content type or array post content types to exclude
	 * - status => a post status, an array of post statuses, or 'any' for all statuses
	 * - year => a year of post publication
	 * - month => a month of post publication, ignored if year is not specified
	 * - day => a day of post publication, ignored if month and year are not specified
	 * - before => a timestamp to compare post publication dates
	 * - after => a timestamp to compare post publication dates
	 * - month_cts => return the number of posts published in each month
	 * - criteria => a literal search string to match post content
	 * - has:info => a post info key or array of post info keys, which should be present
	 * - all:info => a post info key and value pair or array of post info key and value pairs, which should all be present and match
	 * - not:all:info => a post info key and value pair or array of post info key and value pairs, to exclude if all are present and match
	 * - any:info => a post info key and value pair or array of post info key and value pairs, any of which can match
	 * - not:any:info => a post info key and value pair or array of post info key and value pairs, to exclude if any are present and match
	 * - vocabulary => an array describing parameters related to vocabularies attached to posts. This can be one of two forms:
	 *   - object-based, in which an array of Term objects are passed
	 *     - any => posts associated with any of the terms are returned
	 *     - all => posts associated with all of the terms are returned
	 *     - not => posts associated with none of the terms are returned
	 *   - property-based, in which an array of vocabulary names and associated fields are passed
	 *     - vocabulary_name:term => a vocabulary name and term slug pair or array of vocabulary name and term slug pairs, any of which can be associated with the posts
	 *     - vocabulary_name:term_display => a vocabulary name and term display pair or array of vocabulary name and term display pairs, any of which can be associated with the posts
	 *     - vocabulary_name:not:term => a vocabulary name and term slug pair or array of vocabulary name and term slug pairs, none of which can be associated with the posts
	 *     - vocabulary_name:not:term_display => a vocabulary name and term display pair or array of vocabulary name and term display pairs, none of which can be associated with the posts
	 *     - vocabulary_name:all:term => a vocabulary name and term slug pair or array of vocabulary name and term slug pairs, all of which must be associated with the posts
	 *     - vocabulary_name:all:term_display => a vocabulary name and term display pair or array of vocabulary name and term display pairs, all of which must be associated with the posts
	 * - limit => the maximum number of posts to return, implicitly set for many queries
	 * - nolimit => do not implicitly set limit
	 * - offset => amount by which to offset returned posts, used in conjunction with limit
	 * - page => the 'page' of posts to return when paging, sets the appropriate offset
	 * - count => return the number of posts that would be returned by this request
	 * - orderby => how to order the returned posts
	 * - groupby => columns by which to group the returned posts, for aggregate functions
	 * - having => for selecting posts based on an aggregate function
	 * - where => manipulate the generated WHERE clause. Currently broken, see https://trac.habariproject.org/habari/ticket/1383
	 * - add_select => an array of clauses to be added to the generated SELECT clause.
	 * - fetch_fn => the function used to fetch data, one of 'get_results', 'get_row', 'get_value'
	 *
	 * Further description of parameters, including usage examples, can be found at
	 * http://wiki.habariproject.org/en/Dev:Retrieving_Posts
	 *
	 * @return array An array of Post objects, or a single post object, depending on request
	 */
	public static function get( $paramarray = array() )
	{

		// If $paramarray is a querystring, convert it to an array
		$paramarray = Utils::get_params( $paramarray );

		// let plugins alter the param array before we use it. could be useful for modifying search results, etc.
		$paramarray = Plugins::filter( 'posts_get_paramarray', $paramarray );

		$join_params = array();
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value', 'get_query' );
		$select_ary = array();

		// Default fields to select, everything by default
		foreach ( Post::default_fields() as $field => $value ) {
			$select_ary[$field] = "{posts}.$field AS $field";
			$select_distinct[$field] = "{posts}.$field";
		}

		// Default parameters
		$orderby = 'pubdate DESC';

		// Define the WHERE sets to process and OR in the final SQL statement
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets = $paramarray['where'];
		}
		else {
			$wheresets = array( array() );
		}

		/* Start building the WHERE clauses */

		$wheres = array();
		$joins = array();

		// If the request as a textual WHERE clause, skip the processing of the $wheresets since it's empty
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[] = $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// Safety mechanism to prevent empty queries
				$where = array();
				$paramset = array_merge( (array) $paramarray, (array) $paramset );
				// $nots= preg_grep( '%^not:(\w+)$%iu', (array) $paramset );

				if ( isset( $paramset['id'] ) ) {
					if ( is_array( $paramset['id'] ) ) {
						array_walk( $paramset['id'], create_function( '&$a,$b', '$a = intval( $a );' ) );
						$where[] = "{posts}.id IN (" . implode( ',', array_fill( 0, count( $paramset['id'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['id'] );
					}
					else {
						$where[] = "{posts}.id = ?";
						$params[] = (int) $paramset['id'];
					}
				}
				if ( isset( $paramset['not:id'] ) ) {
					if ( is_array( $paramset['not:id'] ) ) {
						array_walk( $paramset['not:id'], create_function( '&$a,$b', '$a = intval( $a );' ) );
						$where[] = "{posts}.id NOT IN (" . implode( ',', array_fill( 0, count( $paramset['not:id'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['not:id'] );
					}
					else {
						$where[] = "{posts}.id != ?";
						$params[] = (int) $paramset['not:id'];
					}
				}
				if ( isset( $paramset['status'] ) && ( $paramset['status'] != 'any' ) && ( 0 !== $paramset['status'] ) ) {
					if ( is_array( $paramset['status'] ) ) {
						// remove 'any' from the list if we have an array
						$paramset['status'] = array_diff( $paramset['status'], array( 'any' ) );
						array_walk( $paramset['status'], create_function( '&$a,$b', '$a = Post::status( $a );' ) );
						$where[] = "{posts}.status IN (" . implode( ',', array_fill( 0, count( $paramset['status'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['status'] );
					}
					else {
						$where[] = "{posts}.status = ?";
						$params[] = (int) Post::status( $paramset['status'] );
					}
				}
				if ( isset( $paramset['content_type'] ) && ( $paramset['content_type'] != 'any' ) && ( 0 !== $paramset['content_type'] ) ) {
					if ( is_array( $paramset['content_type'] ) ) {
						// remove 'any' from the list if we have an array
						$paramset['content_type'] = array_diff( $paramset['content_type'], array( 'any' ) );
						array_walk( $paramset['content_type'], create_function( '&$a,$b', '$a = Post::type( $a );' ) );
						$where[] = "{posts}.content_type IN (" . implode( ',', array_fill( 0, count( $paramset['content_type'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['content_type'] );
					}
					else {
						$where[] = "{posts}.content_type = ?";
						$params[] = (int) Post::type( $paramset['content_type'] );
					}
				}
				if ( isset( $paramset['not:content_type'] ) ) {
					if ( is_array( $paramset['not:content_type'] ) ) {
						array_walk( $paramset['not:content_type'], create_function( '&$a,$b', '$a = Post::type( $a );' ) );
						$where[] = "{posts}.content_type NOT IN (" . implode( ',', array_fill( 0, count( $paramset['not:content_type'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['not:content_type'] );
					}
					else {
						$where[] = "{posts}.content_type != ?";
						$params[] = (int) Post::type( $paramset['not:content_type'] );
					}
				}
				if ( isset( $paramset['slug'] ) ) {
					if ( is_array( $paramset['slug'] ) ) {
						$where[] = "{posts}.slug IN (" . implode( ',', array_fill( 0, count( $paramset['slug'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['slug'] );
					}
					else {
						$where[] = "{posts}.slug = ?";
						$params[] = (string) $paramset['slug'];
					}
				}
				
				if ( isset( $paramset['not:slug'] ) ) {
					if ( is_array( $paramset['not:slug'] ) ) {
						$where[] = "{posts}.slug NOT IN (" . implode( ',', array_fill( 0, count( $paramset['not:slug'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['not:slug'] );
					}
					else {
						$where[] = "{posts}.slug != ?";
						$params[] = (string) $paramset['not:slug'];
					}
				}
				
				if ( isset( $paramset['user_id'] ) && 0 !== $paramset['user_id'] ) {
					if ( is_array( $paramset['user_id'] ) ) {
						array_walk( $paramset['user_id'], create_function( '&$a,$b', '$a = intval( $a );' ) );
						$where[] = "{posts}.user_id IN (" . implode( ',', array_fill( 0, count( $paramset['user_id'] ), '?' ) ) . ")";
						$params = array_merge( $params, $paramset['user_id'] );
					}
					else {
						$where[] = "{posts}.user_id = ?";
						$params[] = (int) $paramset['user_id'];
					}

				}

				if ( isset( $paramset['vocabulary'] ) ) {
					
					if ( is_string( $paramset['vocabulary'] ) ) {
						$paramset['vocabulary'] = Utils::get_params( $paramset['vocabulary'] );
					}
					
					// parse out the different formats we accept arguments in into a single mutli-dimensional array of goodness
					$paramset['vocabulary'] = self::vocabulary_params( $paramset['vocabulary'] );
					$object_id = Vocabulary::object_type_id( 'post' );
					
					$all = array();
					$any = array();
					$not = array();
					
					if ( isset( $paramset['vocabulary']['all'] ) ) {
						$all = $paramset['vocabulary']['all'];
					}
					
					if ( isset( $paramset['vocabulary']['any'] ) ) {
						$any = $paramset['vocabulary']['any'];
					}
					
					if ( isset( $paramset['vocabulary']['not'] ) ) {
						$not = $paramset['vocabulary']['not'];
					}
					
					foreach ( $all as $vocab => $value ) {
						
						foreach ( $value as $field => $terms ) {
							
							// we only support these fields to search by
							if ( !in_array( $field, array( 'id', 'term', 'term_display' ) ) ) {
								continue;
							}
							
							$joins['term2post_posts'] = ' JOIN {object_terms} ON {posts}.id = {object_terms}.object_id';
							$joins['terms_term2post'] = ' JOIN {terms} ON {object_terms}.term_id = {terms}.id';
							$joins['terms_vocabulary'] = ' JOIN {vocabularies} ON {terms}.vocabulary_id = {vocabularies}.id';
							
							$where[] = '{vocabularies}.name = ? AND {terms}.' . $field . ' IN ( ' . Utils::placeholder_string( $terms ) . ' ) AND {object_terms}.object_type_id = ?';
							$params[] = $vocab;
							$params = array_merge( $params, $terms );
							$params[] = $object_id;
							
						}
						
						// this causes no posts to match if combined with 'any' below and should be re-thought... somehow
						$groupby = implode( ',', $select_distinct );
						$having = 'count(*) = ' . count( $terms );
						
					}
					
					foreach ( $any as $vocab => $value ) {
						
						foreach ( $value as $field => $terms ) {
							
							// we only support these fields to search by
							if ( !in_array( $field, array( 'id', 'term', 'term_display' ) ) ) {
								continue;
							}
							
							$joins['term2post_posts'] = ' JOIN {object_terms} ON {posts}.id = {object_terms}.object_id';
							$joins['terms_term2post'] = ' JOIN {terms} ON {object_terms}.term_id = {terms}.id';
							$joins['terms_vocabulary'] = ' JOIN {vocabularies} ON {terms}.vocabulary_id = {vocabularies}.id';
							
							$where[] = '{vocabularies}.name = ? AND {terms}.' . $field . ' IN ( ' . Utils::placeholder_string( $terms ) . ' ) AND {object_terms}.object_type_id = ?';
							$params[] = $vocab;
							$params = array_merge( $params, $terms );
							$params[] = $object_id;
							
						}
						
					}
					
					foreach ( $not as $vocab => $value ) {
						
						foreach ( $value as $field => $terms ) {
							
							// we only support these fields to search by
							if ( !in_array( $field, array( 'id', 'term', 'term_display' ) ) ) {
								continue;
							}
							
							$where[] = 'NOT EXISTS ( SELECT 1
								FROM {object_terms} 
								JOIN {terms} ON {terms}.id = {object_terms}.term_id 
								JOIN {vocabularies} ON {terms}.vocabulary_id = {vocabularies}.id  
								WHERE {terms}.' . $field . ' IN (' . Utils::placeholder_string( $terms ) . ')
								AND {object_terms}.object_id = {posts}.id 
								AND {object_terms}.object_type_id = ? 
								AND {vocabularies}.name = ?
							)';
							$params = array_merge( $params, array_values( $terms ) );
							$params[] = $object_id;
							$params[] = $vocab;
							
						}
						
					}
					
				}

				if ( isset( $paramset['criteria'] ) ) {
					// this regex matches any unicode letters (\p{L}) or numbers (\p{N}) inside a set of quotes (but strips the quotes) OR not in a set of quotes
					preg_match_all( '/(?<=")([\p{L}\p{N}]+[^"]*)(?=")|([\p{L}\p{N}]+)/u', $paramset['criteria'], $matches );
					foreach ( $matches[0] as $word ) {
						$where[] .= "( LOWER( {posts}.title ) LIKE ? OR  LOWER( {posts}.content ) LIKE ?)";
						$params[] = '%' . MultiByte::strtolower( $word ) . '%';
						$params[] = '%' . MultiByte::strtolower( $word ) . '%';  // Not a typo (there are two ? in the above statement)
					}
				}

				if ( isset( $paramset['all:info'] ) || isset( $paramset['info'] ) ) {

					// merge the two possibile calls together
					$infos = array_merge( isset( $paramset['all:info'] ) ? $paramset['all:info'] : array(), isset( $paramset['info'] ) ? $paramset['info'] : array() );

					if ( Utils::is_traversable( $infos ) ) {
						$pi_count = 0;
						foreach ( $infos as $info_key => $info_value ) {
							$pi_count++;
							$joins['info_' . $info_key] = " LEFT JOIN {postinfo} ipi{$pi_count} ON {posts}.id = ipi{$pi_count}.post_id AND ipi{$pi_count}.name = ? AND ipi{$pi_count}.value = ?";
							$join_params[] = $info_key;
							$join_params[] = $info_value;
							$where[] = "ipi{$pi_count}.name <> ''";

							$select_ary["info_{$info_key}_value"] = "ipi{$pi_count}.value AS info_{$info_key}_value";
							$select_distinct["info_{$info_key}_value"] = "info_{$info_key}_value";
						}
					}

				}

				if ( isset( $paramset['any:info'] ) ) {
					if ( Utils::is_traversable( $paramset['any:info'] ) ) {
						$pi_count = 0;
						$pi_where = array();
						foreach ( $paramset['any:info'] as $info_key => $info_value ) {
							$pi_count++;

							$join_params[] = $info_key;
							if ( is_array( $info_value ) ) {
								$joins['any_info_' . $info_key] = " LEFT JOIN {postinfo} aipi{$pi_count} ON {posts}.id = aipi{$pi_count}.post_id AND aipi{$pi_count}.name = ? AND aipi{$pi_count}.value IN (" .Utils::placeholder_string( count( $info_value ) ).")";
								$join_params = array_merge( $join_params, $info_value );
							}
							else {
								$joins['any_info_' . $info_key] = " LEFT JOIN {postinfo} aipi{$pi_count} ON {posts}.id = aipi{$pi_count}.post_id AND aipi{$pi_count}.name = ? AND aipi{$pi_count}.value = ?";
								$join_params[] = $info_value;
							}

							$pi_where[] = "aipi{$pi_count}.name <> ''";

							$select_ary["info_{$info_key}_value"] = "aipi{$pi_count}.value AS info_{$info_key}_value";
							$select_distinct["info_{$info_key}_value"] = "info_{$info_key}_value";
						}
						$where[] = '(' . implode( ' OR ', $pi_where ) . ')';
					}
				}

				if ( isset( $paramset['has:info'] ) ) {
					$the_ins = array();
					$has_info = Utils::single_array( $paramset['has:info'] );
					$pi_count = 0;
					$pi_where = array();
					foreach ( $has_info as $info_name ) {
						$pi_count++;
						$joins['has_info_' . $info_name] = " LEFT JOIN {postinfo} hipi{$pi_count} ON {posts}.id = hipi{$pi_count}.post_id AND hipi{$pi_count}.name = ?";
						$join_params[] = $info_name;
						$pi_where[] = "hipi{$pi_count}.name <> ''";

						$select_ary["info_{$info_name}_value"] = "hipi{$pi_count}.value AS info_{$info_name}_value";
						$select_distinct["info_{$info_name}_value"] = "info_{$info_name}_value";
					}
					$where[] = '(' . implode( ' OR ', $pi_where ) . ')';
				}

				if ( isset( $paramset['not:all:info'] ) || isset( $paramset['not:info'] ) ) {

					// merge the two possible calls together
					$infos = array_merge( isset( $paramset['not:all:info'] ) ? $paramset['not:all:info'] : array(), isset( $paramset['not:info'] ) ? $paramset['not:info'] : array() );

					if ( Utils::is_traversable( $infos ) ) {
						$the_ins = array();

						foreach ( $infos as $info_key => $info_value ) {

							$the_ins[] = ' ({postinfo}.name = ? AND {postinfo}.value = ? ) ';
							$params[] = $info_key;
							$params[] = $info_value;

						}

						$where[] = '
							{posts}.id NOT IN (
							SELECT post_id FROM {postinfo}
							WHERE ( ' . implode( ' OR ', $the_ins ) . ' )
							GROUP BY post_id
							HAVING COUNT(*) = ' . count( $infos ) . ' )
						';
						// see that hard-coded number? sqlite wets itself if we use a bound parameter... don't change that

					}

				}

				if ( isset( $paramset['not:any:info'] ) ) {

					if ( Utils::is_traversable( $paramset['not:any:info'] ) ) {

						foreach ( $paramset['not:any:info'] as $info_key => $info_value ) {

							$the_ins[] = ' ({postinfo}.name = ? AND {postinfo}.value = ? ) ';
							$params[] = $info_key;
							$params[] = $info_value;

						}

						$where[] = '
							{posts}.id NOT IN (
								SELECT post_id FROM {postinfo}
								WHERE ( ' . implode( ' OR ', $the_ins ) . ' )
							)
						';

					}

				}

				/**
				 * Build the statement needed to filter by pubdate:
				 * If we've got the day, then get the date;
				 * If we've got the month, but no date, get the month;
				 * If we've only got the year, get the whole year.
				 */
				if ( isset( $paramset['day'] ) && isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
					$where[] = 'pubdate BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], $paramset['day'] );
					$start_date = HabariDateTime::date_create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 day' )->sql;
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], $paramset['day'], $paramset['year'] ) );
					//$params[] = date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'], $paramset['day'], $paramset['year'] ) );
				}
				elseif ( isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
					$where[] = 'pubdate BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], 1 );
					$start_date = HabariDateTime::date_create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 month' )->sql;
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], 1, $paramset['year'] ) );
					//$params[] = date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'] + 1, 0, $paramset['year'] ) );
				}
				elseif ( isset( $paramset['year'] ) ) {
					$where[] = 'pubdate BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], 1, 1 );
					$start_date = HabariDateTime::date_create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 year' )->sql;
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, 1, 1, $paramset['year'] ) );
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, -1, 1, 1, $paramset['year'] + 1 ) );
				}

				if ( isset( $paramset['after'] ) ) {
					$where[] = 'pubdate > ?';
					$params[] = HabariDateTime::date_create( $paramset['after'] )->sql;
				}

				if ( isset( $paramset['before'] ) ) {
					$where[] = 'pubdate < ?';
					$params[] = HabariDateTime::date_create( $paramset['before'] )->sql;
				}

				// Concatenate the WHERE clauses
				if ( count( $where ) > 0 ) {
					$wheres[] = ' (' . implode( ' AND ', $where ) . ') ';
				}
			}
		}

		// Only show posts to which the current user has permission
		if ( isset( $paramset['ignore_permissions'] ) ) {
			$master_perm_where = '';
		}
		else {
			// This set of wheres will be used to generate a list of post_ids that this user can read
			$perm_where = array();
			$perm_where_denied = array();
			$params_where = array();
			$where = array();

			// Get the tokens that this user is granted or denied access to read
			$read_tokens = isset( $paramset['read_tokens'] ) ? $paramset['read_tokens'] : ACL::user_tokens( User::identify(), 'read', true );
			$deny_tokens = isset( $paramset['deny_tokens'] ) ? $paramset['deny_tokens'] : ACL::user_tokens( User::identify(), 'deny', true );

			// If a user can read his own posts, let him
			if ( User::identify()->can( 'own_posts', 'read' ) ) {
				$perm_where['own_posts_id'] = '{posts}.user_id = ?';
				$params_where[] = User::identify()->id;
			}

			$params_where = array();
			// If a user can read any post type, let him
			if ( User::identify()->can( 'post_any', 'read' ) ) {
				$perm_where = array( 'post_any' => '(1=1)' );
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

				// If a user can read posts with specific tokens, let him
				if ( count( $read_tokens ) > 0 ) {
					$joins['post_tokens__allowed'] = ' LEFT JOIN {post_tokens} pt_allowed ON {posts}.id= pt_allowed.post_id AND pt_allowed.token_id IN ('.implode( ',', $read_tokens ).')';
					$perm_where['perms_join_null'] = 'pt_allowed.post_id IS NOT NULL';
				}

				// If a user has access to read other users' unpublished posts, let him
				if ( User::identify()->can( 'post_unpublished', 'read' ) ) {
					$perm_where[] = '({posts}.status <> ? AND {posts}.user_id <> ?)';
					$params_where[] = Post::status( 'published' );
					$params_where[] = User::identify()->id;
				}

			}

			$params_where_denied = array();
			// If a user is denied access to all posts, do so
			if ( User::identify()->cannot( 'post_any' ) ) {
				$perm_where_denied = array( '(1=0)' );
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

				// If a user is denied access to read other users' unpublished posts, deny it
				if ( User::identify()->cannot( 'post_unpublished' ) ) {
					$perm_where_denied[] = '({posts}.status = ? OR {posts}.user_id = ?)';
					$params_where_denied[] = Post::status( 'published' );
					$params_where_denied[] = User::identify()->id;
				}

			}

			// This doesn't work yet because you can't pass these arrays by reference
			Plugins::act( 'post_get_perm_where', $perm_where, $params_where, $paramarray );
			Plugins::act( 'post_get_perm_where_denied', $perm_where_denied, $params_where_denied, $paramarray );
						
			// Set up the merge params
			$merge_params = array( $join_params, $params );
			
			// If there are granted permissions to check, add them to the where clause
			if ( count( $perm_where ) == 0 && !isset( $joins['post_tokens__allowed'] ) ) {
				// You have no grants.  You get no posts.
				$where['perms_granted'] = '(1=0)';
			}
			elseif ( count( $perm_where ) > 0 ) {
				$where['perms_granted'] = '
					(' . implode( ' OR ', $perm_where ) . ')
				';
				$merge_params[] = $params_where;
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
				$merge_params[] = $params_where_denied;
			}
			
			// Merge the params
			$params = call_user_func_array( 'array_merge', $merge_params );

			// AND the separate permission-related WHERE clauses
			$master_perm_where = implode( ' AND ', $where );
		}

		// Extract the remaining parameters which will be used onwards
		// For example: page number, fetch function, limit
		$paramarray = new SuperGlobal( $paramarray );
		$extract = $paramarray->filter_keys( 'page', 'fetch_fn', 'count', 'orderby', 'groupby', 'limit', 'offset', 'nolimit', 'having', 'add_select' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}

		// Define the LIMIT if it does not exist, unless specific posts are requested or we're getting the monthly counts
		if ( !isset( $limit ) && !isset( $paramset['id'] ) && !isset( $paramset['slug'] ) && !isset( $paramset['month_cts'] ) ) {
			$limit = Options::get( 'pagination' ) ? (int) Options::get( 'pagination' ) : 5;
		}
		elseif ( !isset( $limit ) ) {
			$selected_posts = 0;
			if ( isset( $paramset['id'] ) ) {
				$selected_posts += count( Utils::single_array( $paramset['id'] ) );
			}
			if ( isset( $paramset['slug'] ) ) {
				$selected_posts += count( Utils::single_array( $paramset['slug'] ) );
			}
			$limit = $selected_posts > 0 ? $selected_posts : '';
		}

		// Calculate the OFFSET based on the page number
		if ( isset( $page ) && is_numeric( $page ) && !isset( $paramset['offset'] ) ) {
			$offset = ( intval( $page ) - 1 ) * intval( $limit );
		}

		/**
		 * Determine which fetch function to use:
		 * If it is specified, make sure it is valid (based on the $fns array defined at the beginning of this function);
		 * Else, use 'get_results' which will return a Posts array of Post objects.
		 */
		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn = $fns[0];
			}
		}
		else {
			$fetch_fn = $fns[0];
		}
		
		
		// If the orderby has a function in it, try to create a select field for it with an alias
		if ( strpos( $orderby, '(' ) !== false ) {
			$orders = explode( ',', $orderby );
			$ob_index = 0;
			foreach ( $orders as $key => $order ) {
				if ( !preg_match( '%(?P<field>.+)\s+(?P<direction>DESC|ASC)%i', $order, $order_matches ) ) {
					$order_matches = array(
						'field' => $order,
						'direction' => '',
					);
				}
				
				if ( strpos( $order_matches['field'], '(' ) !== false ) {
					$ob_index++;
					$field = 'orderby' . $ob_index;
					$select_ary[$field] = "{$order_matches['field']} AS $field";
					$select_distinct[$field] = "{$order_matches['field']} AS $field";
					$orders[$key] = $field . ' ' . $order_matches['direction'];
				}
			}
			$orderby = implode( ', ', $orders );
		}

		// Add arbitrary fields to the select clause for sorting and output
		if ( isset( $add_select ) ) {
			$select_ary = array_merge( $select_ary, $add_select );
		}
		

		/**
		 * Turn the requested fields into a comma-separated SELECT field clause
		 */
		$select = implode( ', ', $select_ary );

		/**
		 * If a count is requested:
		 * Replace the current fields to select with a COUNT();
		 * Change the fetch function to 'get_value';
		 * Remove the ORDER BY since it's useless.
		 * Remove the GROUP BY (tag search added it)
		 */
		if ( isset( $count ) ) {
			$select = "COUNT($count)";
			$fetch_fn = 'get_value';
			$orderby = '';
			$groupby = '';
			$having = '';
		}

		// If the month counts are requested, replaced the select clause
		if ( isset( $paramset['month_cts'] ) ) {
			if ( isset( $paramset['vocabulary'] ) ) {
				$select = 'MONTH(FROM_UNIXTIME(pubdate)) AS month, YEAR(FROM_UNIXTIME(pubdate)) AS year, COUNT(DISTINCT {posts}.id) AS ct';
			}
			else {
				$select = 'MONTH(FROM_UNIXTIME(pubdate)) AS month, YEAR(FROM_UNIXTIME(pubdate)) AS year, COUNT(*) AS ct';
			}
			$groupby = 'year, month';
			if ( !isset( $paramarray['orderby'] ) ) {
				$orderby = 'year, month';
			}
		}


		// Remove the LIMIT if 'nolimit'
		// Doing this first should allow OFFSET to work
		if ( isset( $nolimit ) ) {
			$limit = '';
		}

		// Define the LIMIT and add the OFFSET if it exists
		if ( !empty( $limit ) ) {
			$limit = " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit .= " OFFSET $offset";
			}
		}
		else {
			$limit = '';
		}

		/* All SQL parts are constructed, on to real business! */

		/**
		 * Build the final SQL statement
		 */
		$query = '
			SELECT DISTINCT ' . $select . '
			FROM {posts} ' . "\n " . implode( "\n ", $joins ) . "\n";

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE (' . implode( " \nOR\n ", $wheres ) . ')';
			$query .= ( $master_perm_where == '' ) ? '' : ' AND (' . $master_perm_where . ')';
		}
		elseif ( $master_perm_where != '' ) {
			$query .= ' WHERE (' . $master_perm_where . ')';
		}
		$query .= ( ! isset( $groupby ) || $groupby == '' ) ? '' : ' GROUP BY ' . $groupby;
		$query .= ( ! isset( $having ) || $having == '' ) ? '' : ' HAVING ' . $having;
		$query .= ( ( $orderby == '' ) ? '' : ' ORDER BY ' . $orderby ) . $limit;

		/**
		 * DEBUG: Uncomment the following line to display everything that happens in this function
		 */
		//print_R('<pre>'.$query.'</pre>');
		//Utils::debug( $paramarray, $fetch_fn, $query, $params );
		//Session::notice($query);

		if ( 'get_query' == $fetch_fn ) {
			return array(
				$query,
				$params
			);
		}
		
		/**
		 * Execute the SQL statement using the PDO extension
		 */
		DB::set_fetch_mode( PDO::FETCH_CLASS );
		DB::set_fetch_class( 'Post' );
		$results = DB::$fetch_fn( $query, $params, 'Post' );

		//Utils::debug( $paramarray, $fetch_fn, $query, $params, $results );
		//var_dump( $query );

		/**
		 * Return the results
		 */
		if ( 'get_results' != $fetch_fn ) {
			// Since a single result was requested, return a single Post object.
			return $results;
		}
		elseif ( is_array( $results ) ) {
			// With multiple results, return a Posts array of Post objects.
			$c = __CLASS__;
			$return_value = new $c( $results );
			$return_value->get_param_cache = $paramarray;
			return $return_value;
		}
	}

	/**
	 * function by_status
	 * select all posts of a given status
	 * @param int a status value
	 * @return array an array of Comment objects with the same status
	 */
	public static function by_status ( $status )
	{
		return self::get( array( 'status' => $status ) );
	}


	/**
	 * function by_slug
	 * select all post content by slug
	 * @param string a post slug
	 * @return array an array of post content
	 */
	public static function by_slug ( $slug = '' )
	{
		return self::get( array( 'slug' => $slug ) );
	}

	/**
	 * static count_total
	 * return a count for the total number of posts
	 * @param mixed a status value to filter posts by; if false, then no filtering will be performed
	 * @return int the number of posts of specified type ( published or draft )
	 */
	public static function count_total( $status = false )
	{
		$params = array( 'count' => 1 );
		if ( $status !== false ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * return a count for the number of posts last queried
	 * @return int the number of posts of specified type ( published or draft )
	 */
	public function count_all()
	{
		$params = array_merge( ( array ) $this->get_param_cache, array( 'count' => '*', 'nolimit' => 1 ) );
		return Posts::get( $params );
	}

	/**
	 * static count_by_author
	 * return a count of the number of posts by the specified author
	 * @param int an author ID
	 * @param mixed a status value to filter posts by; if false, then no filtering will be performed
	 * @return int the number of posts by the specified author
	 */
	public static function count_by_author( $user_id, $status = false )
	{
		$params = array( 'user_id' => $user_id, 'count' => 1 );
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * static count_by_tag
	 * return a count of the number of posts with the assigned tag
	 * @param string A tag
	 * @param mixed a status value to filter posts by; if false, then no filtering will be performed
	 * @return int the number of posts with the specified tag
	 */
	public static function count_by_tag( $tag, $status = false )
	{
		$params = array( 'vocabulary' => array( Tags::vocabulary()->name . ':term_display' => $tag ), 'count' => 1 );
		if ( false !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * Reassigns the author of a specified set of posts
	 * @param mixed a user ID or name
	 * @param mixed an array of post IDs, an array of Post objects, or an instance of Posts
	 * @return bool Whether the rename operation succeeded or not
	 */
	public static function reassign( $user, $posts )
	{

		if ( ! is_int( $user ) ) {
			$u = User::get( $user );
			$user = $u->id;
		}
		// safety checks
		if ( ( $user == 0 ) || empty( $posts ) ) {
			return false;
		}
		switch ( true ) {
			case is_integer( reset( $posts ) ):
				break;
			case reset( $posts ) instanceof Post:
				$ids = array();
				foreach ( $posts as $post ) {
					$ids[] = $post->id;
				}
				$posts = $ids;
				break;
			default:
				return false;
		}
		$ids = implode( ',', $posts );

		// allow plugins the opportunity to prevent the reassignment now that we've verified the user and posts
		$allow = true;
		$allow = Plugins::filter( 'posts_reassign_allow', $allow, $user, $posts );

		if ( !$allow ) {
			return false;
		}

		// actually perform the reassignment
		Plugins::act( 'posts_reassign_before', array( $user, $posts ) );
		$results = DB::query( "UPDATE {posts} SET user_id=? WHERE id IN ({$ids})", array( $user ) );
		Plugins::act( 'posts_reassign_after', array( $user, $posts ) );

		return $results;
	}

	/**
	 * function publish_scheduled_posts
	 *
	 * Callback function to publish scheduled posts
	 */
	public static function publish_scheduled_posts( $params )
	{
		$select = array();
		// Default fields to select, everything by default
		foreach ( Post::default_fields() as $field => $value ) {
			$select[$field] = "{posts}.$field AS $field";
		}
		$select = implode( ',', $select );
		$posts = DB::get_results( 'SELECT ' . $select . ' FROM {posts} WHERE {posts}.status = ? AND {posts}.pubdate <= ? ORDER BY {posts}.pubdate DESC', array( Post::status( 'scheduled' ), HabariDateTime::date_create() ), 'Post' );
		foreach ( $posts as $post ) {
			$post->publish();
		}
	}

	/**
	 * function update_scheduled_posts_cronjob
	 *
	 * Creates or recreates the cronjob to publish
	 * scheduled posts. It is called whenever a post
	 * is updated or created
	 *
	 */
	public static function update_scheduled_posts_cronjob()
	{
		$min_time = DB::get_value( 'SELECT MIN(pubdate) FROM {posts} WHERE status = ?', array( Post::status( 'scheduled' ) ) );

		CronTab::delete_cronjob( 'publish_scheduled_posts' );
		if ( $min_time ) {
			CronTab::add_single_cron( 'publish_scheduled_posts', array( 'Posts', 'publish_scheduled_posts' ), $min_time, 'Next run: ' . HabariDateTime::date_create( $min_time )->get( 'c' ) );
		}
	}

	/**
	 * Returns an ascending post
	 *
	 * @param The Post from which to start
	 * @param The params by which to work out what is the next ascending post
	 * @return Post The ascending post
	 */
	public static function ascend( $post, $params = null )
	{
		$posts = null;
		$ascend = false;
		if ( !$params ) {
			$params = array( 'where' => "pubdate >= '{$post->pubdate->sql}' AND content_type = {$post->content_type} AND status = {$post->status}", 'limit' => 2, 'orderby' => 'pubdate ASC' );
			$posts = Posts::get( $params );
		}
		elseif ( $params instanceof Posts ) {
			$posts = $params;
		}
		else {
			if ( !array_key_exists( 'orderby', $params ) ) {
				$params['orderby'] = 'pubdate ASC';
			}
			$posts = Posts::get( $params );
		}
		// find $post and return the next one.
		$index = $posts->search( $post );
		$target = $index + 1;
		if ( array_key_exists( $target, $posts ) ) {
			$ascend = $posts[$target];
		}
		return $ascend;
	}

	/**
	 * Returns a descending post
	 *
	 * @param The Post from which to start
	 * @param The params by which to work out what is the next descending post
	 * @return Post The descending post
	 */
	public static function descend( $post, $params = null )
	{
		$posts = null;
		$descend = false;
		if ( !$params ) {
			$params = array( 'where' => "pubdate <= '{$post->pubdate->sql}' AND content_type = {$post->content_type} AND status = {$post->status}", 'limit' => 2, 'orderby' => 'pubdate DESC' );
			$posts = Posts::get( $params );
		}
		elseif ( $params instanceof Posts ) {
			$posts = array_reverse( $params );
		}
		else {
			if ( !array_key_exists( 'orderby', $params ) ) {
				$params['orderby'] = 'pubdate DESC';
			}
			$posts = Posts::get( $params );
		}
		// find $post and return the next one.
		$index = $posts->search( $post );
		$target = $index + 1;
		if ( array_key_exists( $target, $posts ) ) {
			$descend = $posts[$target];
		}
		return $descend;
	}

	/**
	 * Search this Posts object for the needle, returns its key if found
	 *
	 * @param Post $needle Post object to find within this Posts object
	 * @return mixed Returns the index of the needle, on failure, null is returned
	 */
	public function search( $needle )
	{
		return array_search( $needle, $this->getArrayCopy() );
	}

	/**
	 * Parses a search string for status, type, author, and tag keywords. Returns
	 * an associative array which can be passed to Posts::get(). If multiple
	 * authors, statuses, tags, or types are specified, we assume an implicit OR
	 * such that (e.g.) any author that matches would be returned.
	 *
	 * @param string $search_string The search string
	 * @return array An associative array which can be passed to Posts::get()
	 */
	public static function search_to_get( $search_string )
	{
		// if adding to this array, make sure you update the consequences of a search on this below in the switch.
		$keywords = array( 'author' => 1, 'status' => 1, 'type' => 1, 'tag' => 1, 'info' => 1 );
		$statuses = Post::list_post_statuses();
		$types = Post::list_active_post_types();
		$arguments = array(
			'user_id' => array(),
			'status' => array(),
			'content_type' => array(),
			'vocabulary' => array(),
			'info' => array(),
		);
		$criteria = '';

		// this says, find stuff that has the keyword at the start, and then some term straight after.
		// the terms should have no whitespace, or if it does, be ' delimited.
		// ie tag:foo or tag:'foo bar'
		$flag_regex = '/(?P<flag>\w+):(?P<value>[^\'"][^\s]*|(?P<quote>[\'"])[^\3]+(?<!\\\\)\3)/i';

		// now do some matching.
		preg_match_all( $flag_regex, $search_string, $matches, PREG_SET_ORDER );

		// now we remove those terms from the search string, otherwise the keyword search below has issues. It will pick up things like
		// from tag:'pair of' -> matches of'
		$criteria = trim( preg_replace( $flag_regex, '', $search_string ) );

		// Add special criteria based on the flag parameters.
		foreach ( $matches as $match ) {
			// trim out any quote marks that have been matched.
			$quote = isset( $match['quote'] ) ? $match['quote'] : ' ';
			$value = trim( stripslashes( $match['value'] ), $quote );
			
			$flag = $match['flag'];
			$arguments = Plugins::filter( 'posts_search_to_get', $arguments, $flag, $value, $match, $search_string );
			switch ( $flag )  {
				case 'author':
					if ( $u = User::get( $value ) ) {
						$arguments['user_id'][] = (int) $u->id;
					}
					break;
				case 'tag':
					$arguments['vocabulary'][Tags::vocabulary()->name . ':term_display'][] = $value;
					break;
				case 'status':
					if ( isset( $statuses[$value] ) ) {
						$arguments['status'][] = (int) $statuses[$value];
					}
					break;
				case 'type':
					if ( isset( $types[$value] ) ) {
						$arguments['content_type'][] = (int) $types[$value];
					}
					break;
				case 'info':
					if ( strpos( $value, ':' ) !== false ) {
						list( $infokey, $infovalue ) = explode( ':', $value, 2 );
						$arguments['info'][] = array( $infokey=>$infovalue );
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
					if ( is_array( $arg ) ) {
						$arguments[$key] = $arg;
					}
					else {
						$arguments[$key] = $arg[0];
					}
					break;
			}
		}

		if ( $criteria != '' ) {
			$arguments['criteria'] = $criteria;
		}

		return $arguments;
	}

	/**
	 * Check if the requested post is of the type specified, to see if a rewrite rule matches.
	 *
	 * @return Boolean Whether the requested post matches the content type of the rule.
	 */
	public static function rewrite_match_type( $rule, $slug, $parameters )
	{
		$args = $rule->named_arg_values;
		$args['count'] = true;
		$postcount = Posts::get( $args );
		return $postcount > 0;
	}


	/**
	 * Return the type of the content represented by this object
	 *
	 * @return string The name of the content representedt by this object
	 */
	function content_type ()
	{
		if ( isset( $this->preset ) ) {
			return 'posts.' . $this->preset;
		}
		return 'posts';
	}
	
	/**
	 * Accepts a set of term query qualifiers and converts it into a multi-dimensional array
	 * of vocabulary (ie: tags), matching method (any, all, not), matching field (id, term, term_display), and list of terms
	 * 
	 * @return array An array of parsed term-matching conditions
	 */
	private static function vocabulary_params( $params )
	{
		
		$return = array();
		
		foreach ( $params as $key => $value ) {
			// split vocab off the beginning of the key
			if ( strpos( $key, ':' ) !== false ) {
				list( $newkey, $subkey ) = explode( ':', $key, 2 );
				$params[$newkey][$subkey] = $value;
				unset( $params[$key] );
			}
			
		}
		
		
		foreach ( $params as $vocab => $values ) {
			
			foreach ( $values as $key => $value ) {
				
				$value = Utils::single_array( $value );
				
				// if there's a colon we've got a mode and a field
				if ( strpos( $key, ':' ) !== false ) {
					list( $mode, $by_field ) = explode( ':', $key, 2 );
					foreach ( $value as $v ) {
						$return[$mode][$vocab][$by_field][] = $v;
					}
				}
				else {
					
					// if there's no colon we've got a single field name
					foreach ( $value as $v ) {
						
						if ( $v instanceof Term ) {
							// $vocab is not a vocab, but the mode - always match by its ID for the best performance
							$return[$vocab][$v->vocabulary->name]['id'][] = $v->id;
						}
						else {
							$return['any'][$vocab][$key][] = $v;
						}
						
					}
					
					
				}
				
			}
			
		}
		
		return $return;
		
	}
	
}

?>
