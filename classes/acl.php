<?php
/**
 * @package Habari
 *
 */

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
 **/
class ACL {
	/**
	 * How to handle a permission request for a permission that is not in the permission list.
	 * For example, if you request $user->can('some non-existent permission') then this value is returned.
	 * It's true at the moment because that allows access to all features for upgrading users.
	 * @todo Decide if this is a setting we need or want to change, or perhaps it should be an option.
	 **/
	const ACCESS_NONEXISTENT_PERMISSION = false;

	private static $access_names = array( 'read', 'write', 'delete' );

	/**
	 * Check the permission bitmask for a particular access type.
	 * @param mixed $permission The permission bitmask
	 * @param mixed $access The name of the access to check against (read, write, full)
	 * @return bool Returns true if the given access meets exceeds the access to check against
	 */
	public static function access_check( $permission, $access )
	{
		$bitmask = new Bitmask( self::$access_names, $permission );

		switch($access) {
			case 'full':
				return $bitmask->value == $bitmask->full;
			case 'any':
				return $bitmask->value != 0;
			default:
				return $bitmask->$access;
		}
	}
	
	/**
	 * Check the permission bitmask to find the access type
	 * @param mixed $permission The permission bitmask
	 * @return mixed The permission level granted, or false for none
	 */
	public static function access_level( $permission )
	{
		$bitmask = new Bitmask( self::$access_names, $permission );
						
		if($bitmask->value == $bitmask->full) {
			return 'full';
		} elseif(isset($bitmask->flags[$bitmask->value])) {
			return $bitmask->flags[$bitmask->value];
		} else {
			return false;
		}
		
	}

	/**
	 * Create a new permission, and save it to the permission tokens table
	 * @param string The name of the permission
	 * @param string The description of the permission
	 * @return mixed the ID of the newly created permission, or boolean FALSE
	**/
	public static function create_permission( $name, $description )
	{
		$name = self::normalize_permission( $name );
		// first, make sure this isn't a duplicate
		if ( ACL::token_exists( $name ) ) {
			return false;
		}
		$allow = true;
		// Plugins have the opportunity to prevent adding this permission
		$allow = Plugins::filter('permission_create_allow', $allow, $name, $description );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('permission_create_before', $name, $description);
		$result = DB::query('INSERT INTO {tokens} (name, description) VALUES (?, ?)', array( $name, $description) );

		if ( ! $result ) {
			// if it didn't work, don't bother trying to log it
			return false;
		}

		// Add the permission to the admin group
		$perm = ACL::token_id( $name );
		$admin = UserGroup::get( 'admin');
		if ( $admin ) {
			ACL::grant_group( $admin->id, $perm, 'full' );
		}

		EventLog::log('New permission created: ' . $name, 'info', 'default', 'habari');
		Plugins::act('permission_create_after', $name, $description );
		return $result;
	}

	/**
	 * Remove a permission, and any assignments of it
	 * @param mixed a permission ID or name
	 * @return bool whether the permission was deleted or not
	**/
	public static function destroy_permission( $permission )
	{
		// make sure the permission exists, first
		if ( ! self::token_exists( $permission ) ) {
			return false;
		}

		// grab token ID
		$permission = self::token_id( $permission );

		$allow = true;
		// plugins have the opportunity to prevent deletion
		$allow = Plugins::filter('permission_destroy_allow', $allow, $permission);
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('permission_destroy_before', $permission );
		// capture the permission token name
		$name = DB::get_value( 'SELECT name FROM {tokens} WHERE id=?', array( $permission ) );
		// remove all references to this permissions
		$result = DB::query( 'DELETE FROM {group_token_permissions} WHERE permission_id=?', array( $permission ) );
		$result = DB::query( 'DELETE FROM {user_token_permissions} WHERE permission_id=?', array( $permission ) );
		// remove this permission
		$result = DB::query( 'DELETE FROM {tokens} WHERE id=?', array( $permission ) );
		if ( ! $result ) {
			// if it didn't work, don't bother trying to log it
			return false;
		}
		EventLog::log( sprintf(_t('Permission deleted: %s'), $name), 'info', 'default', 'habari');
		Plugins::act('permission_destroy_after', $permission );
		return $result;
	}

	/**
	 * Get an array of QueryRecord objects containing all permissions
	 * @param string the order in which to sort the returning array
	 * @return array an array of QueryRecord objects containing all permissions
	**/
	public static function all_permissions( $order = 'id' )
	{
		$order = strtolower( $order );
		if ( ( 'id' != $order ) && ( 'name' != $order ) && ( 'description' != $order ) ) {
			$order = 'id';
		}
		$permissions = DB::get_results( 'SELECT id, name, description FROM {tokens} ORDER BY ' . $order );
		return $permissions ? $permissions : array();
	}

	/**
	 * Get a permission token's name by its ID
	 * @param int a permission ID
	 * @return string the name of the permission, or boolean FALSE
	**/
	public static function token_name( $id )
	{
		if ( ! is_int( $id ) ) {
			return false;
		} else {
			return DB::get_value( 'SELECT name FROM {tokens} WHERE id=?', array( $id ) );
		}
	}

	/**
	 * Get a permission token's ID by its name
	 * @param string the name of the permission
	 * @return int the permission's ID
	**/
	public static function token_id( $name )
	{
		if( is_numeric($name) ) {
			return $name;
		}
		$name = self::normalize_permission( $name );
		return DB::get_value( 'SELECT id FROM {tokens} WHERE name=?', array( $name ) );
	}

	/**
	 * Fetch a permission token's description from the DB
	 * @param mixed a permission name or ID
	 * @return string the description of the permission
	**/
	public static function token_description( $permission )
	{
		if ( is_int( $permission) ) {
			$query = 'id';
		} else {
			$query = 'name';
			$permission = self::normalize_permission( $permission );
		}
		return DB::get_value( "SELECT description FROM {tokens} WHERE $query=?", array( $permission ) );
	}

	/**
	 * Determine whether a permission token exists
	 * @param mixed a permission name or ID
	 * @return bool whether the permission exists or not
	**/
	public static function token_exists( $permission )
	{
		if ( is_numeric( $permission ) ) {
			$query = 'id';
		}
		else {
			$query = 'name';
			$permission = self::normalize_permission( $permission );
		}
		return ( (int) DB::get_value( "SELECT COUNT(id) FROM {tokens} WHERE $query=?", array( $permission ) ) > 0 );
	}

	/**
	 * Determine whether a group can perform a specific action
	 * @param mixed $group A group ID or name
	 * @param mixed $permission An action ID or name
	 * @param string $access Check for 'read', 'write', or 'delete' access
	 * @return bool Whether the group can perform the action
	**/
	public static function group_can( $group, $permission, $access = 'write' )
	{
		// Use only numeric ids internally
		$group = UserGroup::id( $group );
		$permission = self::token_id( $permission );
		$sql = 'SELECT permission_id FROM {group_token_permissions} WHERE
			group_id=? AND token_id=?;';

		$result = DB::get_value( $sql, array( $group, $permission) );
		if ( isset( $result ) && self::access_check( $result, $access ) ) {
			// the permission has been granted to this group
			return true;
		}
		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}

	/**
	 * Determine whether a user can perform a specific action
	 * @param mixed $user A user object, user ID or a username
	 * @param mixed $token A permission ID or name
	 * @param string $access Check for 'read', 'write', or 'delete' access
	 * @return bool Whether the user can perform the action
	**/
	public static function user_can( $user, $token, $access = 'read' )
	{

		$result = self::get_user_token_permissions( $user, $token );

		if ( isset( $result ) && self::access_check( $result, $access ) ) {
			return true;
		}

		$super_user = self::get_user_token_permissions( $user, 'super_user' );
		if ( isset( $super_user ) && self::access_check( $super_user, 'any' ) ) {
			return true;
		}

		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}

	/**
	 * Determine whether a user is denied permission to perform a specific action
	 * @param mixed $user A user object, user ID or a username
	 * @param mixed $token A permission ID or name
	 * @return bool Whether the user can perform the action
	 **/
	public static function user_cannot( $user, $token )
	{

		$result = self::get_user_token_permissions( $user, $token );

		if ( isset( $result ) && $result == 0 ) {
			return true;
		}

		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}


	/**
	 * Return the permission to a specific token for a specific user
	 *
	 * @param mixed $user A User object instance or user id
	 * @param mixed $token A token string or if
	 * @return integer A permission bitmask integer
	 */
	public static function get_user_token_permissions( $user, $token )
	{
		// Use only numeric ids internally
		$token = self::token_id( $token );

		/**
		 * Do we allow perms that don't exist?
		 * When ACL is functional ACCESS_NONEXISTENT_PERMISSION should be false by default.
		 */
		if ( is_null( $token) ) {
			return self::ACCESS_NONEXISTENT_PERMISSION;
		}

		// if we were given a user ID, use that to fetch the group membership from the DB
		if ( is_numeric( $user ) ) {
			$user_id = $user;
		} else {
			// otherwise, make sure we have a User object, and get
			// the groups from that
			if ( ! $user instanceof User ) {
				$user = User::get( $user );
			}
			$user_id = $user->id;
		}

		/**
		 * Jay Pipe's explanation of the following SQL
		 * 1) Look into user_permissions for the user and the token.
		 * If exists, use that permission flag for the check. If not,
		 * go to 2)
		 *
		 * 2) Look into the group_permissions joined to
		 * users_groups for the user and the token.  Order the results
		 * by the permission_id flag. The lower the flag value, the
		 * fewest permissions that group has. Use the first record's
		 * permission flag to check the ACL.
		 *
		 * This gives the system very fine grained control and grabbing
		 * the permission flag and can be accomplished in a single SQL
		 * call.
		 */
		$sql = <<<SQL
SELECT permission_id
  FROM {user_token_permissions}
  WHERE user_id = :user_id
  AND token_id = :token_id
UNION ALL
SELECT gp.permission_id
  FROM {users_groups} ug
  INNER JOIN {group_token_permissions} gp
  ON ug.group_id = gp.group_id
  AND ug.user_id = :user_id
  AND gp.token_id = :token_id
  ORDER BY permission_id ASC
  LIMIT 1;
SQL;
		$result = DB::get_value( $sql, array( ':user_id' => $user_id, ':token_id' => $token ) );

		return $result;
	}

	/**
	 * Get all the tokens for a given user with a particular kind of access
	 * @param mixed $user A user object, user ID or a username
	 * @param string $access Check for 'read' or 'write' access
	 * @return array of token IDs
	**/
	public static function user_tokens( $user, $access = 'write', $posts_only = false )
	{
		$bitmask = new Bitmask ( self::$access_names, $access );
		$tokens = array();

		$super_user = self::get_user_token_permissions( $user, 'super_user' );
		if ( isset( $super_user ) && self::access_check( $super_user, 'any' ) ) {
			$result = DB::get_results('SELECT id, ? as permission_id FROM {tokens}', array($bitmask->full));
		}
		else {
			// convert $user to an ID
			if ( is_numeric( $user ) ) {
				$user_id = $user;
			}
			else {
				if ( ! $user instanceof User ) {
					$user = User::get( $user );
				}
				$user_id = $user->id;
			}

			$sql = <<<SQL
SELECT token_id, permission_id
	FROM {user_token_permissions}
	WHERE user_id = :user_id
UNION ALL
SELECT gp.token_id, gp.permission_id
  FROM {users_groups} ug
  INNER JOIN {group_token_permissions} gp
  ON ug.group_id = gp.group_id
  AND ug.user_id = :user_id
  ORDER BY token_id ASC
SQL;
			$result = DB::get_results( $sql, array( ':user_id' => $user_id ) );
		}

		if($posts_only) {
			$post_tokens = DB::get_column('SELECT token_id FROM {post_tokens} GROUP BY token_id');
		}

		foreach ( $result as $token ) {
			$bitmask->value = $token->permission_id;
			if ( $bitmask->$access && (!$posts_only || in_array($token->id, $post_tokens))) {
				$tokens[] = $token->token_id;
			}
		}
		return $tokens;
	}
	
	/**
	 * Get the permission of a group for a specific token
	 * @param integer $group The group ID
	 * @param mixed $token The ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function get_group_permission( $group, $token )
	{
		// Use only numeric ids internally
		$group = UserGroup::id( $group );
		$token = self::token_id( $token );
		$sql = 'SELECT permission_id FROM {group_token_permissions} WHERE
			group_id=? AND token_id=?;';
			
		$result = DB::get_value( $sql, array( $group, $token) );
				
		if ( isset( $result ) ) {
			return self::access_level($result);
		}
		return false;
	}

	/**
	 * Grant a permission to a group
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token to grant
	 * @param string $access The kind of access to assign the group
	 * @return Result of the DB query
	 **/
	public static function grant_group( $group_id, $token_id, $access = 'full' )
	{
		$permission_id = DB::get_value( 'SELECT permission_id FROM {group_token_permissions} WHERE group_id=? AND token_id=?',
			array( $group_id, $token_id ) );
		if ( $permission_id ===  false ) {
			$permission_id = 0; // default is 'deny' (bitmask 0)
		}

		$bitmask = new Bitmask( self::$access_names, $permission_id );

		if ( $access == 'full' || $access == 'deny' ) {
			if ( $access == 'full' ) {
				$access = true;
			}
			else {
				$access = false;
			}
			foreach ( self::$access_names as $access_name ) {
				$bitmask->$access_name = $access;
			}
		}
		else {
			$bitmask->$access = true;
		}

		// DB::update will insert if the token is not already in the group tokens table
		$result = DB::update(
			'{group_token_permissions}',
			array( 'permission_id' => $bitmask->value ),
			array( 'group_id' => $group_id, 'token_id' => self::token_id( $token_id ) )
		);

		$ug = UserGroup::get_by_id( $group_id );
		$ug->clear_permissions_cache();

		return $result;
	}

	/**
	 * Grant a permission to a user
	 * @param integer $user_id The user ID
	 * @param integer $token_id The name or ID of the permission token to grant
	 * @param string $access The kind of access to assign the group
	 * @return Result of the DB query
	 **/
	public static function grant_user( $user_id, $token_id, $access = 'full' )
	{
		$permission_id = DB::get_value( 'SELECT permission_id FROM {user_token_permissions} WHERE user_id=? AND token_id=?',
			array( $user_id, $token_id ) );
		if ( $permission_id ===  false ) {
			$permission_id = 0; // default is 'deny' (bitmask 0)
		}

		$bitmask = new Bitmask( self::$access_names, $permission_id );

		if ( $access == 'full' || $access == 'deny' ) {
			if ( $access == 'full' ) {
				$access = true;
			}
			else {
				$access = false;
			}
			foreach ( self::$access_names as $access_name ) {
				$bitmask->$access_name = $access;
			}
		}
		else {
			$bitmask->$access = true;
		}

		$result = DB::update(
			'{user_token_permissions}',
			array( 'permission_id' => $bitmask->value ),
			array( 'user_id' => $user_id, 'token_id' => self::token_id( $token_id ) )
		);

		return $result;
	}

	/**
	 * Deny permission to a group
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return Result of the DB query
	 **/
	public static function deny_group( $group_id, $token_id )
	{
		self::grant_group( $group_id, $token_id, 'deny' );
	}

	/**
	 * Deny permission to a user
	 * @param integer $user_id The user ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return Result of the DB query
	 **/
	public static function deny_user( $user_id, $token_id )
	{
		self::grant_user( $group_id, $token_id, 'deny' );
	}

	/**
	 * Remove a permission from the group permissions table
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function revoke_group_permission( $group_id, $token_id )
	{
		$result = DB::delete( '{group_token_permissions}',
			array( 'group_id' => $group_id, 'token_id' => $token_id ) );

		$ug = UserGroup::get_by_id( $group_id );
		$ug->clear_permissions_cache();

		return $result;
	}

	/**
	 * Remove a permission from the user permissions table
	 * @param integer $user_id The user ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function revoke_user_permission( $user_id, $token_id )
	{
		$result = DB::delete( '{user_token_permissions}',
			array( 'user_id' => $user_id, 'token_id' => $token_id ) );

		return $result;
	}

	/**
	 * Convert a permission name into a valid format
	 *
	 * @param string $name The name of a permission
	 * @return string The permission with spaces converted to underscores and all lowercase
	 */
	public static function normalize_permission( $name )
	{
		return strtolower( preg_replace( '/\s+/', '_', trim($name) ) );
	}
}
?>