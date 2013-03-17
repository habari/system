<?php

namespace Habari;

/**
 * A hidden field control based on FormControl for output via a FormUI.
 */
class FormControlHidden extends FormControl
{

	/**
	 * Produce HTML output for this hidden control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( $forvalidation = true )
	{
		$output = '<input type="hidden" name="' . $this->field . '" value="' . $this->value . '"';
		if(isset($this->id)) {
			$output .= ' id="' . $this->id . '"';
		}
		$output .= '>';
		return $output;
	}

}

?>