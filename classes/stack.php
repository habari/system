<?php
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
 * Stack::add('javascript', Site::get_url('scripts') . '/jquery.js');
 *  
 * // Output the javascript stack:
 * Stack::out('javascript', '<script src="%" type="text/javascript"></script>');
 * </code>   
 *
 * @package Habari
 */

class Stack
{
	private static $stacks= array();
	
	/**
	 * Private constructor for Stack.
	 * Stack objects should only be created using the static 
	 * method Static::create_stack(), or one of the methods that
	 * adds a value directly to a stack.  This prevents multiple Stack 
	 * objects from being created with the same name.
	 * 
	 * @param mixed $input An array or ArrayObject to create the stack from.
	 * @return array The created stack
	 **/
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
	 * Creates and retreives a named stack instance
	 * @param string $stack_name The name of the stack to create and return
	 * @return array The created stack
	 **/	  	 	 	
	public static function create_stack( $stack_name )
	{
		if ( empty( self::$stacks[$stack_name] ) ) {
			$stack= array();
			self::$stacks[$stack_name] = $stack;
		}
		return self::$stacks[$stack_name];
	}
	
	/**
	 * Add a value to a stack
	 * @param string $stack_name The name of the stack
	 * @param string $value The value to add
	 * @return array The stack that was added to	 
	 **/	 
	public static function add( $stack_name, $value )
	{
		$stack= self::get_named_stack( $stack_name );
		$stack[$value]= $value;
		self::$stacks[$stack_name]= $stack;
		return $stack;
	}
	
	/**
	 * Outputs all of the values of the stack
	 * @param string $stack_name The name of the stack to output
	 * @param mixed $format A printf-style formatting string or callback used to output each stack element
	 **/	   	 
	public static function out( $stack_name, $format = null)
	{
		$stack= self::get_named_stack( $stack_name );
		$stack= Plugins::filter( 'stack_out', $stack, $stack_name );
		foreach( $stack as $element ) {
			if ( function_exists($format) ) {
				echo $format( $element ); 
			}
			elseif ( is_string( $format ) ) {
				printf( $format, $element );
			}
			else {
				echo $element;
			}
		}
	}
}


?>
