<?php
/**
 * @package Habari
 *
 */

/**
 * Term metadata
 */
class TermInfo extends InfoRecords
{

	function __construct ( $term_id = null )
	{
		parent::__construct( DB::table( 'terminfo' ), 'term_id', $term_id ); // call parent with appropriate  parameters
	}

}

?>
