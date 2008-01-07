<?php

/**
 * URL class which handles creation of URLs based on the rewrite
 * rules in the database.  Uses rules to construct pretty URLs for use
 * by the system and especially the theme's template engine
 * 
 * @package Habari
 */
class URL extends Singleton
{
	// static collection of rules ( pulled from RewriteController )
	private $rules= NULL;
	private $matched_rule= NULL;
 
	/**
	 * Enables singleton working properly
	 * 
	 * @see singleton.php
	 */
	protected static function instance()
	{
		return self::getInstanceOf( get_class() );
	}
 
	/**
	 * A simple caching mechanism to avoid reloading rule array
	 */
	private function load_rules()
	{
		if ( URL::instance()->rules != NULL ) {
			return;
		}
		URL::instance()->rules= RewriteRules::get_active();
	}
	
	/**
	 * Get the matched RewriteRule that was matched in parse().
	 * 
	 * @return RewriteRule matched rule, or NULL
	 */
	public static function get_matched_rule()
	{
		return URL::instance()->matched_rule;
	}

	/**
	 * Match a URL/URI against the rewrite rules stored in the DB.
	 * This method is used by the Controller class for parsing
	 * requests, and by other classes, such as Pingback, which
	 * uses it to determine the post slug for a given URL.
	 * 
	 * Returns the matched RewriteRule object, or FALSE.
	 * 
	 * @param string $from_url URL string to parse
	 * @return RewriteRule matched rule, or FALSE
	 */
	public static function parse( $from_url )
	{
		$base_url= Site::get_path( 'base', true );
		
		/* 
		 * Strip out the base URL from the requested URL
		 * but only if the base URL isn't / 
		 */
		if ( strpos( $from_url, $base_url ) === 0 ) {
			$from_url= substr( $from_url, strlen( $base_url ) );
		}
		
		/* Trim off any leading or trailing slashes */
		$from_url= trim( $from_url, '/' );
	
		/* Remove the querystring from the URL */
		if ( strpos( $from_url, '?' ) !== FALSE ) {
			list( $from_url, )= explode( '?', $from_url );
		}
	
		$url= URL::instance();
		$url->load_rules(); // Cached in singleton
	
		/* 
		 * Run the stub through the regex matcher
		 */
		$pattern_matches= array();
		foreach ( $url->rules as $rule ) {
			if ( $rule->match( $from_url ) ) {
				$url->matched_rule= $rule;
				/* Stop processing at first matched rule... */
				return $rule;
			}
		}
		
		return FALSE;
	}
	
	/** 
	 * Builds the required pretty URL given a supplied
	 * rule name and a set of placeholder replacement
	 * values and returns the built URL.
	 * 
	 * <code>
	 * 	URL::get( 'display_entries_by_date', array(
	 * 		'year' => '2000',
	 *    	'month' => '05',
	 *    	'day' => '01',
	 * 	) );
	 * </code>
	 * 
	 * @param mixed $rule_names string name of the rule or array of rules which would build the URL
	 * @param mixed $args (optional) array or object of placeholder replacement values
	 * @param boolean $useall If true (default), then all passed parameters that are not part of the built URL are tacked onto the URL as querystring	 
	 */
	public static function get( $rule_names, $args= array(), $useall= true, $noamp= false )
	{
		$args= self::extract_args( $args ); 
		
		$url= URL::instance();
		$url->load_rules();
		if ( !is_array( $rule_names ) ) {
			$rule_names= array( $rule_names );
		}
		foreach ( $rule_names as $rule_name ) {
			if ( $rules= $url->rules->by_name( $rule_name ) ) {
				$rating= null;
				$selectedrule= null;
				foreach ( $rules as $rule ) {
					$newrating= $rule->arg_match( $args );
					// Is the rating perfect?
					if ( $rating == 0 ) {
						$selectedrule= $rule;
						break;
					} 
					if ( empty( $rating ) || ( $newrating < $rating ) ) {
						$rating= $newrating;
						$selectedrule= $rule;
					}
				}
				if ( isset( $selectedrule ) ) {
					$return_url= $selectedrule->build( $args, $useall, $noamp );
					return Site::get_url( 'habari', true ) . $return_url;
				}
			}
		}
	}
	
	/**
	 * Helper wrapper function.  Outputs the URL via echo.
	 * @param string $rule_name name of the rule which would build the URL
	 * @param array $args (optional) array of placeholder replacement values
	 * @param boolean $useall If true (default), then all passed parameters that are not part of the built URL are tacked onto the URL as querystring	 
	 */
	public static function out( $rule_name, $args= array(), $useall= true, $noamp= true )
	{
		echo URL::get( $rule_name, $args, $useall, $noamp );
	}

	/**
	 * Extract the possible arguments to use in the URL from the passed variable
	 * @param mixed $args An array of values or a URLProperties object with properties to use in the construction of a URL
	 * @return array Properties to use to construct  a URL
	 **/	 	 	 	
	public static function extract_args( $args, $prefix= '' )
	{
		if ( is_object( $args ) ) {
			if ( $args instanceof URLProperties ) {
				$args= $args->get_url_args();
			}
			else {
				$args_ob= array();
				foreach ( $args as $key => $value ) {
					$args_ob[$key]= $value;
				}
				$args= $args_ob;
			}
		}
		else {
			$args= Utils::get_params( $args );
		}
		// can this be done with array_walk?
		if ( $prefix && $args ) {
			$args_out= array();
			foreach ( $args as $key => $value ) {
				$args_out[$prefix.$key]= $value;
			}
			$args= $args_out;
		}
		return $args;
	}

}

?>
