<?php
/**
 * @package Habari
 *
 */

/**
* Habari Tags Class
*
*/
class Tags extends ArrayObject
{
	protected $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
	 * Returns a tag or tags based on supplied parameters.
	 * @todo This class should cache query results!
	 *
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return array An array of Tag objects, or a single Tag object, depending on request
	 **/
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select = '';
		// what to select -- by default, everything
		foreach ( Tag::default_fields() as $field => $value ) {
			$select .= ( '' == $select )
				? "{tags}.$field"
				: ", {tags}.$field";
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
			foreach( $wheresets as $paramset ) {
				// safety mechanism to prevent empty queries
				$where = array();
				$paramset = array_merge((array) $paramarray, (array) $paramset);

				$default_fields = Tag::default_fields();
				foreach ( Tag::default_fields() as $field => $scrap ) {
					if ( !isset( $paramset[$field] ) ) {
						continue;
					}
					switch ( $field ) {
						case 'id':
							if ( !is_numeric( $paramset[$field] ) ) {
								continue;
							}
						default:
							$where[] = "{$field}= ?";
							$params[] = $paramset[$field];
					}
				}

				if(count($where) > 0) {
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
			. ' FROM {tags} '
			. $join;

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query .= ( ($orderby == '') ? '' : ' ORDER BY ' . $orderby ) . $limit;
		//Utils::debug($paramarray, $fetch_fn, $query, $params);

		DB::set_fetch_mode(PDO::FETCH_CLASS);
		DB::set_fetch_class('Tag');
		$results = DB::$fetch_fn( $query, $params, 'Tag' );

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
	 * Return a tag based on an id, tag text or slug
	 *
	 * @return QueryRecord A tag QueryRecord
	 **/
	public static function get_one($tag)
	{
		$params = array();
		if( is_numeric( $tag ) ) {
			$params['id'] = $tag;
		}
		else {
			$params['tag_slug'] = Utils::slugify( $tag );
		}
		$params['limit'] = 1;
		$params['fetch_fn'] = 'get_row';
		return Tags::get( $params );
	}

	/**
	 * Deletes a tag
	 *
	 * @param Tag tag The tag to be deleted
	 **/
	public static function delete($tag)
	{
		$tag->delete();
	}

	/**
	 * TODO: be more careful
	 * INSERT INTO {tag2post} / SELECT $master_tag->ID,post_ID FROM {tag2post} WHERE tag_id = $tag->id" and then "DELETE FROM {tag2post} WHERE tag_id = $tag->id"
	 * Renames tags.
	 * If the master tag exists, the tags will be merged with it.
	 * If not, it will be created first.
	 *
	 * @param Array tags The tag text, slugs or ids to be renamed
	 * @param mixed master The Tag to which they should be renamed, or the slug, text or id of it
	 **/
	public static function rename($master, $tags)
	{
		$tags = Utils::single_array( $tags );
		$tag_names = array();
		$post_ids = array();

		// get array of existing tags first to make sure we don't conflict with a new master tag
		foreach ( $tags as $tag ) {
			
			$posts = array();
//			$post_ids = array();
			$tag = Tags::get_one( $tag );
			
			// get all the post ID's tagged with this tag
			$posts = DB::get_results( 'SELECT post_id FROM {tag2post} WHERE tag_id = ?', array( $tag->id ) );

			if ( count( $posts ) > 0 ) {

				// build a list of all the post_id's we need for the new tag
				foreach ( $posts as $post ) {
					$post_ids[] = $post->post_id;
				}
				$tag_names[] = $tag->tag;
			}

			Tags::delete( $tag );
		}
		
		// get the master tag
		$master_tag = Tags::get_one($master);
		
		if ( !isset($master_tag->slug) ) {
			// it didn't exist, so we assume it's tag text and create it
			$master_tag = Tag::create(array('tag_slug' => Utils::slugify($master), 'tag_text' => $master));
			
			$master_ids = array();
		}
		else {
			// get the posts the tag is already on so we don't duplicate them
			$master_posts = DB::get_results( 'SELECT post_id FROM {tag2post} WHERE tag_id = ?', array( $master_tag->id ) );
			
			$master_ids = array();
			
			foreach ( $master_posts as $master_post ) {
				$master_ids[] = $master_post->post_id;
			}
			
		}

		if ( count( $post_ids ) > 0 ) {
			
			// only try and add the master tag to posts it's not already on
			$post_ids = array_diff( $post_ids, $master_ids );
			
			// link the master tag to each distinct post we removed tags from
			foreach ( $post_ids as $post_id ) {

				DB::query( 'INSERT INTO {tag2post} ( tag_id, post_id ) VALUES ( ?, ? )', array( $master_tag->id, $post_id ) );

			}

		}
		EventLog::log(sprintf(
			_n('Tag %s has been renamed to %s.',
				 'Tags %s have been renamed to %s.',
				  count($tags)
			), implode($tag_names, ', '), $master ), 'info', 'tag', 'habari'
		);

	}

	/**
	 * Returns the number of times the most used tag is used.
	 *
	 * @return int The number of times the most used tag is used.
	 **/
	public static function max_count()
	{
		return DB::get_value( 'SELECT count( t2.post_id ) AS max FROM {tags} t, {tag2post} t2 WHERE t2.tag_id = t.id GROUP BY t.id ORDER BY max DESC LIMIT 1' );
	}

	/**
	 * Returns the count of times a tag is used.
	 *
	 * @param mixed The tag to count usage.
	 * @return int The number of times a tag is used.
	 **/
	public static function post_count($tag)
	{
		$params = array();
		$params['fetch_fn'] = 'get_row';
		if ( is_int( $tag ) ) {
			$params['id'] = $tag;
		}
		else if ( is_string( $tag ) ) {
			$params['tag_slug'] = Utils::slugify( $tag );
		}
		$tag =  Tags::get( $params );
		return $tag->count;
	}

	public static function get_by_text($tag)
	{
		return Tags::get( array( 'tag_text' => $tag, 'fetch_fn' => 'get_row', 'limit' => 1  ) );
	}

	public static function get_by_slug($tag)
	{
		return Tags::get( array( 'tag_slug' => $tag, 'fetch_fn' => 'get_row', 'limit' => 1  ) );
	}

	/**
	 * Returns a Tag object based on a supplied ID
	 *
	 * @param		tag_id	The ID of the tag to retrieve
	 * @return	A Tag object
	 */
	public static function get_by_id( $tag )
	{
		return Tags::get( array( 'id' => $tag, 'fetch_fn' => 'get_row', 'limit' => 1 ) );
	}
}
?>
