<?php

namespace Habari;

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlStatic extends FormControl
{
	/** @var string $static The internal representation of the static HTML content */
	public $static = '';


	/**
	 * Create a new instance of FormControlStatic and return it, use the fluent interface
	 * @param string $name The name of the control
	 * @param FormStorage|string|null $storage A storage location for the data collected by the control
	 * @param array $properties An array of properties that apply to the output HTML
	 * @param array $settings An array of settings that apply to this control object
	 * @return FormControlStatic An instance of the referenced FormControl with the supplied parameters
	 */
	public static function create($name, $storage = 'null:null', array $properties = array(), array $settings = array())
	{
		return parent::create($name, $storage, $properties, $settings);
	}

	/**
	 * Set the static content of this control
	 * @param string $static The static content of this control
	 * @return FormControlStatic $this
	 */
	public function set_static($static)
	{
		$this->static = $static;
		return $this;
	}

	/**
	 * Produce HTML output for this static text control.
	 *
	 * @param Theme $theme The theme used to render this control
	 * @return string HTML that will render this control in the form
	 */
	public function get(Theme $theme)
	{
		$this->vars['static'] = $this->static;
		return parent::get($theme);
	}
}

?>