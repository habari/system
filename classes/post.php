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
				'updated' => date('Y-m-d H:i:s')
			),
			$this->fields
		);
		parent::__construct($paramarray);
	}
	
	private function setslug()
	{
		global $db;
		if($this->fields['slug'] != '' && $this->fields['slug'] == $this->newfields['slug']) {
			$value = $this->fields['slug'];
		}
		elseif($this->newfields['slug'] != '') {
			$value = $this->newfields['slug'];
		}
		elseif(($this->fields['slug'] != '')) {
			$value = $this->fields['slug'];
		}
		elseif ($this->newfields['title'] != '') {
			$value = $this->newfields['title'];
		}
		elseif ($this->fields['title'] != '') {
			$value = $this->fields['title'];
		}
		else {
			$value = 'Post';
		}
		
		$slug = strtolower(preg_replace('/[^a-z]+/i', '-', $value));
		$postfix = '';
		$postfixcount = 0;
		do {
			$slugcount = $db->get_row("SELECT count(slug) AS ct FROM habari__posts WHERE slug = ?;", array("{$slug}{$postfix}"));
			if($slugcount->ct != 0) $postfix = "-" . (++$postfixcount);
		} while ($slugcount->ct != 0);
		$this->newfields['slug'] = $slug . $postfix;
		return $this->newfields['slug'];
	}


	/**
	 * function insert
	 * Saves a new post to the posts table
	 */	 	 	 	 	
	public function insert()
	{
		$this->newfields['updated'] = date('Y-m-d h:i:s');
		$this->setslug();
		parent::insert( 'habari__posts' );
	}

	/**
	 * function update
	 * Updates an existing post in the posts table
	 */	 	 	 	 	
	public function update()
	{
		$this->updated = date('Y-m-d h:i:s');
		if(isset($this->fields['guid'])) unset($this->newfields['guid']);
		$this->setslug();
		parent::update( 'habari__posts', array('slug'=>$this->slug) );
	}
	
	/**
	 * function publish
	 * Updates an existing post to published status
	 */	 	 	 	 	
	public function publish()
	{
		$this->status = 'publish';
		$this->update();
	}


}


?>
