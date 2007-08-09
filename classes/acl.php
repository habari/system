<?php

/** 
 * Access Control List class
 *  
 * The default Habari ACL class implements groups, and group permissions
 * Users are assigned to one or more groups.
 * Groups are assigned one or more permissions.
 * Membership in any group that grants a permission
 * means you have that permission.  Membership in any group that denies
 * that permission denies the user that permission, even if another group
 * grants that permission.
 *     
 * @package Habari
 **/
 
class ACL {
	/**
	 * An array of users assigned to a specific group id.
	 * Both the group and the user are id integers, not string values.
	 * The user arrays have both the key and the value set to the user_id. 	 	 
	 * For example:
	 * <code>
	 * self::$groups= array(
	 * 	1 => array(1 => 1),
	 * 	2 => array(1 => 1, 2 => 2),
	 * );
	 * </code>
	 **/	 
	private static $groups= array();

	/**
	 * An array of group IDs with group names.
	 * For example:
	 * <code>
	 * self::$group_names= array(
	 * 	1 => 'administrators',
	 * 	2 => 'guests',
	 * );
	 * </code>	 
	 **/	 	 
	private static $group_names= array();

	/**
	 * An array of permssions assigned to a group.
	 * The group and permission are both id integers, not string values.
	 * The key of each permission array is the permission id.  
	 * The value of the permission id is boolean on whether to grant or deny that permission.
	 * For example:
	 * <code>
	 * self::$group_permissions= array(
	 * 	1 => array( 1 => true, 2 => true),
	 * 	2 => array( 2 => false),
	 * );
	 * </code>
	 **/	 	 	 	 	 	 	 	 	 	 	
	private static $group_permissions= array();

	/**
	 * An array in which specific user permissions are cached as they are built from the other structures.
	 **/	  		
	private static $user_permissions= array();
	
	/**
	 * An array of permissions, with the permission id as the key and the string value as the value.
	 **/	 	
	private static $permissions= array();
	
	/**
	 * How to handle a permission request for a permission that is not in the permission list.
	 * For example, if you request $user->can('some non-existant permission') then this value is returned.
	 * It's true at the moment because that allows access to all features for upgrading users.
	 * @todo Decide if this is a setting we need or want to change, or perhaps it should be an option.
	 **/	 	 	 	 	
	const ACCESS_NONEXISTANT_PERMISSION = true;

	/**
	 * Load all group and permission data from the DB
	 * __static() class members are called by __autoload()	 
	**/
	public static function __static()
	{
		$results= DB::get_results( 'SELECT * FROM ' . DB::table('groups') );
		foreach ($results as $group) {
			self::$group_names[$group->id]= $group->name;
		}
		$results= DB::get_results( 'SELECT * FROM ' . DB::table('permissions'));
		foreach ( $results as $permission ) {
			self::$permissions[$permission->id] = $permission->name;
		}
		$results= DB::get_results( 'SELECT * FROM ' . DB::table('users_groups') );
		foreach ( $results as $group ) {
			self::$groups[$group->group_id][$group->user_id]= $group->user_id;
		}
		$results= DB::get_results( 'SELECT * FROM ' . DB::table('groups_permissions'));
		foreach ( $results as $permission ) {
			self::$group_permissions[$permission->group_id][$permission->permission_id]= !$permission->denied;
		}
	}

	/**
	 * Determine whether the specified user is a member of the specified group
	 * @param int A user  ID
	 * @param int A group ID
	 * @return bool True if the user is in the group, otherwise false
	**/
	public static function user_in_group( $user_id, $group_id )
	{
		return isset(self::$groups[self::group_id( $group_id )][$user_id]);
	}

	/**
	 * Return an array of the groups to which this user belongs
	 * @param int A user ID
	 * @return array An array of group IDs
	**/
	public static function user_group_list( $user_id )
	{
		$user_id= intval($user_id);
		return array_keys( array_filter( self::$groups, create_function('$a', 'return isset($a[' . $user_id . ']);') ) );
	}

	/**
	 * Determine whether a group can perform a specific action
	 * @param int A group ID
	 * @param string An action
	 * @return bool Whether the group can perform the action
	**/
	public static function group_can( $group_id, $action )
	{
		return isset(self::$group_permissions[self::group_id( $group_id )][$action]) && self::$group_permissions[self::group_id( $group_id )][$action];
	}
	
	/**
	 * Return the id of a group, provided the name
	 * @param string $group_id The name of the group
	 * @return int The group id
	 **/
	public static function group_id( $group_id )
	{
		if(!is_numeric($group_id)) {
			$group_id = array_search($group_id, self::$group_names);
		}
		return $group_id;
	}	 	 	 	

	/**
	 * Determine whether a user can perform a specific action
	 * @param int A user ID
	 * @param string An action
	 * @return bool Whether the user can perform the action
	**/
	public static function user_can( $user_id, $action )
	{
		if(($permission_id = array_search($action, self::$permissions)) === false) {
			return ACL::ACCESS_NONEXISTANT_PERMISSION;
		}
		if(!isset(self::$user_permissions[$user_id])) {
			self::$user_permissions[$user_id] = array();
			foreach( self::user_group_list( $user_id ) as $group_id )	{
				foreach( self::$group_permissions[$group_id] as $action => $grant ) {
					if(isset(self::$user_permissions[$user_id][$permission_id])) {
						self::$user_permissions[$user_id][$permission_id]= self::$user_permissions[$user_id][$permission_id] && $grant;
					}
					else {
						self::$user_permissions[$user_id][$permission_id]= $grant;
					}
				}
			}
		}
		return isset(self::$user_permissions[$user_id][$permission_id]) && self::$user_permissions[$user_id][$permission_id];
	}
}
?>
