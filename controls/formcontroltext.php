<?php

namespace Habari;


/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlText extends FormControl
{
	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'text';
	}

}

?>