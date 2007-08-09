<?php

/**
 * Habari ACL (Access Control List) Class
 * 
 * @package habari
 * @version 2007 beta
 *
 */


class ACL {
	private $user_id;
	private static $permission_cache= null;

	/**
	 * Constructor for ACL class.
	 * One instance per user to contain their permissions.
	 */
	public function __construct( $user_id )
	{
		$this->user_id= $user_id;
	}
	
	/**
	 * Grant a permission to this user
	 * @param string $permission The permission to grant
	 * @param string $context optional The context to which the permission applies
	 */
	public function grant( $permission, $context= '' )
	{
		// Set permission in cache and commit to DB
	}

	/**
	 * Explicitly deny a permission to this user
	 * @param string $permission The permission to deny
	 * @param string $context optional The context to which the permission applies
	 */
	public function deny( $permission, $context= '' )
	{
		// Set permission in cache and commit to DB
	}

	/**
	 * Remove a permission from this user, whether granted or denied
	 * @param string $permission The permission to remove
	 * @param string $context optional The context to which the permission applies
	 */
	public function remove( $permission, $context= '' )
	{
		// Set permission in cache and commit to DB
	}
	
	/**
	 * 
	 * @param string $permission ...
	 * @param string $context (default '') ...
	 * @return boolean Whether the current user has the $permission permission
	 */
	public function can( $permission, $context= '')
	{
		ACL::build_cache();
		if ( !array_key_exists( self::permission_cache, $this->user_id ) ) {
			return false;  // User doesn't have any permissions
		}
		$permissions= self::permission_cache[$this->user_id];
		// boolean $permissions[$permission][$context]
		if ( !array_key_exists( $permissions, $permission ) ) {
			return false; // permission does not exist
		}
		elseif ( !array_key_exists( $permissions[$permission], $context ) ) {
			return false;  // User doesn't have this permission
		}
		return $permissions[$permission][$context];  // true if granted, false if denied
	}
	
	/**
	 * @param int $user_id The ID of the user to check
	 * @param string $permission ...
	 * @param string $context (default '') ...
	 * @return boolean Whether the given user has the $permission permission
	 */
	public static function user_can( $user_id, $permission, $context= '')
	{
		$user_acl= new ACL( $user_id ); 
		return $user_acl->can( $permission, $context ); 
	}

	public static function build_cache()
	{
		if ( empty( self::permission_cache ) ) {
			self::permission_cache= DB::do_something();
		}
	
	}

}

?>