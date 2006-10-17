<?php
/**
 * Habari UserRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class User extends QueryRecord
{
	public function __construct($paramarray = array())
	{
		// Defaults
		$this->fields = array_merge(
			array(
				'username' => '', 
				'email' => '', 
				'password' => ''
			),
			$this->fields
		);
		parent::__construct($paramarray);
	}
	
	/**
	 * function insert
	 * Saves a new user to the users table
	 */	 	 	 	 	
	public function insert()
	{
		parent::insert( 'habari__users' );
	}

	/**
	 * function update
	 * Updates an existing post in the posts table
	 */	 	 	 	 	
	public function update()
	{
		parent::update( 'habari__users' );
	}

	/**
	* function remember
	* sets a cookie on the client machine for future logins
	*/
	public function remember()
	{
		// set the cookie
	}

	/** function forget
	* delete a cookie from the client machine
	*/
	public function forget()
	{
		// delete the cookie
	}
	
}


?>
