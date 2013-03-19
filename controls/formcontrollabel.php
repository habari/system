<?php

namespace Habari;

class FormControlLabel extends FormContainer
{
	public $label;

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
		$label_control->settings['ignore_name'] = true;
		return $label_control;
	}
}