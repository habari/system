<?php

namespace Habari;

class ControlStorage implements FormStorage
{
	/** @var Callable|string $saver A fucntion that saves this control value */
	protected $saver;
	/** @var Callable|string $loader A function that loads this control value */
	protected $loader;

	/**
	 * Construct a basic FormStorage object
	 * @param Callable|string $load A function to call to load the control value, OR a non-callable
	 * @param $save
	 */
	public function __construct($load, $save) {
		$this->loader = $load;
		$this->saver = $save;
	}

	/**
	 * Produce a basic FormStorage implementation from a classic storage string
	 * @param string $value A classic storage string, such as "option:someoption" or "user:age"
	 * @return ControlStorage An instance of an object that will save and load to the indicated location
	 */
	public static function from_storage_string($value) {
		$storage = explode( ':', $value, 2 );
		switch ( count( $storage ) ) {
			case 2:
				list( $type, $location ) = $storage;
				break;
			case 1:
				list( $location ) = $storage;
				$type = 'option';
				break;
			default:
				// @todo Figure this case out
				$location = '__';
				$type = '__';
				break;
		}

		switch ( $type ) {
			case 'user':
				$loader = function($name) {
					return User::identify()->info->{$name};
				};
				$saver = function($name, $value) {
					User::identify()->info->{$name} = $value;
					Session::queue(User::identify());
				};
				break;
			case 'option':
				$loader = function($name) use ($location) {
					return Options::get($location);
				};
				$saver = function($name, $value) use ($location) {
					Options::set($location, $value);
				};
				break;
			case 'action':
				$loader = function($name) use ($location) {
					return Plugins::filter( $location, '', $name, false );
				};
				$saver = function($name, $value) use ($location) {
					Plugins::act( $location, $value, $name, true );
				};
				break;
			case 'session':
				$loader = function($name) use ($location) {
					$session_set = Session::get_set( $location, false );
					if ( isset( $session_set[$name] ) ) {
						return $session_set[$name];
					}
					return null;
				};
				$saver = function($name, $value) use ($location) {
					Session::add_to_set( $location, $value, $name );
				};
				break;
			default:
				$loader = function(){};
				$saver = function(){};
				break;
		}

		return new ControlStorage($loader, $saver);
	}

	/**
	 * Create a new ControlStorage instance to save/load a control value from the parameter of a particular object
	 * @param Object $obj The object that will be saved to or loaded from
	 * @param string $parameter The name of a parameter on the object that will be used for storage
	 * @return ControlStorage An instance of a ControlStorage object that will load/save to the specified location
	 */
	function from_object_parameter($obj, $parameter)
	{
		$cs = new ControlStorage(
			function($name) {
				return $obj->$parameter;
			},
			function($name, $value) {
				return $obj->$parameter = $value;
			}
		);
		return $cs;
	}

	/**
	 * Stores a form value into the object
	 *
	 * @param string $key The name of a form component that will be stored
	 * @param mixed $value The value of the form component to store
	 */
	function field_save($key, $value)
	{
		Method::dispatch($this->saver, $key, $value);
	}

	/**
	 * Loads form values from an object
	 *
	 * @param string $key The name of a form component that will be loaded
	 * @return mixed The stored value returned
	 */
	function field_load($key)
	{
		if(is_callable($this->loader)) {
			return Method::dispatch($this->loader, $key);
		}
		return $this->loader;
	}
}
