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
		$this->add_template_class('tab_div_inside', 'splitterinside');
		$this->add_template_class('tab_div', 'splitter');
	}


	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param Theme $theme
	 * @return string HTML that will render this control in the form
	 */
	function get( Theme $theme )
	{
		$this->settings['ignore_name'] = true;
		$controls = array();
		foreach ( $this->controls as $control ) {
			if($class = $this->get_setting('class_each', '')) {
				$control->add_class($class);
			}
			if ( $control instanceof FormContainer ) {
				$control->set_template($this->get_setting('tab_template', 'control.fieldset.fortabs'));
				$controls[$control->caption] = $control->get($theme);
			}
		}
		$this->vars['controls'] = $controls;

		return parent::get($theme);
	}

}

?>