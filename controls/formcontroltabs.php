<?php

namespace Habari;

/**
 * A control to display a tab splitter based on FormControl for output via a FormUI.
 */
class FormControlTabs extends FormContainer
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
		$this->template = isset( $template ) ? $template : 'formcontrol_tabs';
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation, $this );

		foreach ( $this->controls as $control ) {
			if ( $control instanceof FormContainer ) {
				$content = '';
				foreach ( $control->controls as $subcontrol ) {
					// There should be a better way to know if a control will produce actual output,
					// but this instanceof is ok for now:
					if ( $content != '' && !( $subcontrol instanceof FormControlHidden ) ) {
						$content .= '<hr>';
					}
					$content .= $subcontrol->get( $forvalidation );
				}
				$controls[$control->caption] = $content;
			}
		}
		$theme->controls = $controls;
		// Do not move before $contents
		// Else, these variables will contain the last control's values
		$theme->class = $this->class;
		$theme->id = $this->name;
		$theme->caption = $this->caption;

		return $theme->fetch( $this->template, true );
	}

}

?>