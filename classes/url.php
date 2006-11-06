<?php
/**
 * Habari URL Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

/**
 * class URL
 * Examines the URL based on rules and returns data about what response might best fit the request
 **/  
class URL
{
	private $rules = array();  // The rules for parsing the URL
	public $settings = array();  // The settings found based on the rules
	private $stub;  // Keep a persistent stub in this object
	private $using_rule = false;  // Flag for whether a rule was used to set settings
	private $rule_results;  // The matched rule
	public $handlerclass;  // The handler class of page that was requested
	public $handleraction;  // The action to pass to the handler that was requested
	private static $instance;  // Holds the singleton instance of this class
	
	/**
	 * constructor __construct
	 * Builds the query info for the requested url, sets internal structures with found data
	 * @param string A URL to process
	 **/	 	 	 	 	
	public function __construct( $url )
	{
		// Remove the front of the URL path, which isn't determinant for this class
		// Keep the "stub"		
		$base = parse_url( Options::get('base_url') );
		$stub = $url;
		if ( $base[ 'path' ] != '' ) {
			$stub = substr( $stub, strpos( $url, $base[ 'path' ] ) + strlen( $base[ 'path' ] ) );
		}
		if ( substr( $stub, -1, 1 ) == '/' ) {
			$stub = substr( $stub, 0, -1 );
		}
		
		// Remove the querystring
		if ( strpos($stub, '?') ) {
			list($stub, $querystring) = explode('?', $stub, 2);
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
		$setvars = array();
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
				// Check for year part (four numbers)
				case ( $rule_parts[ $z ] == 'index' ) :
					if ( is_numeric( $parts[ $z ] ) ) {
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
				$this->handlerclass = $rule[1];
				$this->handleraction = $rule[2];
				$this->rule_results = $rule;
				break;
			}
		}

		// See if any variables were set on the querystring and override what was found in the URL
		/*
		foreach ( $this->rules as $rule ) {
			$parts = explode( '/', $rule[0] );
			foreach ( $parts as $varname ) {
				if ( $varname{0} == '"' ) continue;
				if ( isset( $_REQUEST[$varname] ) ) {
					$setvars[ $varname ] = $_REQUEST[ $varname ];
				}
			}
		}
		*/
		/**
		 * The above code block scans every rule looking for settable parameters.
		 * It then looks for those parameters on the querystring and absorbs them	
		 * into $setvars, which is then stored in $this->settings.
		 * 
		 * The below code simply merges any posted variables or querystring arguments
		 * into $setvars directly.
		 **/		 		 		 		 		 	 
		// Let's see how deadly this is:
		$setvars = array_merge($setvars, $_GET, $_POST);  // Should do magic_quotes_gpc removal here.

		$this->settings = $setvars;
	}
	
	/**
	 * function handle_request
	 * Act upon discovered results
	 **/
	public function handle_request()
	{
		if(isset($this->handlerclass)) {
			new $this->handlerclass($this->handleraction, $this->settings);
		}
		else {
			// 404!
			_e('The thing you were looking for was not found.  We should probably display some kind of intelligent error here.');
		}
	}
	
	/**
	 * function __get
	 * Returns property values gathered from the URL.
	 * This is the function that returns information about the set of posts that
	 * was requested.  This function should offer property names that are identical
	 * to properties of instances of the Posts class.
	 * @param string Value of $settings to retrieve
	 **/
	public function __get($name)
	{
		switch($name) {
		case 'onepost':
			return ($this->handleraction == 'post');
		}
		return false;
	}
	
	/**
	 * function get
	 * Returns a url for the specified resource.
	 * May be called on an object, or statically if the $url global is set:
	 * 
	 * <code>
	 * $foo = $url->get( 'tag', 'tag=my-tag' );
	 * $foo = $url->get( 'tag', array( 'tag' => 'my-tag' ) );	  	 
	 * $foo = URL::get( 'admin' );
	 * $foo = URL::get( 'admin', array( 'page' => 'options' )  );
	 * </code>
	 * 	 
	 * @param string The type of page the resource is (see the rules)
	 * @param mixed Optional. An associative array or querystring of parameters used to fill the URL structure
	 * @param boolean Optional. If true, any elements from $paramarray that are not used in the URL itself are added as a querystring
	 * @return string A URL
	 **/	 	 	 	  	 	 		
	public function get( $pagetype, $paramarray = array(), $useall = true)
	{
		global $url;
		$c = __CLASS__;
		if (  $this instanceof $c ) {
			// get() was called on an instance
			$rules = $this->rules;
		}
		else {
			// get() was called statically
			$rules = $url->rules;
		}

		$params = Utils::get_params($paramarray);
		
		$fn = create_function( '$a', 'return $a[2] == "' . $pagetype . '";' );
		$rules = array_filter( $rules, $fn );
		foreach ( $rules as $rule ) {
			$output = '';
			$parts = explode( '/', $rule[0] );
			$fail = false;
			$used = array();
			foreach ( $parts as $part ) {
				switch( true ) {
				case ( $part{0} == '"' ) :
					$output .= '/' . substr( $part, 1, -1 );
					break 1;
				default :
					if( $part == '') {
						// Do nothing.
					}
					elseif ( isset( $params[$part] ) && $params[$part] != '' ) {
						$output .= '/' . $params[ $part ];
						$used[] = $part;
						break 1;
					}
					else {
						$fail = true;
						break 2;
					}
				}
			}
			if ( !$fail ) {
				if ( $useall ) {
					$unused = array();
					foreach ( $params as $key=>$param ) {
						if ( !in_array( $key, $used ) && $param != '' ) {
							$unused[$key] = $param;
						}
					}
					$querystring = http_build_query($unused);
					$querystring = ($querystring == '' ? '' : '?') . $querystring;
				}
				else {
					$querystring = '';
				}
				return Utils::end_in_slash(Options::get('base_url')) . trim($output, '/') . $querystring;
			}
		}
		return '#unknown';
	}
	
	/**
	 * function get_url
	 * Alias for get()
	 * Deprecate?  Use get() convention for objects that get things.
	 * May be called on an object, or statically if the $url global is set:
	 * 
	 * <code>
	 * $foo = $url->get_url('admin');
	 * $foo = URL::get_url('admin');
	 * </code>
	 * 	 
	 **/
	public function get_url( $pagetype, $paramarray = array(), $useall = true )
	{
		global $url;
		$c = __CLASS__;
		if ( $this instanceof $c ) {
			// get_url() was called on an instance
			$out = $this->get( $pagetype, $paramarray, $useall );
		}
		else {
			// get_url() was called statically
			$out = $url->get( $pagetype, $paramarray, $useall );
		}
		return $out;
	}
	
	/**
	 * function out
	 * Shortcut to echo the result of $this->get()
	 * May be called on an object, or statically if the $url global is set:
	 * 
	 * <code>
	 * $url->out('admin');
	 * URL::out('admin');
	 * </code>
	 * 	 	 
	 * @param string The type of page the resource is (see the rules)
	 * @param mixed An associative array or querystring of parameters used to fill the URL structure
	 **/
	public function out( $pagetype, $paramarray = array(), $useall = true )
	{
		global $url;
		$c = __CLASS__;
		if ( $this instanceof $c ) {
			// out() was called on an instance
			$out = $this->get( $pagetype, $paramarray, $useall );
		}
		else {
			// out() was called statically
			$out = $url->get( $pagetype, $paramarray, $useall );
		}
		echo $out;
	}
	
	/**
	 * function o
	 * Returns the global $url object instance, whether set or not.
	 * Instead of declaring $url as global everywhere, you can call it like
	 * this for shorthand:	 
	 * 
	 * <code>
	 * URL::o()->get('admin')	 
	 * </code>
	 * 
	 * URL::o() will return whatever the global $url is set to, even null if
	 * $url is not set.	 	 	 
	 * 	 	 
	 * @return mixed The value of the global $url
	 **/	 	 
	public static function o()
	{
		global $url;
		return $url;
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
		 *  Each entry is a three-element array.
		 *  The first element is the URL structure.
		 *  The URL structure consists of
		 *    quoted literal values (that will appear in the output URL literally
		 *    and field names that will be replaced with data from the post or calling procedure
		 *  The second element is the handler class name.
		 *  The handler class name should be a descendant of ActionHandler		 		 		 
		 *  The third element is the action
		 *  There can be more than one entry per action
		 *  The earliest entry that has available matching fields is the one that is used.
		 *  Put literal text matches at any level before capture matches.  For example...
		 *  	Put '"feed"' before 'slug' or else it will always match http://example.com/feed as a slug of "feed".
		 *  	Likewise, put 'foo/"bar"/baz' before 'foo/qux'.
		 *  year, month, and day are all special captures that will capture only their respective types. ie /[0-9]{4}/ and /[0-9]{2}/
		 **/
		// admin rules
		$this->rules[] = array('"admin"/page', 'AdminHandler', 'admin');
		$this->rules[] = array('"admin"', 'AdminHandler', 'admin');
		$this->rules[] = array('"feedback"', 'ContentHandler', 'add_comment');
		$this->rules[] = array('"admin"/"ajax"/action', 'AjaxHandler', 'ajaxhandler');
		// user rules
		$this->rules[] = array('"login"/action', 'UserHandler', 'login');
		$this->rules[] = array('"login"', 'UserHandler', 'login');
		$this->rules[] = array('"logout"', 'UserHandler', 'logout');
		// post rules
		$this->rules[] = array('year/month/day', 'ThemeHandler', 'date');
		$this->rules[] = array('year/month', 'ThemeHandler', 'month');
		$this->rules[] = array('year', 'ThemeHandler', 'year');
		$this->rules[] = array('"tag"/tag', 'ThemeHandler', 'tag');
		$this->rules[] = array('"author"/author', 'ActionHandler', 'author');
		$this->rules[] = array('"atom"/index', 'AtomHandler', 'collection');
		$this->rules[] = array('"atom"', 'AtomHandler', 'introspection');
		$this->rules[] = array('"feed"/feedtype', 'ActionHandler', 'site_feed');
		$this->rules[] = array('"comments"', 'ActionHandler', 'comments_feed');
		$this->rules[] = array('"comments"/feedtype', 'ActionHandler', 'comments_feed');
		$this->rules[] = array('"changepass"', 'UserHandler', 'changepass');
		$this->rules[] = array('"pingback"', 'ActionHandler', 'pingback');
		$this->rules[] = array('', 'ThemeHandler', 'home');
		$this->rules[] = array('"index.php"', 'ThemeHandler', 'home');
		$this->rules[] = array('"ajax"/action', 'ActionHandler', 'ajax');
		$this->rules[] = array('slug', 'ThemeHandler', 'post');
		$this->rules[] = array('slug/"page"/index', 'ThemeHandler', 'post');
		$this->rules[] = array('slug/"atom"', 'AtomHandler', 'entry');
		$this->rules[] = array('slug/"feed"/feedtype', 'ActionHandler', 'post_feed');
		$this->rules[] = array('slug/"trackback"', 'ActionHandler', 'trackback');
	}

}

?>
