<?php
/**
 * Habari PostRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Post extends QueryRecord
{
	public function __construct($paramarray = array())
	{
		// Defaults
		$this->fields = array_merge(
			array(
				'slug' => '', 
				'title' => '', 
				'guid' => '', 
				'content' => '', 
				'author' => '', 
				'status' => 'draft', 
				'pubdate' => date('Y-m-d H:i:s'), 
				'updated' => ''
			),
			$this->fields
		);
		parent::__construct($paramarray);
	}

	/**
	 * function insert
	 * Saves a new post to the posts table
	 */	 	 	 	 	
	public function insert()
	{
		$this->newfields['updated'] = date('Y-m-d h:i:s');
		parent::insert( 'habari__posts' );
	}

	/**
	 * function update
	 * Updates an existing post in the posts table
	 */	 	 	 	 	
	public function update()
	{
		$this->newfields['updated'] = date('Y-m-d h:i:s');
		unset($this->newfields['guid']);
		parent::update( 'habari__posts', array('slug') );
	}
	
	/**
	 * function update
	 * Updates an existing post to published status
	 */	 	 	 	 	
	public function publish()
	{
		$this->newfields['status'] = 'publish';
		$this->newfields['updated'] = date('Y-m-d h:i:s');
		$this->update();
	}


}


?>