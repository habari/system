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
			'required' => 0,
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
			$this->fields,
			$this->newfields
		);

		$paramarray = array(
			'name'=> $name,
			'description' => $description,
			'required' => $properties->value,
		);

		parent::__construct( $paramarray );

		$this->exclude_fields( 'id' );
	}

	/**
	 * Return a Vocabulary by name.
	 * @return Vocabulary The requested vocabulary
	 **/
	public static function get($name)
	{
	}

	/**
	 * Rename a Vocabulary.
	 * @return boolean true if the Vocabulary was renamed, false otherwise
	 **/
	public static function rename($name, $newname)
	{
	}

	/**
	 * Return the names of all vocabularies
	 * @return array Array of Vocabulary names
	 **/
	public static function names()
	{
		return array();
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
		// TODO Set this according to what was passed in. Currently sets everything to hierarchical only.
		return new Bitmask( self::$features, 16 );
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
