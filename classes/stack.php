<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Stack Class
 *
 * This class allows Habari to accumulate a group of unique values that
 * can be output using a specific formatting string.
 * This is useful for collecting a set of unique javascript references to output
 * and then insert them at a specific point on the page.
 *
 * <code>
 * // Add jquery to the javascript stack:
 * Stack::add( 'template_header_javascript', Site::get_url('scripts') . '/jquery.js', 'jquery' );
 *
 * // Add stylesheet to theme_stylesheet stack with media type
 * Stack::add( 'template_stylesheet', array( Site::get_url('theme') . '/style.css', 'screen' ), 'style' );
 *
 * // Output the javascript stack:
 * Stack::out( 'template_header_javascript', '<script src="%s" type="text/javascript"></script>' );
 *
 * // Output the theme_stylesheet stack:
 * Stack::out( 'template_stylesheet', '<link rel="stylesheet" type="text/css"  href="%s" media="%s">' );
 * </code>
 *
 */
class Stack
{
	private static $stacks = array();
	private static $stack_sort = array();
	private static $sorting;

	/**
	 * Private constructor for Stack.
	 * Stack objects should only be created using the static
	 * method Static::create_stack(), or one of the methods that
	 * adds a value directly to a stack.  This prevents multiple Stack
	 * objects from being created with the same name.
	 *
	 * @param mixed $input An array or ArrayObject to create the stack from.
	 * @return array The created stack
	 */
	private function __construct( $input )
	{
		parent::__construct( $input );
	}

	/**
	 * Retreive a named stack instance
	 * @param string $stack_name The name of the stack to return
	 * @return Stack The requested stack
	 **/
	public static function get_named_stack( $stack_name )
	{
		if ( isset( self::$stacks[$stack_name] ) ) {
			return self::$stacks[$stack_name];
		}
		else {
			return self::create_stack( $stack_name );
		}
	}

	/**
	 * Check for the existence of a given stack item.
	 *
	 * @param string $stack_name The name of the stack in which to check.
	 * @param string $value The value to check for.
	 * @return boolean true if the item exists, false otherwise.
	 */
	public static function has ( $stack_name, $value_name )
	{

		// get the stack
		$stack = self::get_named_stack( $stack_name );

		if ( isset( $stack[ $value_name ] ) ) {
			return true;
		}
		else {
			return false;
		}

	}

	/**
	 * Get a single item from a given stack.
	 *
	 * @param string $stack_name The name of the stack to fetch an item from.
	 * @param string $value The item to fetch.
	 * @param mixed $default_value The default value to return if the item does not exist in the stack.
	 * @return mixed The item, or $default_value if it does not exist.
	 */
	public static function get_item ( $stack_name, $value_name, $default_value = null )
	{

		// get the stack
		$stack = self::get_named_stack( $stack_name );

		if ( isset( $stack[ $value_name ] ) ) {
			return $stack[ $value_name ];
		}
		else {
			return $default_value;
		}

	}

	/**
	 * Creates and retreives a named stack instance
	 * @param string $stack_name The name of the stack to create and return
	 * @return array The created stack
	 **/
	public static function create_stack( $stack_name )
	{
		if ( empty( self::$stacks[$stack_name] ) ) {
			$stack = array();
			self::$stacks[$stack_name] = $stack;
			self::$stack_sort[$stack_name] = array();
		}
		return self::$stacks[$stack_name];
	}

	/**
	 * Add a value to a stack
	 * @param string $stack_name The name of the stack
	 * @param mixed $value The value to add
	 * @param string $value_name The name of the value to add
	 * @param string $after The name of the stack element to insert this new element after
	 * @return array The stack that was added to
	 **/
	public static function add( $stack_name, $value, $value_name = null, $after = null )
	{
		$stack = self::get_named_stack( $stack_name );
		$value_name = $value_name ? $value_name : md5( serialize( $value ) );
		if ( !is_null( $after ) ) {
			if ( !is_array( $after ) ) {
				$after = array( $after );
			}
			foreach ( $after as $a ) {
				self::depend($stack_name, $value_name, $a);
			}
		}
		$stack[$value_name] = $value;
		self::$stacks[$stack_name] = $stack;
		return $stack;
	}

	/**
	 * Add a named stack item to the list of things it depends on
	 * @static
	 * @param string $stack_name The name of the stack
	 * @param string $value_name The item name in the stack
	 * @param string $value_name_on The item name on which this item depends
	 */
	public static function depend( $stack_name, $value_name, $value_name_on )
	{
		if ( !isset( self::$stack_sort[$stack_name] ) ) {
			self::$stack_sort[$stack_name] = array();
		}
		if ( !isset( self::$stack_sort[$stack_name][$value_name_on] ) ) {
			self::$stack_sort[$stack_name][$value_name_on] = array();
		}
		foreach(self::$stack_sort[$stack_name] as $parent_key => $parent_stack) {
			if(isset($parent_stack[$value_name_on])) {
				self::depend($stack_name, $value_name, $parent_key);
			}
		}
		self::$stack_sort[$stack_name][$value_name_on][$value_name] = $value_name;
	}

	/**
	 * Remove a value to a stack
	 * @param string $stack_name The name of the stack
	 * @param string $value_name The name of the value to remove
	 * @return array The rest of the stack, post-remove
	 **/
	public static function remove( $stack_name, $value_name = null )
	{
		
		if ( $value_name == null ) {
			unset( self::$stacks[ $stack_name ] );
			return array();
		}
		
		$stack = self::get_named_stack( $stack_name );
		if ( isset( $stack[$value_name] ) ) {
			unset( $stack[$value_name] );
		}
		self::$stacks[$stack_name] = $stack;
		return $stack;
	}

	public static function get_sorted_stack( $stack_name )
	{
		self::$sorting = $stack_name;
		$stack = self::get_named_stack( $stack_name );

		uksort( $stack, array( 'Stack', 'sort_stack_cmp' ) );
		//$stack = array_reverse( $stack );
		return $stack;
	}

	public static function sort_stack_cmp( $a, $b )
	{
		// get the array of sorting values for the first key, or an empty array if there aren't any dependencies
		if ( isset( self::$stack_sort[ self::$sorting ][ $a ] ) ) {
			$a_dependents = self::$stack_sort[ self::$sorting ][ $a ];
		}
		else {
			$a_dependents = array();
		}

		// as above, but for the second key
		if ( isset( self::$stack_sort[ self::$sorting ][ $b ] ) ) {
			$b_dependents = self::$stack_sort[ self::$sorting ][ $b ];
		}
		else {
			$b_dependents = array();
		}

		$b_depends_on_a = isset( $a_dependents[ $b ] );
		$a_depends_on_b = isset( $b_dependents[ $a ] );

		if ( $a_depends_on_b && $b_depends_on_a ) {
			// They depend on each other?  How'd this happen?
			return 0;
		}
		elseif ( $a_depends_on_b ) {
			// a depends on b, b must come first, reverse their order
			return 1;
		}
		elseif ( $b_depends_on_a ) {
			// b depends on a, a must come first, keep their order
			return -1;
		}
		else {
			// neither depends on the other, force their natural order
			return 1;
		}
	}

	/**
	 * Returns all of the values of the stack
	 * @param string $stack_name The name of the stack to output
	 * @param mixed $format A printf-style formatting string or callback used to output each stack element
	 **/
	public static function get( $stack_name, $format = null )
	{
		$out = '';
		$stack = self::get_sorted_stack( $stack_name );
		$stack = Plugins::filter( 'stack_out', $stack, $stack_name, $format );
		foreach ( $stack as $element ) {
			if ( is_callable( $format ) ) {
				$out.= call_user_func_array( $format, (array) $element );
			}
			elseif ( is_string( $format ) ) {
				$out .= vsprintf( $format, (array) $element );
			}
			else {
				$out.= $element;
			}
		}
		return $out;
	}

	/**
	 * Outputs all of the values of the stack
	 * @param string $stack_name The name of the stack to output
	 * @param mixed $format A printf-style formatting string or callback used to output each stack element
	 **/
	public static function out( $stack_name, $format = null )
	{
		echo self::get( $stack_name, $format );
	}

	/**
	 * A callback for Stack::get() that outputs scripts as reference or inline depending on their content
	 *
	 * @param string $element The script element in the stack
	 * @param mixed $attrib Additional attributes, like 'defer' or 'async' allowed for <script src=...> tags
	 * @param string $wrapper An sprintf formatting string in which to output the script tag, for IE conditional comments
	 * @return string The resulting script tag
	 */
	public static function scripts( $element, $attrib = null, $wrapper = '%s' )
	{
		if(is_array($attrib)) {
			$attrib = $attrib + array('type' => 'text/javascript');
			$attrib = Utils::html_attr($attrib);
		}
		else {
			$attrib .= ' type="text/javascript"';
		}
		if ( self::is_url( $element ) ) {
			$output = sprintf( "<script %s src=\"%s\"></script>\r\n", $attrib, $element );
		}
		else {
			$output = sprintf( "<script %s>%s</script>\r\n", $attrib, $element );
		}
		$output = sprintf($wrapper, $output);
		return $output;
	}

	/**
	 * A callback for Stack::get() that outputs styles as link or inline style tags depending on their content
	 *
	 * @param string $element The style element in the stack
	 * @param string $typename The media disposition of the content
	 * @param string $props Additional properties of the style tag output
	 * @return string The resulting style or link tag
	 */
	public static function styles( $element, $typename = null, $props = array() )
	{
		$props = $props + array('type' => 'text/css');
		if ( !empty( $typename ) ) {
			$props['media'] = $typename;
		}

		if ( self::is_url( $element ) ) {
			$props = $props + array('rel' => 'stylesheet', 'href' => $element);
			$output = sprintf( "<link %s>\r\n", Utils::html_attr($props) );
		}
		else {
			$output = sprintf( "<style %2\$s>%1\$s</style>\r\n", $element, Utils::html_attr($props) );
		}
		return $output;
	}
	
	/**
	 * Check if the passed string looks like a URL or an absolute path to a file.
	 * 
	 * @todo There's a good chance this can be done in a better or more generic  
	 * way.
	 * 
	 * @param string $url The string to check.
	 * @return boolean TRUE if the passed string looks like a URL.
	 */
	private static function is_url( $url ) 
	{
		return ( ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 || strpos( $url, '//' ) === 0 || strpos( $url, '/' ) === 0 ) && strpos( $url, "\n" ) === false );
	}
}


?>
