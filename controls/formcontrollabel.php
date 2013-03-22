<?php

namespace Habari;

class FormControlLabel extends FormContainer
{
	public $label;

	/**
	 * Be sure that the name of the label isn't added as an attribute of the label tag
	 */
	public function _extend()
	{
		$this->settings['ignore_name'] = true;
	}

	/**
	 * Create a label for a control
	 * @param string $label The label of the control
	 * @param FormControl $control The control to label
	 * @return FormControl The passed $control value
	 */
	public static function wrap($label, FormControl $control) {
		$label_control = new FormControlLabel('label_for_' . $control->name);
		$label_control->append($control);
		$label_control->vars['label'] = $label;
		$label_control->properties['for'] = $control->get_id();
		return $label_control;
	}

	/**
	 * Set the label text
	 * @param string $label The text of the label
	 * @return FormControlLabel $this
	 */
	public function set_label($label) {
		$this->label = $label;
		return $this;
	}
}