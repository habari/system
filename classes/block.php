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
	
}


?>