<?php

namespace Habari;

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlCheckbox extends FormControl
{
	public $returned_value;

	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'checkbox';
		$this->settings['internal_value'] = true;
		$this->returned_value = true;
	}

	/**
	 * Produce the control for display
	 * @param Theme $theme The theme that will be used to render the template
	 * @return string The output of the template
	 */
	public function get(Theme $theme)
	{
		// Because this is a checkbox, the value isn't directly output in the control
		$this->properties['value'] = $this->returned_value;
		if($this->value == false) {
			unset($this->properties['checked']);
		}
		else {
			$this->properties['checked'] = 'checked';
		}
		return parent::get($theme);
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		if(isset($_POST[$this->input_name()]) && $_POST[$this->input_name()] == $this->returned_value) {
			$this->value = $this->returned_value;
		}
		else {
			$this->value = false;
		}
	}

	/**
	 * Set the value returned when this control is checked
	 * @param mixed $returned_value The value returned when the value of this control is true;
	 * @return FormControlCheckbox $this
	 */
	public function set_returned_value($returned_value)
	{
		$this->returned_value = $returned_value;
		return $this;
	}

	public function set_value($value, $manually = true)
	{
		if($value != false) {
			$this->returned_value = $value;
		}
		return parent::set_value($value, $manually);
	}


}

?>