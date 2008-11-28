<?php
/**
 * FormUI Library - Create interfaces for plugins
 *
 * FormUI			This is the main class, it generates the form itself;
 * FormContainer	A form-related class that can contain form elements, derived by FormUI and FormControlFieldset;
 * FormValidators	Catalog of validation functions, it can be extended if needed;
 * FormControl		Parent class to controls, it contains basic functionalities overrode in each control's class;
 * FormControl*		Every control needs a FormControl* class, FormUI literally looks for example, FormControlCheckbox.
 *
 * @version $Id$
 * @package Habari
 */

class FormContainer
{
	public $name = '';
	public $class = '';
	public $caption = '';
	public $controls = array();
	protected $theme_obj = null;
	protected $checksum;
	public $template = 'formcontainer';
	public $properties = array();

	/**
	 * Constructor for FormContainer prevents construction of this class directly
	 */
	private function __construct() {}

	/**
	 * Append a control to the end of this container
	 *
	 * @param string $name The name of the control
	 * @param string $type A classname, or the postfix of a class starting 'FormControl' that will be used to create the control
	 * @return FormControl An instance of the named FormControl descendant.
	 */
	public function append()
	{
		$control = null;
		$args = func_get_args();
		$type = array_shift($args);

		if($type instanceof FormControl) {
			$control = $type;
			$name = $control->name;
		}
		elseif(is_string($type) && class_exists('FormControl' . ucwords($type))) {
			$name = reset($args);
			$type = 'FormControl' . ucwords($type);

			if(class_exists($type)) {
				// Instanciate a new object from $type
				$controlreflect = new ReflectionClass($type);
				$control = $controlreflect->newInstanceArgs($args);
			}
		}
		if($control) {
			$control->container = $this;
			$this->controls[$name]= $control;
		}
		return $control;
	}

	/**
	 * Insert a control into the container
	 *
	 * @param string The name of the control to insert the new control in front of
	 * @param string The type of the new control
	 * @param string The name of the new control
	 * @return FormControl The new control instance
	 */
	public function insert()
	{
		$args = func_get_args();
		$before = array_shift($args);

		$control = call_user_func_array(array($this, 'append'), $args);
		if(is_string($before)) {
			$before = $this->$before;
		}
		$this->move_before($control, $before);
		return $control;
	}

	/**
	 * Generate a hash for this container
	 *
	 * @return string An md5 hash built using the controls contained within this container
	 */
	public function checksum()
	{
		if(!isset($this->checksum)) {
			$checksum = '';
			foreach($this->controls as $control) {
				if( method_exists($control, 'checksum') ) {
					$checksum .= get_class($control) . ':' . $control->checksum();
				}
				else {
					$checksum .= get_class($control) . ':' . $control->name;
				}
				$checksum .= '::';
			}
			$this->checksum = md5($checksum .= $this->name);
		}
		return $this->checksum;
	}

	/**
	 * Returns an associative array of the controls' values
	 *
	 * @return array Associative array where key is control's name and value is the control's value
	 */
	public function get_values()
	{
		$values = array();
		foreach ($this->controls as $control) {
			if ($control instanceOf FormContainer) {
				$values = array_merge($values, $control->get_values());
			}
			else {
				$values[$control->name]= $control->value;
			}
		}
		return $values;
	}

	/**
	 * Returns an associative array of controls
	 *
	 * @return array An array of FormControls
	 */
	public function get_controls()
	{
		$controls = array();
		foreach ($this->controls as $control) {
			if ($control instanceOf FormContainer) {
				$controls = array_merge($controls, $control->get_controls());
			}
			else {
				$controls[$control->name]= $control;
			}
		}
		return $controls;
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation, $this);
		$contents = '';
		foreach ( $this->controls as $control ) {
			$contents.= $control->get($forvalidation);
		}
		$theme->contents = $contents;
		// Do not move before $contents
		// Else, these variables will contain the last control's values
		$theme->class = $this->class;
		$theme->id = $this->name;
		$theme->caption = $this->caption;

		return $theme->fetch( $this->template );
	}

	/**
	 * Retreive the Theme used to display the form component
	 *
	 * @param boolean $forvalidation If true, perform validation on control and add error messages to output
	 * @param FormControl $control The control to output using a template
	 * @return Theme The theme object to display the template for the control
	 */
	function get_theme($forvalidation = false, $control = null)
	{
		if(!isset($this->theme_obj)) {
			$theme_dir = Plugins::filter( 'control_theme_dir', Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) ) . 'formcontrols/', $control );
			$this->theme_obj = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
		}
		if($control instanceof FormControl) {
			// PHP doesn't allow __get() to return pointers, and passing this array to foreach directly generates an error.
			$properties = $control->properties;
			foreach($properties as $name => $value) {
				$this->theme_obj->$name = $value;
			}
			$this->theme_obj->field = $control->field;
			$this->theme_obj->value = $control->value;
			$this->theme_obj->caption = $control->caption;
			$this->theme_obj->id = (string) $control->id;
			$class = $control->class;

			$message = '';
			if($forvalidation) {
				$validate = $control->validate();
				if(count($validate) != 0) {
					$class[]= 'invalid';
					$message = implode('<br>', (array) $validate);
				}
			}
			$this->theme_obj->class = implode( ' ', (array) $class );
			$this->theme_obj->message = $message;
		}
		return $this->theme_obj;
	}

	/**
	 * Moves a control to target's position to which we add $int if specified
	 * That integer is useful to move before or move after the target
	 *
	 * @param FormControl $control FormControl object to move
	 * @param FormControl $target FormControl object acting as destination
	 * @param int $int Integer added to $target's position (index)
	 */
	function move($source, $target, $offset = 0)
	{
		// Remove the source control from its container's list of controls
		$controls = array();
		foreach($source->container->controls as $name => $ctrl) {
			if($ctrl === $source) {
				$source_name = $name;
				continue;
			}
			$controls[$name] = $ctrl;
		}
		$source->container->controls = $controls;

		// Insert the source control into the destination control's container's list of controls in the correct location
		$target_index = array_search($target, array_values($target->container->controls), true);
		$left_slice = array_slice($target->container->controls, 0, ($target_index + $offset), true);
		$right_slice = array_slice($target->container->controls, ($target_index + $offset), count($target->container->controls), true);

		$target->container->controls = $left_slice + array($source_name => $source) + $right_slice;
	}

	/**
	 * Moves a control before the target control
	 *
	 * @param FormControl $control FormControl object to move
	 * @param FormControl $target FormControl object acting as destination
	 */
	function move_before($control, $target)
	{
		$this->move($control, $target);
	}

	/**
	 * Moves a control after the target control
	 *
	 * @param FormControl $control FormControl object to move
	 * @param FormControl $target FormControl object acting as destination
	 */
	function move_after($control, $target)
	{
		$this->move($control, $target, 1); // Increase left slice's size by one.
	}

	/**
	 * Replaces a target control by the supplied control
	 *
	 * @param FormControl $target FormControl object to replace
	 * @param FormControl $control FormControl object to replace $target with
	 */
	function replace($target, $control)
	{
		$this->move_after($control, $target);
		$this->remove($target);
	}

	/**
	 * Removes a target control from this group (can be the form or a fieldset)
	 *
	 * @param FormControl $target FormControl to remove
	 */
	function remove( $target )
	{
		// Strictness will skip recursiveness, else you get an exception (recursive dependency)
		unset( $this->controls[array_search($target, $this->controls, TRUE)] );
	}

	/**
	 * Returns true if any of the controls this container contains should be stored in userinfo
	 *
	 * @return boolean True if control data should be sotred in userinfo
	 */
	function has_user_options()
	{
		$has_user_options = false;
		foreach($this->controls as $control) {
			$has_user_options |= $control->has_user_options();
		}
		return $has_user_options;
	}


	/**
	 * Magic property getter, returns the specified control
	 *
	 * @param string $name The name of the control
	 * @return FormControl The control object requested
	 */
	function __get($name)
	{
		if(isset($this->controls[$name])) {
			return $this->controls[$name];
		}
		foreach($this->controls as $control) {
			if($control instanceof FormContainer) {
				if($ctrl = $control->$name) {
					return $ctrl;
				}
			}
		}
	}
	
	/**
	 * Magic property isset, returns if the specified control exists
	 *
	 * @param string $name The name of the control
	 * @return bool If the control object is set
	 */
	function __isset($name)
	{
		if(isset($this->controls[$name])) {
			return true;
		}
		foreach($this->controls as $control) {
			if($control instanceof FormContainer) {
				if($ctrl = $control->$name) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return the HTML/script required for all contained controls.  Do it only once.
	 *
	 * @return string The HTML/javascript required for all contained controls.
	 */
	function pre_out()
	{
		$preout = '';
		foreach ($this->controls as $control) {
			$preout.= $control->pre_out();
		}
		return $preout;
	}

	/**
	 * Runs any attached validation functions to check validation of each control contained in this fieldset.
	 *
	 * @return array An array of string validation error descriptions or an empty array if no errors were found.
	 */
	function validate()
	{
		$results = array();
		foreach($this->controls as $control) {
			if ($result = $control->validate()) {
				$results[]= $result;
			}
		}
		return $results;
	}

	/**
	 * Store each contained control's value under the control's specified key.
	 *
	 * @param string $key (optional) The Options table key to store this option in
	 */
	function save()
	{
		foreach($this->controls as $control) {
			$control->save();
		}
	}

}


/**
 * FormUI Class
 * This will generate the <form> structure and call subsequent controls
 *
 * For a list of options to customize its output or behavior see FormUI::set_option()
 */
class FormUI extends FormContainer
{
	private $success_callback;
	private $success_callback_params = array();
	private static $outpre = false;
	private $options = array(
		'save_button' => true,
		'ajax' => false,
		'form_action' => '',
		'template' => 'formcontrol_form',
		'theme' => '',
		'success_message' => '',
	);
	public $class = array( 'formui' );
	public $id = null;

	public $properties = array(
		'action' => '',
		'onsubmit' => '',
		'enctype' => 'application/x-www-form-urlencoded',
	);

	/**
	 * FormUI's constructor, called on instantiation.
	 *
	 * @param string $name The name of the form, used to differentiate multiple forms.
	 */
	public function __construct( $name )
	{
		$this->name = $name;
	}

	/**
	 * Generate a unique MD5 hash based on the form's name or the control's name.
	 *
	 * @return string Unique string composed of 35 hexadecimal digits representing the victim.
	 */
	public function salted_name()
	{
		return md5(Options::get('secret') . 'added salt, for taste' . $this->checksum());
	}

	/**
	 * Produce a form with the contained fields.
	 *
	 * @param boolean $process_for_success Set to true to display the form as it would look if the submission succeeded, but do not execute success methods.
	 * @return string HTML form generated from all controls assigned to this form
	 */
	public function get($process_for_success = true)
	{
		$forvalidation = false;

		$theme = $this->get_theme($forvalidation, $this);
		$theme->start_buffer();
		$theme->success = false;

		// Should we be validating?
		if(isset($_POST['FormUI']) && $_POST['FormUI'] == $this->salted_name()) {
			$validate = $this->validate();
			if(count($validate) == 0) {
				if($process_for_success) {
					$result = $this->success();
					if($result) {
						return $result;
					}
				}
				$theme->success = true;
				$theme->message = $this->options['success_message'];
			}
			else {
				$forvalidation = true;
			}
		}

		$out = '';

		$theme->controls = $this->output_controls($forvalidation);

		foreach($this->properties as $prop => $value) {
			$theme->$prop = $value;
		}

		$theme->id = Utils::slugify($this->name);
		$theme->class = implode( " ", (array) $this->class );
		$theme->action = $this->options['form_action'];
		$theme->onsubmit = ($this->properties['onsubmit'] == '') ? '' : "onsubmit=\"{$this->properties['onsubmit']}\"";
		$theme->salted_name = $this->salted_name();
		$theme->pre_out = $this->pre_out_controls();

		$out = $theme->fetch($this->options['template']);
		$theme->end_buffer();

		return $out;
	}

	/**
	 * Output a form with the contained fields.
	 * Calls $this->get() and echoes.
	 */
	public function out()
	{
		$args = func_get_args();
		echo call_user_func_array(array($this, 'get'), $args);
	}

	/**
	 * Return the form control HTML.
	 *
	 * @param boolean $forvalidation True if the controls should output additional information based on validation.
	 * @return string The output of controls' HTML.
	 */
	public function output_controls( $forvalidation = false )
	{
		$out = '';
		$this->get_theme( $forvalidation )->start_buffer();
		foreach($this->controls as $control) {
			$out.= $control->get( $forvalidation );
		}
		$this->get_theme( $forvalidation )->end_buffer();
		return $out;
	}

	/**
	 * Return pre-output control configuration scripts for any controls that require them.
	 *
	 * @return string The output of controls' pre-output HTML.
	 */
	public function pre_out_controls( )
	{
		$out = '';
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
		$validate = array();
		foreach($this->controls as $control) {
			$validate = array_merge($validate, $control->validate());
		}
		return $validate;
	}

	/**
	 * Set a function to call on form submission success
	 *
	 * @param mixed $callback A callback function or a plugin filter name.
	 */
	public function on_success( $callback )
	{
		$params = func_get_args();
		$callback = array_shift($params);
		$this->success_callback = $callback;
		$this->success_callback_params = $params;
	}

	/**
	 * Calls the success callback for the form, and optionally saves the form values
	 * to the options table.
	 */
	public function success()
	{
		$result = true;
		if(isset($this->success_callback)) {
			$params = $this->success_callback_params;
			array_unshift($params, $this);
			if(is_callable($this->success_callback)) {
				$result = call_user_func_array($this->success_callback, $params);
			}
			else {
				array_unshift($params, $this->success_callback, false);
				$result = call_user_func_array(array('Plugins', 'filter'), $params);
			}
			if($result) {
				return $result;
			}
		}
		else {
			$this->save();
			return false;
		}
	}

	/**
	 * Save all controls to their storage locations
	 */
	public function save()
	{
		foreach($this->controls as $control) {
			$control->save();
		}
		if($this->has_user_options()) {
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

	/**
	 * Configure all the options necessary to make this form work inside a media bar panel
	 * @param string $path Identifies the silo
	 * @param string $panel The panel in the silo to submit to
	 * @param string $callback Javascript function to call on form submission
	 */
	public function media_panel($path, $panel, $callback)
	{
		$this->options['ajax'] = true;
		$this->options['form_action'] = URL::get('admin_ajax', array('context' => 'media_panel'));
		$this->properties['onsubmit'] = "habari.media.submitPanel('$path', '$panel', this, '{$callback}');return false;";
	}

}

/**
 * FormValidators Class
 *
 * Extend this class to supply your own validators, by default we supply most common
 */
class FormValidators
{

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

	/**
	 * A validation function that returns an error if the value passed in is not a valid Email Address,
	 * as per RFC2822 and RFC2821.
	 *
	 * @param string $text A string to test if it is a valid Email Address
	 * @return array An empty array if the string is a valid Email Address, or an array with strings describing the errors
	 */
	public static function validate_email( $text )
	{
		if( !preg_match("@^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*\@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$@i", $text ) ) {
			return array(_t('Value must be a valid Email Address.'));
		}
		return array();
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

	/**
	 * A validation function that returns an error if the value passed does not match the regex specified.
	 *
	 * @param string $value A value to test if it is empty
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $container The container that holds the control
	 * @param string $regex The regular expression to test against
	 * @param string $warning An optional error message
	 * @return array An empty array if the value exists, or an array with strings describing the errors
	 */
	public static function validate_regex( $value, $control, $container, $regex, $warning = NULL )
	{
		if(preg_match($regex, $value)) {
			return array();
		}
		else {
			if ($warning == NULL) {
				$warning = _t('The value does not meet submission requirements');
			}
			else {
				$warning = _t($warning);
			}
			return array($warning);
		}
	}
}

/**
 * A base class from which form controls to be used with FormUI can descend
 */
class FormControl
{
	protected $caption;
	protected $default = null;
	protected $validators = array();
	protected $storage;
	protected $store_user = false;
	protected $theme_obj;
	protected $container = null;
	public $id = null;
	public $class = array( 'formcontrol' );
	public $name;
	protected $properties = array();
	protected $template = null;
	protected $raw = false;

	/**
	 * FormControl constructor - set initial settings of the control
	 *
	 * @param string $storage The storage location for this control
	 * @param string $default The default value of the control
	 * @param string $caption The caption used as the label when displaying a control
	 */
	public function __construct()
	{
		$args = func_get_args();
		list($name, $storage, $caption, $template) = array_merge($args, array_fill(0, 4, null));

		$this->name = $name;
		$this->storage = $storage;
		$this->caption = $caption;
		$this->template = $template;

		$this->default = null;
	}

	/**
	 * Retrieve the FormUI object that contains this control
	 *
	 * @return FormUI The containing form
	 */
	public function get_form()
	{
		$container = $this->container;
		while(!$container instanceof FormUI) {
			$container = $container->container;
		}
		return $container;
	}

	/**
	 * Return a checksum representing this control
	 *
	 * @return string A checksum
	 */
	public function checksum()
	{
		return md5($this->name . $this->storage . $this->caption );
	}


	/**
	 * Set the default value of this control from options or userinfo if the default value isn't explicitly set on creation
	 */
	protected function get_default()
	{
		// Get the default value from Options/UserInfo if it's not set explicitly
		if(empty($this->default)) {
			$storage = explode(':', $this->storage, 2);
			switch(count($storage)) {
				case 2:
					list($type, $location) = $storage;
					break;
				case 1:
					list($location) = $storage;
					$type = 'option';
					break;
				default:
					return $this->default;
			}

			switch($type) {
				case 'user':
					$this->default = User::identify()->info->{$location};
					break;
				case 'option':
					$this->default = Options::get( $location );
					break;
				case 'action':
					$this->default = Plugins::filter($location, '', $this->name, false);
					break;
				case 'null':
					break;
			}

		}
		return $this->default;
	}

	/**
	 * Store this control's value under the control's specified key.
	 *
	 * @param string $storage (optional) A storage location to store the control data
	 */
	public function save($storage = null)
	{
		if($storage == null) {
			$storage = $this->storage;
		}
		$storage = explode(':', $storage, 2);
		switch(count($storage)) {
			case 2:
				list($type, $location) = $storage;
				break;
			case 1:
				list($location) = $storage;
				$type = 'option';
				break;
			default:
				return;
		}

		switch($type) {
			case 'user':
				User::identify()->info->{$location} = $this->value;
				break;
			case 'option':
				Options::set( $location, $this->value );
				break;
			case 'action':
				Plugins::filter($location, $this->value, $this->name, true);
				break;
			case 'null':
				break;
		}
	}

	/**
	 * Return the HTML construction of the control.
	 * Abstract function.
	 *
	 * @param boolean $forvalidation True if the control should output validation information with the control.
	 */
	public function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation);
		$theme->start_buffer();

		foreach($this->properties as $prop => $value) {
			$theme->$prop = $value;
		}

		$theme->caption = $this->caption;
		$theme->id = $this->name;
		$theme->value = $this->value;

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Return the template name associated to this control, whether set explicitly or by class
	 *
	 * @return string The template used to display this control.
	 */
	public function get_template()
	{
		if( isset( $this->template ) ) {
			$template = $this->template;
		}
		else {
			$classname = get_class( $this );
			$type = '';
			if(preg_match('%FormControl(.+)%i', $classname, $controltype)) {
				$type = strtolower($controltype[1]);
			}
			else {
				$type = strtolower($classname);
			}
			$template = 'formcontrol_' . $type;
		}

		return $template;
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
		$valid = array();
		foreach($this->validators as $validator) {
			$validator_fn = array_shift($validator);
			if(is_callable($validator_fn)) {
				$params = array_merge(array($this->value, $this, $this->container), $validator);
				$valid = array_merge($valid, call_user_func_array( $validator_fn, $params ) );
			}
			elseif(method_exists('FormValidators', $validator_fn)) {
				$validator_fn = array('FormValidators', $validator_fn);
				$params = array_merge(array($this->value, $this, $this->container), $validator);
				$valid = array_merge($valid, call_user_func_array( $validator_fn, $params ) );
			}
			else {
				$params = array_merge(array($validator_fn, $valid, $this->value, $this, $this->container), $validator);
				$valid = array_merge($valid, call_user_func_array( array('Plugins', 'filter'), $params ) );
			}
		}
		return $valid;
	}

	/**
	 * Magic function __get returns properties for this object.
	 * Potential valid properties:
	 * field: A valid unique name for this control in HTML.
	 * value: The value of the control, whether the default or submitted in the form
	 *
	 * @param string $name The parameter to retrieve
	 * @return mixed The value of the parameter
	 */
	public function __get($name)
	{
		switch($name) {
			case 'field':
				// must be same every time, no spaces
				return isset($this->id) ? $this->id : sprintf('%x', crc32($this->name));
			case 'value':
				if(isset($_POST[$this->field])) {
					return $this->raw ? $_POST->raw($this->field) : $_POST[$this->field];
				}
				else {
					return $this->get_default();
				}
		}
		if(isset($this->$name)) {
			return $this->$name;
		}
		if(isset($this->properties[$name])) {
			return $this->properties[$name];
		}
		return null;
	}

	public function __toString()
	{
		return $this->value;
	}

	/**
	 * Returns true if this control should be stored as userinfo
	 *
	 * @return boolean True if this control should be stored as userinfo
	 */
	public function has_user_options()
	{
		$storage = explode(':', $this->storage, 2);
		switch(count($storage)) {
			case 2:
				list($type, $location) = $storage;
				break;
			default:
				return false;
		}

		if($type == 'user') {
			return true;
		}
		return false;
	}

	/**
	 * Magic property setter for FormControl and its descendants
	 *
	 * @param string $name The name of the property
	 * @param mixed $value The value to set the property to
	 */
	public function __set($name, $value)
	{
		switch($name) {
			case 'value':
				$this->default = $value;
				break;
			case 'container':
				if($this->container != $value && isset($this->container)) {
					$this->container->remove($this);
				}
				$this->container = $value;
				break;
			case 'name':
				$this->name = (string) $value;
				break;
			case 'caption':
				$this->caption = $value;
				break;
			case 'storage':
				$this->storage = $value;
				$this->default = null;
				break;
			case 'template':
				$this->template = $value;
				break;
			case 'raw':
				$this->raw = $value;
				break;
			default:
				$this->properties[$name] = $value;
				break;
		}
	}

	/**
	 * Return the theme used to output this control and perform validation if required.
	 *
	 * @param boolean $forvalidation If true, process this control for validation (adds validation failure messages to the theme)
	 * @return Theme The theme that will display this control
	 */
	protected function get_theme($forvalidation)
	{
		$theme = $this->container->get_theme($forvalidation, $this);
		foreach($this->properties as $name => $value) {
			$theme->name = $value;
		}
		return $theme;
	}


	/**
	 * Add a validation function to this control
	 * Multiple parameters are passed as parameters to the validation function
	 * @param mixed $validator A callback function
	 * @param mixed $option... Multiple parameters added to those used to call the validator callback
	 * @return FormControl Returns the control for chained execution
	 */
	public function add_validator()
	{
		$args = func_get_args();
		$validator = reset($args);
		if(is_array($validator)) {
			$index = (is_object($validator[0]) ? get_class($validator[0]) : $validator[0]) . ':' . $validator[1];
		}
		else {
			$index = $validator;
		}
		$this->validators[$index]= $args;
		return $this;
	}

	/**
	 * Removes a validation function from this control
	 *
	 * @param string $name The name of the validator to remove
	 */
	public function remove_validator($name)
	{
		if(is_array($name)) {
			$index = (is_object($name[0]) ? get_class($name[0]) : $name[0]) . ':' . $name[1];
		}
		else {
			$index = $name;
		}
		unset($this->validators[$index]);
	}

	/**
	 * Move this control before the target
	 * In the end, this will use FormUI::move()
	 *
	 * @param object $target The target control to move this control before
	 */
	function move_before( $target )
	{
		$this->container->move_before( $this, $target );
	}

	/**
	 * Move this control after the target
	 * In the end, this will use FormUI::move()
	 *
	 * @param object $target The target control to move this control after
	 */
	function move_after( $target )
	{
		$this->container->move_after( $this, $target );
	}

	/**
	 * Remove this controls from the form
	 */
	function remove()
	{
		$this->container->remove($this);
	}

}

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
		list($name, $caption, $template) = array_merge($args, array_fill(0, 3, null));

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
	public function save($key = null, $store_user = null)
	{
		// This function should do nothing.
	}
}

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlText extends FormControl
{
// Placeholder class
}

/**
 * A submit control based on FormControl for output via FormUI
 */
class FormControlSubmit extends FormControlNoSave
{
// Placeholder class
}

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlStatic extends FormControlNoSave
{
	/**
	 * Produce HTML output for this static text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get($forvalidation = true)
	{
		return $this->caption;
	}
}

/**
 * A control to display a single tag for output via FormUI
 */
class FormControlTag extends FormContainer
{
	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $tag A tag object
	 * @param string $template A template to use for display
	 */
	public function __construct()
	{
		$args = func_get_args();
		list($name, $tag, $template) = array_merge($args, array_fill(0, 3, null));

		$this->name = $name;
		$this->tag = $tag;
		$this->template = isset($template) ? $template : 'tabcontrol_tag';
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation, $this);
		$max = Tags::max_count();

		$tag = $this->tag;

		$theme->class = 'tag_'.$tag->slug;
		$theme->id = $tag->id;
		$theme->weight = $max > 0 ? round(($tag->count * 10)/$max) : 0;
		$theme->caption = $tag->tag;
		$theme->count = $tag->count;

		return $theme->fetch( 'tabcontrol_tag' );
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
	public function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation);
		$theme->outvalue = $this->value == '' ? '' : substr(md5($this->value), 0, 8);

		return $theme->fetch( $this->get_template() );
	}

	/**
	 * Magic function __get returns properties for this object, or passes it on to the parent class
	 * Potential valid properties:
	 * value: The value of the control, whether the default or submitted in the form
	 *
	 * @param string $name The paramter to retrieve
	 * @return mixed The value of the parameter
	 */
	public function __get($name)
	{
		$default = $this->get_default();
		switch($name) {
			case 'value':
				if(isset($_POST[$this->field])) {
					if($_POST[$this->field] == substr(md5($default), 0, 8)) {
						return $default;
					}
					else {
						return $_POST[$this->field];
					}
				}
				else {
					return $default;
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
	 * Return the HTML/script required for this control.  Do it only once.
	 * @return string The HTML/javascript required for this control.
	 */
	public function pre_out()
	{
		$out = '';
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

}

/**
 * A select control based on FormControl for output via a FormUI.
 */
class FormControlSelect extends FormControl
{
	public $options = array();
	public $multiple = false;
	public $size = 5;

	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name
	 * @param string $caption
	 * @param array $options
	 * @param string $selected
	 */
	public function __construct( )
	{
		$args = func_get_args();
		list($name, $storage, $caption, $options, $template) = array_merge($args, array_fill(0, 5, null));

		$this->name = $name;
		$this->storage = $storage;
		$this->caption = $caption;
		$this->options = $options;
		$this->template = $template;

		$this->default = null;
	}

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation);
		$theme->options = $this->options;
		$theme->multiple = $this->multiple;
		$theme->size = $this->size;
		$theme->id = $this->name;

		return $theme->fetch( $this->get_template() );
	}
}

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlCheckboxes extends FormControlSelect
{
	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation);
		$theme->options = $this->options;
		$theme->id = $this->name;

		return $theme->fetch( $this->get_template() );
	}

	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get($name)
	{
		switch($name) {
		case 'value':
			if(isset($_POST[$this->field . '_submitted'])) {
				if(isset($_POST[$this->field])) {
					return $_POST[$this->field];
				}
				else {
					return array();
				}
			}
			else {
				return $this->get_default();
			}
		}
		return parent::__get($name);
	}

}


/**
 * A textarea control based on FormControl for output via a FormUI.
 */
class FormControlTextArea extends FormControl
{
	// Placeholder class
}

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlCheckbox extends FormControl
{
	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get($name)
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
				return $this->get_default();
			}
		}
		return parent::__get($name);
	}
}

/**
 * A hidden field control based on FormControl for output via a FormUI.
 */
class FormControlHidden extends FormControl
{

	/**
	 * Produce HTML output for this hidden control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get($forvalidation = true)
	{
		return '<input type="hidden" name="' . $this->field . '" value="' . $this->value . '">';
	}

}

/**
 * A fieldset control based on FormControl for output via a FormUI.
 */
class FormControlFieldset extends FormContainer
{

	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $caption The legend to display in the fieldset markup
	 */
	public function __construct()
	{
		$args = func_get_args();
		list($name, $caption, $template) = array_merge($args, array_fill(0, 3, null));

		$this->name = $name;
		$this->caption = $caption;
		$this->template = isset($template) ? $template : 'formcontrol_fieldset';
	}
}

/**
 * A div wrapper control based on FormContainer for output via FormUI
 */
class FormControlWrapper extends FormContainer
{
	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $classes The classes to use in the div wrapper markup
	 */
	public function __construct()
	{
		$args = func_get_args();
		list($name, $class, $template) = array_merge($args, array_fill(0, 3, null));

		$this->name = $name;
		$this->class = $class;
		$this->caption = '';
		$this->template = isset($template) ? $template : 'formcontrol_wrapper';
	}
}


/**
 * A label control based on FormControl for output via a FormUI.
 */
class FormControlLabel extends FormControlNoSave
{

	/**
	 * Produce HTML output for this label control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get( $forvalidation = true )
	{
		$out = '<div' . (($this->class) ? ' class="' . implode( " ", (array) $this->class ) . '"' : '') . (($this->id) ? ' id="' . $this->id . '"' : '') .'><label for="' . $this->name . '">' . $this->caption . '</label></div>';
		return $out;
	}

}

/**
 * A radio control based on FormControl for output via a FormUI.
 */
class FormControlRadio extends FormControlSelect
{
// Placeholder class
}

/**
 * A control to display media silo contents based on FormControl for output via a FormUI.
 */
class FormControlSilos extends FormControlNoSave
{
// Placeholder class
}

/**
 * A control to display a tab splitter based on FormControl for output via a FormUI.
 */
class FormControlTabs extends FormContainer
{
	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name Name of this control
	 * @param string $caption The legend to display in the fieldset markup
	 */
	public function __construct()
	{
		$args = func_get_args();
		list($name, $caption, $template) = array_merge($args, array_fill(0, 3, null));

		$this->name = $name;
		$this->caption = $caption;
		$this->template = isset($template) ? $template : 'formcontrol_tabs';
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get($forvalidation = true)
	{
		$theme = $this->get_theme($forvalidation, $this);

		foreach ( $this->controls as $control ) {
			if($control instanceof FormContainer) {
				$content = '';
				foreach( $control->controls as $subcontrol) {
					if($content != '') {
						$content .= '<hr>';
					}
					$content .= $subcontrol->get($forvalidation);
				}
				$controls[$control->caption] = $content;
			}
		}
		$theme->controls = $controls;
		// Do not move before $contents
		// Else, these variables will contain the last control's values
		$theme->class = $this->class;
		$theme->id = $this->name;
		$theme->caption = $this->caption;

		return $theme->fetch( $this->template );
	}

}

?>