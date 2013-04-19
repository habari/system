<?php

namespace Habari;

/**
 * A select control based on FormControl for output via a FormUI.
 */
class FormControlSelect extends FormControl
{
	public $options = array();

	/**
	 * Set the options used in the output of this select
	 * @param array $options An array of options. By default, a nested array will create an optgroup using the key as the optgroup label.
	 * @return FormControlSelect $this
	 */
	public function set_options($options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( Theme $theme )
	{
		$this->vars['options'] = $this->options;
		$this->settings['internal_value'] = true;
		return parent::get($theme);
	}
}


?>