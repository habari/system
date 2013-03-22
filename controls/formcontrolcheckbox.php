<?php

namespace Habari;

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlCheckbox extends FormControl
{
	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'checkbox';
		$this->settings['internal_value'] = true;
	}

	/**
	 * Produce the control for display
	 * @param Theme $theme The theme that will be used to render the template
	 * @return string The output of the template
	 */
	public function get(Theme $theme)
	{
		// Because this is a checkbox, the value isn't directly output in the control
		$this->properties['value'] = 'checked';
		if($this->value == true) {
			$this->properties['checked'] = 'checked';
		}
		else {
			unset($this->properties['checked']);
		}
		return parent::get($theme);
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		if(isset($_POST[$this->input_name()]) && $_POST[$this->input_name()] == 'checked') {
			$this->value = true;
		}
		else {
			$this->value = false;
		}
	}


}

?>