<?php

namespace Habari;

/**
 * A control prototype that does not save its data
 */
class FormControlNoSave extends FormControl
{

	/**
	 * The FormControlNoSave constructor initializes the control without a save location
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $caption, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->caption = $caption;
		$this->template = $template;
	}

	/**
	 * Do not store this static control anywhere
	 *
	 * @param mixed $key Unused
	 * @param mixed $store_user Unused
	 */
	public function save( $key = null, $store_user = null )
	{
		// This function should do nothing.
	}

	/**
	 * Return a checksum representing this control
	 *
	 * @return string A checksum
	 */
	public function checksum()
	{
		return md5($this->name . 'static');
	}

}


?>