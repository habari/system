<?php
/**
 * Habari URLParser Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

/**
 * class URLParser
 * Examines the URL based on rules and returns data about what response might best fit the request
 **/  
class URLParser
{
	private $rules = array();  // The rules for parsing the URL
	public $settings = array();  // The settings found based on the rules
	private $stub;  // Keep a persistent stub in this object
	private $using_rule = false;  // Flag for whether a rule was used to set settings
	private $rule_results = array();  // The match ratings of each rule
	public $pagetype;  // The type of page that was requested
	
	/**
	 * constructor __construct
	 * Builds the query info for the requested url, sets internal structures with found data
	 * @param string A URL to process
	 **/	 	 	 	 	
	public function __construct( $url )
	{
		global $options; 

		// Remove the front of the URL path, which isn't determinant for this class
		// Keep the "stub"		
		$base = parse_url( $options->base_url );
		$stub = $url;
		if ( $base[ 'path' ] != '' ) {
			$stub = substr( $stub, strpos( $url, $base[ 'path' ] ) + strlen( $base[ 'path' ] ) );
		}
		if ( substr( $stub, -1, 1 ) == '/' ) {
			$stub = substr( $stub, 0, -1 );
		} 
		
		// Record the stub for future use
		$this->stub = $stub;
		
		// Get rules
		$this->init_rules();
		
		// Break apart the stub
		$parts = explode( '/', $this->stub );
		if( $parts[0] == '' ) $parts = array();

		// Skip rules that don't have the same path part count 
		$fn = create_function('$a', 'return ($a[0] != "" && count(explode("/",$a[0])) == ' . count($parts) . ') || ($a[0] == "" && ' . count($parts) . ' == 0);');
		$rules = array_filter( $this->rules, $fn );

		// See if any rules match
		foreach ( $rules as $rule ) {
			$setvars = array();
			$rule_parts = explode( '/', $rule[0] );
			$fail = false;
			for ( $z = 0 ; $z < count( $parts ) ; $z++ ) {
				switch ( true ) {
				/**
				 * Conditional cases for the path parts go here.
				 * If you wanted to add a thing that checks the url part against a list
				 * of tags, you would add it here.  Doing this might be useful for
				 * allowing tags on the root path to differentiate it from other paths.
				 **/
				// Check for month/day parts (two numbers)
				case ( $rule_parts[ $z ] == 'month' ) :
				case ( $rule_parts[ $z ] == 'day' ) :
					if ( strlen( $parts[ $z ] ) == 2 && is_numeric( $parts[ $z ] ) ) {
						$setvars[ $rule_parts[ $z ] ] = $parts[ $z ];
					}
					else {
						$fail = true;
						break 2;
					}
					break 1;
				// Check for year part (four numbers)
				case ( $rule_parts[ $z ] == 'year' ) :
					if ( strlen( $parts[ $z ] ) == 4 && is_numeric( $parts[ $z ] ) ) {
						$setvars[ $rule_parts[ $z ] ] = $parts[ $z ];
					}
					else {
						$fail = true;
						break 2;
					}
					break 1;
				// Check for literal parts
				case ( $rule_parts[ $z ]{0} == '"' ) :
					if ( substr( $rule_parts[ $z ], 1, -1 ) != $parts[ $z ] ) {
						$fail = true;
						break 2;
					}
					break 1;
				default: 
					$setvars[ $rule_parts[ $z ] ] = $parts[ $z ];
					break 1; 
				}
			}
			if ( !$fail ) {
				$this->pagetype = $rule[1];
				break;
			}
		}

		// See if any variables were set on the querystring and override what was found in the URL
		foreach ( $this->rules as $rule ) {
		
			$parts = explode( '/', $rule[0] );
			foreach ( $parts as $var ) {
				if ( $var{0} == '"' ) continue;
				if ( isset( $_GET[$varname] ) ) {
					$setvars[ $varname ] = $_GET[ $varname ];
				}
			}
		}

		$this->settings = $setvars;
	}

	/**
	 * function get_url
	 * Returns a url for the specified resource.
	 * @param string The type of page the resource is (see the rules)
	 * @param mixed An associative array or querystring of parameters used to fill the URL structure
	 * @return string A URL
	 * 
	 * echo $urlparser->get_url( 'tag', 'tag=my-tag' );
	 * echo $urlparser->get_url( 'tag', array( 'tag' => 'my-tag' ) );	  	 
	 **/	 	 	 	  	 	 		
	public function get_url( $pagetype, $paramarray )
	{
		global $options;
	
		$params = Utils::get_params($paramarray);
		
		$fn = create_function( '$a', 'return $a[1] == "' . $pagetype . '";' );
		$rules = array_filter( $this->rules, $fn );
		foreach ( $rules as $rule ) {
			$parts = explode( '/', $rule[0] );
			$fail = false;
			foreach ( $parts as $part ) {
				switch( true ) {
				case ( $part{0} == '"' ) :
					$output .= '/' . substr( $part, 1, -1 );
					break 1;
				default :
					if ( isset( $params[ $part ] ) ) {
						$output .= '/' . $params[ $part ];
						break 1;
					}
					else {
						$fail = true;
						break 2;
					}
				}
			} 
			if ( !$fail ) {
				return $options->base_url . '/' . trim($output, '/');
			}
		}
		return '#unknown';
	}

	/**
	 * function init_rules()
	 * Sets the basic rules for URL structures.  Will probably not remain in this format.
	 * Called internally only from constructor.	 
	 **/	 	 	
	protected function init_rules()
	{
		/**
		 *  To make a rule:
		 *  Add a new entry to the $this->rules array.
		 *  Each entry is a two-element array.
		 *  The first element is the URL structure.
		 *  The URL structure consists of
		 *    quoted literal values (that will appear in the output URL literally
		 *    and field names that will be replaced with data from the post or calling procedure		 
		 *  The second element is the pagetype
		 *  There can be more than one entry per pagetype
		 *  The earliest entry that has available matching fields is the one that is used.		 		 
		 **/		 
		$this->rules[] = array('year/month/day', 'date');
		$this->rules[] = array('year/month', 'month');
		$this->rules[] = array('year', 'year');
		$this->rules[] = array('"tag"/tag', 'tag');
		$this->rules[] = array('"author"/author', 'author');
		$this->rules[] = array('slug', 'single');
		$this->rules[] = array('', 'home');
		$this->rules[] = array('"ajax"/action', 'ajax');
	}

}

?>
