<?php
/**
 * @package Habari
 *
 */


/** 
 * Habari Block class
 * Block class for theme display of pluggable content
 *
 * @todo Finish this class
 */
class Block extends QueryRecord implements IsContent
{
	private $unserialized_data = false;
	private $datakeys = array();

	/**
	 * Constructor for the Block class.
	 * @param array $paramarray an associative array of initial block field values.
	 **/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields,
			$this->newfields
		);
		parent::__construct( $paramarray );

		$this->exclude_fields( 'id' );
		$this->unserialize_data();
	}

	/**
	 * Return the defined database columns for a Block.
	 * @return array Array of columns in the Block table
	 **/
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'title' => '',
			'data' => '',
			'type' => '',
		);
	}
	
	/**
	 * Render and return the block content 
	 * 
	 * @param Theme $theme the theme object with which the block will be rendered
	 * @return string The rendered block content
	 */
	public function fetch($theme)
	{
		Plugins::act('block_content_' . $block->type, $block);
		$output .= implode( '', $theme->content_return($block));
		return $output;		
	}
	
	/**
	 * Return the content types that this object represents
	 * 
	 * @see IsContent
	 * @return array An array of strings representing the content type of this object
	 */
	public function content_type()
	{
		return array(
			'block.' . $this->type,
			'block',
		);
	}
			
	public function unserialize_data()
	{
		if(!$this->unserialized_data) {
			$this->unserialized_data = true;
			if(trim($this->data) != '') {
				$data = unserialize($this->data);
				$this->datakeys = array_keys($data);
				foreach($data as $key => $value) {
					$this->$key = $value;
				}
			}
		}
	}
	
}


?>