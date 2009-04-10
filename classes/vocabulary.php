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
	/**
	 * Return the defined database columns for a Post.
	 * @return array Array of columns in the Post table
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
	
}

?>