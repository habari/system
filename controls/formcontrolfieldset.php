<?php

namespace Habari;

/**
 * A fieldset control based on FormControl for output via a FormUI.
 */
class FormControlFieldset extends FormContainer
{

	public $caption = '';

	/**
	 * Set the caption for this fieldset
	 * @param $caption
	 * @return FormControlFieldset $this
	 */
	public function set_caption($caption)
	{
		$this->caption = $caption;
		return $this;
	}

	/**
	 * Produce the HTML for this control
	 * @param Theme $theme The theme used for rendering
	 * @return string The rendered control in HTML
	 */
	function get(Theme $theme)
	{
		$this->settings['ignore_name'] = true;
		$this->vars['caption'] = $this->caption;
		return parent::get($theme);
	}

	/**
	 * Get a string that will be used to generate a component of a control's HTML id
	 * @return string
	 */
	public function get_id_component()
	{
		return $this->name;
	}

}

?>