<?php

namespace Habari;

/**
 * A div wrapper control based on FormContainer for output via FormUI
 */
class FormControlWrapper extends FormContainer
{
	/**
	 * Apply additional settings for this control
	 */
	public function _extend()
	{
		$this->settings['ignore_name'] = true;
	}
}

?>