<?php
/**
 * @package Habari
 *
 */

/**
 * Class for storing and retrieving rewrite rules from the DB.
 */
class RewriteRules extends ArrayObject
{
	// cache sorted rules
	protected static $sorted_rules_cache = null;

	/**
	 * Add pre-defined rules to an array of rules only if rules with their names don't already exist
	 *
	 * @param array $rules An array of RewriteRule objects
	 * @return array An array of rules with the system rules potentially added
	 */
	public static function add_system_rules( $rules )
	{
		$default_rules = array(
			// Display content
			array( 'name' => 'display_home', 'parse_regex' => '#^(?:page/(?P<page>0|1))?/?$#', 'build_str' => '(page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_home', 'priority' => 1000, 'description' => 'Homepage (index) display' ),
			array( 'name' => 'display_entries', 'parse_regex' => '#^(?:page/(?P<page>[2-9]|[1-9][0-9]+))/?$#', 'build_str' => '(page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_entries', 'priority' => 999, 'description' => 'Display multiple entries' ),
			array( 'name' => 'display_entries_by_date', 'parse_regex' => '#^(?P<year>[1,2]{1}[\d]{3})(?:/(?P<month>[\d]{2}))?(?:/(?P<day>[\d]{2}))?(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => '{$year}(/{$month})(/{$day})(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_date', 'priority' => 2, 'description' => 'Displays posts for a specific date.' ),
			array( 'name' => 'display_entries_by_tag', 'parse_regex' => '#^tag/(?P<tag>[^/]*)(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => 'tag/{$tag}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_tag', 'priority' => 5, 'description' => 'Return posts matching specified tag.', 'parameters' => serialize( array( 'require_match' => array('Tag', 'rewrite_tag_exists') ) ) ),
			array( 'name' => 'display_entry', 'parse_regex' => '#^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => '{$slug}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_post', 'priority' => 100, 'description' => 'Return entry matching specified slug', 'parameters' => serialize( array( 'require_match' => array('Posts', 'rewrite_match_type'), 'content_type'=>'entry' ) ) ),
			array( 'name' => 'display_page', 'parse_regex' => '#^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => '{$slug}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_post', 'priority' => 100, 'description' => 'Return page matching specified slug', 'parameters' => serialize( array( 'require_match' => array('Posts', 'rewrite_match_type'), 'content_type'=>'page' ) ) ),
			array( 'name' => 'display_search', 'parse_regex' => '#^search(?:/(?P<criteria>[^/]+))?(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => 'search(/{$criteria})(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'search', 'priority' => 8, 'description' => 'Searches posts' ),
			array( 'name' => 'display_404', 'parse_regex' => '/^.*$/', 'build_str' => '', 'handler' => 'UserThemeHandler', 'action' => 'display_404', 'priority' => 9999, 'description' => 'Displays an error page when a URL is not matched.' ),
			array( 'name' => 'display_post', 'parse_regex' => '#^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => '{$slug}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_post', 'priority' => 9998, 'description' => 'Fallback to return post matching specified slug if no content_type match' ),

			// Form actions
			array( 'name' => 'submit_feedback', 'parse_regex' => '#^(?P<id>[0-9]+)/feedback/?$#i', 'build_str' => '{$id}/feedback', 'handler' => 'FeedbackHandler', 'action' => 'add_comment', 'priority' => 8, 'description' => 'Adds a comment to a post' ),

			// User actions
			array( 'name' => 'admin', 'parse_regex' => '#^admin(?:/?$|/(?P<page>[^/]*))/?$#i', 'build_str' => 'admin/({$page})', 'handler' => 'AdminHandler', 'action' => 'admin', 'priority' => 6, 'description' => 'An admin action' ),
			array( 'name' => 'auth', 'parse_regex' => '#^auth/(?P<page>[^/]*)$#i', 'build_str' => 'auth/{$page}', 'handler' => 'UserHandler', 'action' => '{$page}', 'priority' => 7, 'description' => 'A user action or display, for instance the login screen' ),
			array( 'name' => 'user', 'parse_regex' => '#^user/(?P<page>[^/]*)$#i', 'build_str' => 'user/{$page}', 'handler' => 'UserHandler', 'action' => '{$page}', 'priority' => 7, 'description' => 'A user action or display, for instance the login screen' ),
			array( 'name' => 'user_profile', 'parse_regex' => '#^admin/(?P<page>user)/(?P<user>[^/]+)/?$#', 'build_str' => 'admin/{$page}/{$user}', 'handler' => 'AdminHandler', 'action' => 'admin', 'priority' => 4, 'description' => 'The profile page for a specific user' ),

			// AJAX requests
			array( 'name' => 'ajax', 'parse_regex' => '#^ajax/(?P<context>[^/]+)/?$#i', 'build_str' => 'ajax/{$context}', 'handler' => 'AjaxHandler', 'action' => 'ajax', 'priority' => 8, 'description' => 'Ajax handling' ),
			array( 'name' => 'auth_ajax', 'parse_regex' => '#^auth_ajax/(?P<context>[^/]+)/?$#i', 'build_str' => 'auth_ajax/{$context}', 'handler' => 'AjaxHandler', 'action' => 'auth_ajax', 'priority' => 8, 'description' => 'Authenticated ajax handling' ),
			array( 'name' => 'admin_ajax', 'parse_regex' => '#^admin_ajax/(?P<context>[^/]+)/?$#i', 'build_str' => 'admin_ajax/{$context}', 'handler' => 'AdminHandler', 'action' => 'admin_ajax', 'priority' => 8, 'description' => 'Authenticated ajax handling for the admin' ),

			// Atom Syndication Format
			array( 'name' => 'rsd', 'parse_regex' => '/^rsd$/i', 'build_str' => 'rsd', 'handler' => 'AtomHandler', 'action' => 'rsd', 'priority' => 1, 'description' => 'RSD output' ),
			array( 'name' => 'atom_entry', 'parse_regex' => '#^(?P<slug>[^/]+)/atom/?$#i', 'build_str' => '{$slug}/atom', 'handler' => 'AtomHandler', 'action' => 'entry', 'priority' => 8, 'description' => 'Atom Publishing Protocol' ),
			array( 'name' => 'atom_feed', 'parse_regex' => '#^atom/(?P<index>[^/]+)(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => 'atom/{$index}(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'collection', 'priority' => 8, 'description' => 'Atom collection' ),
			array( 'name' => 'atom_feed_comments', 'parse_regex' => '#^atom/comments(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => 'atom/comments(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'comments', 'priority' => 7, 'description' => 'Entries comments' ),
			array( 'name' => 'atom_feed_tag', 'parse_regex' => '#^tag/(?P<tag>[^/]+)/atom(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => 'tag/{$tag}/atom(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'tag_collection', 'priority' => 8, 'description' => 'Atom Tag Collection', 'parameters' => serialize( array( 'require_match' => array('Tag', 'rewrite_tag_exists') ) ) ),
			array( 'name' => 'atom_feed_entry_comments', 'parse_regex' => '#^(?P<slug>[^/]+)/atom/comments(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => '{$slug}/atom/comments(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'entry_comments', 'priority' => 8, 'description' => 'Entry comments' ),
			array( 'name' => 'atom_feed_page_comments', 'parse_regex' => '#^(?P<slug>[^/]+)/atom/comments(?:/page/(?P<page>\d+))?/?$#i', 'build_str' => '{$slug}/atom/comments(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'entry_comments', 'priority' => 8, 'description' => 'Page comments' ),

			// Atom Publishing Protocol
			array( 'name' => 'atompub_servicedocument', 'parse_regex' => '/^atom$/i', 'build_str' => 'atom', 'handler' => 'AtomHandler', 'action' => 'introspection', 'priority' => 1, 'description' => 'Atom introspection' ),

			// Cron handling
			array( 'name' => 'cron', 'parse_regex' => '#^cron/(?P<time>[0-9.]+)/?$#i', 'build_str' => 'cron/{$time}', 'handler' => 'CronTab', 'action' => 'poll_cron', 'priority' => 1, 'description' => 'Asyncronous cron processing' ),

			// XMLRPC requests
			array( 'name' => 'xmlrpc', 'parse_regex' => '#^xmlrpc/?$#i', 'build_str' => 'xmlrpc', 'handler' => 'XMLRPCServer', 'action' => 'xmlrpc_call', 'priority' => 8, 'description' => 'Handle incoming XMLRPC requests.' ),
		);
		$default_rules = Plugins::filter( 'default_rewrite_rules', $default_rules );
		$default_rules_properties = array( 'is_active' => 1, 'rule_class' => RewriteRule::RULE_SYSTEM );
		$rule_names = array_flip( array_map( create_function( '$a', 'return $a->name;' ), $rules ) );
		foreach ( $default_rules as $default_rule ) {
			if ( !isset( $rule_names[$default_rule['name']] ) ) {
				$rule_properties = array_merge( $default_rule, $default_rules_properties );
				$rules[] = new RewriteRule( $rule_properties );
			}
		}
		return $rules;
	}

	/**
	 * Return the active rewrite rules, both in the database and applied by plugins
	 *
	 * @return array Array of RewriteRule objects for active rewrite rules
	 */
	public static function get_active()
	{
		static $system_rules;

		if ( !isset( $system_rules ) ) {
			$sql = "
				SELECT rr.rule_id, rr.name, rr.parse_regex, rr.build_str, rr.handler, rr.action, rr.priority, rr.parameters
				FROM {rewrite_rules} AS rr
				WHERE rr.is_active= 1
				ORDER BY rr.priority";
			$db_rules = DB::get_results( $sql, array(), 'RewriteRule' );

			$system_rules = self::add_system_rules( $db_rules );
		}
		$rewrite_rules = Plugins::filter( 'rewrite_rules', $system_rules );

		$rewrite_rules = self::sort_rules( $rewrite_rules );

		// cache the sorted rules for this instance to use
		$c = __CLASS__;
		self::$sorted_rules_cache = new $c ( $rewrite_rules );
		return self::$sorted_rules_cache;
	}

	/**
	 * Helper function for sorting rewrite rules by priority.
	 *
	 * Required because plugins would insert their rules at the end of the array,
	 * which would allow any other rule (including the one that executes by default
	 * when no other rules work) to execute first.
	 *
	 * @param array $rewrite_rules An array of RewriteRules
	 * @return array Sorted rewrite rules by priority
	 */
	public static function sort_rules( $rewrite_rules )
	{
		$pr = array();
		$max_priority = 0;
		foreach ( $rewrite_rules as $r ) {
			$priority = $r->priority;
			$pr[$priority][] = $r;
			$max_priority = max( $max_priority, $priority );
		}
		$rewrite_rules = array();
		for ( $z = 0; $z <= $max_priority; $z++ ) {
			if ( isset( $pr[$z] ) ) {
				$rewrite_rules = array_merge( $rewrite_rules, $pr[$z] );
			}
		}
		return $rewrite_rules;
	}

	/**
	 * Get a RewriteRule by its name
	 *
	 * @param string $name The name of the rule
	 * @return array An array of all matched RewriteRule objects.
	 */
	public static function by_name( $name )
	{
		static $named = null;

		if ( self::$sorted_rules_cache == null ) {
			self::get_active();
		}

		if ( $named == null ) {
			$named = array();
			$rules = self::$sorted_rules_cache;
			foreach ( $rules as $rule ) {
				$named[$rule->name][] = $rule;
			}
		}

		return isset( $named[$name] ) ? $named[$name] : false;
	}
}

?>
