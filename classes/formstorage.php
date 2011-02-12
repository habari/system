<?php

/**
 * interface FormStorage
 *
 * Describes functions that are required to store form data into an object
 */

interface FormStorage
{
	/**
	 * Stores a form value into the object
	 * 
	 * @param string $key The name of a form component that will be stored
	 * @param mixed $value The value of the form component to store
	 */
	function field_save( $key, $value );
	
	
	/**
	 * Loads form values from an object
	 * 
	 * @param string $key The name of a form component that will be loaded
	 * @return mixed The stored value returned
	 */
	function field_load( $key );
}

?>
