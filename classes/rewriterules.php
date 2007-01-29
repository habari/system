<?php

/**
 * Class for storing and retrieving rewrite rules from the DB.
 */      
class RewriteRules extends ArrayObject {
	
	static public function get_active()
	{
		$sql= "
			SELECT rr.rule_id, rr.name, rr.parse_regex, rr.build_str, rr.handler, rr.action, COUNT( * ) as num_args
			FROM " . DB::table( 'rewrite_rules' ) . " AS rr
			LEFT JOIN " . DB::table( 'rewrite_rule_args' ) . " AS rra
			ON rr.rule_id= rra.rule_id
			WHERE rr.is_active= 1
			GROUP BY rr.rule_id, rr.name, rr.parse_regex, rr.build_str, rr.handler, rr.action, rr.priority
			ORDER BY rr.priority";
		$db_rules= DB::get_results( $sql, array(), 'RewriteRule' );

		$c = __CLASS__;
		return new $c ( $db_rules );
	}
	
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
