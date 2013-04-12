<?php
	/**
	* @package Habari
	*
	*/

	/**
	 * Habari StackItem Class
	 *
	 * This class represents a single item that can be used as a component in Habari's stack output
	 * @property mixed $resource The value of the StackItem used for output
	 */

class StackItem
{
	static $items = array();
	protected $dependencies = array();
	protected $resource = '';

	/**
	 * Constructor for StackItem
	 * @param string $name Name of the item
	 * @param string $version PHP version string-compatible version number
	 * @param mixed $resource Value of the item
	 */
	public function __construct($name, $version, $resource)
	{
		$this->name = $name;
		$this->version = $version;
		$this->resource = $resource;
	}

	/**
	 * Define behavior for when this StackItem is cast to a string
	 * @return string
	 */
	public function __toString()
	{
		if(is_callable($this->resource)) {
			/** @var Callable $fn */
			$fn = $this->resource;
			return $fn($this);
		}
		return $this->resource;
	}

	public function __get($name)
	{
		switch($name) {
			case 'resource':
				return Plugins::filter('get_stackitem_resource', $this->resource, $this);
		}
	}

	/**
	 * Add a dependency to this StackItem
	 * @param string|StackItem $itemname The name of the stack item upon which this item depends
	 * @param null|string $version Optional PHP-compatible version number string
	 * @return \StackItem Fluid interface returns $this
	 */
	public function add_dependency($itemname, $version = null)
	{
		if($itemname instanceof StackItem) {
			$this->dependencies[] = $itemname;
		}
		else {
			$this->dependencies[] = array('name' => $itemname, 'version' => $version);
		}
		return $this;
	}

	/**
	 * Get the dependencies for this item
	 * @return array An array of StackItems that this item depends on
	 */
	public function get_dependencies()
	{
		$results = array();
		foreach($this->dependencies as $dependency) {
			if(is_array($dependency)) {
				$stackitem = self::get($dependency['name'], $dependency['version']);
				if($stackitem instanceof StackItem) {
					$results[$dependency['name']] = $stackitem;
				}
			}
			else {
				$results[$dependency->name] = $dependency;
			}
		}
		return array_filter($results);
	}

	/**
	 * Determine if this item is in the specified stack array
	 * @param Array $stack The stack to look in for this item
	 * @return bool True if the stack contains an index that matches this item's name
	 */
	public function in_stack_index($stack)
	{
		return isset($stack[$this->name]);
	}

	/**
	 * @param string $name Name of the item to register
	 * @param string $resource The resource to
	 * @param string $version Verison of the item to register
	 * @return StackItem
	 */
	public static function register($name, $resource = '', $version = 0) {
		// @todo add a hook here to register StackItem contents on demand
		$item = new StackItem($name, $version, $resource);
		if(!isset(self::$items[$name])) {
			self::$items[$name] = array();
		}
		self::$items[$name][$version] = $item;
		uksort(self::$items[$name], function($a, $b) {
			return version_compare($a, $b);
		});
		return $item;
	}

	/**
	 * Get the named stack item
	 * @param string $name Name of the stack item to get
	 * @param string $version (optional) Minimum version number of the item to get
	 */
	public static function get($name, $version = null) {
		if(isset(self::$items[$name])) {
			$result = array_reduce(self::$items[$name], function(&$result, $item) use ($version) {
				// If there was no result yet
				if(is_null($result)) {
					// If the version requested was omitted
					// or the version of this item is greater than or equal to the version requested
					if(is_null($version) || version_compare($version, $item->version) >= 0) {
						// return this item
						return $item;
					}
				}
				else {
					// If this item has a higher version than the one we already have
					if(version_compare($result->version, $item->version) < 0) {
						// return this item, which is more current
						return $item;
					}
				}
				// Otherwise, return the result we already have
				return $result;
			}, null);
			return $result;
		}
		return null;
	}
}