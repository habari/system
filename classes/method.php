<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Encapsulate a method as a value.
 */

class Method
{
	public $class;
	public $method;

	/**
	 * Quick-create for building an instance of Method
	 * @param string $class The name of the class
	 * @param string $method The method of the class
	 * @return Method An instance of Method to represent the method call
	 */
	public static function create($class, $method) {
		$instance = new Method($class, $method);
		return $instance;
	}

	/**
	 * Constructor
	 * @param string $class The name of the class
	 * @param string $method The method of the class
	 */
	public function __construct($class, $method) {
		$this->class = $class;
		$this->method = $method;
	}

	/**
	 * Dispatch a method, whether a filter or function
	 * @param Callable|string $method The method to call
	 * @param mixed $args Multiple arguments to dispatch() should be passed as separate arguments
	 * @return bool|mixed The return value from the dispatched method
	 */
	public static function dispatch($method, $args)
	{
		$args = func_get_args();
		if(is_callable($method)) {
			array_shift($args);  // Take $method off the front, pass only args
			return call_user_func_array($method, $args);
		}
		elseif(is_string($method)) {
			return call_user_func_array(Method::create('\Habari\Plugins', 'filter'), $args);
		}
		return false;
	}

	/**
	 * Execute the representative method when this object is called as a function.
	 * Example:
	 *   $fn = Method::create('Habari/Utils', 'debug');
	 *   $fn('foo'); // Calls Habari/Utils::debug('foo');
	 *
	 * This magic method should not be called directly
	 * @return mixed The return value of the function this Method object represents
	 */
	public function __invoke() {
		// Try the \Habari namespace if the class doesn't exist and the \Habari namespace works
		if(strpos($this->class, '\\') === false && !class_exists($this->class, true)) {
			if(class_exists('\\Habari\\' . $this->class)) {
				$this->class = '\\Habari\\' . $this->class;
			}
		}

		$args = func_get_args();
		return call_user_func_array(array($this->class, $this->method), $args);
	}
}
