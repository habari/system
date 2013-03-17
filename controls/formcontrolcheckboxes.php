<?php

namespace Habari;

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlCheckboxes extends FormControlSelect
{
	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		$theme->options = $this->options;
		$theme->id = $this->name;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field . '_submitted'] ) ) {
					if ( isset( $_POST[$this->field] ) ) {
						return $_POST[$this->field];
					}
					else {
						return array();
					}
				}
				else {
					return $this->get_default();
				}
		}
		return parent::__get( $name );
	}

}

?>