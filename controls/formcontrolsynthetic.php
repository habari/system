<?php

namespace Habari;

/**
 * This control allows values to be loaded from/saved to fields and operated upon without producing any output for the
 * control in HTML
 */
class FormControlSynthetic extends FormControl
{
	/**
	 * This control shouldn't output anything
	 * @param Theme $theme
	 * @return string
	 */
	public function get(Theme $theme)
	{
		return '';
	}

	/**
	 * Process incoming values when the form is submitted
	 */
	public function process()
	{
		// Do nothing, since we want to keep the initial value, not the value from $_POST
	}

}

?>