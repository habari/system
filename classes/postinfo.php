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
		if ( strpos( $name, '_' ) !== false ) {
			// pick off the last _'d piece
			preg_match( '/^(.*)_([^_]+)$/', $name, $matches );
			list( $junk, $name, $filter ) = $matches;
			
			// so that we don't break every info value that has a _ in it, only _out is an acceptable filter name
			if ( $filter != 'out' ) {
				// put it back together
				$name = $name . '_' . $filter;
				
				// turn off the filter
				$filter = false;
			}
			
		}
		else {
			$filter = false;
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
