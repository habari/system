<?php

namespace Habari;

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlStatic extends FormControl
{
	/** @var string $static The internal representation of the static HTML content */
	protected $static = '';

	/**
	 * Set the static content of this control
	 * @param string $static The static content of this control
	 * @return FormControlStatic $this
	 */
	public function set_static($static)
	{
		$this->static = $static;
		return $this;
	}

	/**
	 * Produce HTML output for this static text control.
	 *
	 * @param Theme $theme The theme used to render this control
	 * @return string HTML that will render this control in the form
	 */
	public function get(Theme $theme)
	{
		$this->vars['static'] = $this->static;
		return parent::get($theme);
	}
}

?>