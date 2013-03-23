<?php

namespace Habari;

/**
 * This control is not rendered to the page, but retains data from when it is defined to when the form is processed for success
 */
class FormControlSynthetic extends FormControl
{
	public $selector = '';

	/**
	 * This control shouldn't output anything
	 * @param Theme $theme
	 * @return string
	 */
	public function get(Theme $theme)
	{
		return '';
	}

	protected function get_form_static()
	{
		$parent = $this;
		while(!$parent instanceof FormUI) {
			$parent = $parent->container;
		}
		return $parent->_form_html;
	}

	public function set_selector($selector)
	{
		$this->selector = $selector;
		return $this;
	}

	public function set_value($value, $manually = true)
	{
		$static = $this->get_form_static();
		$html = new HTMLDom($static->static);
		$es = $html->find($this->selector);
		$e = reset($es);
		//Utils::debug($e, $e->value);
		$e->value = $value;
		$static->static = $html->save();
		return parent::set_value($value, $manually);
	}


}

?>