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
			'feature_mask' => 0,
		);
	}

	/**
	 * Vocabulary constructor
	 * Creates a Vocabulary instance
	 *
	 * @param string $name The name of the Vocabulary
	 * @param string $description A description of the Vocabulary
	 * @param BitMask $properties Properties of this Vocabulary
	 */
	public function __construct($name, $description, BitMask $properties)
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields
		);

		$paramarray = array(
			'name'=> $name,
			'description' => $description,
			'feature_mask' => $properties,
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
		if ( $name == 'feature_mask' && is_int($out) ) {
			$out = new Bitmask( self::$features, $out );
		}
		elseif ( in_array( $name, self::$features ) ) {
			$out = $this->feature_mask->$name;
		}
		return $out;
	}

	/**
	 * Return a Vocabulary by name.
	 * @return Vocabulary The requested vocabulary
	 **/
	public static function get($name)
	{
		// TODO Make this work; arguments are not correctly passed to the constructor
		// return DB::get_row( 'SELECT name FROM {vocabularies} WHERE name=?', array($name), 'Vocabulary' );

		$result = DB::get_row( 'SELECT DISTINCT * FROM {vocabularies} WHERE name=? LIMIT 1', array($name) );
		$v = new Vocabulary( $result->name, $result->description, new Bitmask(self::$features , $result->feature_mask) );
		$v->id = $result->id;
		return $v;
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
	 * Produce a BitMask for a feature mask. Convenience method for use when creating a Vocabulary.
	 * @return BitMask Mask representing the features of this vocabulary
	 **/
	public static function feature_mask($hierarchical, $required, $multiple, $free)
	{
		$features = array($hierarchical, $required, $multiple, $free);
		// Convert booleans to an integer
		// TODO this should be in the setter for Bitmask (but first Bitmask needs to be fixed)
		$mask = 0;
		for($z=0;$z<count($features);$z++){$mask += $features[$z]<<$z;}
		return new Bitmask( self::$features, $mask );
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

		// Store the feature mask as an integer
		if ( isset($this->newfields['feature_mask']) && $this->newfields['feature_mask'] instanceOf Bitmask ) {
			$this->newfields['feature_mask'] = $this->newfields['feature_mask']->value;
		}

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_insert_allow', $allow, $this );
		if ( !$allow ) {
			return false;
		}
		Plugins::act( 'vocabulary_insert_before', $this );

		$result = parent::insertRecord( DB::table( 'vocabularies' ) );

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
	 * @param bool $minor Indicates if this is a major or minor update
	 */
	public function update()
	{
		// Don't allow duplicate vocabularies
		if ( isset($this->newfields['name']) && Vocabulary::exists($this->newfields['name']) ) {
			return false;
		}

		// Store the feature mask as an integer
		if ( isset($this->newfields['feature_mask']) && $this->newfields['feature_mask'] instanceOf Bitmask ) {
			$this->newfields['feature_mask'] = $this->newfields['feature_mask']->value;
		}

		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_update_allow', $allow, $this );
		if ( !$allow ) {
			return;
		}
		Plugins::act( 'vocabulary_update_before', $this );

		$result = parent::updateRecord( DB::table( 'vocabularies' ), array( 'id' => $this->id ) );

		// Let plugins act after we write to the database
		Plugins::act( 'vocabulary_update_after', $this );

		return $result;
	}

	/**
	 * Delete an existing vocabulary
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'vocabulary_delete_allow', $allow, $this );
		if ( !$allow ) {
			return;
		}
		// invoke plugins
		Plugins::act( 'vocabulary_delete_before', $this );

		// TODO Delete all terms associated with this vocabulary

		$result = parent::deleteRecord( '{vocabularies}', array( 'id'=>$this->id ) );
		EventLog::log( sprintf(_t('Vocabulary %1$s (%2$s) deleted.'), $this->id, $this->name), 'info', 'content', 'habari' );

		// invoke plugins on the after_vocabulary_delete action
		Plugins::act( 'vocabulary_delete_after', $this );
		return $result;
	}

	/**
	 * Adds a term to the vocabulary. Returns a Term object. null parameters append the term to the end of any hierarchies.
	 * @return Term The Term object added
	 **/
	public function add_term($term, $parent_term = null, $before_term = null)
	{
	}

	/**
	 * Gets the term object for that string. No parameter returns the root Term object.
	 * @return Term The Term object requested
	 **/
	public function get_term($term)
	{
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
	 * @return Array The Term objects requested
	 **/
	public function delete_term($term)
	{
	}

}

?>
