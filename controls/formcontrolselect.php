<?php

namespace Habari;

/**
 * A select control based on FormControl for output via a FormUI.
 */
class FormControlSelect extends FormControl
{
	public $options = array();
	public $multiple = false;
	public $size = 5;

	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name
	 * @param string $caption
	 * @param array $options
	 * @param string $template
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $storage, $caption, $options, $template ) = array_merge( $args, array_fill( 0, 5, null ) );

		$this->name = $name;
		$this->storage = $storage;
		$this->caption = $caption;
		$this->options = $options;
		$this->template = $template;

		$this->default = null;
	}

	/**
	 * Produce HTML output for this text control.
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

		$theme->options = $this->options;
		$theme->multiple = $this->multiple;
		$theme->size = $this->size;
		$theme->id = $this->name;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
	}
}


?>