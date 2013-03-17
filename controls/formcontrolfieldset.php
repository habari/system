<?php

namespace Habari;

/**
 * A fieldset control based on FormControl for output via a FormUI.
 */
class FormControlFieldset extends FormContainer
{

	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $caption The legend to display in the fieldset markup
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $caption, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->caption = $caption;
		$this->template = isset( $template ) ? $template : 'formcontrol_fieldset';
	}
}

?>