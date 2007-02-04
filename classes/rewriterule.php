<?php
/**
 * Helper class to encapsulate rewrite rule data
 */
class RewriteRule extends QueryRecord 
{
/*
  public $name;                       // name of the rule
  public $parse_regex;                // regex expression for incoming matching
  public $build_str;                  // string with optional placeholders for outputting URL
  public $handler;                    // name of action handler class
  public $action;                     // name of action that handler should execute
*/  
  public $entire_match= '';           // exact matched string
//  public $named_args= array();        // named argument matches
  public $named_arg_values= array();  // values of named arguments filled during URL::parse()
  
  private $m_named_args= null;
  
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
	 * Wraps the passed-in value in {$ }
	 * Used by an array_map privately in this class.
	 * @param string $value A string value
	 * @returns string The value wrapped in {$ }
	 **/	 	 	  	 	 	
	private static function wrap_replacement($value)
	{
		return '{$' . $value . '}';
	}
	
	/**
	 * Builds a URL using this rule based on the passed in data
	 * @param array $args An associative array of arguments to use for replacement in the rule
	 * @return string The URL created from the substituted arguments
	 **/	 	 
	public function build($args)
	{
		$searches= array_map(array('RewriteRule', 'wrap_replacement'), array_keys($args));
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
			if(empty($m_named_args)) {
				preg_match_all('/\\{\\$(\\w+?)\\}/', $this->build_str, $matches);
				$m_named_args = $matches[1];
			}
			return $m_named_args;
		default:
			return parent::__get($name);
		}
	}
}

?>
