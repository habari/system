<?php

namespace Habari;

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlCheckboxes extends FormControlSelect
{
	public function get(Theme $theme)
	{
		$checkboxes = $this->options;
		$control = $this;

		if(!is_array($control->value)) {
			$control->value = array();
		}
		array_walk(
			$checkboxes,
			function(&$item, $key) use($control) {
				$item = array(
					'label' => Utils::htmlspecialchars($item),
					'id' => Utils::slugify( $control->get_id() . '-' . $key ),
					'checked' => in_array($key, $control->value) ? 'checked="checked"' : '',
				);
			}
		);
		$this->vars['checkboxes'] = $checkboxes;
		$this->settings['ignore_name'] = true;
		return parent::get($theme);
	}


	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		if(isset($_POST[$this->input_name()])) {
			$this->value = $_POST[$this->input_name()];
		}
		else {
			$this->value = false;
		}
	}

}

?>