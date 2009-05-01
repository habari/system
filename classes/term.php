<?php

/**
 * Term Class
 *
 *
 * @version $Id$
 * @copyright 2009
 */

class Term extends QueryRecord
{
	// static variable to hold the vocabulary this term belongs to
	private $vocabulary = null;

	/**
	 * Return the defined database columns for a Term.
	 * @return array Array of columns in the Term table
	 **/
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
	 * function insert
	 * Saves a new term to the terms table
	 */
	public function insert()
	{
		// Let plugins disallow and act before we write to the database
		$allow = true;
		$allow = Plugins::filter( 'term_insert_allow', $allow, $this );
		if ( !$allow ) {
			return false;
		}
		Plugins::act( 'term_insert_before', $this );

		$result = parent::insertRecord( '{terms}' );

		// Make sure the id is set in the term object to match the row id
		$this->newfields['id'] = DB::last_insert_id();

		// Update the term's fields with anything that changed
		$this->fields = array_merge( $this->fields, $this->newfields );

		// We've inserted the term, reset newfields
		$this->newfields = array();

		EventLog::log( sprintf(_t('New term %1$s (%2$s)'), $this->id, $this->name), 'info', 'content', 'habari' );

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
		if ( !$allow ) {
			return;
		}
		Plugins::act( 'term_update_before', $this );

		$result = parent::updateRecord( '{terms}', array( 'id' => $this->id ) );

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

		$result = parent::deleteRecord( '{terms}', array( 'id'=>$this->id ) );
		EventLog::log( sprintf(_t('Term %1$s (%2$s) deleted.'), $this->id, $this->name), 'info', 'content', 'habari' );

		// Let plugins act after we write to the database
		Plugins::act( 'term_delete_after', $this );
		return $result;
	}

	/**
	 * Find this Term's ancestors.
	 * @return Array Direct ancestors from the root to this Term in descendant order.
	 **/
	public function ancestors()
	{
	}

	/**
	 * Find this Term's descendants.
	 * @return Array of all descendants in MPTT left-to-right order.
	 **/
	public function descendants()
	{
	}

	/**
	 * The Term that is this Term's parent in hierarchy.
	 * @return Term This Term's parent
	 **/
	public function parent()
	{
	}

	/**
	 * Find this Term's siblings.
	 * @return Array of all siblings including self in MPTT left-to-right order.
	 **/
	public function siblings()
	{
	}

	/**
	 * Find this Term's children.
	 * @return Array of all direct children (compare to descendants()) in MPTT left-to-right order.
	 **/
	public function children()
	{
	}

	/**
	 * Find objects of a given type associated with this Term.
	 *
	 * @return Array of object ids associated with this term for the given type.
	 **/
	public function objects($type)
	{
	}

	/**
	 * Associate this term to an object of a certain type via its id.
	 *
	 **/
	public function associate($type, $id)
	{
	}

}



?>
