<?php
/**
 * @package Habari
 *
 */

/**
 * Habari RewriteRule Class
 *
 * Helper class to encapsulate rewrite rule data
 *
 */
class RewriteRule extends QueryRecord
{

	const RULE_SYSTEM = 0;
	const RULE_THEME = 1;
	const RULE_PLUGIN = 2;
	const RULE_CUSTOM = 5;

	public $entire_match = null; // Entire matched string from the URL
	public $named_arg_values = array(); // Values of named arguments filled during URL::parse()
	private $m_named_args = null; // Named arguments matches


	/**
	 * Returns the defined database columns for a rewrite rule.
	 * @return array Array of columns in the rewrite_rules table
	 */
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
			'description' => '',
			'parameters' => '',
		);
	}

	/**
	 * Constructor for the rewrite_rule class.
	 * @param array $paramarray an associative array or querystring of initial field values
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields );

		parent::__construct( $paramarray );
		$this->exclude_fields( 'rule_id' );
	}


	/**
	 * Match the stub against this rule
	 * Also sets internal structures based on a successful match
	 * @param string The URL stub to match against
	 * @return boolean True if this rule matches the stub, false if not
	 */
	public function match( $stub )
	{
		if ( preg_match( $this->parse_regex, $stub, $pattern_matches ) > 0 ) {
			$this->entire_match = array_shift( $pattern_matches ); // The entire matched string is returned at index 0
			$named_args = $this->named_args; // Direct call shows a PHP notice

			if ( (is_string($this->parameters) && $parameters = unserialize( $this->parameters )) || (is_array($this->parameters) && $parameters = $this->parameters )) {
				$this->named_arg_values = array_merge( $this->named_arg_values, $parameters );
			}

			foreach ( $named_args as $keys ) {
				foreach ( $keys as $key ) {
					if ( !empty( $pattern_matches[$key] ) ) {
						$this->named_arg_values[$key] = urldecode( str_replace( '%252F', '%2F', $pattern_matches[$key] ) );
					}
				}
			}

			if ( preg_match( '/^\\{\\$(\\w+)\\}$/u', $this->action, $matches ) > 0 ) {
				$this->action = $this->named_arg_values[$matches[1]];
			}

			if ( isset( $parameters['require_match'] ) ) {
				return call_user_func( $parameters['require_match'], $this, $stub, $parameters );
			}

			return true;
		}
		return false;
	}

	/**
	 * Builds a URL using this rule based on the passed in data
	 * @param array $args An associative array of arguments to use for replacement in the rule
	 * @param boolean $useall If true (default), then all passed parameters that are not part of the built URL are tacked onto the URL as querystring
	 * @return string The URL created from the substituted arguments
	 */
	public function build( $args, $useall = true, $noamp = false )
	{
		$named_args = $this->named_args; // Direct call prints a PHP notice
		$named_args_combined = array_flip( array_merge( $named_args['required'], $named_args['optional'] ) );

		$args_defined = array_intersect_key( $args, $named_args_combined );
		$args = Plugins::filter( 'rewrite_args', $args, $this->name );
		// Replace defined arguments with their value
		$searches = array();
		$replacements = array();
		foreach ( $named_args as $keys ) {
			foreach ( $keys as $key ) {
				if ( !empty( $args[$key] ) ) {
					$searches[] = '/{\$'.$key.'}/';
					$replacements[] = str_replace( '%2F', '%252F', urlencode( $args[$key] ) );
				}
			}
		}

		// Remove undefined arguments
		$searches[] = '/\([^\(\)]*\$+[^\(\)]*\)/';
		$replacements[] = '';
		// Remove parens left from defined optional arguments
		$searches[] = '/\(|\)/';
		$replacements[] = '';

		$return_url = preg_replace( $searches, $replacements, $this->build_str );

		// Append any remaining args as query string arguments
		if ( $useall ) {
			$args = array_diff_key( $args, $named_args_combined );
			$query_seperator = ( $noamp ) ? '&amp;' : '&';
			$return_url.= ( count( $args ) == 0 ) ? '' : '?' . http_build_query( $args, '', $query_seperator );
		}

		return $return_url;
	}

	/**
	 * Returns a distance from 0 indicating the appropriateness of the rule
	 * based on the passed-in arguments.
	 * @param array $args An array of arguments
	 * @return integer Returns 0 for an exact match, a higher number for less of a match
	 * @todo Enable this logic
	 */
	public function arg_match( $args )
	{
		return 0; // Let's let this logic linger for a little while

		/* This needs further testing once that logic is established */
		$named_args = $this->named_args; // Direct call prints a PHP notice
		$named_args_combined = array_flip( array_merge( $named_args['required'], $named_args['optional'] ) );

		$args = Plugins::filter( 'rewrite_args', $args, $this->name );

		$diffargs = array_diff_key( $args, $named_args_combined );
		$sameargs = array_intersect_key( $args, $named_args_combined );
		$rating = count( $named_args_combined ) - count( $sameargs ) + count( $diffargs );

		return $rating;
	}

	/**
	 * Magic property getter for this class
	 * @param string $name The name of the class property to return
	 * @returns mixed The value of that field in this object
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'named_args':
				if ( empty( $this->m_named_args ) ) {
					preg_match_all( '/(?<!\()\{\$(\w+?)\}(?!\))/u', $this->build_str, $required );
					preg_match_all( '/(?<=\()[^\(\)]*\{\$(\\w+?)\}[^\(\)]*(?=\))/u', $this->build_str, $optional );
					$this->m_named_args['required'] = $required[1];
					$this->m_named_args['optional'] = $optional[1];
				}
				return $this->m_named_args;
			default:
				return parent::__get( $name );
		}
	}

	/**
	 * Saves a new rewrite rule to the rewrite_rules table
	 */
	public function insert()
	{
		return parent::insertRecord( DB::table( 'rewrite_rules' ) );
	}

	/**
	 * Updates an existing rule in the rewrite_rules table
	 */
	public function update()
	{
		return parent::updateRecord( DB::table( 'rewrite_rules' ), array( 'rule_id' => $this->rule_id ) );
	}

	/**
	 * Deletes an existing rule
	 */
	public function delete()
	{
		return parent::deleteRecord( DB::table( 'rewrite_rules' ), array( 'rule_id' => $this->rule_id ) );
	}

	/**
	 * Create an old-style rewrite rule
	 * @param string $build_str
	 * @param string $handler
	 * @param string $action
	 * @return RewriteRule The created rule
	 */
	public static function create_url_rule( $build_str, $handler, $action )
	{
		$arr = explode( '/', $build_str );

		$searches = array( '/^([^"\']+)$/', '/^["\'](.+)["\']$/' );
		$replacements = array( '(?P<\1>.+)', '\1' );
		$re_arr = preg_replace( $searches, $replacements, $arr );

		$searches = array( '/^([^"\']+)$/', '/^["\'](.+)["\']$/' );
		$replacements = array( '{$\1}', '\1' );
		$str_arr = preg_replace( $searches, $replacements, $arr );

		$regex = '/^' . implode( '\/', $re_arr ) . '\/?$/i';
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
