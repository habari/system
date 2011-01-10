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
	function __construct ( $post_id = NULL )
	{
		parent::__construct ( DB::table('postinfo'), "post_id", $post_id ); // call parent with appropriate  parameters
	}
	
	public function __get ( $name ) {
		
		$value = parent::__get( $name );
		
		$value = Plugins::filter( "post_info_{$name}", $value );
		
		return $value;
		
	}
}
?>
