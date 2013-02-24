<?php

namespace Habari;

abstract class FormControl
{
	/** @var string $name The name of the conrol for the purposes of manipulating it from the container object */
	public $name;
	/** @var FormStorage|null $storage The storage object for this form */
	public $storage;
	/** @var string  */
	public $caption;
	/** @var array $properties Contains an array of properties used to assign to the output HTML */
	public $properties;
	/** @var array $settings Contains an array of settings that control the behavior of this control */
	public $settings;
	/** @var mixed $value This is the value of the control, which will differ depending on at what time you access it */
	public $value;
	/** @var FormContainer The container that contains this control */
	public $container;

	/**
	 * Construct a control.
	 * @param string $name
	 * @param FormStorage|string|Callable|null $storage
	 * @param string $caption
	 * @param array $properties
	 */
	public function __construct($name, $storage, $caption, $properties, $settings)
	{
		$this->name = $name;
		$this->storage = $storage;
		if(is_string($storage)) {
			$storage = ControlStorage::from_storage_string($storage);
		}
		$this->storage = $storage;
		$this->caption = $caption;
		$this->properties = $properties;
		$this->settings = $settings;
	}

	/**
	 * Take an array of parameters, use the first as the FormControl class/type, and run the constructor with the rest
	 * @param array $arglist The array of arguments
	 * @return FormControl The created control.
	 */
	public static function from_args($arglist)
	{
		$arglist = array_pad($arglist, 6, null);
		list($type, $name, $storage, $caption, $properties, $settings) = $arglist;
		if(class_exists('\\Habari\\FormControl' . ucwords($type))) {
			$type = '\\Habari\\FormControl' . ucwords($type);
		}
		if(!class_exists($type)) {
			throw new \Exception(_t('The FormControl type "%s" is invalid.', array($type)));
		}

		return new $type($name, $storage, $caption, $properties, $settings);
	}

	/**
	 * Save this control's data to the initialized storage location
	 */
	public function save()
	{
		$this->storage->field_save($this->name, $this->value);
	}

	/**
	 * Load this control's initial data from the initialized storage location
	 */
	public function load()
	{
		$this->value = $this->storage->field_load($this->name);
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		$this->value = $_POST[$this->input_name()];
	}

	/**
	 * Set the container for this control
	 * @param FormContainer $container A container that this control is inside
	 */
	public function set_container($container)
	{
		$this->container = $container;
	}

	/**
	 * Get the value of a setting
	 * @param string $name The name of the setting to get
	 * @param mixed $default The default value to use if the setting is not set
	 * @return mixed The value fo the setting or the default supplied
	 */
	public function get_setting($name, $default = null)
	{
		if(isset($this->settings[$name])) {
			return $this->settings[$name];
		}
		elseif(is_callable($default)) {
			return $default($name);
		}
		else {
			return $default;
		}
	}

	/**
	 * Get the name to use for the input field
	 * @return string The name to use in the HTML for this control
	 */
	public function input_name()
	{
		return $this->get_setting(
			'input_name',
			$this->name
		);
	}

	public function pre_out()
	{
		return '';
	}
}
