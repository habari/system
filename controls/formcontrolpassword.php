<?php

namespace Habari;


/**
 * A password control based on FormControlText for output via a FormUI.
 */
class FormControlPassword extends FormControlText
{

	/**
	 * Produce HTML output for this password control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		foreach ( $this->properties as $prop => $value ) {
			$theme->$prop = $value;
		}

		$theme->caption = $this->caption;
		$theme->id = $this->name;
		$theme->control = $this;
		$theme->outvalue = $this->value == '' ? '' : substr( md5( $this->value ), 0, 8 );

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Magic function __get returns properties for this object, or passes it on to the parent class
	 * Potential valid properties:
	 * value: The value of the control, whether the default or submitted in the form
	 *
	 * @param string $name The paramter to retrieve
	 * @return mixed The value of the parameter
	 */
	public function __get( $name )
	{
		$default = $this->get_default();
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field] ) ) {
					if ( $_POST[$this->field] == substr( md5( $default ), 0, 8 ) ) {
						return $default;
					}
					else {
						return $_POST[$this->field];
					}
				}
				else {
					return $default;
				}
			default:
				return parent::__get( $name );
		}
	}
}

?>