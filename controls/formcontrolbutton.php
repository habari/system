<?php

namespace Habari;


/**
* A button control based on FormControl for output via FormUI
*/
class FormControlButton extends FormControl
{
	/**
	 * Set the caption of this submit button
	 * @param string $caption The caption to set
	 * @return FormControlSubmit $this
	 */
	public function set_caption($caption)
	{
		$this->vars['caption'] = $caption;
		return $this;
	}
}

?>