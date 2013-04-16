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
		if(isset($control->container)) {
			$control->container->insert($control, $label_control);
			$control->container->remove($control);
		}
		$label_control->append($control);
		$label_control->label = $label;
		return $label_control;
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get(Theme $theme)
	{
		$this->vars['label'] = $this->label;
		$this->properties['for'] = reset($this->controls)->get_visualizer();
		return parent::get($theme);
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

	/**
	 * Apply this label to a control
	 * @param FormControl $for The control that this label is for
	 * @return FormControlLabel $this
	 */
	public function set_for($for) {
		$this->properties['for'] = $for->get_id();
		return $this;
	}
}