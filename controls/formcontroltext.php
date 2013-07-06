<?php

namespace Habari;


/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlText extends FormControl
{
	/**
	 * Called upon construct.  Sets default control properties
	 */
	public function __construct($name, $storage = 'null:null', array $properties = array(), array $settings = array())
	{
		$this->properties['type'] = 'text';
		parent::__construct($name, $storage, $properties, $settings);
	}

}

?>