<?php

/**
 * Habari InfoRecords Class
 *
 * Requires PHP 5.1 or later
 * Base class for managing metadata about various Habari objects
 * 
 * @package Habari
 */
abstract class InfoRecords extends ArrayObject
{	
	// the info array
	protected $__inforecord_array;
	// table which contains the info records
	protected $_table_name;
	// name of the primary key in the info record table
	protected $_key_name;
	// value of the primary key - the master record
	protected $_key_value;
	// set to TRUE only when the inforecords have been loaded
	protected $_loaded= FALSE;

	/**
	 * Takes three parameters. The table of the options table, the name of the master key  and the record_id for which options are managed.
	 * posts take a slug as a record_id, comments take a comment_id and users take a user_id (user_name)
	 *
	 * <b>IMPORTANT:</b> if <tt>$primary_key_value</tt> is not set in the constructor, set_key MUST be called before inforecords can be set. Or bad things
	 * (think swarms of locusts o'er the land) will happen
	 *
	 * @param string $table_name name of the table to insert info (use the DB::o()->table_name syntax)
	 * @param string $key_name name of the primary key (for example "post_id")
	 * @param mixed $key_value (optional) the master record key value (for example, info for post_id = 1 managed by setting this param to 1). Use 
	 *		set_key method if not set here.
	 **/
	public function __construct( $table_name, $key_name, $key_value= NULL ) 
	{	
		$this->_table_name= $table_name;
		$this->_key_name= $key_name;
		$this->_key_value= $key_value;
		$this->_loaded= FALSE;
	}
	
	/**
	 * Test if the master record value has been set (and thus, safe to set info records).
	 *
	 * @return boolean TRUE if master record value has been set already, FALSE otherwise
	 **/
	public function is_key_set() 
	{
		return isset( $this->_key_value );
	}

	/**
	 * function set_key
	 * For use in cases where the master record key is not known at the time of object instantiation (ie: a new post)
	 *
	 * @param mixed $metadata_key the id of the master record (could be int or string, most likely int)
	 *
	 **/
	public function set_key( $metadata_key )
	{
		$this->_key_value= $metadata_key;
	}
	
	/**
	 * Populate the internal hashmap with the values from the DB.
	 */
	protected function _load()
	{
		if ( $this->_loaded == TRUE ) {
			return;
		}
		 
		$result= DB::get_results( '
			SELECT name, value, type
			FROM ' . $this->_table_name . '
			WHERE ' . $this->_key_name . ' = ?',
			array( $this->_key_value )
		);
		
		foreach ( $result as $result_element ) {
			// XXX is this logic right?	
			if ( $result_element->type == 1 ) {
				$this->__inforecord_array[$result_element->name]= array('value'=>unserialize($result_element->value));
			}
			else {						
				$this->__inforecord_array[$result_element->name]= array('value'=>$result_element->value);
			}
		}
		
		$this->_loaded= TRUE;
	}

	/**
	 * Fetch info record value.
	 * @param string $name Name of the key to get
	 * @return mixed Stored value for specified key
	 **/
	public function __get ( $name )	
	{
		$this->_load();
		return $this->__inforecord_array[$name]['value'];
	}	

	/**
	 * Update the info record.  
	 * The value will not be stored in the database until calling $this->commit();
	 * 
	 * @param string $name Name of the key to set
	 * @param mixed $value Value to set
	 **/	 	 
	public function __set( $name, $value ) 
	{
		$this->_load();
		$this->__inforecord_array[$name]= array('changed'=>true, 'value'=>$value);		
	}

	/**
	 * Test for the existence of specified info value
	 * 
	 * NOTE: __isset is only available from PHP 5.1 onwards.
	 * 
	 * @param string $name Name of the option to set
	 * @return boolean TRUE if the info option exists, FALSE in all other cases
	 **/	
	public function __isset ( $name )
	{
		$this->_load();
		return isset( $this->__inforecord_array[$name] );
	}

	/**
	 * Remove an info option; immediately unsets from the storage AND removes from database. Use with caution.
	 * 
	 * NOTE: __unset is only available from PHP 5.1 onwards.
	 * 
	 * @param string $name Name of the option to unset
	 * @return boolean TRUE if the option is successfully unset, FALSE otherwise
	 **/	
		public function __unset( $name )
	{
		$this->_load();
		if ( isset( $this->__inforecord_array[$name] ) ) {			
			DB::delete( $this->_table_name, array ( $this->_key_name => $this->_key_value, "name"=> $name ) );
			unset( $this->__inforecord_array[$name] );
			return true;
		}
		return false;        
	}	
	
	/**
	 * Remove all info options. Primarily used when deleting the parent object. 
	 * I.E. when deleting a user, the delete method would call this.
	 * 
	 * @return boolean TRUE if the options were successfully unset, FALSE otherwise
	 **/	
	public function delete_all()
	{
		$result= DB::query( '
			DELETE FROM ' . $this->_table_name . '
			WHERE ' . $this->_key_name . ' = ?',
			array( $this->_key_value )
		);
		if ( Error::is_error( $result ) ) {
			$result->out();
			return false;
		} 
		$this->__inforecord_array = array();
		return true;
	}
	
	/**
	 * Commit all of the changed info options to the database.
	 * If this function is not called, then the options will not be written.
	 * 
	 * @param mixed $metadata_key (optional) Key to use when writing info data.
	 */
	public function commit( $metadata_key = null )
	{
		if ( !$this->_loaded ) {
			return true;
		}
		if ( isset( $metadata_key ) ) {
			$this->_key_value= $metadata_key;
		}

		foreach( $this->__inforecord_array as $name=>$record ) {
			if( isset( $record['changed'] ) && $record['changed'] ) {
				$value = $record['value'];
				if ( is_array( $value ) || is_object( $value ) ) {
					$result= DB::update( 
						$this->_table_name, 
						array(
							$this->_key_name=>$this->_key_value, 
							'name'=>$name, 
							'value'=>serialize($value),
							'type'=>1
						), 
						array('name'=>$name, $this->_key_name=>$this->_key_value)
					); 
				}			
				else {
					$result= DB::update( 
						$this->_table_name, 
						array(
							$this->_key_name=>$this->_key_value, 
							'name'=>$name, 
							'value'=>$value, 
							'type'=>0
						), 
						array('name'=>$name, $this->_key_name=> $this->_key_value)	 
					); 
				}
				
				if ( Error::is_error( $result ) ) {
					$result->out();
				}
				$this->__inforecord_array[$name] = array('value'=>$value);	
			}
		}
	}
	
}

?>