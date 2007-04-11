<?php
/**
 * Helper class to encapsulate rewrite rule data
 */
class RewriteRule extends QueryRecord 
{
	const RULE_SYSTEM = 0;
	const RULE_THEME = 1;
	const RULE_PLUGIN = 2;
	const RULE_CUSTOM = 5;
	
	public $entire_match= ''; // exact matched string
	public $named_arg_values= array(); // values of named arguments filled during URL::parse()
	private $m_named_args= null; // named argument matches

	
	/**
	 * function default_fields
	 * Returns the defined database columns for a Post
	 * @return array Array of columns in the Post table
	**/
	public static function default_fields()
	{
		return array(
			'rule_id' => 0,
			'name' => '',
			'parse_regex' => '/.^/',
			'build_str' => '',
			'handler' => '',
			'action' => '',
			'priority' => 0,
			'is_active' => 0,
			'rule_class' => RewriteRule::RULE_CUSTOM,
			'description' => ''
		);
	}
	
	/**
	 * Constructor for the rewrite_rule class.
	 * @param array $paramarray an associative array or querystring of initial field values
	 **/	 	 	 	
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields );
		
		parent::__construct( $paramarray );
		$this->exclude_fields('rule_id');
	}
	
  
	/**
	 * Match the stub against this rule
   * Also sets internal structures based on a successful match   
   * @param string The URL stub to match against
	 * @return boolean True if this rule matches the stub, false if not
   **/	 	 	   
	public function match($stub) 
	{
		if( preg_match($this->parse_regex, $stub, $pattern_matches) ) {
			$this->entire_match= array_shift( $pattern_matches ); // The entire matched string is returned at index 0
			if(count($this->named_args) > 0) {
				$this->named_arg_values= array_combine($this->named_args, $pattern_matches);
				if (preg_match('/^\\{\\$(\\w+)\\}$/', $this->action, $matches)) {
					$this->action= $this->named_arg_values[$matches[1]]; 
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Builds a URL using this rule based on the passed in data
	 * @param array $args An associative array of arguments to use for replacement in the rule
	 * @return string The URL created from the substituted arguments
	 **/	 	 
	public function build($args)
	{
		$args = Plugins::filter('rewrite_args', $args, $this->name);
		$searches= array_map(array('Utils', 'map_array'), array_keys($args));
		$return_url= str_replace($searches, $args, $this->build_str);
		$args = array_diff_key($args, array_flip($this->named_args));
		// Append any remaining args as query string arguments:
		$return_url.= (count($args)==0) ? '' : '?' . http_build_query($args);
		return $return_url;
	}
	
	/**
	 * Magic property getter for this class
	 * @param string $name The name of the class property to return
	 * @returns mixed The value of that field in this object
	 **/	 	 
	public function __get($name) 
	{
		switch($name) {
		case 'named_args':
			if(empty($this->m_named_args)) {
				preg_match_all('/\\{\\$(\\w+?)\\}/', $this->build_str, $matches);
				$this->m_named_args = $matches[1];
			}
			return $this->m_named_args;
		default:
			return parent::__get($name);
		}
	}
	
	/**
	 * Saves a new rewrite rule to the rewrite_rules table
	 */	 	 	 	 	
	public function insert()
	{
		$result = parent::insert( DB::table('rewrite_rules') );
		return $result;
	}	
	
	/**
	 * Updates an existing rule in the rewrite_rules table
	 */	 	 	 	 	
	public function update()
	{
		$result = parent::update( DB::table('rewrite_rules'), array('rule_id'=>$this->rule_id) );
		return $result;
	}
	
	/**
	 * Deletes an existing rule
	 */	 	 	 	 	
	public function delete()
	{
		return parent::delete( DB::table('rewrite_rules'), array('rule_id'=>$this->rule_id) );
	}

	/**
	* Create an old-style rewrite rule
	* @return RewriteRule The created rule
	*/
	static public function create_url_rule( $build_str, $handler, $action )
	{
		$arr = split( '/', $build_str );

		$re_arr = preg_replace('/^([^"\']+)$/', "(.+)", $arr);
		$re_arr = preg_replace('/^["\'](.+)["\']$/', '\\1', $re_arr);

		$str_arr = preg_replace('/^([^"\']+)$/', '{$\\1}', $arr);
		$str_arr = preg_replace('/^["\'](.+)["\']$/', '\\1', $str_arr);

		$regex = '/^' . implode( '\\/', $re_arr ) . '\\/?$/i';
		$build_str = implode( '/', $str_arr );

		return new RewriteRule( array(
			'name' => $action, 
			'parse_regex' => $regex, 
			'build_str' => $build_str, 
			'handler' => $handler, 
			'action' => $action, 
			'priority' => 1,
			'is_active' => 1,
			'rule_class' => RewriteRule::RULE_CUSTOM,
			'description' => 'Custom old-style rule.',
		) );
	}	
	
}

?>
