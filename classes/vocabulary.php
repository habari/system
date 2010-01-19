<?php

/**
 * Vocabulary Class
 *
 * Vocabulary is part of the taxonomy system. A vocabulary holds terms and has features.
 *
 */

class Vocabulary extends QueryRecord
{
	/**
	 *
	 * @var Array of strings $features. An array of the features that limit the behaviour of the vocabulary.
	 * Default values can include:
	 *		hierarchical:	The vocabulary's terms exist in a parent child hierarchy
	 *		required:
	 *		multiple:		More than one term in the vocabulary can be associated with an object
	 *		free:			Terms within the vocabulary can have any value
	 */
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
				if ( ! is_array( $out ) ) {
					$out = unserialize( $out );
				}
				break;
		}
		if ( is_null( $out ) ) {
			$out = in_array( $name, $this->features );
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
	 * For example, return all terms associated with a particular post, from all vocabularies.
	 *
	 * @return array Array of Vocabulary names
	 **/
	public static function get_all_object_terms($object_type, $id)
	{
		$results = DB::get_results(
			'SELECT id, term, term_display, vocabulary_id, mptt_left, mptt_right FROM {terms}
			JOIN {object_terms} ON {terms}.id = {object_terms}.term_id
			WHERE {object_terms}.object_type_id = ?
				AND {object_terms}.object_id = ?',
			array( self::object_type_id( $object_type ), $id ),
			'Term'
		);

		return $results;
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
		if ( Vocabulary::exists( $this->fields['name'] ) ) {
			return false;
		}

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_insert_allow', $allow, $this );
		if ( !$allow ) {
			return false;
		}
		Plugins::act( 'vocabulary_insert_before', $this );

		// Serialize features before they are stored
		if ( isset( $this->newfields['features'] ) ) {
			$this->newfields['features'] = serialize( $this->newfields['features'] );
		}
		if ( isset( $this->fields['features'] ) ) {
			$this->fields['features'] = serialize( $this->fields['features'] );
		}
		$result = parent::insertRecord( '{vocabularies}' );

		// Make sure the id is set in the vocabulary object to match the row id
		$this->newfields['id'] = DB::last_insert_id();

		// Update the vocabulary's fields with anything that changed
		$this->fields = array_merge( $this->fields, $this->newfields );
		// And unserialize the features
		if ( isset( $this->fields['features'] ) ) {
			$this->fields['features'] = unserialize( $this->fields['features'] );
		}

		// We've inserted the vocabulary, reset newfields
		$this->newfields = array();

		EventLog::log( sprintf( _t( 'New vocabulary %1$s (%2$s)' ), $this->id, $this->name ), 'info', 'content', 'habari' );

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
		if ( isset( $this->newfields['name'] ) && Vocabulary::exists( $this->newfields['name'] ) ) {
			return false;
		}

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_update_allow', $allow, $this );
		if ( !$allow ) {
			return;
		}
		Plugins::act( 'vocabulary_update_before', $this );

		if ( isset( $this->newfields['features'] ) ) {
			$this->newfields['features'] = serialize( $this->newfields['features'] );
		}
		if ( isset( $this->fields['features'] ) ) {
			$this->fields['features'] = serialize( $this->fields['features'] );
		}
		$result = parent::updateRecord( '{vocabularies}', array( 'id' => $this->id ) );

		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();

		if ( isset( $this->fields['features'] ) ) {
			$this->fields['features'] = unserialize( $this->fields['features'] );
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

		// Get the ids for all this vocabulary's terms
		$ids = DB::get_column('SELECt id FROM {terms} WHERE vocabulary_id = ?', array( $this->id ) );
		// Delete the records from object_terms for those ids
		$placeholder = Utils::placeholder_string( count( $ids ) );
		DB::query('DELETE FROM {object_terms} WHERE term_id IN ($placeholder)', $ids );

		// Delete this vocabulary's terms
		DB::delete( '{terms}', array( 'vocabulary_id' => $this->id ) );

		// Finally, delete the vocabulary
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
					$ref = DB::get_value( 'SELECT mptt_right FROM {terms} WHERE vocabulary_id=? ORDER BY mptt_right DESC LIMIT 1', array($this->id) );
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
					$ref = DB::get_value( 'SELECT mptt_right FROM {terms} WHERE vocabulary_id=? ORDER BY mptt_right DESC LIMIT 1', array($this->id) );
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
		$result = $new_term->insert();
		if ( $result ) {
			return $new_term;
		}
		else {
			return FALSE;
		}

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
	 * Gets the Term objects associated to that type of object with that id in this vocabulary
	 * For example, return all terms in this vocabulary that are associated with a particular post
	 *
	 * @param String the name of the object type
	 * @param integer The id of the object for which you want the terms
	 * @return Array The Term objects requested
	 **/
	public function get_object_terms($object_type, $id)
	{
		$results = DB::get_results(
			'SELECT id, term, term_display, vocabulary_id, mptt_left, mptt_right FROM {terms}
			JOIN {object_terms} ON {terms}.id = {object_terms}.term_id
			WHERE {terms}.vocabulary_id = ?
				AND {object_terms}.object_type_id = ?
				AND {object_terms}.object_id = ?',
			array( $this->id, self::object_type_id( $object_type ), $id ),
			'Term'
		);

		return $results;
	}

	/**
	 * Sets the Term objects associated to that type of object with that id in this vocabulary
	 *
	 * @param String the name of the object type
	 * @param Integer The id of the object for which you want the terms
	 * @param Array. The names of the terms to associate
	 *
	 * @return boolean. Whether the associations were successful or not
	 **/
	public function set_object_terms( $object_type, $id, $terms )
	{
		// no terms? then let's get out'a'here
		if (count($terms) == 0) {
			Plugins::act( 'term_detach_all_from_object_before', $this->id );

			$results = $this->get_object_terms( $object_type, $this->id );
			foreach ( $results as $term ) {
				$term->dissociate( $term->id, $id );
			}

			Plugins::act( 'term_detach_all_from_object_after', $this->id );
			return TRUE;
		}
		/*
		 * First, let's clean the incoming tag text array, ensuring we have
		 * a unique set of tag texts and slugs.
		 */
		$term_ids_to_object = $clean_terms = array();
		foreach ( ( array ) $terms as $term )
			if ( ! in_array( $term, array_keys( $clean_terms ) ) )
				if ( ! in_array( $slug = Utils::slugify( $term ), array_values( $clean_terms ) ) )
					$clean_terms[$term] = $slug;

		/* Now, let's insert any *new* term display text or slugs into the terms table */
		$placeholders = Utils::placeholder_string( count( $clean_terms ) );
		$sql_terms_exist = "SELECT id, term_display, term
			FROM {terms}
			WHERE term_display IN ({$placeholders})
			OR term IN ({$placeholders})
			AND vocabulary_id = ?";
		$params = array_merge( array_keys( $clean_terms ), array_values( $clean_terms ), (array)$this->id );
		$existing_terms = DB::get_results( $sql_terms_exist, $params, 'Term' );
		if ( count( $existing_terms ) > 0 ) {
			/* Terms exist which match the term text or the term */
			foreach ( $existing_terms as $existing_term ) {
				/*
				 * Term exists.
				 * Attach object to term, then remove the term from creation list.
				 */
				$existing_term->associate( $object_type, $id );
				$term_ids_to_object[] = $existing_term->id;

				/*
				 * We remove it from the clean_terms collection as we only
				 * want to add to the terms table those terms which don't already exist
				 */
				if ( in_array( $existing_term->term_display, array_keys( $clean_terms ) ) ) {
					unset( $clean_terms[$existing_term->term_display] );
				}
				if ( in_array( $existing_term->term, array_values( $clean_terms ) ) ) {
					foreach ( $clean_terms as $text => $slug ) {
						if ( $slug == $existing_term->term ) {
							unset( $clean_terms[$text] );
							break;
						}
					}
				}
			}
		}

		/*
		 * $clean_terms now contains an associative array of terms
		 * we need to add to the main terms table, so add them
		 *
		 */
		foreach ( $clean_terms as $new_term_text => $new_term_slug ) {
			$term = new Term( array( 'term_display' => $new_term_text, 'term' => $new_term_slug ) );
			$this->add_term( $term );
			$term->associate( $object_type, $id );
			$term_ids_to_object[] = $term->id;
		}

		/*
		 * Finally, remove the terms which are no longer associated with the object.
		 */
		$repeat_questions = Utils::placeholder_string( count( $term_ids_to_object ) );
		$sql_delete = "SELECT term_id FROM {object_terms}
			JOIN {terms} ON term_id = {terms}.id
			WHERE object_id = ? AND term_id NOT IN ({$repeat_questions}) AND object_type_id = ?
			AND {terms}.vocabulary_id = ?";
		$params = array_merge( (array) $id, array_values( $term_ids_to_object ), (array)Vocabulary::object_type_id( $object_type ), (array)$this->id );

		$result = DB::get_column( $sql_delete, $params );

		foreach ( $result as $t ) {
			$term = $this->get_term( $t );
			$term->dissociate( $object_type, $id );
		}

		return TRUE;
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
	public function get_tree($orderby = 'mptt_left ASC')
	{
		return DB::get_results( "SELECT * FROM {terms} WHERE vocabulary_id=? ORDER BY $orderby", array($this->id), 'Term' );
	}

	/**
	 * Get all root elements in this vocabulary
	 * @return Array The root Term objects in the vocabulary
	 */
	public function get_root_terms()
	{
		/**
		 * If we INNER JOIN the terms table with itself on ALL the descendents,
		 * then descendents one level down are listed once, two levels down are listed twice,
		 * etc. If we return only those terms which appear once, we get root elements.
		 * ORDER BY NULL to avoid the MySQL filesort.
		 */
		$query = <<<SQL
SELECT child.term as term,
	child.term_display as term_display,
	child.mptt_left as mptt_left,
	child.mptt_right as mptt_right,
	child.vocabulary_id as vocabulary_id
FROM {terms} as parent
INNER JOIN {terms} as child
	ON child.mptt_left BETWEEN parent.mptt_left AND parent.mptt_right
	AND child.vocabulary_id = parent.vocabulary_id
WHERE parent.vocabulary_id = ?
GROUP BY child.term
HAVING COUNT(child.term)=1
ORDER BY NULL
SQL;
		return DB::get_results( $query, array($this->id), 'Term' );
	}

	/**
	 * inserts a new object type into the database, if it doesn't exist
	 * @param string The name of the new post type
	 * @param bool Whether the new post type is active or not
	 * @return none
	**/
	public static function add_object_type( $type )
	{
		$params = array( 'name' => $type );
		if ( ! DB::exists( "{object_types}", $params ) ) {
			DB::insert( "{object_types}", $params );
		}
	}

	/**
	 * Return the object type id for a named object, such as a post
	 *
	 * @param string $name The type of object
	 * @return integer The id of the object type
	*/
	public static function object_type_id( $type )
	{
		$id = (int)DB::get_value( "SELECT id FROM {object_types} WHERE name = ?", array( $type ) );
		return $id;

	}
}

?>
