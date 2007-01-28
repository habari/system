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
	private $rules= null;
 
	/**
	 * Enables singleton working properly
	 * 
	 * @see singleton.php
	 */
	static protected function instance()
	{
		return parent::instance( get_class() );
	}
 
	/**
	 * A simple caching mechanism to avoid reloading rule array
	 */
	private function load_rules()
	{
		if ( URL::instance()->rules != NULL )
			return;
		URL::instance()->rules= RewriteRules::get_active();
	}

  /**
   * A method which accepts a URL/URI and runs the string against
   * rewrite rules stored in the DB.  This method is used by 
   * the Controller class in parsing regular requests, as well
   * as other classes, such as Pingback, which take a URL from the
   * raw HTTP payload and determine slugs from that URL.
   * 
   * The function returns a RewriteRule object that is matched, or 
   * FALSE otherwise
   * 
   * @param url URL string to parse
   * @return  RewriteRule matched rule
   */
  public static function parse($from_url) {
    $base_url= Options::get('base_url');
    
    /* 
     * Strip out the base URL from the requested URL
     * but only if the base URL isn't / 
     */
    if ( '/' != $base_url)
	    $from_url= str_replace($base_url, '', $from_url);
    
    /* Trim off any leading or trailing slashes */
    $from_url= trim($from_url, '/');

    /* Remove the querystring from the URL */
    if ( strpos($from_url, '?') !== FALSE )
      list($from_url, )= explode('?', $from_url);
  
    $url= URL::instance();
    $url->load_rules(); // Cached in singleton

    /* 
     * Run the stub through the regex matcher
     */
    $pattern_matches= array();
    foreach ($url->rules as $rule) {
      if ( 1 == preg_match(
                $rule->parse_regex
                , $from_url
                , $pattern_matches) ) {
        
	      $rule->entire_match= array_shift( $pattern_matches ); // The entire matched string is returned at index 0
	      if(count($rule->named_args) > 0) {
	        $rule->named_arg_values= array_combine($rule->named_args, $pattern_matches);
					if (preg_match('/^\\{\\$(\\w+)\\}$/', $rule->action, $matches)) {
						$rule->action= $rule->named_arg_values[$matches[1]]; 
					}
				}
        
        return $rule;
        /* Stop processing at first matched rule... */
        break;
      }
    }
    return false;
  }  

	/** 
	 * Builds the required pretty URL given a supplied
	 * rule name and a set of placeholder replacement
	 * values and returns the built URL.
	 * 
	 * <code>
	 * URL::get( 'display_posts_by_date', 
	 *  array( 'year'=>'2000'
	 *    , 'month'=>'05'
	 *    , 'day'=>'01' );
	 * </code>
	 * 
	 * @param rule  string identifier for the rule which would build the URL
	 * @param args  ( optional ) array of placeholder replacement values
	 */
	static public function get( $rule_name, $args= array() )
	{
		/*
		 * This code is here for backwards compatibility with the old
		 * URL API which allowed passing arguments as a querystring-style
		 * long string or an array.  Would be nice to remove this and standardize
		 * on one method or the other...
		 * @todo TODO Standardize the argument input.
		 */
		if ( ! is_array( $args ) )
			parse_str( $args, $args ); 
		
		$url= URL::instance();
		$url->load_rules();
		if ( isset( $url->rules[$rule_name] ) ) {
			$rule= $url->rules[$rule_name];
			$return_url= $rule->build_str;
			foreach ( $rule->named_args as $replace ) {
				if( isset( $args[$replace] ) ) {
					$return_url= str_replace( '{$' . $replace . '}', $args[$replace], $return_url );
					/* 
					 * Remove from the argument list so we can append 
					 * any outlier args as query string args
					 */
					unset( $args[$replace] );
				}
			}
			/*
			 * OK, now append any outliers passed in to the function
			 * as query string arguments
			 */
			if ( count( $args ) > 0 ) {
				$return_url.= '?';
				foreach ( $args as $key => $value ) {
					$return_url.= $key . '=' . $value . '&';
				}
				$url= rtrim( $return_url, '&' );
			}
			
			return
				'http' . ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 's' : '' ) . 
				'://' . $_SERVER['HTTP_HOST'] . '/' . ltrim( Controller::get_base_url(), '/' ) .
				$return_url;
		}
	}

	/**
	 * Helper wrapper function.  Outputs the URL via echo.
	 */
	static public function out( $rule_name, $args= array() )
	{
		echo URL::get( $rule_name, $args );
	}
}

?>
