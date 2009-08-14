<?php

/**
 * Vocabulary Class
 *
 *
 * @version $Id$
 * @copyright 2009
 */

class Vocabulary extends QueryRecord
{
	public static $features = array('hierarchical', 'required', 'multiple', 'free');

	/**
	 * Return the defined database columns for a Vocabulary.
	 * @return array Array of columns in the Vocabulary table
	 **/
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'name' => '',
			'description' => '',
			'features' => array(),
		);
	}

	/**
	 * Vocabulary constructor
	 * Creates a Vocabulary instance
	 *
	 * @param array $paramarray an associative array of initial vocabulary values
	 **/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields
		);

		parent::__construct( $paramarray );

		$this->exclude_fields( 'id' );
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __get( $name )
	{
		$out = parent::__get( $name );
		switch($name) {
			case 'features':
				return unserialize($out);
		}
		if ( is_null( $out ) ) {
			$features = unserialize( parent::__get( 'features' ) );
			return in_array($name, $features);
		}
		return $out;
	}

	/**
	 * Return a Vocabulary by name.
	 * @return Vocabulary The requested vocabulary
	 **/
	public static function get($name)
	{
		return DB::get_row( 'SELECT * FROM {vocabularies} WHERE name=?', array($name), 'Vocabulary' );
	}
	
	/**
	 * Return a Vocabulary by id
	 * 
	 * @param integer $id The id of the vocabulary
	 * @return Vocabulary The object requested
	 */
	public static function get_by_id($id)
	{
		return DB::get_row( 'SELECT * FROM {vocabularies} WHERE id=?', array($id), 'Vocabulary' );
	}
	
	/**
	 * Return all vocabularies as Vocabulary objects
	 * 
	 * @return array An array of Vocabulary objects
	 */
	public static function get_all()
	{
		return DB::get_results( 'SELECT * FROM {vocabularies}', array(), 'Vocabulary' );
	}

	/**
	 * Rename a Vocabulary.
	 * @return boolean true if the Vocabulary was renamed, false otherwise
	 **/
	public static function rename($name, $newname)
	{
		$vocab = Vocabulary::get($name);
		$vocab->name = $newname;
		$result = $vocab->update();

		return $result;
	}

	/**
	 * Return the names of all vocabularies
	 * @return array Array of Vocabulary names
	 **/
	public static function names()
	{
		$names = array();
		$vocabs = DB::get_results( 'SELECT name FROM {vocabularies}' );
		foreach ( $vocabs as $vocab ) {
			$names[] = $vocab->name;
		}
		return $names;
	}

	/**
	 * Return the Term objects associated to that type of object with that id in any vocabulary.
	 * @return array Array of Vocabulary names
	 **/
	public static function get_all_object_terms($object_type, $id)
	{
	}

	/**
	 * Determine whether a vocabulary exists
	 * @param string $name a vocabulary name
	 * @return bool whether the vocabulary exists or not
	**/
	public static function exists( $name )
	{
		return ( (int) DB::get_value( "SELECT COUNT(id) FROM {vocabularies} WHERE name=?", array( $name ) ) > 0 );
	}

	/**
	 * function insert
	 * Saves a new vocabulary to the vocabularies table
	 */
	public function insert()
	{
		// Don't allow duplicate vocabularies
		if ( Vocabulary::exists($this->fields['name']) ) {
			return false;
		}

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_insert_allow', $allow, $this );
		if ( !$allow ) {
			return false;
		}
		Plugins::act( 'vocabulary_insert_before', $this );

		if(isset($this->newfields['features'])) {
			$this->newfields['features'] = serialize($this->newfields['features']);
		}
		if(isset($this->fields['features'])) {
			$this->fields['features'] = serialize($this->fields['features']);
		}
		$result = parent::insertRecord( '{vocabularies}' );
		if(isset($this->newfields['features'])) {
			$this->newfields['features'] = unserialize($this->newfields['features']);
		}
		if(isset($this->fields['features'])) {
			$this->fields['features'] = unserialize($this->fields['features']);
		}

		// Make sure the id is set in the vocabulary object to match the row id
		$this->newfields['id'] = DB::last_insert_id();

		// Update the vocabulary's fields with anything that changed
		$this->fields = array_merge( $this->fields, $this->newfields );

		// We've inserted the vocabulary, reset newfields
		$this->newfields = array();

		EventLog::log( sprintf(_t('New vocabulary %1$s (%2$s)'), $this->id, $this->name), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'vocabulary_insert_after', $this );

		return $result;
	}

	/**
	 * function update
	 * Updates an existing vocabulary in the vocabularies table
	 */
	public function update()
	{
		// Don't allow duplicate vocabularies
		if ( isset($this->newfields['name']) && Vocabulary::exists($this->newfields['name']) ) {
			return false;
		}

		// Store the features as a serial
		if ( isset($this->newfields['features']) && is_array($this->newfields['features']) ) {
			$this->newfields['features'] = serialize($this->newfields['features']);
		}

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_update_allow', $allow, $this );
		if ( !$allow ) {
			return;
		}
		Plugins::act( 'vocabulary_update_before', $this );

		if(isset($this->newfields['features'])) {
			$this->newfields['features'] = serialize($this->newfields['features']);
		}
		if(isset($this->fields['features'])) {
			$this->fields['features'] = serialize($this->fields['features']);
		}
		$result = parent::updateRecord( '{vocabularies}', array( 'id' => $this->id ) );
		if(isset($this->newfields['features'])) {
			$this->newfields['features'] = unserialize($this->newfields['features']);
		}
		if(isset($this->fields['features'])) {
			$this->fields['features'] = unserialize($this->fields['features']);
		}

		// Let plugins act after we write to the database
		Plugins::act( 'vocabulary_update_after', $this );

		return $result;
	}

	/**
	 * Delete an existing vocabulary
	 */
	public function delete()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_delete_allow', $allow, $this );
		if ( !$allow ) {
			return;
		}
		Plugins::act( 'vocabulary_delete_before', $this );

		// TODO Delete all terms associated with this vocabulary

		$result = parent::deleteRecord( '{vocabularies}', array( 'id'=>$this->id ) );
		EventLog::log( sprintf(_t('Vocabulary %1$s (%2$s) deleted.'), $this->id, $this->name), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'vocabulary_delete_after', $this );
		return $result;
	}

	/**
	 * Adds a term to the vocabulary. Returns a Term object. null parameters append the term to the end of any hierarchies.
	 * @return Term The Term object added
	 **/
	public function add_term($term, $parent_term = null, $before_term = null)
	{
		$new_term = $term;
		if ( is_string($term) ) {
			$new_term = new Term(array('term_display' => $term));
		}

		$new_term->vocabulary_id = $this->id;

		$ref = 0;
		// If there are terms in the vocabulary, work out the reference point
		if ( !$this->is_empty() ) {

			if ( $this->hierarchical ) {
				// If no parent is specified, put the new term after the last term
				if ( null == $parent_term ) {
					$ref = DB::get_value( 'SELECT mptt_right FROM habari__terms WHERE vocabulary_id=? ORDER BY mptt_right DESC LIMIT 1', array($this->id) );
				}
				else {
					if ( null == $before_term ) {
						$ref = $parent_term->mptt_right - 1;
					}
					else {
						$ref = $before_term->mptt_left - 1;
					}
				}
			}
			else {
				// If no before_term is specified, put the new term after the last term
				if ( null == $before_term ) {
					$ref = DB::get_value( 'SELECT mptt_right FROM habari__terms WHERE vocabulary_id=? ORDER BY mptt_right DESC LIMIT 1', array($this->id) );
				}
				else {
					$ref = $before_term->mptt_left - 1;
				}
			}

			// Make space for the new node
			$params = array($this->id, $ref);
			DB::query('UPDATE {terms} SET mptt_right=mptt_right+2 WHERE vocabulary_id=? AND mptt_right>?', $params);
			DB::query('UPDATE {terms} SET mptt_left=mptt_left+2 WHERE vocabulary_id=? AND mptt_left>?', $params);

		}

		// Set the right and left appropriately
		$new_term->mptt_left = $ref + 1;
		$new_term->mptt_right = $ref + 2;

		// Insert the new node
		$new_term->insert();

		return $new_term;
	}

	/**
	 * Gets the term object by id. No parameter returns the root Term object.
	 * @param integer $term_id The id of the term to fetch, or null for the root node
	 * @return Term The Term object requested
	 **/
	public function get_term($term_id = null)
	{
		return Term::get($this->id, $term_id);
	}

	/**
	 * Gets the Term objects associated to that type of object with that id.
	 * @return Array The Term objects requested
	 **/
	public function get_object_terms($object_type, $id)
	{
	}

	/**
	 * Remove the term from the vocabulary.  Convenience method to ->get_term('foo')->delete().
	 *
	 **/
	public function delete_term($term)
	{
		if ( is_string($term) ) {
			$term = $this->get_term($term);
		}

		// TODO How should we handle deletion of a term with descendants?
		// Perhaps a $keep_children flag to move descendants to be descendants of
		// the deleted term's parent? Terms should not change the left and right
		// values of other terms, and thus their deletion should only occur through
		// the vocabulary to which they belong. Is it feasible to restrict this?
		// For the moment, just delete the descendants
		$params = array($this->id, $term->mptt_left, $term->mptt_right);
		DB::query('DELETE from {terms} WHERE vocabulary_id=? AND mptt_left>? AND mptt_right<?', $params);

		// Fix mptt_left and mptt_right values for other nodes in the vocabulary
		$offset = $term->mptt_right - $term->mptt_left + 1;
		$ref = $this->mptt_left;
		$params = array($offset, $this->id, $term->mptt_left);

		// Delete the term
		$term->delete();

		// Renumber left and right values of other nodes appropriately
		DB::query('UPDATE {terms} SET mptt_right=mptt_right-? WHERE vocabulary_id=? AND mptt_right>?', $params);
		DB::query('UPDATE {terms} SET mptt_left=mptt_left-? WHERE vocabulary_id=? AND mptt_left>?', $params);

	}

	/**
	 * Check if this vocabulary is empty.
	 *
	 **/
	public function is_empty()
	{
		return ( (int) DB::get_value( "SELECT COUNT(id) FROM {terms} WHERE vocabulary_id=?", array( $this->id ) ) == 0 );
	}


	/**
	 * Retrieve the vocabulary
	 * @return Array The Term objects in the vocabulary, in tree order
	 **/
	public function get_tree()
	{
		// TODO There should probably be a Term::get()
		return DB::get_results( 'SELECT * FROM {terms} WHERE vocabulary_id=? ORDER BY mptt_left ASC', array($this->id), 'Term' );
	}

}

?>