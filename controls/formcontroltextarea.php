<?php

namespace Habari;


/**
 * A textarea control based on FormControl for output via a FormUI.
 */
class FormControlTextArea extends FormControl
{
	/**
	 * Produce the control for display
	 * @param Theme $theme The theme that will be used to render the template
	 * @return string The output of the template
	 */
	public function get(Theme $theme)
	{
		$this->settings['internal_value'] = true;
		return parent::get($theme);
	}

}

?>