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
 *
 * @property-read boolean $onepost Whether or not this object contains only one post
 * @property-read Post $first The first Post in this object
 * @property-read array $preset The presets for this object
 *
 */
class Posts extends ArrayObject implements IsContent
{
	public $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
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
			case 'first':
				return reset($this);
			case 'preset':
				return isset($this->get_param_cache['preset']) ? $this->get_param_cache['preset'] : array();
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
	 * - criteria => a literal search string to match post content or title
	 * - title => an exact case-insensitive match to a post title
	 * - title_search => a search string that acts only on the post title
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
	 * - on_query_built => a closure that accepts a Query as a parameter, allowing a plugin to alter the Query for this request directly
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
	 * - fetch_fn => the function used to fetch data, one of 'get_results', 'get_row', 'get_value', 'get_query'
	 *
	 * Further description of parameters, including usage examples, can be found at
	 * http://wiki.habariproject.org/en/Dev:Retrieving_Posts
	 *
	 * @return Posts|Post|string An array of Post objects, or a single post object, depending on request
	 */
	public static function get( $paramarray = array() )
	{
		static $presets;

		// If $paramarray is a string, use it as a Preset
		if(is_string($paramarray)) {
			$paramarray = array('preset' => $paramarray);
		}

		// If $paramarray is a querystring, convert it to an array
		$paramarray = Utils::get_params( $paramarray );
		if($paramarray instanceof ArrayIterator) {
			$paramarray = $paramarray->getArrayCopy();
		}

		// If a preset is defined, get the named array and merge it with the provided parameters,
		// allowing the additional $paramarray settings to override the preset
		if(isset($paramarray['preset'])) {
			if(!isset($presets)) {
				$presets = Plugins::filter('posts_get_all_presets', $presets, $paramarray['preset']);
			}
			$paramarray = Posts::merge_presets($paramarray, $presets);
		}

		// let plugins alter the param array before we use it. could be useful for modifying search results, etc.
		$paramarray = Plugins::filter( 'posts_get_paramarray', $paramarray );

		$join_params = array();
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value', 'get_query' );
		$select_ary = array();

		// Default fields to select, everything by default
		$default_fields = Plugins::filter('post_default_fields', Post::default_fields(), $paramarray);
		if(isset($paramarray['default_fields'])) {
			$param_defaults = Utils::single_array($paramarray['default_fields']);
			$default_fields = array_merge($default_fields, $param_defaults);
		}
		foreach ( $default_fields as $field => $value ) {
			if(preg_match('/(?:(?P<table>[\w\{\}]+)\.)?(?P<field>\w+)(?:(?:\s+as\s+)(?P<alias>\w+))?/i', $field, $fielddata)) {
				if(empty($fielddata['table'])) {
					$fielddata['table'] = '{posts}';
				}
				if(empty($fielddata['alias'])) {
					$fielddata['alias'] = $fielddata['field'];
				}
			}
			$select_ary[$fielddata['alias']] = "{$fielddata['table']}.{$fielddata['field']} AS {$fielddata['alias']}";
			$select_distinct[$fielddata['alias']] = "{$fielddata['table']}.{$fielddata['field']}";
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

		$query = Query::create('{posts}');
		$query->select($select_ary);

		// If the request has a textual WHERE clause, add it to the query then continue the processing of the $wheresets
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$query->where()->add($paramarray['where']);
		}
		foreach ( $wheresets as $paramset ) {
			$where = new QueryWhere();

			$paramset = array_merge( (array) $paramarray, (array) $paramset );

			if ( isset( $paramset['id'] ) ) {
				$where->in('{posts}.id', $paramset['id'], 'posts_id', 'intval');
			}
			if ( isset( $paramset['not:id'] ) ) {
				$where->in('{posts}.id', $paramset['not:id'], 'posts_not_id', 'intval', false);
			}

			if ( isset( $paramset['status'] ) && ( $paramset['status'] != 'any' ) && ( 0 !== $paramset['status'] ) ) {
				$where->in('{posts}.status', $paramset['status'], 'posts_status', function($a) {return Post::status( $a );} );
			}

			if ( isset( $paramset['not:status'] ) && ( $paramset['not:status'] != 'any' ) && ( 0 !== $paramset['not:status'] ) ) {
				$where->in('{posts}.status', $paramset['not:status'], 'posts_not_status', function($a) {return Post::status( $a );}, null, false );
			}

			if ( isset( $paramset['content_type'] ) && ( $paramset['content_type'] != 'any' ) && ( 0 !== $paramset['content_type'] ) ) {
				$where->in('{posts}.content_type', $paramset['content_type'], 'posts_content_type', function($a) {return Post::type( $a );} );
			}
			if ( isset( $paramset['not:content_type'] ) ) {
				$where->in('{posts}.content_type', $paramset['not:content_type'], 'posts_not_content_type', function($a) {return Post::type( $a );}, false );
			}

			if ( isset( $paramset['slug'] ) ) {
				$where->in('{posts}.slug', $paramset['slug'], 'posts_slug');
			}
			if ( isset( $paramset['not:slug'] ) ) {
				$where->in('{posts}.slug', $paramset['not:slug'], 'posts_not_slug', null, false);
			}

			if ( isset( $paramset['user_id'] ) && 0 !== $paramset['user_id'] ) {
				$where->in('{posts}.user_id', $paramset['user_id'], 'posts_user_id', 'intval');
			}
			if ( isset( $paramset['not:user_id'] ) && 0 !== $paramset['not:user_id'] ) {
				$where->in('{posts}.user_id', $paramset['not:user_id'], 'posts_not_user_id', 'intval', false);
			}

			if ( isset( $paramset['vocabulary'] ) ) {

				if ( is_string( $paramset['vocabulary'] ) ) {
					$paramset['vocabulary'] = Utils::get_params( $paramset['vocabulary'] );
				}

				// parse out the different formats we accept arguments in into a single mutli-dimensional array of goodness
				$paramset['vocabulary'] = self::vocabulary_params( $paramset['vocabulary'] );
				$object_id = Vocabulary::object_type_id( 'post' );

				if ( isset( $paramset['vocabulary']['all'] ) ) {
					$all = $paramset['vocabulary']['all'];

					foreach ( $all as $vocab => $value ) {

						foreach ( $value as $field => $terms ) {

							// we only support these fields to search by
							if ( !in_array( $field, array( 'id', 'term', 'term_display' ) ) ) {
								continue;
							}

							$join_group = Query::new_param_name('join');
							$query->join( 'JOIN {object_terms} ' . $join_group . '_ot ON {posts}.id = ' . $join_group . '_ot.object_id', array(), 'term2post_posts_' . $join_group );
							$query->join( 'JOIN {terms} ' . $join_group . '_t ON ' . $join_group . '_ot.term_id = ' . $join_group . '_t.id', array(), 'terms_term2post_' . $join_group );
							$query->join( 'JOIN {vocabularies} ' . $join_group . '_v ON ' . $join_group . '_t.vocabulary_id = ' . $join_group . '_v.id', array(), 'terms_vocabulary_' . $join_group );

							$where->in( $join_group . '_v.name', $vocab );
							$where->in( $join_group . "_t.{$field}", $terms );
							$where->in( $join_group . '_ot.object_type_id', $object_id );
						}

						// this causes no posts to match if combined with 'any' below and should be re-thought... somehow
						$groupby = implode( ',', $select_distinct );
						$having = 'count(*) = ' . count( $terms );

					}
				}

				if ( isset( $paramset['vocabulary']['any'] ) ) {
					$any = $paramset['vocabulary']['any'];

					$orwhere = new QueryWhere( 'OR' );

					foreach ( $any as $vocab => $value ) {

						foreach ( $value as $field => $terms ) {

							$andwhere = new QueryWhere();

							// we only support these fields to search by
							if ( !in_array( $field, array( 'id', 'term', 'term_display' ) ) ) {
								continue;
							}

							$join_group = Query::new_param_name('join');
							$query->join( 'JOIN {object_terms} ' . $join_group . '_ot ON {posts}.id = ' . $join_group . '_ot.object_id', array(), 'term2post_posts_' . $join_group );
							$query->join( 'JOIN {terms} ' . $join_group . '_t ON ' . $join_group . '_ot.term_id = ' . $join_group . '_t.id', array(), 'terms_term2post_' . $join_group );
							$query->join( 'JOIN {vocabularies} ' . $join_group . '_v ON ' . $join_group . '_t.vocabulary_id = ' . $join_group . '_v.id', array(), 'terms_vocabulary_' . $join_group );

							$andwhere->in( $join_group . '_v.name', $vocab );
							$andwhere->in( $join_group . "_t.{$field}", $terms );
							$andwhere->in( $join_group . '_ot.object_type_id', $object_id );
						}
						$orwhere->add( $andwhere );

					}
					$where->add( $orwhere );
				}

				if ( isset( $paramset['vocabulary']['not'] ) ) {
					$not = $paramset['vocabulary']['not'];

					foreach ( $not as $vocab => $value ) {

						foreach ( $value as $field => $terms ) {

							// we only support these fields to search by
							if ( !in_array( $field, array( 'id', 'term', 'term_display' ) ) ) {
								continue;
							}

							$subquery_alias = Query::new_param_name('subquery');
							$subquery = Query::create( '{object_terms}' )->select('object_id');
							$subquery->join( 'JOIN {terms} ON {terms}.id = {object_terms}.term_id' );
							$subquery->join( 'JOIN {vocabularies} ON {terms}.vocabulary_id = {vocabularies}.id' );

							$subquery->where()->in( "{terms}.{$field}", $terms );
							$subquery->where()->in( '{object_terms}.object_type_id', $object_id );
							$subquery->where()->in( '{vocabularies}.name', $vocab );

							$query->join( 'LEFT JOIN (' . $subquery->get() . ') ' . $subquery_alias . ' ON ' . $subquery_alias . '.object_id = {posts}.id', $subquery->params(), $subquery_alias );

							$where->add( 'COALESCE(' . $subquery_alias . '.object_id, 0) = 0' );
						}

					}
				}
			}

			if ( isset( $paramset['criteria'] ) ) {
				// this regex matches any unicode letters (\p{L}) or numbers (\p{N}) inside a set of quotes (but strips the quotes) OR not in a set of quotes
				preg_match_all( '/(?<=")([\p{L}\p{N}]+[^"]*)(?=")|([\p{L}\p{N}]+)/u', $paramset['criteria'], $matches );
				foreach ( $matches[0] as $word ) {
					$crit_placeholder = $query->new_param_name('criteria');
					$where->add("( LOWER( {posts}.title ) LIKE :{$crit_placeholder} OR LOWER( {posts}.content ) LIKE :{$crit_placeholder})", array($crit_placeholder => '%' . MultiByte::strtolower( $word ) . '%'));
				}
			}

			if ( isset( $paramset['title'] ) ) {
				$where->add("LOWER( {posts}.title ) LIKE :title_match", array('title_match' => MultiByte::strtolower( $paramset['title'] )));
			}

			if ( isset( $paramset['title_search'] ) ) {
				// this regex matches any unicode letters (\p{L}) or numbers (\p{N}) inside a set of quotes (but strips the quotes) OR not in a set of quotes
				preg_match_all( '/(?<=")([\p{L}\p{N}]+[^"]*)(?=")|([\p{L}\p{N}]+)/u', $paramset['title_search'], $matches );
				foreach ( $matches[0] as $word ) {
					$crit_placeholder = $query->new_param_name('title_search');
					$where->add("LOWER( {posts}.title ) LIKE :{$crit_placeholder}", array($crit_placeholder => '%' . MultiByte::strtolower( $word ) . '%'));
				}
			}

			// Handle field queries on posts and joined tables
			foreach($select_ary as $field => $aliasing) {
				if(in_array($field, array('id', 'title', 'slug', 'status', 'content_type', 'user_id')) ) {
					// skip fields that we're handling a different way
					continue;
				}
				if(isset($paramset[$field])) {
					if(is_callable($paramset[$field])) {
						$paramset[$field]($where, $paramset);
					}
					else {
						$where->in($field, $paramset[$field], 'posts_field_' . $field);
					}
				}
			}

			//Done
			if ( isset( $paramset['all:info'] ) || isset( $paramset['info'] ) ) {

				// merge the two possibile calls together
				$infos = array_merge( isset( $paramset['all:info'] ) ? $paramset['all:info'] : array(), isset( $paramset['info'] ) ? $paramset['info'] : array() );

				if ( Utils::is_traversable( $infos ) ) {
					$pi_count = 0;
					foreach ( $infos as $info_key => $info_value ) {
						$pi_count++;

						$infokey_field = Query::new_param_name('info_key' );
						$infovalue_field = Query::new_param_name( 'info_value');
						$query->join( "LEFT JOIN {postinfo} ipi{$pi_count} ON {posts}.id = ipi{$pi_count}.post_id AND ipi{$pi_count}.name = :{$infokey_field} AND ipi{$pi_count}.value = :{$infovalue_field}", array( $infokey_field => $info_key, $infovalue_field => $info_value ), 'all_info_' . $info_key );
						$where->add( "ipi{$pi_count}.name <> ''" );
						$query->select( array( "info_{$info_key}_value" => "ipi{$pi_count}.value AS info_{$info_key}_value" ) );
						$select_distinct["info_{$info_key}_value"] = "info_{$info_key}_value";
					}
				}

			}

			//Done
			if ( isset( $paramset['any:info'] ) ) {
				if ( Utils::is_traversable( $paramset['any:info'] ) ) {
					$pi_count = 0;
					$orwhere = new QueryWhere( 'OR' );
					foreach ( $paramset['any:info'] as $info_key => $info_value ) {
						$pi_count++;

						if ( is_array( $info_value ) ) {
							$infokey_field = Query::new_param_name( 'info_key' );
							$inwhere = new QueryWhere( '' );
							$inwhere->in( "aipi{$pi_count}.value", $info_value );
							$query->join( "LEFT JOIN {postinfo} aipi{$pi_count} ON {posts}.id = aipi{$pi_count}.post_id AND aipi{$pi_count}.name = :{$infokey_field} AND " . $inwhere->get(), array_merge( array( $info_key ), $inwhere->params() ), 'any_info_' . $info_key );
						}
						else {
							$infokey_field = Query::new_param_name( 'info_key' );
							$infovalue_field = Query::new_param_name( 'info_value' );
							$query->join( "LEFT JOIN {postinfo} aipi{$pi_count} ON {posts}.id = aipi{$pi_count}.post_id AND aipi{$pi_count}.name = :{$infokey_field} AND aipi{$pi_count}.value = :{$infovalue_field}", array( $infokey_field => $info_key, $infovalue_field => $info_value ), 'any_info_' . $info_key );
						}

						$orwhere->add( "aipi{$pi_count}.name <> ''" );

						$query->select( array( "info_{$info_key}_value" => "aipi{$pi_count}.value AS info_{$info_key}_value" ) );
						$select_distinct["info_{$info_key}_value"] = "info_{$info_key}_value";
					}
					$where->add( '(' . $orwhere->get() . ')' );
				}
			}

			// Done
			if ( isset( $paramset['has:info'] ) ) {
				$has_info = Utils::single_array( $paramset['has:info'] );
				$pi_count = 0;
				$orwhere = new QueryWhere( 'OR' );
				foreach( $has_info as $info_name ) {
					$infoname_field = Query::new_param_name( 'info_name' );
					$pi_count++;
					$query->join("LEFT JOIN {postinfo} hipi{$pi_count} ON {posts}.id = hipi{$pi_count}.post_id AND hipi{$pi_count}.name = :{$infoname_field}", array( $infoname_field => $info_name ), 'has_info_' . $info_name );
					$orwhere->add( "hipi{$pi_count}.name <> ''" );

					$query->select( array( "info_{$info_name}_value" => "hipi{$pi_count}.value AS info_{$info_name}_value" ) );
					$select_distinct["info_{$info_name}_value"] = "info_{$info_name}_value";
				}
				$where->add( '(' . $orwhere->get() . ')' );
			}

			//Done
			if ( isset( $paramset['not:all:info'] ) || isset( $paramset['not:info'] ) ) {

				// merge the two possible calls together
				$infos = array_merge( isset( $paramset['not:all:info'] ) ? $paramset['not:all:info'] : array(), isset( $paramset['not:info'] ) ? $paramset['not:info'] : array() );

				if ( Utils::is_traversable( $infos ) ) {
					$orwhere = new QueryWhere( 'OR' );
					foreach ( $infos as $info_key => $info_value ) {
						$andwhere = new QueryWhere();
						$andwhere->in( '{postinfo}.name', $info_key );
						$andwhere->in( '{postinfo}.value', $info_value );
						$orwhere->add( $andwhere );

					}
					// see that hard-coded number in having()? sqlite wets itself if we use a bound parameter... don't change that
					$subquery = Query::create( '{postinfo}' )->select( '{postinfo}.post_id' )->groupby( 'post_id' )->having( 'COUNT(*) = ' . count( $infos ) );
					$subquery->where()->add( $orwhere );

					$where->in( '{posts}.id', $subquery, 'posts_not_all_info_query', null, false );
				}

			}

			//Tested. Test fails with original code
			if ( isset( $paramset['not:any:info'] ) ) {
				if ( Utils::is_traversable( $paramset['not:any:info'] ) ) {
					$subquery = Query::create('{postinfo}')->select('post_id');

					foreach ( $paramset['not:any:info'] as $info_key => $info_value ) {
						$infokey_field = $query->new_param_name('info_key');
						$infovalue_field = $query->new_param_name('info_value');
//							$subquery->where()->add(" ({postinfo}.name = :{$infokey_field} AND {postinfo}.value = :{$infovalue_field} ) ", array($infokey_field => $info_key, $infovalue_field => $info_value));
						$subquery->where( 'OR' )->add(" ({postinfo}.name = :{$infokey_field} AND {postinfo}.value = :{$infovalue_field} ) ", array($infokey_field => $info_key, $infovalue_field => $info_value));
					}

					$where->in('{posts}.id', $subquery, 'posts_not_any_info', null, false);
				}
			}

			/**
			 * Build the statement needed to filter by pubdate:
			 * If we've got the day, then get the date;
			 * If we've got the month, but no date, get the month;
			 * If we've only got the year, get the whole year.
			 */
			if ( isset( $paramset['day'] ) && isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
				$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], $paramset['day'] );
				$start_date = HabariDateTime::date_create( $start_date );
				$where->add('pubdate BETWEEN :start_date AND :end_date', array('start_date' => $start_date->sql, 'end_date' => $start_date->modify( '+1 day -1 second' )->sql));
			}
			elseif ( isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
				$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], 1 );
				$start_date = HabariDateTime::date_create( $start_date );
				$where->add('pubdate BETWEEN :start_date AND :end_date', array('start_date' => $start_date->sql, 'end_date' => $start_date->modify( '+1 month -1 second' )->sql));
			}
			elseif ( isset( $paramset['year'] ) ) {
				$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], 1, 1 );
				$start_date = HabariDateTime::date_create( $start_date );
				$where->add('pubdate BETWEEN :start_date AND :end_date', array('start_date' => $start_date->sql, 'end_date' => $start_date->modify( '+1 year -1 second' )->sql));
			}

			if ( isset( $paramset['after'] ) ) {
				$where->add('pubdate > :after_date', array('after_date' => HabariDateTime::date_create( $paramset['after'] )->sql));
			}

			if ( isset( $paramset['before'] ) ) {
				$where->add('pubdate < :before_date', array('before_date' => HabariDateTime::date_create( $paramset['before'] )->sql));
			}

			// Concatenate the WHERE clauses
			$query->where()->add($where);
		}

		if(isset($paramset['post_join'])) {
			$post_joins = Utils::single_array($paramset['post_join']);
			foreach($post_joins as $post_join) {
				if(preg_match('#^(\S+)(?:\s+as)?\s+(\S+)$#i', $post_join, $matches)) {
					$query->join("LEFT JOIN {$matches[1]} {$matches[2]} ON {$matches[2]}.post_id = {posts}.id ");
				}
				else {
					$query->join("LEFT JOIN {$post_join} ON {$post_join}.post_id = {posts}.id ");
				}
			}
		}



		// Only show posts to which the current user has permission
		if ( isset( $paramset['ignore_permissions'] ) ) {
			$master_perm_where = new QueryWhere();
			// Set up the merge params
			$merge_params = array( $join_params, $params );
			$params = call_user_func_array( 'array_merge', $merge_params );
		}
		else {
			$master_perm_where = new QueryWhere();
			// This set of wheres will be used to generate a list of post_ids that this user can read
			$perm_where = new QueryWhere('OR');
			$perm_where_denied = new QueryWhere('AND');

			// Get the tokens that this user is granted or denied access to read
			$read_tokens = isset( $paramset['read_tokens'] ) ? $paramset['read_tokens'] : ACL::user_tokens( User::identify(), 'read', true );
			$deny_tokens = isset( $paramset['deny_tokens'] ) ? $paramset['deny_tokens'] : ACL::user_tokens( User::identify(), 'deny', true );

			// If a user can read any post type, let him
			if ( User::identify()->can( 'post_any', 'read' ) ) {
				$perm_where->add( '(1=1)' );
			}
			else {
				// If a user can read his own posts, let him
				if ( User::identify()->can( 'own_posts', 'read' ) ) {
					$perm_where->add('{posts}.user_id = :current_user_id', array('current_user_id' => User::identify()->id));
				}

				// If a user can read specific post types, let him
				$permitted_post_types = array();
				foreach ( Post::list_active_post_types() as $name => $posttype ) {
					if ( User::identify()->can( 'post_' . Utils::slugify( $name ), 'read' ) ) {
						$permitted_post_types[] = $posttype;
					}
				}
				if ( count( $permitted_post_types ) > 0 ) {
					$perm_where->in('{posts}.content_type', $permitted_post_types, 'posts_permitted_types', 'intval');
				}

				// If a user can read posts with specific tokens, let him
				if ( count( $read_tokens ) > 0 ) {
					$query->join('LEFT JOIN {post_tokens} pt_allowed ON {posts}.id= pt_allowed.post_id AND pt_allowed.token_id IN ('.implode( ',', $read_tokens ).')', array(), 'post_tokens__allowed');
					$perm_where->add('pt_allowed.post_id IS NOT NULL', array(), 'perms_join_not_null');
				}

				// If a user has access to read other users' unpublished posts, let him
				if ( User::identify()->can( 'post_unpublished', 'read' ) ) {
					$perm_where->add('({posts}.status <> :status_published AND {posts}.user_id <> :current_user_id)', array('current_user_id' => User::identify()->id, 'status_published' => Post::status('published')));
				}

			}

			// If a user is denied access to all posts, do so
			if ( User::identify()->cannot( 'post_any' ) ) {
				$perm_where_denied->add('(1=0)');
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
					$perm_where_denied->in('{posts}.content_type', $denied_post_types, 'posts_denied_types', 'intval', false);
				}

				// If a user is denied read access to posts with specific tokens, deny it
				if ( count( $deny_tokens ) > 0 ) {
					$query->join('LEFT JOIN {post_tokens} pt_denied ON {posts}.id= pt_denied.post_id AND pt_denied.token_id IN ('.implode( ',', $deny_tokens ).')', array(), 'post_tokens__denied');
					$perm_where_denied->add('pt_denied.post_id IS NULL', array(), 'perms_join_null');
				}

				// If a user is denied access to read other users' unpublished posts, deny it
				if ( User::identify()->cannot( 'post_unpublished' ) ) {
					$perm_where_denied->add('({posts}.status = :status_published OR {posts}.user_id = :current_user_id)', array('current_user_id' => User::identify()->id, 'status_published' => Post::status('published')));
				}

			}

			Plugins::act( 'post_get_perm_where', $perm_where, $paramarray );
			Plugins::act( 'post_get_perm_where_denied', $perm_where_denied, $paramarray );

			// If there are granted permissions to check, add them to the where clause
			if($perm_where->count() == 0 && !$query->joined('post_tokens__allowed')) {
				$master_perm_where->add('(1=0)', array(), 'perms_granted');
			}
			else {
				$master_perm_where->add($perm_where, array(), 'perms_granted');
			}


			// If there are denied permissions to check, add them to the where clause
			if($perm_where_denied->count() > 0 || $query->joined('post_tokens__denied')) {
				$master_perm_where->add($perm_where_denied, array(), 'perms_denied');
			}

		}
		$query->where()->add($master_perm_where, array(), 'master_perm_where');

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


		// Add arbitrary fields to the select clause for sorting and output
		if ( isset( $add_select ) ) {
			$query->select($add_select);
		}


		/**
		 * If a count is requested:
		 * Replace the current fields to select with a COUNT();
		 * Change the fetch function to 'get_value';
		 * Remove the ORDER BY since it's useless.
		 * Remove the GROUP BY (tag search added it)
		 */
		if ( isset( $count ) ) {
			$query->set_select( "COUNT({$count})" );
			$fetch_fn = isset($paramarray['fetch_fn']) ? $fetch_fn : 'get_value';
			$orderby = null;
			$groupby = null;
			$having = null;
		}

		// If the month counts are requested, replaced the select clause
		if ( isset( $paramset['month_cts'] ) ) {
			if ( isset( $paramset['vocabulary'] ) ) {
				$query->set_select('MONTH(FROM_UNIXTIME(pubdate)) AS month, YEAR(FROM_UNIXTIME(pubdate)) AS year, COUNT(DISTINCT {posts}.id) AS ct');
			}
			else {
				$query->set_select('MONTH(FROM_UNIXTIME(pubdate)) AS month, YEAR(FROM_UNIXTIME(pubdate)) AS year, COUNT(*) AS ct');
			}
			$groupby = 'year, month';
			if ( !isset( $paramarray['orderby'] ) ) {
				$orderby = 'year, month';
			}
		}


		// Remove the LIMIT if 'nolimit'
		// Doing this first should allow OFFSET to work
		if ( isset( $nolimit ) ) {
			$limit = null;
		}

		// Define the LIMIT, OFFSET, ORDER BY, GROUP BY if they exist
		if(isset($limit)) {
			$query->limit($limit);
		}
		if(isset($offset)) {
			$query->offset($offset);
		}
		if(isset($orderby)) {
			$query->orderby($orderby);
		}
		if(isset($groupby)) {
			$query->groupby($groupby);
		}
		if(isset($having)) {
			$query->having($having);
		}

		if(isset($paramarray['on_query_built'])) {
			foreach(Utils::single_array($paramarray['on_query_built']) as $built) {
				$built($query);
			}
		}

		Plugins::act('posts_get_query', $query, $paramarray);
		/* All SQL parts are constructed, on to real business! */

		/**
		 * DEBUG: Uncomment the following line to display everything that happens in this function
		 */
		//print_R('<pre>'.$query.'</pre>');
		//Utils::debug( $paramarray, $fetch_fn, $query, $params );
		//Session::notice($query);

		if ( 'get_query' == $fetch_fn ) {
			return array($query->get(), $query->params());
		}

		/**
		 * Execute the SQL statement using the PDO extension
		 */
		DB::set_fetch_mode( PDO::FETCH_CLASS );
		$fetch_class = 'Post';
		if(isset($paramarray['fetch_class'])) {
			$fetch_class = $paramarray['fetch_class'];
		}
		DB::set_fetch_class( $fetch_class );
		$results = DB::$fetch_fn( $query->get(), $query->params(), $fetch_class );

		//Utils::debug($results, $query->get(), $query->params());
		//Utils::debug( $paramarray, $fetch_fn, $query->get(), $query->params(), $results );
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
	 * Accept a parameter array for Posts::get() with presets, and return an array with all defined parameters from those presets
	 * @param array $paramarray An array of parameters to Posts::get that may contain presets
	 * @param array $presets a list of presets, keyed by preset name, each an array of parameters that define the preset
	 * @return array The processed array, including all original presets and all newly added recursive presets and parameters
	 */
	public static function merge_presets($paramarray, $presets) {
		if(isset($paramarray['preset'])) {
			// Get the preset from the paramarray.
			$requested_presets = Utils::single_array($paramarray['preset']);
			unset($paramarray['preset']);

			// Get the previously processed presets and remove them from the presets requested
			$processed_presets = isset($paramarray['_presets']) ? array_keys($paramarray['_presets']) : array();
			$requested_presets = array_diff($requested_presets, $processed_presets);

			// Process fallbacks (in the simplest case, this will just iterate once - for the requested fallback-less preset)
			foreach($requested_presets as $requested_preset) {
				if(isset($presets[$requested_preset])) {
					// We found one that exists, let plugins filter it and then merge it with our paramarray
					$preset = Plugins::filter('posts_get_update_preset', $presets[$requested_preset], $requested_preset, $paramarray);
					if(is_array($preset) || $preset instanceof \ArrayObject || $preset instanceof \ArrayIterator) {
						$preset = new SuperGlobal($preset);
						// This merge order ensures that the outside object has precedence
						$paramarray = $preset->merge($paramarray)->getArrayCopy();
						// Save the preset as "processed"
						$paramarray['_presets'][$requested_preset] = true;
						// We might have retrieved new presets to use. Do it again!
						$paramarray = Posts::merge_presets($paramarray, $presets);
					}
				}
				else {
					// Save the preset as "tried to process but didn't"
					$paramarray['_presets'][$requested_preset] = false;
				}
			}

			// Restore the original requested preset to the paramarray
			$paramarray['preset'] = $requested_presets;
		}

		return $paramarray;
	}

	/**
	 * Extract parameters from a Posts::get()-style param array, even from within where's
	 * @static
	 * @param array $paramarray An array of Posts::get()-style parameters
	 * @param string $param The parameters to extract
	 * @return array|bool The parameters in the $paramarray that match $param or false
	 */
	public static function extract_param($paramarray, $param) {
		$result = array();
		if(isset($paramarray[$param])) {
			$result = array_merge($result, Utils::single_array($paramarray[$param]));
		}
		if(isset($paramarray['where'])) {
			if(is_array($paramarray['where'])) {
				foreach($paramarray['where'] as $where) {
					if(isset($where[$param])) {
						$result = array_merge($result, Utils::single_array($where[$param]));
					}
				}
			}
		}
		return count($result) ? $result : false;
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
	 * return the query that generated this set of posts
	 * @return string The SQL and paramters used to generate this set of posts
	 */
	public function get_query()
	{
		$params = array_merge( ( array ) $this->get_param_cache, array( 'fetch_fn' => 'get_query') );
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
		if($posts) {
			// find $post and return the next one.
			$index = $posts->search( $post );
			$target = $index + 1;
			if ( array_key_exists( $target, $posts ) ) {
				$ascend = $posts[$target];
				return $ascend;
			}
		}
		return false;
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
		if($posts) {
			// find $post and return the next one.
			$index = $posts->search( $post );
			$target = $index + 1;
			if ( array_key_exists( $target, $posts ) ) {
				$descend = $posts[$target];
				return $descend;
			}
		}
		return false;
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
						$arguments['info'][$infokey] = $infovalue;
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
	 * @return array The names of the possible content represented by this object
	 */
	function content_type ()
	{
		$content_type = array_map(
			function($a){
				return 'posts.' . $a;
			},
			$this->preset
		);
		$content_type[] = 'posts';
		return $content_type;
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

	/**
	 * Register plugin hooks
	 * @static
	 */
	public static function __static()
	{
		Pluggable::load_hooks('Posts');
	}

	/**
	 * Provide some default presets
	 * @static
	 * @param array $presets List of presets that other classes might provide
	 * @return array List of presets this class provides
	 */
	public static function filter_posts_get_all_presets($presets)
	{
		$presets['page_list'] = array( 'content_type' => 'page', 'status' => 'published', 'nolimit' => true );
		$presets['asides'] = array( 'vocabulary' => array( 'tags:term' => 'aside' ), 'limit' => 5 );
		$presets['home'] = array( 'content_type' => Post::type( 'entry' ), 'status' => Post::status( 'published' ), 'limit' => Options::get('pagination', 5) );

		return $presets;
	}

	/**
	 * function delete
	 * Delete all Posts in a Posts object
	 */
	public function delete()
	{
		foreach( $this as $post ) {
			$post->delete();
		}
	}

	/**
	 * Serialize these posts as JSON
	 * @return string Posts as JSON
	 */
	public function to_json()
	{
		$posts = array_map(function($e){return $e->to_json();}, $this->getArrayCopy());
		return '[' . implode(',', $posts) . ']';
	}
}

?>