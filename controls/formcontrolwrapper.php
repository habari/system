<?php

namespace Habari;

/**
 * A div wrapper control based on FormContainer for output via FormUI
 */
class FormControlWrapper extends FormContainer
{
	/**
	 * Apply additional settings for this control
	 */
	public function _extend()
	{
		$this->settings['ignore_name'] = true;
		$this->set_settings(array(
			'ignore_name' => true,
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
		return parent::get($theme);
	}
}

?>