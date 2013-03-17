<?php

namespace Habari;

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlStatic extends FormControlNoSave
{
	/**
	 * Produce HTML output for this static text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( $forvalidation = true )
	{
		return $this->caption;
	}
}

?>