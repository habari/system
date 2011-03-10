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
	public static $features = array( 'hierarchical', 'required', 'multiple', 'free' );

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
		if ( is_string( $this->features ) ) {
			$this->features = unserialize( $this->features );
		}
	}

	/**
	 * Create a vocabulary and save it.
	 *
	 * @param array $paramarray An associative array of vocabulary fields
	 * @return Vocabulary The new vocabulary object
	 */
	static function create( $paramarray )
	{
		$vocabulary = new Vocabulary( $paramarray );
		$vocabulary->insert();
		return $vocabulary;
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
		switch ( $name ) {
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
	public static function get( $name )
	{
		return DB::get_row( 'SELECT * FROM {vocabularies} WHERE name=?', array( $name ), 'Vocabulary' );
	}

	/**
	 * Return a Vocabulary by id
	 *
	 * @param integer $id The id of the vocabulary
	 * @return Vocabulary The object requested
	 */
	public static function get_by_id( $id )
	{
		return DB::get_row( 'SELECT * FROM {vocabularies} WHERE id=?', array( $id ), 'Vocabulary' );
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
	public function rename( $newname )
	{
		$this->name = $newname;
		$result = $this->update();

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
	public static function get_all_object_terms( $object_type, $id )
	{
		$results = DB::get_results(
			'SELECT id, term, term_display, vocabulary_id, mptt_left, mptt_right FROM {terms}
			JOIN {object_terms} ON {terms}.id = {object_terms}.term_id
			WHERE {object_terms}.object_type_id = ?
				AND {object_terms}.object_id = ?',
			array( self::object_type_id( $object_type ), $id ),
			'Term'
		);

		return new Terms( $results );
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
		$result = parent::insertRecord( DB::table( 'vocabularies' ) );

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

		EventLog::log( _t( 'New vocabulary %1$s (%2$s)', array( $this->id, $this->name ) ), 'info', 'content', 'habari' );

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
		$ids = DB::get_column( 'SELECT id FROM {terms} WHERE vocabulary_id = ?', array( $this->id ) );

		// Delete the records from object_terms for those ids (if there were any)
		if ( count( $ids ) ) {
			$placeholder = Utils::placeholder_string( count( $ids ) );
			DB::query( "DELETE FROM {object_terms} WHERE term_id IN ($placeholder)", $ids );
		}

		// Delete this vocabulary's terms
		DB::delete( '{terms}', array( 'vocabulary_id' => $this->id ) );

		// Finally, delete the vocabulary
		$result = parent::deleteRecord( '{vocabularies}', array( 'id'=>$this->id ) );
		EventLog::log( sprintf( _t( 'Vocabulary %1$s (%2$s) deleted.' ), $this->id, $this->name ), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'vocabulary_delete_after', $this );
		return $result;
	}

	/**
	 * Adds a term to the vocabulary. Returns a Term object. null parameters append the term to the end of any hierarchies.
	 * @return Term The Term object added
	 **/
	public function add_term( $term, $target_term = null, $before = false )
	{
		$new_term = $term;
		if ( is_string( $term ) ) {
			$new_term = new Term( array( 'term_display' => $term ) );
		}

		$new_term->vocabulary_id = $this->id;

		$ref = 0;
		DB::begin_transaction();

		// If there are terms in the vocabulary, work out the reference point
		if ( !$this->is_empty() ) {

			if ( $this->hierarchical ) {
				// If no parent is specified, put the new term after the last term
				if ( null == $target_term ) {
					$ref = DB::get_value( 'SELECT mptt_right FROM {terms} WHERE vocabulary_id=? ORDER BY mptt_right DESC LIMIT 1', array( $this->id ) );
				}
				else {
					if ( ! $before ) {
						$ref = $target_term->mptt_right - 1;
					}
					else {
						$ref = $target_term->mptt_left - 1;
					}
				}
			}
			else {
				// If no before_term is specified, put the new term after the last term
				if ( ! $before ) {
					$ref = DB::get_value( 'SELECT mptt_right FROM {terms} WHERE vocabulary_id=? ORDER BY mptt_right DESC LIMIT 1', array( $this->id ) );
				}
				else {
					$ref = $target_term->mptt_left - 1;
				}
			}

			// Make space for the new node
			$params = array( 'vocab_id' => $this->id, 'ref' => $ref );
			$res = DB::query( 'UPDATE {terms} SET mptt_right=mptt_right+2 WHERE vocabulary_id=:vocab_id AND mptt_right>:ref', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}
			$res = DB::query( 'UPDATE {terms} SET mptt_left=mptt_left+2 WHERE vocabulary_id=:vocab_id AND mptt_left>:ref', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

		}

		// Set the right and left appropriately
		$new_term->mptt_left = $ref + 1;
		$new_term->mptt_right = $ref + 2;

		// Insert the new node
		$result = $new_term->insert();
		if ( $result ) {
			DB::commit();
			return $new_term;
		}
		else {
			DB::rollback();
			return false;
		}

	}

	/**
	 * Gets the term object by id. No parameter returns the root Term object.
	 * @param mixed $term A Term object, null (for the first node in the tree), a string (for a term slug or display), or an integer (for a Term ID).
	 * @param string $term_class The class of the returned term object.
	 * @return Term The Term object requested
	 * @todo improve selective fetching by term slug vs term_display	 
	 **/
	public function get_term( $term = null, $term_class = 'Term' )
	{
		$params = array( 'vocab_id' => $this->id );
		$query = '';
		if ( $term instanceof Term ) {
			$params[ 'term_id' ] = $term->id;
			$query = 'SELECT * FROM {terms} WHERE vocabulary_id = :vocab_id AND id = ABS(:term_id)';
		}
		elseif ( is_null( $term )  ) {
			// The root node has an mptt_left value of 1
			$params[ 'left' ] = 1;
			$query = 'SELECT * FROM {terms} WHERE vocabulary_id = :vocab_id AND mptt_left = :left';
		}
		elseif ( is_string( $term ) ) {
			$params[ 'term' ] = $term;
			$query = 'SELECT * FROM {terms} WHERE vocabulary_id = :vocab_id AND (term = :term OR term_display = :term)';
		}
		elseif ( is_int( $term ) ) {
			$params[ 'term_id' ] = $term;
			$query = 'SELECT * FROM {terms} WHERE vocabulary_id = :vocab_id AND id = ABS(:term_id)';
		}
		return DB::get_row( $query, $params, $term_class );
	}

	/**
	 * Gets the Term objects associated to that type of object with that id in this vocabulary
	 * For example, return all terms in this vocabulary that are associated with a particular post
	 *
	 * @param String the name of the object type
	 * @param integer The id of the object for which you want the terms
	 * @return Array The Term objects requested
	 **/
	public function get_object_terms( $object_type, $id )
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
		if ( ! isset( $object_type ) || ! isset( $id ) ) {
			return false;
		}

		if ( ! isset( $terms ) || empty( $terms ) ) {
			$terms = array();
//			DB::query( "DELETE FROM {object_terms} WHERE object_id = :object_id AND object_type_id = :type_id", array( 'object_id' => $id, 'type_id' => Vocabulary::get_object_id( $object_type ) ) );
//			return true;
		}

		// Make sure we have an array
		$terms = new Terms( $terms );
		$new_terms = array();

		// Make sure we have terms and they're in the database.
		// Key the terms to their id while we're at it.
		foreach ( $terms as $term ) {
			$new_term = $this->get_term( $term );
			if ( ! $new_term instanceof Term ) {
				$new_term = $this->add_term( $term );
			}
			if ( ! array_key_exists( $new_term->id, $new_terms ) ) {
				$new_terms[$new_term->id] = $new_term;
			}
		}

		// Get the current terms
		$old_terms = $this->get_object_terms( $object_type, $id );
		$keys = array_keys( $new_terms );
		foreach ( $old_terms as $term ) {
			// If the old term isn't in the new terms, dissociate it from the object
			if ( ! in_array( $term->id, $keys ) ) {
				$term->dissociate( $object_type, $id );
			}
		}

		// Associate the new terms
		foreach ( $new_terms as $term ) {
			$term->associate( $object_type, $id );
		}

		return true;
	}

	/**
	 * Remove the term from the vocabulary.  Convenience method to ->get_term('foo')->delete().
	 *
	 **/
	public function delete_term( $term )
	{
		if ( ! is_object( $term ) ) {
			$term = $this->get_term( $term );
		}

		// TODO How should we handle deletion of a term with descendants?
		// Perhaps a $keep_children flag to move descendants to be descendants of
		// the deleted term's parent? Terms should not change the left and right
		// values of other terms, and thus their deletion should only occur through
		// the vocabulary to which they belong. Is it feasible to restrict this?
		// For the moment, just delete the descendants
		$params = array( $this->id, $term->mptt_left, $term->mptt_right );
		DB::query( 'DELETE from {terms} WHERE vocabulary_id=? AND mptt_left>? AND mptt_right<?', $params );

		// Fix mptt_left and mptt_right values for other nodes in the vocabulary
		$offset = $term->mptt_right - $term->mptt_left + 1;
		$ref = $this->mptt_left;
		$params = array( $offset, $this->id, $term->mptt_left );

		// Delete the term
		$term->delete();

		// Renumber left and right values of other nodes appropriately
		DB::query( 'UPDATE {terms} SET mptt_right=mptt_right-? WHERE vocabulary_id=? AND mptt_right>?', $params );
		DB::query( 'UPDATE {terms} SET mptt_left=mptt_left-? WHERE vocabulary_id=? AND mptt_left>?', $params );

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
	 * @return Terms The Term objects in the vocabulary, in tree order
	 **/
	public function get_tree( $orderby = 'mptt_left ASC' )
	{
		return new Terms(DB::get_results( "SELECT * FROM {terms} WHERE vocabulary_id=:vid ORDER BY {$orderby}", array( 'vid' => $this->id ), 'Term' ));
	}

	/**
	 * Retrieve the vocabulary as an associative array suitable for FormUI select controls
	 * @return Array The Term objects in the vocabulary, in tree order
	 **/
	public function get_options()
	{
		$tree = $this->get_tree( 'mptt_left ASC' );
		$output = array();
		if ( $firstnode = reset( $tree ) ) {
			$lastright = $lastleft = reset( $tree )->mptt_left;
			$indent = 0;
			$stack = array();
			foreach ( $tree as $term ) {
				while ( count( $stack ) > 0 && end( $stack )->mptt_right < $term->mptt_left ) {
					array_pop( $stack );
				}
				$output[$term->id] = str_repeat( '- ', count( $stack ) ) . $term->term_display;
				$stack[] = $term;
			}
		}

		return $output;
	}

	/**
	 * Get all root elements in this vocabulary
	 * @return Array The root Term objects in the vocabulary
	 */
	public function get_root_terms()
	{
		/**
		 * If we INNER JOIN the terms table with itself on ALL the descendants,
		 * then descendants one level down are listed once, two levels down are listed twice,
		 * etc. If we return only those terms which appear once, we get root elements.
		 * ORDER BY NULL to avoid the MySQL filesort.
		 */
		$query = <<<SQL
SELECT child.term as term,
	child.term_display as term_display,
	child.mptt_left as mptt_left,
	child.mptt_right as mptt_right,
	child.vocabulary_id as vocabulary_id,
	child.id as id
FROM {terms} as parent
INNER JOIN {terms} as child
	ON child.mptt_left BETWEEN parent.mptt_left AND parent.mptt_right
	AND child.vocabulary_id = parent.vocabulary_id
WHERE parent.vocabulary_id = ?
GROUP BY child.term
HAVING COUNT(child.term)=1
ORDER BY NULL
SQL;
		return DB::get_results( $query, array( $this->id ), 'Term' );
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

	/**
	 * Moves a term within the vocabulary. Returns a Term object. null parameters append the term to the end of any hierarchies.
	 * @return Term The Term object moved
	 **/
	public function move_term( $term, $target_term = null, $before = false )
	{
		// We assume that the arguments passed are valid terms. Check them before calling this.

		// If there are terms in the vocabulary, work out the reference point
		if ( !$this->is_empty() ) {

			$source_left = $term->mptt_left;
			$source_right = $term->mptt_right;
			$range = $source_right - $source_left + 1;

			$nodes_moving = ( $range ) / 2; // parent and descendants

			DB::begin_transaction();

			// Move the source nodes out of the way by making mptt_left and mptt_right negative
			$source_to_temp = $source_right + 1; // move the terms so that the rightmost one's mptt_right is -1
			$params = array( 'displacement' => $source_to_temp, 'vocab_id' => $this->id, 'range_left' => $source_left, 'range_right' => $source_right );
			$res = DB::query( '
				UPDATE {terms}
				SET
					mptt_left = mptt_left - :displacement,
					mptt_right = mptt_right - :displacement
				WHERE
					vocabulary_id = :vocab_id
					AND mptt_left BETWEEN :range_left AND :range_right
				',
				$params
			);

			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			// Close the gap in the tree created by moving those nodes out
			$params = array( 'range' => $range, 'vocab_id' => $this->id, 'source_left' => $source_left );
			$res = DB::query( 'UPDATE {terms} SET mptt_left=mptt_left-:range WHERE vocabulary_id=:vocab_id AND mptt_left > :source_left', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			$res = DB::query( 'UPDATE {terms} SET mptt_right=mptt_right-:range WHERE vocabulary_id=:vocab_id AND mptt_right > :source_left', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			// Determine the insertion point mptt_target
			if ( $this->hierarchical ) {
				if ( null == $target_term ) {
					// If no target is specified, put the new term after the last term
					$mptt_target = DB::get_value( 'SELECT MAX(mptt_right) FROM {terms} WHERE vocabulary_id=?', array( $this->id ) ) + 1;
				}
				else {
					if ( $before == false ) {
						$mptt_target = DB::get_value( 'SELECT mptt_right FROM {terms} WHERE vocabulary_id=? AND id = ?', array( $this->id, $target_term->id ) );
					}
					else {
						$mptt_target = DB::get_value( 'SELECT mptt_left FROM {terms} WHERE vocabulary_id=? AND id = ?', array( $this->id, $target_term->id ) );
					}
				}
			}
			else {
				// vocabulary is not hierarchical
				if ( $before != false ) {
					$mptt_target = DB::get_value( 'SELECT mptt_left FROM {terms} WHERE vocabulary_id=? AND id = ?', array( $this->id, $target_term->id ) );
				}
				else {
					$mptt_target = DB::get_value( 'SELECT MAX(mptt_right) FROM {terms} WHERE vocabulary_id=?', array( $this->id ) ) + 1;
				}
			}
			$temp_left = $source_left - $source_to_temp;
			$temp_to_target = $mptt_target - $temp_left;

			// Create space in the tree for the insertion
			$params = array( 'vocab_id' => $this->id, 'range' => $range, 'mptt_target' => $mptt_target );
			$res = DB::query( 'UPDATE {terms} SET mptt_left=mptt_left+:range WHERE vocabulary_id=:vocab_id AND mptt_left >= :mptt_target', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			$res = DB::query( 'UPDATE {terms} SET mptt_right=mptt_right+:range WHERE vocabulary_id=:vocab_id AND mptt_right >= :mptt_target', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			// Move the temp nodes into the space created for them
			$params = array( 'vocab_id' => $this->id, 'temp_to_target' => $temp_to_target );
			$res = DB::query( 'UPDATE {terms} SET mptt_left=mptt_left+:temp_to_target WHERE vocabulary_id=:vocab_id AND mptt_left < 0', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			$res = DB::query( 'UPDATE {terms} SET mptt_right=mptt_right+:temp_to_target WHERE vocabulary_id=:vocab_id AND mptt_right < 0', $params );
			if ( ! $res ) {
				DB::rollback();
				return false;
			}

			// Success!
			DB::commit();

			// @todo: need to return the updated term
			return $term;
		}
		return false;
	}


	/**
	 * Returns the number of tags in the database.
	 *
	 * @return int The number of tags in the database.
	 **/
	public  function count_total()
	{
		return count( $this->get_tree() );
	}

	/**
	 * Returns the number of times the most used tag is used.
	 *
	 * @return int The number of times the most used tag is used.
	 **/
	public function max_count()
	{
		return DB::get_value( 'SELECT count( t2.object_id ) AS max FROM {terms} t, {object_terms} t2 WHERE t2.term_id = t.id AND t.vocabulary_id = ? GROUP BY t.id ORDER BY max DESC LIMIT 1', array( $this->id ) );
	}

	/**
	 * Renames terms.
	 * If the master term exists, the terms will be merged with it.
	 * If not, it will be created first.
	 *
	 * @param mixed $master The Term to which they should be renamed, or the slug, text or id of it
	 * @param Array $tags The tag text, slugs or ids to be renamed
	 **/
	public function merge( $master, $tags, $object_type = 'post' )
	{
		$type_id = Vocabulary::object_type_id( $object_type );

		$post_ids = array();
		$tag_names = array();

		// get array of existing tags first to make sure we don't conflict with a new master tag
		foreach ( $tags as $tag ) {

			$posts = array();
			$term = $this->get_term( $tag );

			// get all the post ID's tagged with this tag
			$posts = $term->objects( $object_type );

			if ( count( $posts ) > 0 ) {
				// merge the current post ids into the list of all the post_ids we need for the new tag
				$post_ids = array_merge( $post_ids, $posts );
			}

			$tag_names[] = $tag;
			if ( $tag != $master ) {
				$this->delete_term( $term->id );
			}
		}

		// get the master term
		$master_term = $this->get_term( $master );

		if ( !isset( $master_term->term ) ) {
			// it didn't exist, so we assume it's tag text and create it
			$master_term = $this->add_term( $master );

			$master_ids = array();
		}
		else {
			// get the posts the tag is already on so we don't duplicate them
			$master_ids = $master_term->objects( $object_type );

		}

		if ( count( $post_ids ) > 0 ) {
			// only try and add the master tag to posts it's not already on
			$post_ids = array_diff( $post_ids, $master_ids );
		}
		else {
			$post_ids = $master_ids;
		}
		// link the master tag to each distinct post we removed tags from
		foreach ( $post_ids as $post_id ) {
			$master_term->associate( $object_type, $post_id );
		}

		EventLog::log( sprintf(
			_n( 'Term %1$s in the %2$s vocabulary has been renamed to %3$s.',
				'Terms %1$s in the %2$s vocabulary have been renamed to %3$s.',
				count( $tags )
			), implode( $tag_names, ', ' ), $this->name, $master ), 'info', 'vocabulary', 'habari'
		);

	}
	

	/**
	 * Get the tags associated with this object
	 *
	 * @param Integer $object_id. The id of the tagged object
	 * @param String $object_type. The name of the type of the object being tagged. Defaults to post
	 *
	 * @return Terms The terms associated with this object
	 */
	public function get_associations( $object_id, $object_type = 'post' )
	{
		$terms = $this->get_object_terms( $object_type, $object_id );
		if ( $terms ) {
			$terms = new Terms( $terms );
		}

		return $terms;
	}
	

	/**
	 * Returns the count of times a tag is used.
	 *
	 * @param mixed $term The tag to count usage.
	 * @return int The number of times a tag is used.
	 **/
	public function post_count( $term, $object_type = 'post' )
	{
		$term = $this->get_term( $term );
		return $term->count( $object_type );
	}

	/**
	 * Moves all of the terms into a temporary area so that they can be moved
	 *
	 * @static
	 * @param  Terms $terms An array of Term objects
	 */
	public static function prep_update( $terms )
	{
		$index = -1;
		foreach($terms as $term) {
			DB::query( 'UPDATE {terms} SET mptt_left=:left, mptt_right=:right WHERE id=:id', array('id' => $term->id, 'left' => $index, 'right' => $index -1 ) );
			$index -=2;
		}
	}

}

?>
