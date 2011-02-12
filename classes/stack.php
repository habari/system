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
				if ( !isset( self::$stack_sort[$stack_name] ) ) {
					self::$stack_sort[$stack_name] = array();
				}
				if ( !isset( self::$stack_sort[$stack_name][$a] ) ) {
					self::$stack_sort[$stack_name][$a] = array();
				}
				self::$stack_sort[$stack_name][$a][$value_name] = $value_name;
			}
		}
		$stack[$value_name] = $value;
		self::$stacks[$stack_name] = $stack;
		return $stack;
	}

	/**
	 * Remove a value to a stack
	 * @param string $stack_name The name of the stack
	 * @param string $value_name The name of the value to remove
	 * @return array The rest of the stack, post-remove
	 **/
	public static function remove( $stack_name, $value_name )
	{
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
		return $stack;
	}

	public static function sort_stack_cmp( $a, $b )
	{
		$aa = isset( self::$stack_sort[self::$sorting][$a] ) ? self::$stack_sort[self::$sorting][$a] : array();
		$ba = isset( self::$stack_sort[self::$sorting][$b] ) ? self::$stack_sort[self::$sorting][$b] : array();
		$acb = isset( $aa[$b] );
		$bca = isset( $ba[$a] );
		$ac = count( $aa );
		$bc = count( $ba );
		if ( ( $acb && $bca ) || !( $acb || $bca ) ) {
			if ( $ac == $bc ) {
				// they are equal in 'bias', so go with the order in which they were added.
				return 1;
			}
			return $ac > $bc ? -1 : 1;
		}
		elseif ( $acb ) {
			return -1;
		}
		elseif ( $bca ) {
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
	 * @return string The resulting script tag
	 */
	public static function scripts( $element )
	{
		if ( ( strpos( $element, 'http://' ) === 0 || strpos( $element, 'https://' ) === 0 ) && strpos( $element, "\n" ) === false ) {
			$output = sprintf( '<script src="%s" type="text/javascript"></script>'."\r\n", $element );
		}
		else {
			$output = sprintf( '<script type="text/javascript">%s</script>'."\r\n", $element );
		}
		return $output;
	}

	/**
	 * A callback for Stack::get() that outputs styles as link or inline style tags depending on their content
	 *
	 * @param string $element The style element in the stack
	 * @param string $typename The media disposition of the content
	 * @return string The resulting style or link tag
	 */
	public static function styles( $element, $typename )
	{
		if ( ( strpos( $element, 'http://' ) === 0 || strpos( $element, 'https://' ) === 0 ) && strpos( $element, "\n" ) === false ) {
			$output = sprintf( '<link rel="stylesheet" type="text/css" href="%s" media="%s">'."\r\n", $element, $typename );
		}
		else {
			$output = sprintf( '<style type="text/css" media="%s">%s</style>'."\r\n", $typename, $element );
		}
		return $output;
	}
}


?>
