<?php

/**
 * Habari String Class
 *
 * Allows Habari to manipulat strings as objects, which has many advantages.
 *
 */

class String implements ArrayAccess
{
	protected $string;
	protected $filters = array();

	/**
	 * Constructor for the String class
	 * @param mixed $string A string or String to be stored
	 */
	public function __construct($string)
	{
		$this->string = (string)$string;
	}


	/**
	 * Return this object as a PHP-native string type
	 *
	 * @return string The value of the string
	 */
	public function __tostring()
	{
		return $this->string;
	}


	/**
	 * Applies context filters to the string and returns it
	 *
	 * @param string $property The property of the string to return
	 * @return String A new modified String representation of the filtered string
	 */
	public function __get($property)
	{
		$out = $this->string;
		foreach($this->filters as $filter) {
			$out= Plugins::filter($filter . '_' . $property, $out, $this->string);
		}

		switch($property) {
			case 'specialchars':
				$out = htmlspecialchars($out);
				break;
			case 'attribute':
				$out = htmlspecialchars($out);
				break;
		}

		$out= Plugins::filter('string_' . $property, $out, $this->string);
		return new String($out);
	}

	/**
	 * Apply a named filter to the String
	 *
	 * @param string Name of the filter to apply
	 * @return String The filtered String object.
	 */
	public function filter()
	{
		$args = func_get_args();
		$filter = array_shift($args);
		array_unshift($args, $this->string);

		return new String(call_user_func_array(array('Plugins', 'filter'), $args));
	}

	/**
	 * Implement any undefined methods as pluggable filters
	 * The filter name is "string_{$method_name}", so you would implement a filter
	 * for $mystring->foo() with filter_string_foo($string)
	 *
	 * @param string $name The method called on this object
	 * @param array $args An array of arguments as passed to the method call
	 * @return String a instance of the string, filtered by plugins
	 */
	public function __call($name, $args)
	{
		$filter = 'string_' . $name;
		array_unshift($args, $this->string);

		return new String(call_user_func_array(array('Plugins', $filter), $args));
	}
	
	// ArrayAccess Implementation for (e.g.) $string[0]
	
	public function offsetExists($offset) {
		return $offset >= 0 && $offset < strlen($this->string);
	}
	public function offsetGet($offset) {
		return $this->string[$offset];
	}
	public function offsetSet($offset, $value) {
		$this->string[$offset] = $value[0];
	}
	public function offsetUnset ($offset) {
		throw new Exception('Cannot unset String offsets');
	}
}

?>