<?php

/**
 * @package Habari
 *
 * Class which describes a single Tag object
 */
class Tag extends QueryRecord 
{

	/**
	 * Return the defined database columns for a Tag.
	 * @return array Array of columns in the tags table
   */
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'tag_slug' => '',
			'tag_text' => ''
		);
	}

	/**
	 * Constructor for the Tag class.
	 * @param array $paramarray an associative array of initial Tag field values.
	 **/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields,
			$this->newfields
		);

		parent::__construct( $paramarray );
		$this->exclude_fields( 'id' );
	}

	/**
	 * Return a single requested tag.
	 *
	 * <code>
	 * $tag= Tag::get( array( 'tag_slug' => 'wooga' ) );
	 * </code>
	 *
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return Tag The first tag that matched the given criteria
	 **/
	static function get( $paramarray = array() )
	{
		// Defaults
		$defaults = array ('where' => array(),	'fetch_fn' => 'get_row');
		foreach ( $defaults['where'] as $index => $where ) {
			$defaults['where'][$index]= array_merge( Controller::get_handler()->handler_vars, $where, Utils::get_params( $paramarray ) );
		}
		// make sure we get at most one result
		$defaults['limit']= 1;

		return Tags::get( $defaults );
	}

	/**
	 * Create a tag and save it.
	 *
	 * @param array $paramarray An associative array of tag fields
	 * @return Tag The new Tag object
	 **/
	static function create( $paramarray )
	{
		$tag = new Tag( $paramarray );
		$tag->insert();
		return $tag;
	}

	/**
	 * Attaches (relates) a tag to a post
	 *
	 * @param		tag_id		The ID of the tag
	 * @param		post_id		The ID of the post
	 * @return	TRUE or FALSE depending if relation was created.
	 */
	public static function attach_to_post( $tag_id, $post_id ) 
	{
		$result = TRUE;
		Plugins::act( 'tag_attach_to_post_before', $tag_id, $post_id );
		if (0 == (int) DB::get_value( "SELECT COUNT(*) FROM {tag2post} WHERE tag_id = ? AND post_id = ?", array( $tag_id, $post_id ) ) ) {
			$sql = "INSERT INTO {tag2post} (tag_id, post_id) VALUES (?,?)";
			$result = DB::query( $sql, array( $tag_id, $post_id ) );
		}
		Plugins::act( 'tag_attach_to_post_after', $tag_id, $post_id );
		return $result;
	}
	
	public static function detatch_from_post( $tag_id, $post_id ) {
		
		Plugins::act( 'tag_detatch_from_post_before', $tag_id, $post_id );
		
		$result = DB::query( 'DELETE FROM {tag2post} WHERE tag_id = ? AND post_id = ?', array( $tag_id, $post_id ) );
		
		// should we delete the tag if it's the only one left?
		$count = DB::get_value( 'SELECT COUNT(tag_id) FROM {tag2post} WHERE tag_id = ?', array( $tag_id ) );
		
		if ( $count == 0 ) {
			$delete = true;
			$delete = Plugins::filter( 'tag_detach_from_post_delete_empty_tag', $delete, $tag_id );
			
			if ( $delete ) {
				DB::query( 'DELETE FROM {tags} WHERE id = ?', array( $tag_id ) );
			}
		}
		
		Plugins::act( 'tag_detatch_from_post_after', $tag_id, $post_id, $result );
		
		return $result;
		
	}

	/**
	 * Generate a new slug for the tag.
	 *
	 * @return string The slug
	 */
	private function setslug()
	{
		// determine the base value from:
		// - the new slug
		if ( isset( $this->newfields['tag_slug']) && $this->newfields['tag_slug'] != '' ) {
			$value = $this->newfields['tag_slug'];
		}
		// - the existing slug
		elseif ( $this->fields['tag_slug'] != '' ) {
			$value = $this->fields['tag_slug'];
		}
		// - the new tag's text
		elseif ( isset( $this->newfields['tag_text'] ) && $this->newfields['tag_text'] != '' ) {
			$value = $this->newfields['tag_text'];
		}
		// - the existing tag text 
		elseif ( $this->fields['tag_text'] != '' ) {
			$value = $this->fields['tag_text'];
		}

		// make sure our slug is unique
		$slug = Plugins::filter( 'tag_setslug', $value );
		$slug = Utils::slugify( $slug );
		return $this->newfields['tag_slug']= $slug;
	}

	/**
	 * function insert
	 * Saves a new tag into the tags table
	 */
	public function insert()
	{
		$this->setslug();

		$allow = true;
		$allow = Plugins::filter( 'tag_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'tag_insert_before', $this );

		// Invoke plugins for all fields, since they're all "changed" when inserted
		foreach ( $this->fields as $fieldname => $value ) {
			Plugins::act( 'tag_update_' . $fieldname, $this, ( $this->id == 0 ) ? null : $value, $this->$fieldname );
		}

		$result = parent::insertRecord( DB::table( 'tags' ) );
		$this->newfields['id']= DB::last_insert_id(); // Make sure the id is set in the Tag object to match the row id
		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();
		EventLog::log( sprintf(_t('New tag %1$s (%2$s);  Slug: %3$s'), $this->id, $this->tag_text, $this->tag_slug), 'info', 'content', 'habari' );
		Plugins::act( 'tag_insert_after', $this );

		return $result;
	}

	/**
	 * function update
	 * Updates an existing tag in the tags table
	 */
	public function update()
	{

		$allow = true;
		$allow = Plugins::filter( 'tag_update_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'tag_update_before', $this );

		// Call setslug() only when tag slug is changed
		if ( isset( $this->newfields['tag_slug'] ) && $this->newfields['tag_slug'] != '' ) {
			if ( $this->fields['tag_slug'] != $this->newfields['tag_slug'] ) {
				$this->setslug();
			}
		}

		// invoke plugins for all fields which have been changed
		// For example, a plugin action "tag_update_slug" would be
		// triggered if the tag has a new slug value
		foreach ( $this->newfields as $fieldname => $value ) {
			Plugins::act( 'tag_update_' . $fieldname, $this, $this->fields[$fieldname], $value );
		}

		$result = parent::updateRecord( DB::table( 'tags' ), array( 'id' => $this->id ) );

		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();
		Plugins::act( 'tag_update_after', $this );
		return $result;
	}

	/**
	 * function delete
	 * Deletes an existing tag and all relations to it (e.g. a post2tag relationship)
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'tag_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		// invoke plugins
		Plugins::act( 'tag_delete_before', $this );

		// Delete all tag2post records associated with this tag
		$sql = "DELETE FROM {tag2post} WHERE tag_id = ?";
		DB::query( $sql, array( $this->id ) );

		// Delete the parent tags record
		$result = parent::deleteRecord( DB::table( 'tags' ), array( 'id'=>$this->id ) );
		EventLog::log( sprintf(_t('Tag %1$s (%2$s) deleted.'), $this->id, $this->tag_text), 'info', 'content', 'habari' );

		Plugins::act( 'tag_delete_after', $this );
		return $result;
	}

	/**
	 * Handle calls to this Tag object that are implemented by plugins
	 * @param string $name The name of the function called
	 * @param array $args Arguments passed to the function call	 
	 * @return mixed The value returned from any plugin filters, null if no value is returned	 
	 **/	 
	public function __call( $name, $args )
	{
		array_unshift($args, 'tag_call_' . $name, null, $this);
		return call_user_func_array(array('Plugins', 'filter'), $args);
	}

}
?>
