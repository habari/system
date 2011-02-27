<?php
/**
 * @package Habari
 *
 */

/**
 * Post metadata
 */
class PostInfo extends InfoRecords
{
	function __construct( $post_id = null )
	{
		parent::__construct( DB::table( 'postinfo' ), "post_id", $post_id ); // call parent with appropriate  parameters
	}
	
	public function __get( $name )
	{
		
		// if there is a _ in the name, there is a filter at the end
		$filter = false;
		$fieldnames = array_keys($this->__inforecord_array);
		if ( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			// pick off the last _'d piece
			$field_matches = implode('|', $fieldnames);
			if(preg_match( '/^(' . $field_matches . ')_(.+)$/', $name, $matches )) {
				list( $junk, $name, $filter ) = $matches;
			}
		}
		
		// get the value by calling our parent function directly
		$value = parent::__get( $name );
		
		// apply the main filter so values can be altered regardless of any _filter
		$value = Plugins::filter( "post_info_{$name}", $value );
		
		// if there is a filter, apply that specific one too
		if ( $filter ) {
			$value = Plugins::filter( "post_info_{$name}_{$filter}", $value );
		}
		
		return $value;
		
	}
}
?>
