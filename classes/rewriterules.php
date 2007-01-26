<?php

/**
 * Class for storing and retrieving rewrite rules from the DB.
 */      
class RewriteRules {
	
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
		DB::set_fetch_mode( PDO::FETCH_ASSOC );
		$db_rules= DB::get_results( $sql );

		/* 
		 * OK, so we start with an array of rewrite rule data
		 * from the DB filtered for active rules.  Now, we
		 * create the RewriteRule objects, noting which ones
		 * we need to query for named args from the DB afterwards
		 */
		$rules_with_args= array();
		$rules= array(); // array of RewriteRule objects we return
		
		foreach ( $db_rules as $db_rule ) {
			if ( ( int ) $db_rule['num_args'] > 0 ) {
				$rules_with_args[]= $db_rule['rule_id'];
			}
			$current_rule= new RewriteRule();
			foreach ( array( 'name','parse_regex','build_str','handler','action' ) as $key ) {
				$current_rule->$key= $db_rule[$key];
			}
			$rules[$db_rule['name']]= $current_rule;
		}

		/*
		 * Now grab the named arguments for the needed rules
		 */
		if ( count( $rules_with_args ) > 0 ) {
			$sql= "
				SELECT rr.name as rule_name, rra.rule_id, rra.name, rra.arg_index
				FROM " . DB::table( 'rewrite_rule_args' ) . " AS rra
				INNER JOIN " . DB::table( 'rewrite_rules' ) . " AS rr
				ON rra.rule_id= rr.rule_id
				WHERE rra.rule_id IN ( " . implode( ',', $rules_with_args ) . " )
				ORDER BY rra.rule_id, rra.arg_index";
			$rule_args= DB::get_results( $sql );
	
			/*
			 * Loop through the named args, matching to the rules
			 */
			foreach ( $rule_args as $rule_arg ) {
				$rules[$rule_arg['rule_name']]->named_args[]= $rule_arg['name'];
			}
		}

		return $rules;
	}
}

?>
