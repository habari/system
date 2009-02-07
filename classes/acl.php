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
 * @todo Rename all functions and variables to normalize conventions: Users and groups have "access" to a "token".  The access applied to a token is a "permission".  A "token" alone is not a "permission".
 *
 **/
class ACL {
	/**
	 * How to handle a permission request for a permission that is not in the permission list.
	 * For example, if you request $user->can('some non-existent permission') then this value is returned.
	 **/
	const ACCESS_NONEXISTENT_PERMISSION = 0;

	public static $access_names = array( 'read', 'edit', 'delete', 'create' );

	/**
	 * Check a permission bitmask for a particular access type.
	 * @param Bitmask $bitmask The permission bitmask
	 * @param mixed $access The name of the access to check against (read, write, full)
	 * @return bool Returns true if the given access meets exceeds the access to check against
	 */
	public static function access_check( $bitmask, $access )
	{
		if ( $access instanceof Bitmask ) {
			return ($bitmask->value & $access->value) == $access->value;
		}

		switch ( $access ) {
			case 'full':
				return $bitmask->value == $bitmask->full;
			case 'any':
				return $bitmask->value != 0;
			case 'deny':
				return $bitmask->value == 0;
			default:
				return $bitmask->$access;
		}
	}

	/**
	 * Get a Bitmask object representing the supplied access integer
	 *
	 * @param integer $mask The access mask, usually stored in the database
	 * @return Bitmask An object representing the access value
	 */
	public static function get_bitmask( $mask )
	{
		$bitmask = new Bitmask( self::$access_names, $mask );
		return $bitmask;
	}

	/**
	 * Check the permission bitmask to find the access type
	 * <em>This function is horribly, horribly broken, and shouldn't be used.
	 * For example, it will return that a permission is only "read" when it is actually "read+write".</em>
	 * Use get_bitmask() to retrieve a Btimask instead, and use its properties for testing values.
	 * @param mixed $mask The access bitmask
	 * @return mixed The permission level granted, or false for none
	 */
	public static function access_level( $mask )
	{
		$bitmask = new Bitmask( self::$access_names, $mask );


		if ( $bitmask->value == $bitmask->full ) {
			return 'full';
		}
		else {
			foreach ( $bitmask->flags as $flag ) {
				if ( $bitmask->$flag ) {
					return $flag;
				}
			}
		}
		return false;

	}

	/**
	 * Create a new permission token, and save it to the permission tokens table
	 * @param string $name The name of the permission
	 * @param string $description The description of the permission
	 * @param string $group The token group for organizational purposes
	 * @param bool $crud Indicates if the token is a CRUD or boolean type token (default is boolean)
	 * @return mixed the ID of the newly created permission, or boolean FALSE
	**/
	public static function create_token( $name, $description, $group, $crud = false )
	{
		$name = self::normalize_token( $name );
		$crud = ( $crud ) ? 1 : 0;
		// first, make sure this isn't a duplicate
		if ( ACL::token_exists( $name ) ) {
			return false;
		}
		$allow = true;
		// Plugins have the opportunity to prevent adding this token
		$allow = Plugins::filter('token_create_allow', $allow, $name, $description, $group, $crud );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('token_create_before', $name, $description, $group, $crud );

		$result = DB::query('INSERT INTO {tokens} (name, description, token_group, token_type) VALUES (?, ?, ?, ?)', array( $name, $description, $group, $crud) );

		if ( ! $result ) {
			// if it didn't work, don't bother trying to log it
			return false;
		}

		// Add the token to the admin group
		$token = ACL::token_id( $name );
		$admin = UserGroup::get( 'admin');
		if ( $admin ) {
			ACL::grant_group( $admin->id, $token, 'full' );
		}

		EventLog::log('New permission token created: ' . $name, 'info', 'default', 'habari');
		Plugins::act('permission_create_after', $name, $description, $group, $crud );
		return $result;
	}

	/**
	 * Remove a permission token, and any assignments of it
	 * @param mixed $permission a permission ID or name
	 * @return bool whether the permission was deleted or not
	**/
	public static function destroy_token( $token )
	{
		// make sure the permission exists, first
		if ( ! self::token_exists( $token ) ) {
			return false;
		}

		// grab token ID
		$token_id = self::token_id( $token );

		$allow = true;
		// plugins have the opportunity to prevent deletion
		$allow = Plugins::filter('token_destroy_allow', $allow, $token_id);
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('token_destroy_before', $token_id );
		// capture the token name
		$name = DB::get_value( 'SELECT name FROM {tokens} WHERE id=?', array( $token_id ) );
		// remove all references to this permissions
		$result = DB::query( 'DELETE FROM {group_token_permissions} WHERE token_id=?', array( $token_id ) );
		$result = DB::query( 'DELETE FROM {user_token_permissions} WHERE token_id=?', array( $token_id ) );
		// remove this token
		$result = DB::query( 'DELETE FROM {tokens} WHERE id=?', array( $token_id ) );
		if ( ! $result ) {
			// if it didn't work, don't bother trying to log it
			return false;
		}
		EventLog::log( sprintf(_t('Permission token deleted: %s'), $name), 'info', 'default', 'habari');
		Plugins::act('token_destroy_after', $token_id );
		return $result;
	}

	/**
	 * Get an array of QueryRecord objects containing all permission tokens
	 * @param string $order the order in which to sort the returning array
	 * @return array an array of QueryRecord objects containing all tokens
	**/
	public static function all_tokens( $order = 'id' )
	{
		$order = strtolower( $order );
		if ( ( 'id' != $order ) && ( 'name' != $order ) && ( 'description' != $order ) ) {
			$order = 'id';
		}
		$tokens = DB::get_results( 'SELECT id, name, description, token_group, token_type FROM {tokens} ORDER BY ' . $order );
		return $tokens ? $tokens : array();
	}

	/**
	 * Get a permission token's name by its ID
	 * @param int $id a permission ID
	 * @return string the name of the permission, or boolean FALSE
	**/
	public static function token_name( $id )
	{
		if ( ! is_int( $id ) ) {
			return false;
		}
		else {
			return DB::get_value( 'SELECT name FROM {tokens} WHERE id=?', array( $id ) );
		}
	}

	/**
	 * Get a permission token's ID by its name
	 * @param string $name the name of the permission
	 * @return int the permission's ID
	**/
	public static function token_id( $name )
	{
		if( is_numeric($name) ) {
			return intval( $name );
		}
		$name = self::normalize_token( $name );
		return intval( DB::get_value( 'SELECT id FROM {tokens} WHERE name=?', array( $name ) ) );
	}

	/**
	 * Fetch a permission token's description from the DB
	 * @param mixed $permission a permission name or ID
	 * @return string the description of the permission
	**/
	public static function token_description( $permission )
	{
		if ( is_int( $permission) ) {
			$query = 'id';
		}
		else {
			$query = 'name';
			$permission = self::normalize_token( $permission );
		}
		return DB::get_value( "SELECT description FROM {tokens} WHERE $query=?", array( $permission ) );
	}

	/**
	 * Determine whether a permission token exists
	 * @param mixed $permission a permission name or ID
	 * @return bool whether the permission exists or not
	**/
	public static function token_exists( $permission )
	{
		if ( is_numeric( $permission ) ) {
			$query = 'id';
		}
		else {
			$query = 'name';
			$permission = self::normalize_token( $permission );
		}
		return ( (int) DB::get_value( "SELECT COUNT(id) FROM {tokens} WHERE $query=?", array( $permission ) ) > 0 );
	}

	/**
	 * Determine whether a group can perform a specific action
	 * @param mixed $group A group ID or name
	 * @param mixed $token_id A permission token ID or name
	 * @param string $access Check for 'create', 'read', 'update', 'delete', or 'full' access
	 * @return bool Whether the group can perform the action
	**/
	public static function group_can( $group, $token_id, $access = 'full' )
	{
		$bitmask = get_group_token_access( $group, $token_id );

		if ( isset( $bitmask ) && self::access_check( $bitmask, $access ) ) {
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
	 * @param mixed $token_id A permission ID or name
	 * @param string $access Check for 'create', 'read', 'update', 'delete', or 'full' access
	 * @return bool Whether the user can perform the action
	**/
	public static function user_can( $user, $token_id, $access = 'full' )
	{

		$result = self::get_user_token_access( $user, $token_id );

		if ( isset( $result ) && self::access_check( $result, $access ) ) {
			return true;
		}

		$super_user_access = self::get_user_token_access( $user, 'super_user' );
		if ( isset( $super_user_access ) && self::access_check( $super_user_access, 'any' ) ) {
			return true;
		}

		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}

	/**
	 * Determine whether a user is denied permission to perform a specific action
	 * @param mixed $user A User object, user ID or a username
	 * @param mixed $token_id A permission ID or name
	 * @return bool Whether the user can perform the action
	 **/
	public static function user_cannot( $user, $token_id )
	{

		$result = self::get_user_token_access( $user, $token_id );

		if ( isset( $result ) && self::access_check( $result, 'deny' ) ) {
			return true;
		}

		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}


	/**
	 * Return the access bitmask to a specific token for a specific user
	 *
	 * @param mixed $user A User object instance or user id
	 * @param mixed $token_id A permission token name or token ID
	 * @return integer An access bitmask
	 * @todo Implement cache on these permissions
	 */
	public static function get_user_token_access( $user, $token_id )
	{
		// Use only numeric ids internally
		$token_id = self::token_id( $token_id );

		/**
		 * Do we allow perms that don't exist?
		 * When ACL is functional ACCESS_NONEXISTENT_PERMISSION should be false by default.
		 */
		if ( is_null( $token_id ) ) {
			return self::get_bitmask( self::ACCESS_NONEXISTENT_PERMISSION );
		}

		// if we were given a user ID, use that to fetch the group membership from the DB
		if ( is_numeric( $user ) ) {
			$user_id = $user;
		}
		else {
			// otherwise, make sure we have a User object, and get
			// the groups from that
			if ( ! $user instanceof User ) {
				$user = User::get( $user );
			}
			$user_id = $user->id;
		}

		// Implement cache RIGHT HERE

		/**
		 * Jay Pipe's explanation of the following SQL
		 * 1) Look into user_permissions for the user and the token.
		 * If exists, use that permission flag for the check. If not,
		 * go to 2)
		 *
		 * 2) Look into the group_permissions joined to
		 * users_groups for the user and the token.  Order the results
		 * by the access bitmask. The lower the mask value, the
		 * fewest permissions that group has. Use the first record's
		 * access mask to check the ACL.
		 *
		 * This gives the system very fine grained control and grabbing
		 * the permission flag and can be accomplished in a single SQL
		 * call.
		 */
		
		$exceptions = '';
		$default_groups = array();
		$default_groups = Plugins::filter( 'user_default_groups', $default_groups, $user_id );
		$default_groups = array_filter(array_map('intval', $default_groups));
		switch(count($default_groups)) {
			case 0: // do nothing
				break;
			case 1: // single argument
				$exceptions = 'OR ug.group_id = ' . reset($default_groups);
				break;
			default: // multiple arguments
				$exceptions = 'OR ug.group_id IN (' . implode(',', $default_groups) . ')';
				break;
		}

		$sql = <<<SQL
SELECT access_mask
  FROM {user_token_permissions}
  WHERE user_id = :user_id
  AND token_id = :token_id
UNION ALL
SELECT gp.access_mask
  FROM {users_groups} ug
  INNER JOIN {group_token_permissions} gp
  ON ((ug.group_id = gp.group_id
  AND ug.user_id = :user_id)
	{$exceptions})
  AND gp.token_id = :token_id
  ORDER BY access_mask ASC
SQL;
		$accesses = DB::get_column( $sql, array( ':user_id' => $user_id, ':token_id' => $token_id ) );
		
		$accesses = Plugins::filter( 'user_token_access', $accesses, $user_id, $token_id );

		$result = 0;
		foreach ( $accesses as $access ) {
			if ( $access == 0 ) {
				$result = 0;
				break;
			}
			else {
				$result |= $access;
			}
		}

		return self::get_bitmask( $result );
	}

	/**
	 * Get all the tokens for a given user with a particular kind of access
	 * @param mixed $user A user object, user ID or a username
	 * @param string $access Check for 'create' or 'read', 'update', or 'delete' access
	 * @return array of token IDs
	**/
	public static function user_tokens( $user, $access = 'full', $posts_only = false )
	{
		$bitmask = new Bitmask ( self::$access_names, $access );
		$tokens = array();

		$super_user_access = self::get_user_token_access( $user, 'super_user' );
		if ( isset( $super_user_access ) && self::access_check( $super_user_access, 'any' ) ) {
			$result = DB::get_results('SELECT id, ? as access_mask FROM {tokens}', array($bitmask->full) );
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
SELECT token_id, access_mask
	FROM {user_token_permissions}
	WHERE user_id = :user_id
UNION ALL
SELECT gp.token_id, gp.access_mask
  FROM {users_groups} ug
  INNER JOIN {group_token_permissions} gp
  ON ug.group_id = gp.group_id
  AND ug.user_id = :user_id
  ORDER BY token_id ASC
SQL;
			$result = DB::get_results( $sql, array( ':user_id' => $user_id ) );
		}

		if ( $posts_only ) {
			$post_tokens = DB::get_column('SELECT token_id FROM {post_tokens} GROUP BY token_id');
		}

		foreach ( $result as $token ) {
			$bitmask->value = $token->access_mask;
			if ( $access == 'deny' && $bitmask->value == 0 ) {
				$tokens[] = $token->token_id;
			}
			else {
				if ( $bitmask->$access && ( !$posts_only || in_array($token->token_id, $post_tokens) ) ) {
					$tokens[] = $token->token_id;
				}
			}
		}
		return $tokens;
	}

	/**
	 * Get the access bitmask of a group for a specific permission token
	 * @param integer $group The group ID
	 * @param mixed $token_id A permission name or ID
	 * @return an access bitmask
	 **/
	public static function get_group_token_access( $group, $token_id )
	{
		// Use only numeric ids internally
		$group = UserGroup::id( $group );
		$token_id = self::token_id( $token_id );
		$sql = 'SELECT access_mask FROM {group_token_permissions} WHERE
			group_id=? AND token_id=?;';

		$result = DB::get_value( $sql, array( $group, $token_id) );

		if ( isset( $result ) ) {
			return self::get_bitmask($result);
		}
		return null;
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
		$token_id = self::token_id( $token_id );
		$row = DB::get_row( 'SELECT access_mask, count(access_mask) as granted FROM {group_token_permissions} WHERE group_id=? AND token_id=?', array( $group_id, $token_id ) );
		$access_mask = $row->access_mask;
		$row_exists = $row->granted != 0;
		if ( $access_mask ===  false ) {
			$access_mask = 0; // default is 'not granted' (bitmask 0)
		}

		$bitmask = self::get_bitmask( $access_mask );
		$orig_value = $bitmask->value;

		if ( $access instanceof Bitmask ) {
			$bitmask->value = $access->value;
		}
		elseif ( $access == 'full' ) {
			$bitmask->value = $bitmask->full;
		}
		elseif ( $access == 'deny' ) {
			$bitmask->value = 0;
		}
		else {
			$bitmask->$access = true;
		}

		// Only update if the value is changed
		if ( $orig_value != $bitmask->value || ( $orig_value == 0 && !$row_exists && $bitmask->value == 0 ) ) {
			// DB::update will insert if the token is not already in the group tokens table
			$result = DB::update(
				'{group_token_permissions}',
				array( 'access_mask' => $bitmask->value ),
				array( 'group_id' => $group_id, 'token_id' => $token_id )
			);

			$ug = UserGroup::get_by_id( $group_id );
			$ug->clear_permissions_cache();
			$msg = _t( 'Group %1$s: Access to %2$s changed to %3$s', array( $ug->name, ACL::token_name( $token_id ), $bitmask ) );
			EventLog::log( $msg, 'notice', 'user', 'habari' );
		}
		else {
			$result = true;
		}

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
		$token_id = self::token_id( $token_id );
		$access_mask = DB::get_value( 'SELECT access_mask FROM {user_token_permissions} WHERE user_id=? AND token_id=?',
			array( $user_id, $token_id ) );
		if ( $access_mask ===  false ) {
			$permission_bit = 0; // default is 'deny' (bitmask 0)
		}

		$bitmask = self::get_bitmask( $access_mask );

		if ( $access == 'full' ) {
			$bitmask->value= $bitmask->full;
		}
		elseif ( $access == 'deny' ) {
			$bitmask->value = 0;
		}
		else {
			$bitmask->$access = true;
		}

		$result = DB::update(
			'{user_token_permissions}',
			array( 'access_mask' => $bitmask->value ),
			array( 'user_id' => $user_id, 'token_id' => $token_id )
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
	 * Remove a permission token from the group permissions table
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function revoke_group_token( $group_id, $token_id )
	{
		$token_id = self::token_id( $token_id );
		$ug = UserGroup::get_by_id( $group_id );

		$access = self::get_group_token_access($group_id, $token_id);

		if(empty($access)) {
			$result = true;
		}
		else {
			$result = DB::delete( '{group_token_permissions}',
				array( 'group_id' => $group_id, 'token_id' => $token_id ) );
			EventLog::log( _t( 'Group %1$s: Permission to %2$s revoked.', array( $ug->name, ACL::token_name( $token_id ) ) ), 'notice', 'user', 'habari' );
		}

		$ug->clear_permissions_cache();

		return $result;
	}

	/**
	 * Remove a permission token from the user permissions table
	 * @param integer $user_id The user ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function revoke_user_token( $user_id, $token_id )
	{
		$token_id = self::token_id( $token_id );
		$result = DB::delete( '{user_token_permissions}',
			array( 'user_id' => $user_id, 'token_id' => $token_id ) );

		return $result;
	}

	/**
	 * Convert a token name into a valid format
	 *
	 * @param string $name The name of a permission
	 * @return string The permission with spaces converted to underscores and all lowercase
	 */
	public static function normalize_token( $name )
	{
		return strtolower( preg_replace( '/\s+/', '_', trim($name) ) );
	}

	/**
	 * Creates the default set of permissions.
	 */
	public static function create_default_tokens()
	{
		// super user token
		self::create_token( 'super_user', 'Permissions for super users', 'Super User' );

		// admin tokens
		self::create_token( 'manage_all_comments', 'Manage comments on all posts', 'Administration' );
		self::create_token( 'manage_own_post_comments', 'Manage comments on one\'s own posts', 'Administration' );
		self::create_token( 'manage_tags', 'Manage tags', 'Administration' );
		self::create_token( 'manage_options', 'Manage options', 'Administration' );
		self::create_token( 'manage_theme', 'Change theme', 'Administration' );
		self::create_token( 'manage_theme_config', 'Configure the active theme', 'Administration' );
		self::create_token( 'manage_plugins', 'Activate/deactivate plugins', 'Administration' );
		self::create_token( 'manage_plugins_config', 'Configure active plugins', 'Administration' );
		self::create_token( 'manage_import', 'Use the importer', 'Administration' );
		self::create_token( 'manage_users', 'Add, remove, and edit users', 'Administration' );
		self::create_token( 'manage_groups', 'Manage groups and permissions', 'Administration' );
		self::create_token( 'manage_logs', 'Manage logs', 'Administration' );

		// content tokens
		self::create_token( 'own_posts_any', 'Permissions on one\'s own posts', 'Content', true );
		self::create_token( 'post_any', 'Permissions to all posts', 'Content', true );
		foreach ( Post::list_active_post_types() as $name => $posttype ) {
			self::create_token( 'post_' . Utils::slugify($name), _t('Permissions to posts of type "%s"', array($name) ), 'Content', true );
			self::create_token( 'own_post_' . Utils::slugify($name), _t('Permissions to one\'s own posts of type "%s"', array($name) ), 'Content', true );
		}

		// comments tokens
		self::create_token( 'comment', 'Make comments on any post', 'Comments' );
	}

	public static function rebuild_permissions( $user = null )
	{
		// Clear out all permission-related values
		DB::query('DELETE FROM {tokens}');
		DB::query('DELETE FROM {group_token_permissions}');
		//DB::query('DELETE FROM {groups}');
		DB::query('DELETE FROM {post_tokens}');
		DB::query('DELETE FROM {user_token_permissions}');
		//DB::query('DELETE FROM {users_groups}');

		// Create initial groups if they don't already exist
		$admin_group = UserGroup::get_by_name( _t('admin') );
		if ( ! $admin_group instanceof UserGroup ) {
			$admin_group = UserGroup::create( array( 'name' => _t('admin') ) );
		}

		$anonymous_group = UserGroup::get_by_name( _t('anonymous') );
		if ( ! $anonymous_group instanceof UserGroup ) {
			$anonymous_group = UserGroup::create( array( 'name' => _t('anonymous') ) );
		}

		// Add all users or the passed user to the admin group
		if ( empty($user) ) {
			$users = Users::get_all();
			$ids = array();
			foreach ( $users as $user ) {
				$ids[] = $user->id;
			}
			$admin_group->add( $ids );
		}
		else {
			$admin_group->add($user);
		}

		// create default permissions
		self::create_default_tokens();
		// Make the admin group all superusers
		$admin_group->grant('super_user');
		// Add entry and page read access to the anonymous group
		$anonymous_group->grant('post_entry', 'read');
		$anonymous_group->grant('post_page', 'read');
		$anonymous_group->grant( 'comment' );

		// Add the anonumous user to the anonymous group
		$anonymous_group->add( 0 );
	}

}
?>