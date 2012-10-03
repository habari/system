<?php
	/**
	* @package Habari
	*
	*/

	/**
	* Habari StackItem Class
	*
	* This class represents a single item that can be used as a component in Habari's stack output
	*/

class StackItem
{
	static $items = array();
	private $dependencies = array();

	public function __construct($name, $version, $resource)
	{
		$this->name = $name;
		$this->version = $version;
		$this->resource = $resource;
	}

	public function __toString()
	{
		if(is_callable($this->resource)) {
			return $this->resource($this);
		}
		return $this->resource;
	}

	/**
	 * Add a dependency to this StackItem
	 * @param StackItem $item A Stack Item upon which this item depends
	 */
	public function add_dependency($itemname, $version = null)
	{
		$this->dependencies[] = array('name' => $itemname, 'version' => $version);
		return $this;
	}

	public function get_dependencies()
	{
		$dependencies = array();
		foreach($this->dependencies as $dependency) {
			$stackitem = self::get($dependency['name'], $dependency['version']);
			if(is_null($stackitem)) {
				$stackitem = self::register($dependency['name']);
			}
			$dependencies[$dependency['name']] = &$stackitem;
		}
		return $dependencies;
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
				if(is_null($result)) {
					if(is_null($version) || version_compare($version, $item->version) >= 0) {
						return $item;
					}
				}
				return $result;
			}, null);
			return $result;
		}
		return null;
	}
}