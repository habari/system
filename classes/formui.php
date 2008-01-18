<?php

/**
 * Create forms for use as plugin interfaces
 *
 * @version $Id$
 * @package Habari
 */

class FormUI
{
	private $name;
	public $controls= array();
	private $success_callback;
	private static $outpre = false;
	private $has_user_options = false;

	/** Option for the form **/
	private $options = array(
		'show_form_on_success' => true,
		'save_button' => true,
		'ajax' => false,
	);
	/**
	 * Form UI constructor - create to build form UI.
	 *
	 * @param string $name The name of the form, used to differentiate multiple forms.
	 */
	public function __construct( $name )
	{
		$this->name= $name;
	}

	/**
	 * Get a unique id for this form, based on its name.
	 *
	 * @return string A salted name for the form
	 */
	public function salted_name()
	{
		return md5(Options::get('secret') . 'added salt, for taste' . $this->name);
	}

	/**
	 * Produce a form with the contained fields.
	 *
	 * @return string Form HTML.
	 */
	public function get()
	{
		$forvalidation = false;
		$showform = false;
		// Should we be validating?
		if(isset($_POST['FormUI']) && $_POST['FormUI'] == $this->salted_name()) {
			$validate= $this->validate();
			if(count($validate) == 0) {
				$this->success();
				$showform = $this->options['show_form_on_success'];
			}
			else {
				$forvalidation= true;
			}
		}

		$out = '';
		if($showform) {
			$out.= '
				<form method="post" action="" class="FormUI">
				<input type="hidden" name="FormUI" value="' . $this->salted_name() . '">
			';
			$out.= $this->pre_out_controls();
			$out.= $this->output_controls($forvalidation);

			if($this->options['save_button']) {
				$out.= '<input type="submit" value="save">';
			}

			$out.= '</form>';
		}

		return $out;
	}

	/**
	 * Output a form with the contained fields.
	 * Calls $this->get() and echoes.
	 */
	public function out()
	{
		$args= func_get_args();
		echo call_user_func_array(array($this, 'get'), $args);
	}

	/**
	 * Return the form control HTML.
	 *
	 * @param boolean $forvalidation True if the controls should output additional information based on validation.
	 * @return string The output of controls' HTML.
	 */
	public function output_controls( $forvalidation= false )
	{
		$out= '';
		foreach($this->controls as $control) {
			$out.= $control->out( $forvalidation );
		}
		return $out;
	}

	/**
	 * Return pre-output control configuration scripts for any controls that require them.
	 *
	 * @return string The output of controls' pre-output HTML.
	 */
	public function pre_out_controls( )
	{
		$out= '';
		if(!FormUI::$outpre) {
			FormUI::$outpre = true;
			$out.= '<script type="text/javascript">var controls = Object();</script>';
		}
		foreach($this->controls as $control) {
			$out.= $control->pre_out( );
		}
		return $out;
	}

	/**
	 * Process validation on all controls of this form.
	 *
	 * @return array An array of strings describing validation issues, or an empty array if no issues.
	 */
	public function validate()
	{
		$validate= array();
		foreach($this->controls as $control) {
			$validate= array_merge($validate, $control->validate());
		}
		return $validate;
	}

	/**
	 * Add a control to this form.
	 * If a default value is not provided, the function attempts to obtain the value
	 * from the Options table using the form name and the control name as the key.  For
	 * example, if the form name is "myform" and the control name is "mycontrol" then
	 * the function attempts to obtain the control's value from Options::get('myform:mycontrol')
	 * These settings may also be used in FormUI::success() later to write the value
	 * of the control back into the options table.
	 *
	 * @param string $type A classname, or the postfix of a class starting 'FormControl' that will be used to create the control
	 * @param string $name The name of the control, also the latter part of the Options table key value
	 * @param string $caption The caption used in the form to label the control
	 * @param mixed $default (optional) A default value for the control, otherwise taken from Options table
	 * @return FormControl An instance of the named FormControl descendant.
	 */
	public function add($type, $name, $caption, $default= null)
	{
		$control= null;


		if(is_string($type) && class_exists('FormControl' . ucwords($type))) {
			$type= 'FormControl' . ucwords($type);
		}
		if(strpos($name, 'user:') === 0) {
			$store_user = true;
			$name = substr($name, 5);
			$storage_name = $this->name . '_' . $name;
		}
		else {
			$store_user = false;
			$storage_name = $this->name . ':' . $name;
		}

		if(empty($default)) {
			if($store_user) {
				$default= User::identify()->info->{$storage_name};
				$this->has_user_options = true;
			}
			else {
				$default= Options::get( $storage_name );
			}
		}
		if(class_exists($type)) {
			$control= new $type($name, $caption, $default);
			$control->set_storage( $storage_name, $store_user );
			$this->controls[$name]= $control;
		}
		return $control;
	}

	/**
	 * Set a function to call on form submission success
	 *
	 * @param mixed $callback A callback function or a plugin filter name.
	 */
	public function on_success( $callback )
	{
		$this->success_callback = $callback;
	}

	/**
	 * Calls the success callback for the form, and optionally saves the form values
	 * to the options table.
	 */
	public function success()
	{
		$result= true;
		if(isset($this->success_callback)) {
			if(is_callable($this->success_callback)) {
				$result= call_user_func($this->success_callback, $this);
			}
			else {
				$result= Plugins::filter($this->success_callback, $result, $this);
			}
		}
		if($result) {
			foreach($this->controls as $control) {
				$control->save();
			}
		}
		if($this->has_user_options) {
			User::identify()->info->commit();
		}
	}


	/**
	 * Set a form option
	 * Defaults for options are stored in the $this->options array
	 *
	 * @param string $option The name of the option to set
	 * @param mixed $value The value of the option
	 */
	public function set_option( $option, $value )
	{
		$this->options[$option] = $value;
	}

}


/**
 * A base class from which form controls to be used with FormUI can descend
 */
class FormControl
{
	protected $name;
	protected $caption;
	protected $default;
	protected $validators= array();
	protected $storage;
	protected $store_user = false;

	/**
	 * FormControl constructor - set initial settings of the control
	 *
	 * @param string $name The name of the control
	 * @param string $caption The caption used a the label when displaying a control
	 * @param string $default The default value of the control
	 */
	public function __construct($name, $caption, $default)
	{
		$this->name= $name;
		$this->caption= $caption;
		$this->default= $default;
	}


	/**
	 * Set the Options table key under which this option will be stored
	 *
	 * @param string $key The Options table key to store this option in
	 * @param boolean $store_user True to store the value in userinfo rather than
	 */
	public function set_storage($key, $store_user = false)
	{
		$this->storage= $key;
		$this->store_user = $store_user;
	}

	/**
	 * Store this control's value under the conrol's specified key.
	 *
	 * @param string $key (optional) The Options table key to store this option in
	 */
	public function save($key= null, $store_user= null)
	{
		if(isset($key)) {
			$this->storage= $key;
		}
		if(isset($store_user)) {
			$this->store_user= $store_user;
		}
		if($this->store_user) {
			User::identify()->info->{$this->storage} = $this->value;
		}
		else {
			Options::set($this->storage, $this->value);
		}
	}

	/**
	 * Return the HTML construction of the control.
	 * Abstract function.
	 *
	 * @param boolean $forvalidation True if the control should output validation information with the control.
	 */
	public function out($forvalidation)
	{
		return '';
	}

	/**
	 * Return the HTML/script required for this type of control.
	 * Abstract function.
	 *
	 */
	public function pre_out()
	{
		return '';
	}

	/**
	 * Runs any attached validation functions to check validation of this control.
	 *
	 * @return array An array of string validation error descriptions or an empty array if no errors were found.
	 */
	public function validate()
	{
		$valid= array();
		foreach($this->validators as $validator) {
			if(is_callable($validator)) {
				$valid= array_merge($valid, call_user_func($validator, $this->value));
			}
			elseif(is_callable(array($this, $validator))){
				$valid= array_merge($valid, call_user_func(array($this, $validator), $this->value));
			}
			else {
				$valid= array_merge($valid, Plugins::filter($validator, $valid, $this->value));
			}
		}
		return $valid;
	}

	/**
	 * Magic function __get returns properties for this object.
	 * Potential valid properties:
	 * field: A valid unique name for this control in HTML.
	 * value: The value of the control, whether the default or submitted in the form
	 * name: The name of the control
	 *
	 * @param string $name The paramter to retrieve
	 * @return mixed The value of the parameter
	 */
	protected function __get($name)
	{
		switch($name) {
		case 'field':
			// must be same every time, no spaces
			return sprintf('%x', crc32($this->name));
		case 'value':
			if(isset($_POST[$this->field])) {
				return $_POST[$this->field];
			}
			else {
				return $this->default;
			}
		}
		if(isset($this->$name)) {
			return $this->$name;
		}
		return null;
	}


	/**
	 * Add a validation function to this control.
	 *
	 * @param mixed $callback A callback function or a plugin filter hook that will return an array of validation errors.
	 */
	public function add_validator( $callback )
	{
		$this->validators[]= $callback;
	}

	/**
	 * A validation function that returns an error if the value passed in is not set.
	 *
	 * @param string $text A value to test if it is empty
	 * @return array An empty array if the value exists, or an array with strings describing the errors
	 */
	public static function validate_required( $value )
	{
		if(empty($value) || $value == '') {
			return array(_t('A value for this field is required.'));
		}
		return array();
	}

}

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlText extends FormControl
{

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		$class= 'text formcontrol';
		if($forvalidation) {
			$validate= $this->validate();
			if(count($validate) != 0) {
				$class.= ' invalid';
				$message= implode('<br>', $validate);
			}
		}

		$out= '<div class="' . $class . '"><label>' . $this->caption . '<input type="text" name="' . $this->field . '" value="' . $this->value . '"></label>';
		if(isset($message)) {
			$out.= '<p class="error">' . $message . '</p>';
		}
		$out.= '</div>';
		return $out;
	}

	/**
	 * A validation function that returns an error if the value passed in is not a valid URL.
	 *
	 * @param string $text A string to test if it is a valid URL
	 * @return array An empty array if the string is a valid URL, or an array with strings describing the errors
	 */
	public static function validate_url( $text )
	{
		if ( !empty( $text ) ) {
			if(!preg_match('/^(?P<protocol>https?):\/\/(?P<domain>[-A-Z0-9.]+)(?P<file>\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?(?P<parameters>\\?[-A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $text)) {
				return array(_t('Value must be a valid URL.'));
			}
		}
		return array();
	}
}

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlStatic extends FormControl
{

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		return '<div class="static formcontrol">' . $this->caption . '</div>';
	}

	/**
	 * Do not store this static control anywhere
	 *
	 * @param mixed $key Unused
	 * @param mixed $store_user Unused
	 */
	public function save($key= null, $store_user= null)
	{
		// This function should do nothing.
	}

}


/**
 * A password control based on FormControlText for output via a FormUI.
 */
class FormControlPassword extends FormControlText
{

	/**
	 * Produce HTML output for this password control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		$class= 'text formcontrol';
		if($forvalidation) {
			$validate= $this->validate();
			if(count($validate) != 0) {
				$class.= ' invalid';
				$message= implode('<br>', $validate);
			}
		}

		$out= '<div class="' . $class . '"><label>' . $this->caption . '<input type="password" name="' . $this->field . '" value="' . substr(md5($this->value), 0, 8) . '"></label>';
		if(isset($message)) {
			$out.= '<p class="error">' . $message . '</p>';
		}
		$out.= '</div>';
		return $out;
	}

	/**
	 * Magic function __get returns properties for this object, or passes it on to the parent class
	 * Potential valid properties:
	 * value: The value of the control, whether the default or submitted in the form
	 *
	 * @param string $name The paramter to retrieve
	 * @return mixed The value of the parameter
	 */
	protected function __get($name)
	{
		switch($name) {
			case 'value':
				if(isset($_POST[$this->field])) {
					if($_POST[$this->field] == substr(md5($this->default), 0, 8)) {
						return $this->default;
					}
					else {
						return $_POST[$this->field];
					}
				}
				else {
					return $this->default;
				}
			default:
				return parent::__get($name);
		}
	}
}

/**
 * A multiple-slot text control based on FormControl for output via a FormUI.
 * @todo Make DHTML fallback for non-js browsers
 */
class FormControlTextMulti extends FormControl
{
	public static $outpre = false;

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		$class= 'textmulti formcontrol';
		if($forvalidation) {
			$validate= $this->validate();
			if(count($validate) != 0) {
				$class.= ' invalid';
				$message= implode('<br>', $validate);
			}
		}

		$out= '<div class="' . $class . '">';
		if(isset($message)) {
			$out.= '<p class="error">' . $message . '</p>';
		}
		$out.= '<p>' . $this->caption . '</p>';
		$values = $this->value;
		if(!is_array($values)) {
			$values = array($values);
		}
		foreach($values as $value) {
			$out.= '<label><input type="text" name="' . $this->field . '[]" value="' . $value . '"> <a href="#" onclick="return controls.textmulti.remove(this);">[' . _t('remove') . ']</a></label>';
		}
		$out.= '<a href="#" onclick="return controls.textmulti.add(this, \'' . $this->field . '\');">[' . _t('add') . ']</a>';
		$out.= '</div>';
		return $out;
	}

	/**
	 * Return the HTML/script required for this control.  Do it only once.
	 * @return string The HTML/javascript required for this control.
	 */
	public function pre_out()
	{
		$out= '';
		if(!FormControlTextMulti::$outpre) {
			FormControlTextMulti::$outpre = true;
			$out.= '
				<script type="text/javascript">
				controls.textmulti = {
					add: function(e, field){
						$(e).before("<label><input type=\"text\" name=\"" + field + "[]\"> <a href=\"#\" onclick=\"return controls.textmulti.remove(this);\">[' . _t('remove') . ']</a></label>");
						return false;
					},
					remove: function(e){
						if(confirm("' . _t('Remove this item?') . '")) {
							$(e).parents("label").remove();
						}
						return false;
					}
				}
				</script>
			';
		}
		return $out;
	}

	/**
	 * A validation function that returns an error if the value passed in is not a valid URL.
	 *
	 * @param array $text An array of strings to test if they are all valid URLs
	 * @return array An empty array if the array of strings is all valid URLs, or an array with strings describing the errors
	 */
	public static function validate_url( $arry )
	{
		$result = array();
		foreach($arry as $text) {
			if ( !empty( $text ) ) {
				if(!preg_match('/^(?P<protocol>https?):\/\/(?P<domain>[-A-Z0-9.]+)(?P<file>\/[-A-Z0-9+&@#\/%=~_|!:,.;]*)?(?P<parameters>\\?[-A-Z0-9+&@#\/%=~_|!:,.;]*)?/i', $text)) {
					$result[] = sprintf(_t('Value %s must be a valid URL.', $text));
				}
			}
		}
		return $result;
	}
}

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlSelect extends FormControl
{
	public $options = array();

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		$class= 'select formcontrol';
		if($forvalidation) {
			$validate= $this->validate();
			if(count($validate) != 0) {
				$class.= ' invalid';
				$message= implode('<br>', $validate);
			}
		}

		$out= '<div class="' . $class . '"><label>' . $this->caption . '<select name="' . $this->field . '">';
		foreach ( (array) $this->options as $key => $value ) {
			$out.= '<option value="' . $key . '"' . ( ( $this->value == $key ) ? ' selected' : '' ) . '>' . $value . '</option>';
		}
		$out.= '</select></label>';

		if(isset($message)) {
			$out.= '<p class="error">' . $message . '</p>';
		}
		$out.= '</div>';

		return $out;
	}
}

/**
 * A textarea control based on FormControl for output via a FormUI.
 */
class FormControlTextArea extends FormControl
{

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		$class= 'textarea formcontrol';
		if($forvalidation) {
			$validate= $this->validate();
			if(count($validate) != 0) {
				$class.= ' invalid';
				$message= implode('<br>', $validate);
			}
		}

		$out= '<div class="' . $class . '"><label>' . $this->caption . '<textarea name="' . $this->field . '">' . $this->value . '</textarea></label>';

		if(isset($message)) {
			$out.= '<p class="error">' . $message . '</p>';
		}
		$out.= '</div>';

		return $out;
	}
}

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlCheckbox extends FormControl
{

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function out($forvalidation)
	{
		$class= 'checkbox formcontrol';
		if($forvalidation) {
			$validate= $this->validate();
			if(count($validate) != 0) {
				$class.= ' invalid';
				$message= implode('<br>', $validate);
			}
		}

		$out= '<div class="' . $class . '"><label>' . $this->caption . '<input type="checkbox" name="' . $this->field . '" value="1" ' . (($this->value) ? 'checked' : '' ) . '><input type="hidden" name="' . $this->field . '_submitted" value="1" ></label>';

		if(isset($message)) {
			$out.= '<p class="error">' . $message . '</p>';
		}
		$out.= '</div>';

		return $out;
	}

	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	protected function __get($name)
	{
		switch($name) {
		case 'value':
			if(isset($_POST[$this->field . '_submitted'])) {
				if(isset($_POST[$this->field])) {
					return true;
				}
				else {
					return false;
				}
			}
			else {
				return $this->default;
			}
		}
		return parent::__get($name);
	}
}

?>
