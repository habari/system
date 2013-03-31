<?php

namespace Habari;

/**
 * A control to display media silo contents based on FormControl for output via a FormUI.
 */
class FormControlSilos extends FormControl
{
	public function get(Theme $theme)
	{
		$silos = Media::dir();

		foreach($silos as &$silo) {
			$silo->path_slug = Utils::slugify($silo->path);
		}

		$this->vars['silos'] = $silos;

		return parent::get($theme);
	}


}

?>