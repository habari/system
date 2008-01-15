<?php
/**
* Habari UserGroup Class
* @package Habari
**/
class UserGroup
{
        /**
         * An array of users assigned to a specific group id.
         * Both the group and the user are id integers, not string values.
         * The user arrays have both the key and the value set to the user_id.  
         *
         * For example:
         * <code>
         * self::$groups= array(
         *      1 => array(1 => 1),
         *      2 => array(1 => 1, 2 => 2),
         * );
         * </code>
         **/     
        private static $groups= array();

        /**
         * An array of group IDs with group names.
         * For example:
         * <code>
         * self::$group_names= array(
         *      1 => 'administrators',
         *      2 => 'guests',
         * );
         * </code>       
         **/             
        private static $group_names= array();

        /**
         * An array of permssions assigned to a group.
         * The group and permission are both id integers, not string values.
         * The key of each permission array is the permission id.  
         * The value of the permission id is boolean on whether to grant or deny
				 * that permission.
				 *
         * For example:
         * <code>
         * self::$group_permissions= array(
         *      1 => array( 1 => true, 2 => true),
         *      2 => array( 2 => false),
         * );
         * </code>
         **/
	private static $group_permissions= array();

	/**
	 * __static() class members are called by __autoload()
	**/
	public static function __static()
	{
		self::load_groups();
	}

	/**
	 * Load all group data from the DB
	**/
	public static function load_groups ()
	{
		self::$group_names= array();
		$results= DB::get_results( 'SELECT * FROM {groups}' );
		natsort( $results );
		foreach ($results as $group) {
			self::$group_names[$group->id]= $group->name;
		}
		self::$groups= array();
		$results= DB::get_results( 'SELECT * FROM {users_groups}' );
		foreach ( $results as $group ) {
			self::$groups[$group->group_id][$group->user_id]= $group->user_id;
		}
	}

	/**
	 * function all_groups
	 * Returns an array of all the groups, in the form id => name
	 * @return array an array of group id => name
	**/
	public static function all_groups()
	{
		return self::$group_names;
	}

	/**
	 * function add_group
	 * Adds a new group to the Groups table
	 * @param string The name of the group to add
	 * @return bool Whether the group was added or not
	**/
	public static function add_group( $group )
	{
		if ( in_array( $group, self::$group_names ) ) {
			Session::notice( _t( 'That group already exists.' ) );
			return false;
		}
		$allow= true;
		$allow= Plugins::filter('usergroup_insert_allow', $allow );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('usergroup_add_before');
		$results= DB::query( 'INSERT INTO {groups} (name) VALUES (?)', array( $group ) );
		Plugins::act('usergroup_add_after');
		self::load_groups();
		return true;
	}

	/**
	 * function remove_group
	 * Remove a group from the Groups table.  Also removes all users_groups members of the group.
	 * @param mixed A Group name or integer ID
	 * @return bool Whether the group was removed or not
	**/
	public static function remove_group( $group )
	{
		if ( is_int( $group ) ) {
			if ( ! array_key_exists( $group, self::$group_names ) ) {
				Session::notice( _t('That group does not exist.' ) );
				return false;
			}
		} else {
			if ( ! in_array( $group, self::$group_names ) ) {
				Session::notice( _t('That group does not exist.' ) );
				return false;
			}
			$groups= array_flip( self::$group_names );
			$group= $groups[ $group ];
		}
		$allow= true;
		$allow= Plugins::filter('usergroup_insert_allow', $allow );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('usergroup_remove_before');
		// delete the group
		$results= DB::query( 'DELETE FROM {groups} WHERE id=?', array( $group) );
		// and delete any members assigned to the group
		$results= DB::query( 'DELETE FROM {users_groups} WHERE group_id=?', array( $group ) );
		Plugins::act('usergroup_remove_after');
		self::load_groups();
		return true;
	}

	/**
	 * function members
	 * returns an array of user IDs belogning to the specified group
	 * @param int a group ID
	 * @return array an array of user IDs
	**/
	public static function members( $group )
	{
		$members= array();
		if ( isset( self::$groups[ intval($group) ] ) ) {
			$members= self::$groups[ intval($group) ];
		}
		return $members;
	}
	
	/**
	 * Add a user to a group
	 * @param int a group ID
	 * @param int a user ID
	**/
	public static function add_user( $group, $id )
	{
		if ( empty( self::$groups[ intval( $group) ] ) || ! in_array( self::$groups[ intval($group) ], $id ) )
		{
			$results= DB::query( 'INSERT INTO ' . DB::table('users_groups') . ' (group_id, user_id) VALUES (?, ?)', array( intval($group), intval($id) ) );
			$groups= self::load_groups();
		}
		$user= User::get_by_id( $id );
		Session::notice( sprintf( _t('Added %1s to %2s'), $user->username, self::$group_names[$group] ) );
	}

	/**
	 * Remove a user from a group
	 * @param int a group ID
	 * @param int a user ID
	**/
	public static function remove_user( $group, $id )
	{
		if ( in_array( intval($id), self::$groups[ intval($group) ] ) ) {
			$results= DB::query( 'DELETE FROM {users_groups} WHERE group_id=? and user_id= ?', array( intval($group), intval($id) ) );
			$groups= self::load_groups();
		}
		$user= User::get_by_id($id);
		Session::notice( sprintf( _t('Removed %1s from %2s'), $user->username, self::$group_names[$group]) );
	}

	/**
	 * Assign a new permission to a group
	 * @param int A group ID
	 * @param int A permission ID
	 * @param int Whether this permission should be denied to this group
	**/
	public static function grant_permission( $group, $permission, $denied= 0 )
	{
		// first, see if this group has this permission
		// either granted or denied
		$check= DB::get_row('SELECT id,denied FROM ' . DB::table('groups_permissions') . ' WHERE group_id=? AND permission_id=?', array( intval($group), intval($permission) ) );
		if ( ! empty( $check ) ) {
			if ( $check->denied === intval($denied) ) {
				// no change
				return;
			} else {
				// the "denied" value is different, so update
				// the existing record
				DB::query('UPDATE ' . DB::table('groups_permissions') . ' SET denied=? WHERE id=?', array( intval($denied), $check->id) );
				return;
			}
		}
		// We got here, which means that the permission does not yet
		// exist.  Let's add it.
		DB::query('INSERT INTO ' . DB::table('groups_permissions') . ' (group_id, permission_id, denied) VALUES ( ?, ?, ?)', array( intval($group), intval($permission), intval($denied) ) );
	}

	/**
	 * Remove a permission from a group
	 * @param int a group ID
	 * @param int a permission ID
	**/
	public static function revoke_permission( $group, $permission )
	{
		DB::query('DELETE FROM ' . DB::table('groups_permissions') . ' WHERE group_id=? and permission_id=?', array( intval($group), intval($permission) ) );
	}

	/**
	 * Determine whether members of a group can do something
	 * @param int a group ID
	 * @param string a text description of a permission
	 * @return bool Whether the group can do the thing
	**/
	public static function can( $group_id, $permission )
	{
		return ACL::group_can( intval($group_id), $permission );
	}
}
?>
