<?php
/**
 * @package Habari
 *
 */

/**
 * XMLRPC Struct type
 * Used to hold struct types (objects) that are returned in XMLRPC requests.
 *
 */
class XMLRPCStruct
{
	private $fields = array();
	
	/**
	 * Property setter for the XMLRPCStruct class.
	 * This allows the following:
	 * <code>
	 * $struct = new XMLRPCStruct();
	 * $struct->foo = 'bar'; // This is done by __set() and assigns 'bar' into $this->fields['foo']
	 * </code>
	 *
	 * @param string $name The name of the property on this object to set
	 * @param mixed $value The value to set in the property
	 * @return
	 */
	public function __set( $name, $value )
	{
		$this->fields[$name] = $value;
	}
	
	/**
	 * Property getter for the XMLRPCStruct class.
	 * Returns the value of $this->fields for the specified porperty name.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value stored for the requested property
	 */
	public function __get( $name )
	{
		return $this->fields[$name];
	}
	
	/**
	 * Magic isset for XMLRPCStruct, returns whether a property value is set.
	 * @param string $name The name of the parameter
	 * @return boolean True if the value is set, false if not
	 */
	public function __isset( $name )
	{
		return isset( $this->fields[$name] );
	}
	
	/**
	 * Get the list of properties that this object contains.
	 *
	 * @return array List of object properties, as stored in $this->fields.
	 */
	public function get_fields()
	{
		return array_keys( $this->fields );
	}
	
	/**
	 * Constructor for XMLRPCStruct
	 *
	 * @param array $fields Default field values to set into properties.
	 */
	public function __construct( $fields = array() )
	{
		$this->fields = $fields;
	}
}

?>
