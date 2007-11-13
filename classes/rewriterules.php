<?php

/**
 * Class for storing and retrieving rewrite rules from the DB.
 */      
class RewriteRules extends ArrayObject {

	/**
	 * Add pre-defined rules to an array of rules only if rules with their names don't already exist
	 * 
	 * @param array $rules An array of RewriteRule objects
	 * @return array An array of rules with the system rules potentially added
	 */
	static public function add_system_rules($rules)
	{
		$default_rules= array(
			array( 'name' => 'display_entries_by_date', 'parse_regex' => '%^(?P<year>[1,2]{1}[\d]{3})(?:/(?P<month>[\d]{2}))?(?:/(?P<day>[\d]{2}))?(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => '{$year}/({$month}/)({$day}/)(page/{$page}/)', 'handler' => 'UserThemeHandler', 'action' => 'display_date', 'priority' => 2, 'description' => 'Displays posts for a specific date.' ),
			array( 'name' => 'admin', 'parse_regex' => '%^admin/?(?P<page>[^/]*)/?$%i', 'build_str' => 'admin/({$page})', 'handler' => 'AdminHandler', 'action' => 'admin', 'priority' => 6, 'description' => 'An admin action' ),
			array( 'name' => 'userprofile', 'parse_regex' => '%^admin/(?P<page>user)/(?P<user>[^/]+)/?$%', 'build_str' => 'admin/{$page}/{$user}', 'handler' => 'AdminHandler', 'action' => 'admin', 'priority' => 4, 'description' => 'The profile page for a specific user' ),
			array( 'name' => 'user', 'parse_regex' => '%^user/(?P<page>[^/]*)$%i', 'build_str' => 'user/{$page}', 'handler' => 'UserHandler', 'action' => '{$page}', 'priority' => 7, 'description' => 'A user action or display, for instance the login screen' ),
			array( 'name' => 'display_entry', 'parse_regex' => '%^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => '{$slug}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_post', 'priority' => 100, 'description' => 'Return entry matching specified slug' ),
			array( 'name' => 'display_page', 'parse_regex' => '%^(?P<slug>[^/]+)(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => '{$slug}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_post', 'priority' => 100, 'description' => 'Return page matching specified slug' ),
			array( 'name' => 'index_page', 'parse_regex' => '%^(?:page/(?P<page>\d+)/?)$%', 'build_str' => '(page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_home', 'priority' => 1000, 'description' => 'Homepage (index) display' ),
			array( 'name' => 'rsd', 'parse_regex' => '%^rsd$%i', 'build_str' => 'rsd', 'handler' => 'AtomHandler', 'action' => 'rsd', 'priority' => 1, 'description' => 'RSD output' ),
			array( 'name' => 'introspection', 'parse_regex' => '%^atom$%i', 'build_str' => 'atom', 'handler' => 'AtomHandler', 'action' => 'introspection', 'priority' => 1, 'description' => 'Atom introspection' ),
			array( 'name' => 'collection', 'parse_regex' => '%^atom/(?P<index>[^/]+)(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => 'atom/{$index}(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'collection', 'priority' => 8, 'description' => 'Atom collection' ),
			array( 'name' => 'search', 'parse_regex' => '%^search(?:/(?P<criteria>[^/]+))?(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => 'search(/{$criteria})(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'search', 'priority' => 8, 'description' => 'Searches posts' ),
			array( 'name' => 'comment', 'parse_regex' => '%^(?P<id>[0-9]+)/feedback/?$%i', 'build_str' => '{$id}/feedback', 'handler' => 'FeedbackHandler', 'action' => 'add_comment', 'priority' => 8, 'description' => 'Adds a comment to a post' ),
			array( 'name' => 'ajax', 'parse_regex' => '%^ajax/(?P<context>[^/]+)/?$%i', 'build_str' => 'ajax/{$context}', 'handler' => 'AjaxHandler', 'action' => 'ajax', 'priority' => 8, 'description' => 'Ajax handling' ),
			array( 'name' => 'auth_ajax', 'parse_regex' => '%^auth_ajax/(?P<context>[^/]+)/?$%i', 'build_str' => 'auth_ajax/{$context}', 'handler' => 'AjaxHandler', 'action' => 'auth_ajax', 'priority' => 8, 'description' => 'Authenticated ajax handling' ),
			array( 'name' => 'entry', 'parse_regex' => '%^(?P<slug>[^/]+)/atom/?$%i', 'build_str' => '{$slug}/atom', 'handler' => 'AtomHandler', 'action' => 'entry', 'priority' => 8, 'description' => 'Atom Publishing Protocol' ),
			array( 'name' => 'entry_comments', 'parse_regex' => '%^(?P<slug>[^/]+)/atom/comments(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => '{$slug}/atom/comments(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'entry_comments', 'priority' => 8, 'description' => 'Entry comments' ),
			array( 'name' => 'comments', 'parse_regex' => '%^atom/comments(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => 'atom/comments(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'comments', 'priority' => 7, 'description' => 'Entries comments' ),
			array( 'name' => 'tag_collection', 'parse_regex' => '%^tag/(?P<tag>[^/]+)/atom(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => 'tag/{$tag}/atom(/page/{$page})', 'handler' => 'AtomHandler', 'action' => 'tag_collection', 'priority' => 8, 'description' => 'Atom Tag Collection' ),
			array( 'name' => 'display_entries_by_tag', 'parse_regex' => '%^tag/(?P<tag>[^/]*)(?:/page/(?P<page>\d+))?/?$%i', 'build_str' => 'tag/{$tag}(/page/{$page})', 'handler' => 'UserThemeHandler', 'action' => 'display_tag', 'priority' => 5, 'description' => 'Return posts matching specified tag.' ),
			array( 'name' => 'xmlrpc', 'parse_regex' => '%^xmlrpc/?$%i', 'build_str' => 'xmlrpc', 'handler' => 'XMLRPCServer', 'action' => 'xmlrpc_call', 'priority' => 8, 'description' => 'Handle incoming XMLRPC requests.' ),
		);
		$default_rules= Plugins::filter('default_rewrite_rules', $default_rules);
		$default_rules_properties= array( 'is_active' => 1, 'rule_class' => RewriteRule::RULE_SYSTEM );
		$rule_names= array_flip( array_map( create_function( '$a', 'return $a->name;' ), $rules ) );
		foreach( $default_rules as $default_rule ) {
			if( !isset( $rule_names[$default_rule['name']] ) ) {
				$rule_properties= array_merge( $default_rule, $default_rules_properties );
				$rules[]= new RewriteRule( $rule_properties );
			}
		}
		return $rules;
	}
	
	/**
	 * Return the active rewrite rules, both in the database and applied by plugins
	 * 
	 * @return array Array of RewriteRule objects for active rewrite rules
	 **/	  	 	 	
	static public function get_active()
	{
		static $system_rules;
	
		if(!isset($system_rules)) {
			$sql= "
				SELECT rr.rule_id, rr.name, rr.parse_regex, rr.build_str, rr.handler, rr.action, rr.priority
				FROM " . DB::table( 'rewrite_rules' ) . " AS rr
				WHERE rr.is_active= 1
				ORDER BY rr.priority";
			$db_rules= DB::get_results( $sql, array(), 'RewriteRule' );
		
			$system_rules= self::add_system_rules( $db_rules );
		}
		$rewrite_rules= Plugins::filter('rewrite_rules', $system_rules);

		usort($rewrite_rules, array('RewriteRules', 'sort_rules'));

		$c = __CLASS__;
		return new $c ( $rewrite_rules );
	}
	
	/**
	 * Helper function for sorting rewrite rules by priority.
	 * 
	 * Required because plugins would insert their rules at the end of the array,
	 * which would allow any other rule (including the one that executes by default
	 * when no other rules work) to execute first.
	 *
	 * @param RewriteRule $rulea A rule to compare
	 * @param RewriteRule $ruleb A rule to compare
	 * @return integer The standard usort() result values, -1, 0, 1 	 	 	  
	 **/	 	 	 
	public function sort_rules($rulea, $ruleb)
	{
		if( $rulea->priority == $ruleb->priority ) {
			return 0;
		}
		return ($rulea->priority < $ruleb->priority) ? -1 : 1;
	}
	
	/**
	 * Get a RewriteRule by its name
	 * 
	 * @param string $name The name of the rule
	 * @return RewriteRule The rule requested
	 * @todo Make this return more than one rule when more than one rule matches.
	 **/	 	 	 	 	
	public function by_name( $name )
	{
		$rules= self::get_active();
		$results = array();
		foreach($rules as $rule) {
			if($rule->name == $name) {
				$results[]= $rule;
			}
		}
		return count($results) ? $results : false;
	}
}

?>
