<?php

namespace Habari;

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlCheckbox extends FormControl
{
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
						return true;
					}
					else {
						return false;
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