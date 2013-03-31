<?php

namespace Habari;

/**
 * A hidden field control based on FormControl for output via a FormUI.
 */
class FormControlHidden extends FormControl
{
	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'hidden';
	}
}

?>