<?php

/**
 * Class for storing and retrieving rewrite rules from the DB.
 */      
class RewriteRules extends ArrayObject {
	
	/**
	 * Return the active rewrite rules, both in the database and applied by plugins
	 * 
	 * @return array Array of RewriteRule objects for active rewrite rules
	 **/	  	 	 	
	static public function get_active()
	{
		$sql= "
			SELECT rr.rule_id, rr.name, rr.parse_regex, rr.build_str, rr.handler, rr.action, rr.priority
			FROM " . DB::table( 'rewrite_rules' ) . " AS rr
			WHERE rr.is_active= 1
			ORDER BY rr.priority";
		$db_rules= DB::get_results( $sql, array(), 'RewriteRule' );

		$db_rules= Plugins::filter('rewrite_rules', $db_rules);
		
		usort($db_rules, array('RewriteRules', 'sort_rules'));

		$c = __CLASS__;
		return new $c ( $db_rules );
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
	 * @param $name The name of the rule
	 * @return RewriteRule The rule requested
	 **/	 	 	 	 	
	public function by_name( $name )
	{
		foreach($this as $rule) {
			if($rule->name == $name) {
				return $rule;
			}
		}
		return false;
	}
}

?>
