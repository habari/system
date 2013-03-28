<?php

namespace Habari;

/**
 * A file upload control based on FormControl for output via a FormUI.
 */
class FormControlFile extends FormControl
{
	/**
	 * Magic function __get returns properties for this object, or passes it on to the parent class
	 * Potential valid properties:
	 * tmp_file: The uploaded file
	 *
	 * @param string $name The parameter to retrieve
	 * @return mixed The value of the parameter
	 *
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'tmp_file':
				return $_FILES[$this->field]['tmp_name'];

			default:
				return parent::__get( $name );
		}
	}

	/**
	 * Return the HTML construction of the control, after changing the encoding of the parent form to allow for file uploads.
	 *
	 * @param boolean $forvalidation True if the control should output validation information with the control.
	 */
	public function get( $forvalidation = true )
	{
		$form = $this->get_form();
		$form->properties['enctype'] = 'multipart/form-data';

		return parent::get( $forvalidation );
	}

	/**
	 * Store this control's value under the control's specified key.
	 *
	 * @param string $storage (optional) A storage location to store the control data
	 */
	public function save( $storage = null )
	{
		if ( $storage == null ) {
			$storage = $this->storage;
		}

		if ( is_string( $storage ) ) {
			$storage = explode( ':', $storage, 2 );
			switch ( count( $storage ) ) {
				case 2:
					list( $type, $location ) = $storage;
					break;
				default:
					return;
			}
		}

		switch ( $type ) {
			case 'silo':
				// TODO
				// Get silo by path $location
				// Create a MediaAsset based on $_FILES[$this->name]['tmp_name']
				break;
			case 'path':
				move_uploaded_file( $_FILES[$this->field]['tmp_name'], $location . '/' . basename( $_FILES[$this->field]['name'] ) );
				break;
			/* TODO is there any use for any of these ?
		case 'user':
			User::identify()->info->{$location} = $this->value;
			break;
		case 'option':
			Options::set( $location, $this->value );
			break;
		case 'action':
			Plugins::filter($location, $this->value, $this->name, true);
			break;
		case 'formstorage':
			$storage->field_save( $this->name, $this->value );
			break;
			*/
			case 'null':
				break;
		}
	}
}

?>