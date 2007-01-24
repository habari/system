<?php
/**
 * Habari InfoRecords Class
 *
 * Requires PHP 5.1 or later
 * Base class for managing metadata about various Habari objects
 * 
 * @package Habari
 */

abstract class InfoRecords extends ArrayObject {	
	
	protected $__inforecord_array; // the info array
	protected $_table_name;	// table which contains the info records
	protected $_key_name;	// name of the primary key in the info record table
	protected $_key_value;	// value of the primary key - the master record
	protected $_loaded;	// set to 1 only when the inforecords have been loaded

	/**
	 * constructor __construct
	 * Takes three parameters. The table of the options table, the name of the master key  and the record_id for which options are managed.
	 * posts take a slug as a record_id, comments take a comment_id and users take a user_id (user_name)
	 *
 	 *	IMPORTANT: if $primary_key_value is not set in the constructor, set_key MUST be called before inforecords can be set. Or bad things
	 * (think swarms of locusts o'er the land) will happen
	 *
	 * @param string name of the table to insert info (use the DB::o()->table_name syntax)
	 * @param string name of the primary key (for example "post_id")
	 * @param optional mixed the master record key value (for example, info for post_id = 1 managed by setting this param to 1). Use 
	 *		set_key method if not set here.
	 *
	 **/

	function __construct ( $table_name, $key_name, $key_value= null) 
	{	
		$this->_table_name = $table_name;
		$this->_key_name = $key_name;
		$this->_key_value = $key_value;
		$this->_loaded = 0;
	}
	
	/**
	 * function is_key_set
	 * Test if the master record value has been set (and thus, safe to set info records)
	 *
	 * @return boolean true if master record value has been set already, false otherwise
	 *
	 **/
	function is_key_set () 
	{
		return isset ( $this->_key_value);
	}

	/**
	 * function set_key
	 * For use in cases where the master record key is not known at the time of object instantiation (ie: a new post)
	 *
	 * @param mixed the id of the master record (could be int or string, most likely int)
	 *
	 **/
	
	function set_key( $metadata_key )
	{
		$this->_key_value= $metadata_key;
	}

	/**
	 * function __get
	 * Allows retrieval of option values for a specific metadata type
	 * @param string Name of the option to get
	 * @return mixed Stored value for specified option
	 **/

	public function __get ( $name )	
	{
		if ( 0 == $this->_loaded ) {  // check if info has been loaded previously, if not; load it up
			$result = DB::get_results('SELECT name, value, type FROM ' . $this->_table_name . ' WHERE ' . $this->_key_name . " = ?"
				, array($this->_key_value ) );
			
			foreach ( $result as $result_element ) {	
				if ( is_object( $result_element->value ) || is_array ( $result_element->value ) ) {
					if( $result_element->type == 1 ) {
						$this->__inforecord_array[$result_element->name]= unserialize($result->value);
					}
					else {						
						$this->__inforecord_array[$result_element->name]= $result_element->value;
					}
				}
				else {
					$this->__inforecord_array[$result_element->name]= $result_element->value;
				}
			}
			$this->_loaded= 1;
		}
		return $this->__inforecord_array[$name];
	}	

	/**
	 * function __set
	 * Applies the option value to the options table
	 * @param string Name of the option to set
	 * @param mixed Value to set
	 **/	 	 
	public function __set( $name, $value ) 
	{
		$this->__inforecord_array[$name]= $value;		

		if( is_array($value) || is_object($value)) {
			$result= DB::update( $this->_table_name, array($this->_key_name=>$this->_key_value, 'name'=>$name, 'value'=>serialize($value)
				, 'type'=>1), array('name'=>$name, $this->_key_name=>$this->_key_value)); 
		}			
		else {
			$result= DB::update( $this->_table_name, array($this->_key_name=>$this->_key_value, 'name'=>$name, 'value'=>$value, 'type'=>0)
				, array('name'=>$name, $this->_key_name=> $this->_key_value)	 ); 
		}
		if( Error::is_error($result) ) {
			$result->out();
			// die();
		}	
	}

	/**
	 * function __isset
	 * Tests for the existence of specified info value
	*
	*	__isset is only available from PHP 5.1 onwards.
	 * @param string Name of the option to set
	 *	@return boolean true if the info option exists, false in all other cases
	 **/	


	public function __isset ( $name )
	{
        if ( isset( $this->__inforecord_array[$name] ) ) {
            return true;
        }
        return false;
    }

	/**
	 * function __unset
	 * Removes info option; immediately unsets from the storage AND removes from database. Use with caution.
	*
	*	__unset is only available from PHP 5.1 onwards.
	 * @param string Name of the option to unset
	 *	@return boolean true if the option is successfully unset, false otherwise
	 **/	

    public function __unset( $name )
	{
        if ( isset($this->__inforecord_array[$name]) ) {			
			DB::delete ($this->_table_name, array ( $this->_key_name => $this->_key_value, "name"=> $name) );
			unset( $this->__inforecord_array[$name] );
			return true;
        } 
        return false;        
    }	

}
?>
