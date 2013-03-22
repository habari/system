<?php

namespace Habari;

/**
 * A control to display a tab splitter based on FormControl for output via a FormUI.
 */
class FormControlTabs extends FormContainer
{
	public function _extend()
	{
		$this->properties['class'][] = 'container';
		$this->properties['class'][] = 'pagesplitter';
	}


	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param Theme $theme
	 * @return string HTML that will render this control in the form
	 */
	function get( Theme $theme )
	{
		foreach ( $this->controls as $control ) {
			if ( $control instanceof FormContainer ) {
				$content = '';
				foreach ( $control->controls as $subcontrol ) {
					// There should be a better way to know if a control will produce actual output,
					// but this instanceof is ok for now:
					if ( $content != '' && !( $subcontrol instanceof FormControlHidden ) ) {
						$content .= '<hr>';
					}
					$content .= $subcontrol->get( $theme );
				}
				$controls[$control->caption] = $content;
			}
		}
		$this->vars['controls'] = $controls;

		return parent::get($theme);
	}

}

?>