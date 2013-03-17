<?php

namespace Habari;

/**
 * A div wrapper control based on FormContainer for output via FormUI
 */
class FormControlWrapper extends FormContainer
{
	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $classes The classes to use in the div wrapper markup
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $class, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->class = $class;
		$this->caption = '';
		$this->template = isset( $template ) ? $template : 'formcontrol_wrapper';
	}
}

?>