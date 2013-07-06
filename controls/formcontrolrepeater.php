<?php

namespace Habari;

/**
 * A div wrapper control based on FormContainer for output via FormUI
 */
class FormControlRepeater extends FormContainer
{
	/**
	 * Apply additional settings for this control
	 */
	public function _extend()
	{
		$this->set_settings(array(
			'ignore_name' => true,
			'process' => false,
		));
	}

	/**
	 * Get this control for display
	 * @param Theme $theme The theme to use for rendering
	 * @return string The output
	 */
	public function get(Theme $theme)
	{
		$this->vars['element'] = $this->get_setting('wrap_element', 'div');

		$content = '';

		foreach($this->value as $key => $data) {
			// Push the $key of this array item into the control's name
			$this->each(function(FormControl $control) use($key) {
				$control->add_input_array($key);
			});

			$content .= $this->get_contents($theme);

			// Pop the key of this array item out of the control's name
			$this->each(function(FormControl $control) {
				$control->pop_input_array();
			});
		}

		$this->vars['content'] = $content;

		return FormControl::get($theme);
	}
}

?>