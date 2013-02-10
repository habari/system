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
	 * Execute the representative method when this object is called as a function.
	 * Example:
	 *   $fn = Method::create('Habari/Utils', 'debug');
	 *   $fn('foo'); // Calls Habari/Utils::debug('foo');
	 *
	 * This magic method should not be called directly
	 * @return mixed The return value of the function this Method object represents
	 */
	public function __invoke() {
		$args = func_get_args();
		return call_user_func_array(array($this->class, $this->method), $args);
	}
}
