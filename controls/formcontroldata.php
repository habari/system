<?php

namespace Habari;

/**
 * This control is not rendered to the page, but retains data from when it is defined to when the form is processed for success
 */
class FormControlData extends FormControl
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
	 * This control's value should not be affected by what is submitted from the browser
	 */
	public function process()
	{}

}

?>