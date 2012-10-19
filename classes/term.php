<?php

/**
 * Term Class
 *
 * Term is part of the vocabulary system. A term exists in a vocabulary and can
 * be associated with objects, such as posts.
 *
 * @property int id
 * @property int mptt_left
 * @property int mptt_right
 * @property string term_display
 * @property string term
 * @property int vocabulary_id
 */

class Term extends QueryRecord
{
	protected $inforecords = null;

	public $unsetfields = array(
		'object_id' => 'object_id',
		'type' => 'type',
	);
	
	/**
	 * Return the defined database columns for a Term.
	 * @return array Array of columns in the Term table
	 */
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'term' => '',
			'term_display' => '',
			'vocabulary_id' => 0,
			'mptt_left' => 0,
			'mptt_right' => 0
		);
	}

	/**
	 * Term constructor
	 * Creates a Term instance
	 *
	 * @param array $paramarray an associative array of initial term values
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields
		);

		if ( is_string( $paramarray ) ) {
			$paramarray = array(
				'term_display' => $paramarray,
				'term' => Utils::slugify( $paramarray ),
			);
		}

		parent::__construct( $paramarray );

		// TODO does term need to be a slug ?
		// TODO How should we handle neither being present ?
		// A Vocabulary may be used for organization, display, or both.
		// Therefore, a Term can be constructed with a term, term_display, or both
		if ( $this->term == '' ) {
			$this->fields[ 'term' ] = Utils::slugify( $this->fields[ 'term_display' ] );
		}

		$this->exclude_fields( 'id' );
	}

	/**
	 * Generate a new slug for the post.
	 *
	 * @return string The slug
	 */
	protected function setslug()
	{
		$value = '';
		// determine the base value from:
		// - the new slug
		if ( isset( $this->newfields[ 'term' ] ) && $this->newfields[ 'term' ] != '' ) {
			$value = $this->newfields[ 'term' ];
		}
		// - the existing slug
		elseif ( $this->fields[ 'term' ] != '' ) {
			$value = $this->fields[ 'term' ];
		}
		// - the new term display text
		elseif ( isset( $this->newfields[ 'term_display' ] ) && $this->newfields[ 'term_display' ] != '' ) {
			$value = $this->newfields[ 'term_display' ];
		}
		// - the existing term display text
		elseif ( $this->fields[ 'term_display' ] != '' ) {
			$value = $this->fields[ 'term_display' ];
		}

		// make sure our slug is unique
		$slug = Plugins::filter( 'term_setslug', $value );
		$slug = Utils::slugify( $slug );
		$postfix = '';
		$postfixcount = 0;
		do {
			if ( ! $slugcount = DB::get_row( 'SELECT COUNT(term) AS ct FROM {terms} WHERE term = ? AND vocabulary_id = ?;', array( $slug . $postfix, $this->fields['vocabulary_id'] ) ) ) {
				Utils::debug( DB::get_errors() );
				exit;
			}
			if ( $slugcount->ct != 0 ) {
				$postfix = "-" . ( ++$postfixcount );
			}
		} while ( $slugcount->ct != 0 );

		return $this->newfields[ 'term' ] = $slug . $postfix;
	}

	/**
	 * function insert
	 * Saves a new term to the terms table
	 */
	public function insert()
	{
		$this->setslug();

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'term_insert_allow', $allow, $this );
		$allow = $this->is_valid();
		if ( !$allow ) {
			return $allow;
		}
		Plugins::act( 'term_insert_before', $this );

		$result = parent::insertRecord( DB::table( 'terms' ) );

		// Make sure the id is set in the term object to match the row id
		$this->newfields[ 'id' ] = DB::last_insert_id();

		// Update the term's fields with anything that changed
		$this->fields = array_merge( $this->fields, $this->newfields );

		// We've inserted the term, reset newfields
		$this->newfields = array();

		// Commit the info records
		$this->info->commit( $this->fields['id'] );
		
		EventLog::log( _t( 'New term %1$s: %2$s', array( $this->id, $this->term_display ) ), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'term_insert_after', $this );

		return $result;
	}

	/**
	 * function update
	 * Updates an existing term in the terms table
	 */
	public function update()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'term_update_allow', $allow, $this );
		$allow = $this->is_valid();
		if ( !$allow ) {
			return;
		}
		Plugins::act( 'term_update_before', $this );

		// Call setslug() only when term is changed
		if ( isset( $this->newfields[ 'term' ] ) && $this->newfields[ 'term' ] != '' ) {
			if ( $this->fields[ 'term' ] != $this->newfields[ 'term' ] ) {
				$this->setslug();
			}
		}

		$result = parent::updateRecord( '{terms}', array( 'id' => $this->id ) );
		$this->fields = array_merge( $this->fields, $this->newfields );

		$this->info->commit();
		
		// Let plugins act after we write to the database
		Plugins::act( 'term_update_after', $this );

		return $result;
	}

	/**
	 * Delete an existing term
	 */
	public function delete()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'term_delete_allow', $allow, $this );
		if ( !$allow ) {
			return false;
		}
		Plugins::act( 'term_delete_before', $this );

		// Delete all info records associated with this comment
		$this->info->delete_all();

		DB::query( 'DELETE FROM {object_terms} WHERE term_id = :id', array( 'id' => $this->id ) );

		$result = parent::deleteRecord( '{terms}', array( 'id'=>$this->id ) );
		EventLog::log( _t( 'Term %1$s (%2$s) deleted.', array( $this->id, $this->term_display ) ), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'term_delete_after', $this );
		return $result;
	}

	/**
	 * Find this Term's ancestors.
	 * @return Array Direct ancestors from the root to this Term in descendant order.
	 */
	public function ancestors()
	{
		$params = array( 'vocab_id' => $this->vocabulary_id, 'left' => $this->mptt_left, 'right' => $this->mptt_right );
		$query = 'SELECT * FROM {terms} WHERE vocabulary_id=:vocab_id AND mptt_left<:left AND mptt_right>:right ORDER BY mptt_left ASC';
		return DB::get_results( $query, $params, 'Term' );
	}

	/**
	 * Find all Terms in this Term's Vocabulary that are not its ancestors, or it.
	 * @return Array of Terms in MPTT left-to-right order.
	 */
	public function not_ancestors()
	{
		$params = array( 'vocab_id' => $this->vocabulary_id, 'left' => $this->mptt_left, 'right' => $this->mptt_right );
		$query = 'SELECT * FROM {terms} WHERE vocabulary_id=:vocab_id AND (mptt_left>:left OR mptt_right<:right) ORDER BY mptt_left ASC';
		return DB::get_results( $query, $params, 'Term' );
	}

	/**
	 * Find this Term's descendants.
	 * @return Array of all descendants in MPTT left-to-right order.
	 */
	public function descendants()
	{
		$params = array( 'vocab_id' => $this->vocabulary_id, 'left' => $this->mptt_left, 'right' => $this->mptt_right );
		$query = 'SELECT * FROM {terms} WHERE vocabulary_id=:vocab_id AND mptt_left>:left AND mptt_right<:right ORDER BY mptt_left ASC';
		return DB::get_results( $query, $params, 'Term' );
	}

	/**
	 * Find all Terms in this Term's Vocabulary that are not its descendants, or it.
	 * @return Array of Terms in MPTT left-to-right order.
	 */
	public function not_descendants()
	{
		$params = array( 'vocab_id' => $this->vocabulary_id, 'left' => $this->mptt_left, 'right' => $this->mptt_right );
		$query = 'SELECT * FROM {terms} WHERE vocabulary_id=:vocab_id AND mptt_left NOT BETWEEN :left AND :right ORDER BY mptt_left ASC';
		return DB::get_results( $query, $params, 'Term' );
	}

	/**
	 * Test a Term's lineage.
	 * @return boolean true if $term is an ancestor of $this
	 */
	public function is_descendant_of( Term $term )
	{
		if ( $this->vocabulary_id != $term->vocabulary_id ) {
			return false;
		}
		if ( ( $this->mptt_left > $term->mptt_left ) && ( $this->mptt_right < $term->mptt_right ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Test a Term's ancestry
	 * @return boolean true if $term is a descendant of $this
	 */
	public function is_ancestor_of( Term $term )
	{
		if ( $this->vocabulary_id != $term->vocabulary_id ) {
			return false;
		}
		if ( ( $this->mptt_left < $term->mptt_left ) && ( $this->mptt_right > $term->mptt_right ) ) {
			return true;
		}
		return false;
	}

	/**
	 * The Term that is this Term's parent in hierarchy.
	 * @return Term This Term's parent
	 */
	public function parent()
	{
		$params = array( 'vocab_id' => $this->vocabulary_id, 'left' => $this->mptt_left, 'right' => $this->mptt_right );
		$query = 'SELECT * FROM {terms} WHERE vocabulary_id=:vocab_id AND mptt_left<:left AND mptt_right>:right ORDER BY mptt_left DESC LIMIT 1';
		return DB::get_row( $query, $params, 'Term' );
	}

	/**
	 * Find this Term's siblings.
	 * @return Array of all siblings including self.
	 */
	public function siblings()
	{
		$parent = $this->parent();
		if ( $parent ) {
			return $parent->children();
		}
		else {
			return $this->vocabulary->get_root_terms();
		}
	}

	/**
	 * Find this Term's children.
	 * @return Array of all direct children (compare to descendants()).
	 */
	public function children()
	{
		$params = array( 'vocab' => $this->vocabulary_id,
			'left' => $this->mptt_left,
			'right' => $this->mptt_right
		);
		/**
		 * If we INNER JOIN the terms table with itself on ALL the descendants of our term,
		 * then descendants one level down are listed once, two levels down are listed twice,
		 * etc. If we return only those terms which appear once, we get immediate children.
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
WHERE parent.mptt_left > :left AND parent.mptt_right < :right
	AND parent.vocabulary_id = :vocab
GROUP BY child.term
HAVING COUNT(child.term)=1
ORDER BY mptt_left
SQL;
		return new Terms(DB::get_results( $query, $params, 'Term' ));
	}

	/**
	 * Find objects of a given type associated with this Term.
	 *
	 * @param $type string. The name of the object type for which the associations are wanted.
	 * @return Array of object ids associated with this term for the given type.
	 */
	public function objects( $type )
	{
		$type_id = Vocabulary::object_type_id( $type );
		$results = DB::get_column( "SELECT object_id FROM {object_terms} WHERE term_id = ? AND object_type_id = ?", array( $this->id, $type_id ) );
		return $results;
	}

	/**
	 * Find the types of objects associated with this Term.
	 *
	 * @return Array of objects, with each object containing an object id and an object type name
	 */
	public function object_types()
	{
		$results = DB::get_results( 'SELECT terms.object_id, types.name as `type` FROM {object_terms} terms, {object_types} types WHERE terms.object_type_id = types.id and term_id = :term_id', array( 'term_id' => $this->id ) );
		return $results;
	}

	/**
	 * Find the count of objects of a given type associated with this Term.
	 *
	 * @param $type string. The name of the object type for which the associations are wanted.
	 * @return Array of object ids associated with this term for the given type.
	 */
	public function object_count( $type )
	{
		$type_id = Vocabulary::object_type_id( $type );
		$result = DB::get_value( "SELECT count(object_id) FROM {object_terms} WHERE term_id = ? AND object_type_id = ?", array( $this->id, $type_id ) );
		return $result;
	}

	/**
	 * Associate this term to an object of a certain type via its id.
	 * @param $type string. The name of the object type we want to set an association for
	 * @param $id integer. The object's id
	 *
	 */
	public function associate( $type, $id )
	{
		$result = true;
		$type_id = Vocabulary::object_type_id( $type );

		Plugins::act( 'term_associate_to_object_before', $this->id, $id, $type_id );

		if ( ! DB::exists( "{object_terms}", array( 'term_id' => $this->id, 'object_id' => $id, 'object_type_id' => $type_id ) ) ) {
			$result = DB::insert( "{object_terms}", array( 'term_id' => $this->id, 'object_id' => $id, 'object_type_id' => $type_id ) );
		}

		Plugins::act( 'term_associate_to_object_after', $this->id, $id, $type_id );

		return $result;
	}

	/**
	 * Disassociate this term from an object of a certain type via its id.
	 * @param $type string. The name of the object type we want to unset an association for
	 * @param $id integer. The object's id
	 *
	 */
	public function dissociate( $type = null, $id = null )
	{
		$result = true;

		$type_id = Vocabulary::object_type_id( $type );
		Plugins::act( 'term_dissociate_from_object_before', $this->id, $id, $type_id );

		$result = DB::query( "DELETE FROM {object_terms} WHERE term_id = ? AND object_id = ? AND object_type_id = ?", array( $this->id, $id, $type_id ) );

		Plugins::act( 'term_dissociate_from_object_after', $this->id, $id, $type_id, $result );

		return $result;
	}

	/**
	 * Allow output when the term is cast to a string
	 * @return string The terms display text
	 *
	 */
	public function __tostring()
	{
		return $this->term_display;
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param $name string Name of property to return
	 * @return mixed The requested field value
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'vocabulary':
				$out = Vocabulary::get_by_id( $this->vocabulary_id );
				break;
			case 'tag_text_searchable':
				// if it's got spaces, then quote it.
				if ( strpos( $this->term_display, ' ' ) !== false || strpos( $this->term_display, ',' ) !== false ) {
					$out = '\'' . str_replace( "'", "\'", $this->term_display ) . '\'';
				}
				else {
					$out = $this->term_display;
				}
				break;
			case 'count':
				$out = (int)$this->count();
				break;
			case 'id':
				return (int)parent::__get( $name );
			case 'info':
				return $this->get_info();
			default:
				$out = parent::__get( $name );
				break;
		}
		return $out;
	}

	/**
	 * Gets the info object for this term, which contains data from the terminfo table
	 * related to this term.
	 * @return TermInfo object
	 */
	protected function get_info()
	{
		if ( ! $this->inforecords ) {
			if ( 0 == $this->id ) {
				$this->inforecords = new TermInfo();
			}
			else {
				$this->inforecords = new TermInfo( $this->id );
			}
		}
		return $this->inforecords;
	}

	/**
	 * Get a count of how many times the tag has been used in a post
	 * @param string $object_type The type of object to count	 
	 * @return integer The number of times the tag has been used
	 */
	public function count( $object_type = 'post' )
	{
		return $this->object_count( $object_type );
	}

	/**
	 * Handle calls to this Term object that are implemented by plugins
	 * @param string $name The name of the function called
	 * @param array $args Arguments passed to the function call
	 * @return mixed The value returned from any plugin filters, null if no value is returned
	 */
	public function __call( $name, $args )
	{
		array_unshift( $args, 'term_call_' . $name, null, $this );
		return call_user_func_array( array( 'Plugins', 'filter' ), $args );
	}

	/**
	 * Gets the term object by criteria.
	 * @param mixed $term A Term object, a string (for a term slug or display), or an integer (for a Term ID).
	 * @param string $term_class The class of the returned term object.
	 * @return Term The Term object requested
	 * @todo improve selective fetching by term slug vs term_display
	 **/
	public static function get( $term, $term_class = 'Term' )
	{
		$query = '';
		if ( $term instanceof Term ) {
			// This seems kind of silly, but by passing a different $term_class, the 
			// database values are loaded into a different class instance and fire its constructor
			$params[ 'term_id' ] = $term->id;
			$query = 'SELECT * FROM {terms} WHERE id = ABS(:term_id)';
		}
		elseif ( is_string( $term ) ) {
			$params[ 'term' ] = $term;
			$query = 'SELECT * FROM {terms} WHERE (term = :term OR term_display = :term) ORDER BY term <> :term';
		}
		elseif ( is_int( $term ) ) {
			$params[ 'term_id' ] = $term;
			$query = 'SELECT * FROM {terms} WHERE id = ABS(:term_id)';
		}
		return DB::get_row( $query, $params, $term_class );
	}

	/**
	 * Make sure we have a valid term before inserting it in the database or updating it
	 * @return bool True if a valid term, false if not
	 */
	protected function is_valid()
	{
		if( strlen( trim( $this->term_display ) )  && strlen( trim( $this->term ) ) && $this->vocabulary_id != 0 ) {
			return true;
		}
		else {
			return false;
		}
	}
}

?>
