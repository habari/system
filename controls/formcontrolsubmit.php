<?php

namespace Habari;

/**
 * A submit control based on FormControl for output via FormUI
 */
class FormControlSubmit extends FormControl
{
	public $caption;

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

}


?>