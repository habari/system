<?php

namespace Habari;


/**
 * A password control based on FormControlText for output via a FormUI.
 * @todo reimplement password md5-ing and obfuscation
 */
class FormControlPassword extends FormControlText
{

	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'password';
	}

	/**
	 * Produce HTML output for this password control.
	 *
	 * @param Theme $theme The theme to use to render this control
	 * @return string HTML for this control in the form
	 */
	public function get( Theme $theme )
	{
		$this->vars['value'] = $this->value == '' ? '' : substr( md5( $this->value ), 0, 8 );
		return parent::get($theme);
	}


}

?>