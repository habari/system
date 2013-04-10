<?php

namespace Habari;

/**
 * A submit control based on FormControl for output via FormUI
 */
class FormControlSubmit extends FormControl
{
	public $caption;
	public $on_success = array();

	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'submit';
	}

	/**
	 * Set the caption of this submit button
	 * @param string $caption The caption to set
	 * @return FormControlSubmit $this
	 */
	public function set_caption($caption)
	{
		$this->caption = $caption;
		return $this;
	}

	/**
	 * Produce HTML output for this password control.
	 *
	 * @param Theme $theme The theme to use to render this control
	 * @return string HTML for this control in the form
	 */
	public function get( Theme $theme )
	{
		$this->properties['value'] = $this->caption;
		return parent::get($theme);
	}

	/**
	 * This control only executes its on_success callbacks when it was clicked
	 * @return bool|string A string to replace the rendering of the form with, or false
	 */
	public function do_success()
	{
		if(isset($_POST[$this->input_name()])) {
			return parent::do_success();
		}
		return false;
	}

	/**
	 * This control only validates if it's clicked
	 * @return array If empty, no errors.  One string element describing each error
	 */
	public function validate()
	{
		if(isset($_POST[$this->input_name()])) {
			return parent::validate();
		}
		return array();
	}


}


?>