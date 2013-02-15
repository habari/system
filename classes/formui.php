<?php
/**
 * @package Habari
 *
 */

/**
 * FormUI Library - Create interfaces for plugins
 *
 * FormUI			This is the main class, it generates the form itself;
 * FormContainer	A form-related class that can contain form elements, derived by FormUI and FormControlFieldset;
 * FormValidators	Catalog of validation functions, it can be extended if needed;
 * FormControl		Parent class to controls, it contains basic functionalities overrode in each control's class;
 * FormControl*		Every control needs a FormControl* class, FormUI literally looks for example, FormControlCheckbox.
 *
 */

class FormComponents
{
	/**
	 * Produces a list of HTML parameters from specific values in this object
	 * @param array $map A list of attributes and the fields on this object of which to map the values to them
	 * @param array $additional A list of attributes and values to add explicitly to this output
	 * @return string A list of HTML-style parameters as produced from the input arrays
	 */
	function parameter_map($map = array(), $additional = array()) {
		$output = '';
		foreach($map as $tag_param => $tag_fields) {
			if(is_numeric($tag_param)) {
				$tag_param = $tag_fields;
			}
			$value_out = $this->get_value_out($tag_fields);
			if($value_out) {
				if(is_array($value_out)) {
					$output .= ' ' . $tag_param . '="' . implode(' ', $value_out) . '"';
				}
				else {
					$output .= ' ' . $tag_param . '="' . $value_out . '"';
				}
			}
		}
		foreach($additional as $tag_param => $value_out) {
			$output .= ' ' . $tag_param . '="' . $value_out . '"';
		}
		return $output;
	}

	/**
	 * Return the property value that is associated with the first present property from an array list
	 * @param array $tag_fields A list of potential fields to try
	 * @return bool|string False if no value found, string of the property value found
	 */
	public function get_value_out($tag_fields) {
		$value_out = false;
		foreach(Utils::single_array($tag_fields) as $tag_field) {
			if(isset($this->$tag_field)) {
				$value_out = $this->$tag_field;
				break;
			}
		}
		return $value_out;
	}
}

class FormContainer extends FormComponents
{
	public $name = '';
	public $class = '';
	public $caption = '';
	public $controls = array();
	/** @var Theme $theme_obj */
	protected $theme_obj = null;
	protected $checksum;
	public $template = 'formcontainer';
	public $properties = array();
	public $prefix = '';
	public $postfix = '';

	/**
	 * Constructor for FormContainer prevents construction of this class directly
	 */
	private function __construct() {}

	/**
	 * Append a control to the end of this container
	 *
	 * @param string $name The name of the control
	 * @param string $type A classname, or the postfix of a class starting 'FormControl' that will be used to create the control
	 * @return FormControl|FormContainer An instance of the named FormControl descendant.
	 */
	public function append()
	{
		$control = null;
		$args = func_get_args();
		$type = array_shift( $args );

		if ( $type instanceof FormControl || $type instanceof FormContainer) {
			$control = $type;
			$name = $control->name;
		}
		elseif ( is_string( $type ) && class_exists( 'FormControl' . ucwords( $type ) ) ) {
			$name = reset( $args );
			$type = 'FormControl' . ucwords( $type );

			if ( class_exists( $type ) ) {
				// Instanciate a new object from $type
				$controlreflect = new ReflectionClass( $type );
				$control = $controlreflect->newInstanceArgs( $args );
			}
		}
		if ( $control ) {
			$control->container = $this;
			$this->controls[$name] = $control;
		}
		return $control;
	}

	/**
	 * Insert a control into the container
	 *
	 * @param string The name of the control to insert the new control in front of
	 * @param string The type of the new control
	 * @param string The name of the new control
	 * @return FormControl|FormContainer The new control instance
	 */
	public function insert()
	{
		$args = func_get_args();
		$before = array_shift( $args );

		$control = call_user_func_array( array( $this, 'append' ), $args );
		if ( is_string( $before ) ) {
			$before = $this->$before;
		}
		$this->move_before( $control, $before );
		return $control;
	}

	/**
	 * Generate a hash for this container
	 *
	 * @return string An md5 hash built using the controls contained within this container
	 */
	public function checksum()
	{
		if ( !isset( $this->checksum ) ) {
			$checksum = '';
			foreach ( $this->controls as $control ) {
				if ( method_exists( $control, 'checksum' ) ) {
					$checksum .= get_class( $control ) . ':' . $control->checksum();
				}
				else {
					$checksum .= get_class( $control ) . ':' . $control->name;
				}
				$checksum .= '::';
			}
			$this->checksum = md5( $checksum .= $this->name );
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
		foreach ( $this->controls as $control ) {
			if ( $control instanceOf FormContainer ) {
				$values = array_merge( $values, $control->get_values() );
			}
			else {
				$values[$control->name] = $control->value;
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
		foreach ( $this->controls as $control ) {
			if ( $control instanceOf FormContainer ) {
				$controls = array_merge( $controls, $control->get_controls() );
			}
			else {
				$controls[$control->name] = $control;
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
	function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation, $this );
		$contents = '';
		foreach ( $this->controls as $control ) {
			$contents .= $control->get( $forvalidation );
		}
		$theme->contents = $contents;
		// Do not move before $contents
		// Else, these variables will contain the last control's values
		$theme->class = $this->class;
		$theme->id = $this->name;
		$theme->caption = $this->caption;
		$theme->control = $this;

		return $this->prefix . $theme->fetch( $this->template, true ) . $this->postfix;
	}

	/**
	 * Retreive the Theme used to display the form component
	 *
	 * @param boolean $forvalidation If true, perform validation on control and add error messages to output
	 * @param FormControl $control The control to output using a template
	 * @return Theme The theme object to display the template for the control
	 */
	function get_theme( $forvalidation = false, $control = null )
	{
		if ( !isset( $this->theme_obj ) ) {
			$theme_dir = Plugins::filter( 'control_theme_dir', Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) ) . 'formcontrols/', $control );
			$this->theme_obj = Themes::create( ); // Create the current theme instead of: 'admin', 'RawPHPEngine', $theme_dir
			// Add the templates for the form controls tothe current theme,
			// and allow any matching templates from the current theme to override
			$formcontrol_templates = Utils::glob($theme_dir . '*.php');
			foreach($formcontrol_templates as $template) {
				$template_name = basename($template, '.php');
				$this->theme_obj->add_template($template_name, $template);
			}
			$list = array();
			$list = Plugins::filter('available_templates', $list);
			foreach($list as $template_name) {
				if($template = Plugins::filter('include_template_file', null, $template_name)) {
					$this->theme_obj->add_template($template_name, $template);
				}
			}
		}
		$this->theme_obj->start_buffer();
		if ( $control instanceof FormControl ) {
			// PHP doesn't allow __get() to return pointers, and passing this array to foreach directly generates an error.
			$properties = $control->properties;
			foreach ( $properties as $name => $value ) {
				$this->theme_obj->$name = $value;
			}
			$this->theme_obj->field = $control->field;
			$this->theme_obj->value = $control->value;
			$this->theme_obj->caption = $control->caption;
			$this->theme_obj->id = (string) $control->id;
			$class = (array) $control->class;

			$message = '';
			if ( $forvalidation ) {
				$validate = $control->validate();
				if ( count( $validate ) != 0 ) {
					$class[] = 'invalid';
					$message = implode( '<br>', (array) $validate );
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
	function move( $source, $target, $offset = 0 )
	{
		// Remove the source control from its container's list of controls
		$controls = array();
		foreach ( $source->container->controls as $name => $ctrl ) {
			if ( $ctrl === $source ) {
				$source_name = $name;
				continue;
			}
			$controls[$name] = $ctrl;
		}
		$source->container->controls = $controls;

		// Insert the source control into the destination control's container's list of controls in the correct location
		$target_index = array_search( $target, array_values( $target->container->controls ), true );
		$left_slice = array_slice( $target->container->controls, 0, ( $target_index + $offset ), true );
		$right_slice = array_slice( $target->container->controls, ( $target_index + $offset ), count( $target->container->controls ), true );

		$target->container->controls = $left_slice + array( $source_name => $source ) + $right_slice;
	}

	/**
	 * Moves a control before the target control
	 *
	 * @param FormControl $control FormControl object to move
	 * @param FormControl $target FormControl object acting as destination
	 */
	function move_before( $control, $target )
	{
		$this->move( $control, $target );
	}

	/**
	 * Moves a control after the target control
	 *
	 * @param FormControl $control FormControl object to move
	 * @param FormControl $target FormControl object acting as destination
	 */
	function move_after( $control, $target )
	{
		$this->move( $control, $target, 1 ); // Increase left slice's size by one.
	}

	/**
	 * Move a control into the container
	 *
	 * @param FormControl $control FormControl object to move
	 * @param FormControl $target FormControl object acting as destination
	 */
	public function move_into( $control, $target )
	{
		// Remove the source control from its container's list of controls
		$controls = array();
		foreach ( $control->container->controls as $name => $ctrl ) {
			if ( $ctrl === $control ) {
				$source_name = $name;
				continue;
			}
			$controls[$name] = $ctrl;
		}
		$control->container->controls = $controls;

		$target->controls[$control->name] = $control;
	}

	/**
	 * Replaces a target control by the supplied control
	 *
	 * @param FormControl $target FormControl object to replace
	 * @param FormControl $control FormControl object to replace $target with
	 */
	function replace( $target, $control )
	{
		$this->move_after( $control, $target );
		$this->remove( $target );
	}

	/**
	 * Removes a target control from this group (can be the form or a fieldset)
	 *
	 * @param FormControl $target FormControl to remove
	 */
	function remove( $target )
	{
		// Strictness will skip recursiveness, else you get an exception (recursive dependency)
		unset( $this->controls[array_search( $target, $this->controls, true )] );
	}

	/**
	 * Returns true if any of the controls this container contains should be stored in userinfo
	 *
	 * @return boolean True if control data should be sotred in userinfo
	 */
	function has_user_options()
	{
		$has_user_options = false;
		foreach ( $this->controls as $control ) {
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
	function __get( $name )
	{
		if ( isset( $this->controls[$name] ) ) {
			return $this->controls[$name];
		}
		foreach ( $this->controls as $control ) {
			if ( $control instanceof FormContainer ) {
				// Assignment is needed to avoid an indirect modification notice
				if ( $ctrl = $control->$name ) {
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
	function __isset( $name )
	{
		if ( isset( $this->controls[$name] ) ) {
			return true;
		}
		foreach ( $this->controls as $control ) {
			if ( $control instanceof FormContainer ) {
				// Assignment is needed to avoid an indirect modification notice
				if ( $ctrl = $control->$name ) {
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
		foreach ( $this->controls as $control ) {
			$preout .= $control->pre_out();
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
		foreach ( $this->controls as $control ) {
			if ( $result = $control->validate() ) {
				$results[] = $result;
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
		foreach ( $this->controls as $control ) {
			$control->save();
		}
	}

	/**
	 * Explicitly assign the theme object to be used with this container
	 *
	 * @param Theme $theme The theme object to use to output this container
	 */
	function set_theme( $theme )
	{
		$this->theme_obj = $theme;
	}

	/**
	 * Output any validation errors on any controls in this container using the supplied format
	 * $this->validate must be called first!
	 *
	 * @params string $format A sprintf()-style format string to format the validation error
	 * @params string $format A sprintf()-style format string to wrap the returned error, only if at least one error exists
	 */
	public function errors_out( $format, $wrap = '%s' )
	{
		echo $this->errors_get( $format, $wrap );
	}

	/**
	 * Return any validation errors on any controls in this container using the supplied format
	 * $this->validate must be called first!
	 *
	 * @params string $format A sprintf()-style format string to format the validation error
	 * @params string $format A sprintf()-style format string to wrap the returned error, only if at least one error exists
	 * @return string The errors in the supplied format
	 */
	public function errors_get( $format, $wrap = '%s' )
	{
		$out = '';
		foreach ( $this->get_controls() as $control ) {
			foreach ( $control->errors as $error ) {
				$out .= sprintf( $format, $error );
			}
		}
		if ( $out != '' ) {
			$out = sprintf( $wrap, $out );
		}
		return $out;
	}

	/**
	 * Return the property value that is associated with the first present property from an array list
	 * This version only searches the list of the class' $properties array, because __get() on this objcet returns named FormControls instances
	 * @param array $tag_fields A list of potential fields to try
	 * @return bool|string False if no value found, string of the property value found
	 */
	public function get_value_out($tag_fields) {
		$properties = array_merge($this->properties, get_object_vars($this));
		$value_out = false;
		foreach(Utils::single_array($tag_fields) as $tag_field) {
			if(isset($properties[$tag_field])) {
				$value_out = $properties[$tag_field];
				break;
			}
		}
		return $value_out;
	}

}


/**
 * FormUI Class
 * This will generate the <form> structure and call subsequent controls
 *
 * For a list of options to customize its output or behavior see FormUI::set_option()
 */
class FormUI extends FormContainer implements IsContent
{
	private $success_callback;
	private $success_callback_params = array();
	private $on_save = array();
	public $success = false;
	public $submitted = false;
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
	public $formtype = '';

	public $properties = array(
		'action' => '',
		'onsubmit' => '',
		'enctype' => 'application/x-www-form-urlencoded',
		'accept_charset' => 'UTF-8',
	);

	/**
	 * FormUI's constructor, called on instantiation.
	 *
	 * @param string $name The name of the form, used to differentiate multiple forms.
	 * @param string $formtype The type of the form, used to classify form types for plugin modification
	 */
	public function __construct( $name, $formtype = null )
	{
		$this->name = $name;
		if ( isset( $formtype ) ) {
			$this->formtype = $formtype;
		}
		else {
			$this->formtype = $name;
		}
	}

	/**
	 * Generate a unique MD5 hash based on the form's name or the control's name.
	 *
	 * @return string Unique string composed of 35 hexadecimal digits representing the victim.
	 */
	public function salted_name()
	{
		return md5( Options::get( 'secret' ) . 'added salt, for taste' . $this->checksum() );
	}

	/**
	 * Produce a form with the contained fields.
	 *
	 * @param boolean $process_for_success Set to true to display the form as it would look if the submission succeeded, but do not execute success methods.
	 * @return string HTML form generated from all controls assigned to this form
	 */
	public function get( $use_theme = null, $process_for_success = true )
	{
		$forvalidation = false;

		Plugins::act( 'modify_form_' . Utils::slugify( $this->formtype, '_' ), $this );
		Plugins::act( 'modify_form', $this );

		if ( isset( $use_theme ) ) {
			$theme = $use_theme;
		}
		else {
			$theme = $this->get_theme( $forvalidation, $this );
		}
		$theme->start_buffer();
		$theme->success = false;
		$this->success = false;
		$this->submitted = false;

		$this->properties['id'] = isset($this->properties['id']) ? $this->properties['id'] : Utils::slugify( $this->name );

		// Should we be validating?
		if ( isset( $_POST['FormUI'] ) && $_POST['FormUI'] == $this->salted_name() ) {
			$this->submitted = true;
			$validate = $this->validate();
			if ( count( $validate ) == 0 ) {
				if ( $process_for_success ) {
					$result = $this->success();
					if ( $result ) {
						return $result;
					}
				}
				$theme->success = true;
				$this->success = true;
				$theme->message = $this->options['success_message'];
			}
			else {
				$forvalidation = true;
				if ( !isset( $_SESSION['forms'][$this->salted_name()]['url'] ) ) {
					$_SESSION['forms'][$this->salted_name()]['url'] = Site::get_url( 'habari', true ) . Controller::get_stub() . '#' . $this->properties['id'];
				}
			}
		}
		else {
			$_SESSION['forms'][$this->salted_name()]['url'] = Site::get_url( 'habari', true ) . Controller::get_stub() . '#' . $this->properties['id'];
		}
		if ( isset( $_SESSION['forms'][$this->salted_name()]['error_data'] ) ) {
			foreach ( $_SESSION['forms'][$this->salted_name()]['error_data'] as $key => $value ) {
				$_POST[$key] = $value;
			}
			unset( $_SESSION['forms'][$this->salted_name()]['error_data'] );
			$forvalidation = true;
		}

		$out = '';

		$theme->controls = $this->output_controls( $forvalidation );
		$theme->form = $this;

		foreach ( $this->properties as $prop => $value ) {
			$theme->$prop = $value;
		}

		$theme->class = Utils::single_array( $this->class );
		$this->action = $this->options['form_action'];
		$theme->salted_name = $this->salted_name();
		$theme->pre_out = $this->pre_out_controls();

		$out = $this->prefix . $theme->display_fallback( $this->options['template'], 'fetch' ) . $this->postfix;
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
		echo call_user_func_array( array( $this, 'get' ), $args );
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
		$theme = $this->get_theme( $forvalidation );
		foreach ( $this->controls as $control ) {
			$out .= $control->get( $forvalidation );
		}
		$theme->end_buffer();
		return $out;
	}

	/**
	 * Return pre-output control configuration scripts for any controls that require them.
	 *
	 * @return string The output of controls' pre-output HTML.
	 */
	public function pre_out_controls()
	{
		$out = '';
		if ( !FormUI::$outpre ) {
			$out .= '<script type="text/javascript">if(controls==undefined){var controls = {init:function(fn){if(fn!=undefined){controls.inits.push(fn);}else{for(var i in controls.inits){controls.inits[i]();}}},inits:[]};}</script>';
		}
		foreach ( $this->controls as $control ) {
			$out .= $control->pre_out( );
		}
		if ( !FormUI::$outpre ) {
			FormUI::$outpre = true;
			$out .= '<script type="text/javascript">window.setTimeout(function(){controls.init();}, 500);</script>';
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
		foreach ( $this->controls as $control ) {
			$validate = array_merge( $validate, $control->validate() );
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
		$callback = array_shift( $params );
		$this->success_callback = $callback;
		$this->success_callback_params = $params;
	}

	/**
	 * Set a function to call on form submission success
	 *
	 * @param mixed $callback A callback function or a plugin filter name.
	 */
	public function on_save( $callback )
	{
		$this->on_save[] = func_get_args();
	}

	/**
	 * Calls the success callback for the form, and optionally saves the form values
	 * to the options table.
	 */
	public function success()
	{
		$result = true;
		if ( isset( $this->success_callback ) ) {
			$params = $this->success_callback_params;
			array_unshift( $params, $this );
			if ( is_callable( $this->success_callback ) ) {
				$result = call_user_func_array( $this->success_callback, $params );
			}
			else {
				array_unshift( $params, $this->success_callback, false );
				$result = call_user_func_array( array( 'Plugins', 'filter' ), $params );
			}
			if ( $result ) {
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
		foreach ( $this->controls as $control ) {
			$control->save();
		}
		foreach ( $this->on_save as $save ) {
			$callback = array_shift( $save );
			if ( is_callable( $callback ) ) {
				array_unshift( $save, $this );
				call_user_func_array( $callback, $save );
			}
			else {
				array_unshift( $save, $callback, $this );
				call_user_func_array( array( 'Plugins', 'act' ), $save );
			}
		}
		if ( $this->has_user_options() ) {
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
	 * Get a form option
	 *
	 * @param string $option The name of the option to get
	 * @return mixed The value of the named option if set, null if not set
	 */
	public function get_option( $option )
	{
		return isset( $this->options[$option] ) ? $this->options[$option] : null;
	}

	/**
	 * Configure all the options necessary to make this form work inside a media bar panel
	 * @param string $path Identifies the silo
	 * @param string $panel The panel in the silo to submit to
	 * @param string $callback Javascript function to call on form submission
	 */
	public function media_panel( $path, $panel, $callback )
	{
		$this->options['ajax'] = true;
		$this->options['form_action'] = URL::get( 'admin_ajax', array( 'context' => 'media_panel' ) );
		$this->properties['onsubmit'] = "habari.media.submitPanel('$path', '$panel', this, '{$callback}');return false;";
	}

	/**
	 * Redirect the user back to the stored URL value in session
	 */
	public function bounce()
	{
		$_SESSION['forms'][$this->salted_name()]['error_data'] = $_POST;
		Utils::redirect( $_SESSION['forms'][$this->salted_name()]['url'] );
	}

	/**
	 * Implementation of IsContent
	 * @return array An array of content types that this object represents, starting with the most specific
	 */
	public function content_type()
	{
		return array('form');
	}

	/**
	 * Convert this object instance to a string
	 * @return string The form as HTML
	 */
	public function __toString()
	{
		return $this->get();
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
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $form The container that holds the control
	 * @param string $warning An optional error message
	 * @return array An empty array if the string is a valid URL, or an array with strings describing the errors
	 */
	public static function validate_url( $text, $control, $form, $warning = null, $schemes = array( 'http', 'https' ) )
	{
		if ( ! empty( $text ) ) {
			$parsed = InputFilter::parse_url( $text );
			if ( $parsed['is_relative'] ) {
				// guess if they meant to use an absolute link
				$parsed = InputFilter::parse_url( 'http://' . $text );
				if ( $parsed['is_error'] ) {
					// disallow relative URLs
					$warning = empty( $warning ) ? _t( 'Relative urls are not allowed' ) : $warning;
					return array( $warning );
				}
			}
			if ( $parsed['is_pseudo'] || ! in_array( $parsed['scheme'], $schemes ) ) {
				// allow only http(s) URLs
				$warning = empty( $warning ) ? _t( 'Only %s urls are allowed', array( Format::and_list( $schemes ) ) ) : $warning;
				return array( $warning );
			}
		}
		return array();
	}

	/**
	 * A validation function that returns an error if the value passed in is not a valid Email Address,
	 * as per RFC2822 and RFC2821.
	 *
	 * @param string $text A string to test if it is a valid Email Address
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $form The container that holds the control
	 * @param string $warning An optional error message
	 * @return array An empty array if the string is a valid Email Address, or an array with strings describing the errors
	 */
	public static function validate_email( $text, $control, $form, $warning = null )
	{
		if ( ! empty( $text ) ) {
			if ( !preg_match( "@^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*\@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$@i", $text ) ) {
				$warning = empty( $warning ) ? _t( 'Value must be a valid Email Address.' ) : $warning;
				return array( $warning );
			}
		}
		return array();
	}

	/**
	 * A validation function that returns an error if the value passed in is not set.
	 *
	 * @param string $text A value to test if it is empty
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $form The container that holds the control
	 * @param string $warning An optional error message
	 * @return array An empty array if the value exists, or an array with strings describing the errors
	 */
	public static function validate_required( $value, $control, $form, $warning = null )
	{
		if ( empty( $value ) || $value == '' || (is_array( $value ) && implode( '', $value ) == '' ) ) {
			$warning = empty( $warning ) ? _t( 'A value for this field is required.' ) : $warning;
			return array( $warning );
		}
		return array();
	}

	/**
	 * A validation function that returns an error if the the passed username is unavailable
	 *
	 * @param string $text A value to test as username
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $form The container that holds the control
	 * @param string $allowed_name An optional name which overrides the check and is always allowed
	 * @param string $warning An optional error message
	 * @return array An empty array if the value exists, or an array with strings describing the errors
	 */
	public static function validate_username( $value, $control, $form, $allowed_name = null, $warning = null )
	{
		if ( isset( $allowed_name ) && ( $value == $allowed_name ) ) {
			return array();
		}
		if ( User::get_by_name( $value ) ) {
			$warning = empty( $warning ) ? _t( 'That username is already in use!' ) : $warning;
			return array( $warning );
		}
		return array();
	}


	/**
	 * A validation function that returns an error if the passed control values do not match
	 *
	 * @param string $text A value to test for similarity
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $form The container that holds the control
	 * @param FormControl $matcher The control which should have a matching value
	 * @param string $warning An optional error message
	 * @return array An empty array if the value exists, or an array with strings describing the errors
	 */
	public static function validate_same( $value, $control, $form, $matcher, $warning = null )
	{
		if ( $value != $matcher->value ) {
			$warning = empty( $warning ) ? _t( 'The value of this field must match the value of %s.', array( $matcher->caption ) ) : $warning;
			return array( $warning );
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
	public static function validate_regex( $value, $control, $container, $regex, $warning = null )
	{
		if ( preg_match( $regex, $value ) ) {
			return array();
		}
		else {
			if ( $warning == null ) {
				$warning = _t( 'The value does not meet submission requirements' );
			}
			return array( $warning );
		}
	}

	/**
	 * A validation function that returns an error if the value passed is not within a specified range
	 *
	 * @param string $value A value to test if it is empty
	 * @param FormControl $control The control that defines the value
	 * @param FormContainer $container The container that holds the control
	 * @param float $min The minimum value, inclusive
	 * @param float $max The maximum value, inclusive
	 * @param string $warning An optional error message
	 * @return array An empty array if the value is value, or an array with strings describing the errors
	 */
	public static function validate_range( $value, $control, $container, $min, $max, $warning = null )
	{
		if ( $value < $min ) {
			if ( $warning == null ) {
				$warning = _t( 'The value entered is lesser than the minimum of %d.', array( $min ) );
			}
			return array( $warning );
		}
		elseif ( $value > $max ) {
			if ( $warning == null ) {
				$warning = _t( 'The value entered is greater than the maximum of %d.', array( $max ) );
			}
			return array( $warning );
		}
		else {
			return array();
		}
	}
	
	public static function validate_array( $value, $control, $container, $validator_name, $validator_params = array() )
	{	
		$errors = array();
		
		if( !isset( $value ) ) {
			return $errors;
		}
		
		if( !is_array( $value ) ) {
			throw new Exception( _t( '%s only works for array values.', array( 'validate_array' ) ) );
		}
		
		if ( is_callable( $validator_name ) ) {
			foreach( $value as $single_value ) {
				$errors = array_merge( $errors, call_user_func_array( $validator_name, array_merge( array( $single_value, $control, $container ), $validator_params ) ) );
			}
		}
		elseif ( method_exists( 'FormValidators', $validator_name ) ) {
			$validator_name = array( 'FormValidators', $validator_name );
			foreach( $value as $single_value ) {
				$errors = array_merge( $errors, call_user_func_array( $validator_name, array_merge( array( $single_value, $control, $container ), $validator_params ) ) );
			}
		}
		
		return $errors;
	}
}

/**
 * A base class from which form controls to be used with FormUI can descend
 */
class FormControl extends FormComponents
{
	public $caption;
	protected $default = null;
	protected $validators = array();
	protected $storage;
	protected $store_user = false;
	protected $theme_obj;
	public $container = null;
	public $id = null;
	public $class = array( 'formcontrol' );
	public $name;
	public $properties = array();
	public $template = null;
	public $raw = false;
	public $errors = array();

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
		list( $name, $storage, $caption, $template ) = array_merge( $args, array_fill( 0, 4, null ) );

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
		while ( !$container instanceof FormUI ) {
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
		if ( is_array( $this->storage ) ) {
			$storage = reset($this->storage);
		}
		else if ( is_object( $this->storage ) ) {
			$storage = get_class($this->storage);
		}
		else if ( is_scalar( $this->storage ) ) {
			$storage = $this->storage;
		}
		else {
			$storage = 'unknown';
		}
		return md5( $this->name . $storage . $this->caption );
	}


	/**
	 * Set the default value of this control from options or userinfo if the default value isn't explicitly set on creation
	 */
	protected function get_default()
	{
		// Get the default value from Options/UserInfo if it's not set explicitly
		if ( empty( $this->default ) ) {
			if ( $this->storage instanceof FormStorage ) {
				$type = 'formstorage';
			}
			else {
				$storage = explode( ':', $this->storage, 2 );
				switch ( count( $storage ) ) {
					case 2:
						list( $type, $location ) = $storage;
						break;
					case 1:
						list( $location ) = $storage;
						$type = 'option';
						break;
					default:
						return $this->default;
				}
			}

			switch ( $type ) {
				case 'user':
					$this->default = User::identify()->info->{$location};
					break;
				case 'option':
					$this->default = Options::get( $location );
					break;
				case 'action':
					$this->default = Plugins::filter( $location, '', $this->name, false );
					break;
				case 'session';
					$session_set = Session::get_set( $location, false );
					if ( isset( $session_set[$this->name] ) ) {
						$this->default = $session_set[$this->name];
					}
					break;
				case 'formstorage':
					$this->default = $this->storage->field_load( $this->name );
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
				case 1:
					list( $location ) = $storage;
					$type = 'option';
					break;
				default:
					return;
			}
		}
		elseif ( $storage instanceof FormStorage ) {
			$type = 'formstorage';
		}
		elseif ( is_array( $storage ) ) {
			$type = 'actionarray';
			$location = array_shift( $storage );
		}
		else {
			// Dunno what was intended here, but it wasn't a valid/known storage option, so store nothing
			$type = 'null';
		}

		switch ( $type ) {
			case 'user':
				$user = User::identify();
				$user->info->{$location} = $this->value;
				$user->info->commit();
				break;
			case 'option':
				Options::set( $location, $this->value );
				break;
			case 'filter':
				Plugins::filter( $location, $this->value, $this->name, true, $this );
				break;
			case 'action':
				Plugins::act( $location, $this->value, $this->name, true, $this );
				break;
			case 'actionarray':
				Plugins::act( $location, $this->value, $this->name, $storage );
				break;
			case 'session';
				Session::add_to_set( $location, $this->value, $this->name );
				break;
			case 'formstorage':
				$storage->field_save( $this->name, $this->value );
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
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );

		foreach ( $this->properties as $prop => $value ) {
			$theme->$prop = $value;
		}

		$theme->caption = $this->caption;
		$theme->id = $this->name;
		$theme->value = $this->value;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Return the template name associated to this control, whether set explicitly or by class
	 *
	 * @return string The template used to display this control.
	 */
	public function get_template()
	{
		if ( isset( $this->template ) ) {
			$template = $this->template;
		}
		else {
			$classname = get_class( $this );
			$type = '';
			if ( preg_match( '%FormControl(.+)%i', $classname, $controltype ) ) {
				$type = strtolower( $controltype[1] );
			}
			else {
				$type = strtolower( $classname );
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
		return isset($this->properties['pre_out']) ? $this->properties['pre_out'] : '';
	}

	/**
	 * Runs any attached validation functions to check validation of this control.
	 *
	 * @return array An array of string validation error descriptions or an empty array if no errors were found.
	 */
	public function validate()
	{
		$valid = array();
		foreach ( $this->validators as $validator ) {
			$validator_fn = array_shift( $validator );
			if ( is_callable( $validator_fn ) ) {
				$params = array_merge( array( $this->value, $this, $this->container ), $validator );
				$valid = array_merge( $valid, call_user_func_array( $validator_fn, $params ) );
			}
			elseif ( method_exists( 'FormValidators', $validator_fn ) ) {
				$validator_fn = array( 'FormValidators', $validator_fn );
				$params = array_merge( array( $this->value, $this, $this->container ), $validator );
				$valid = array_merge( $valid, call_user_func_array( $validator_fn, $params ) );
			}
			else {
				$params = array_merge( array( $validator_fn, $valid, $this->value, $this, $this->container ), $validator );
				$valid = array_merge( $valid, call_user_func_array( array( 'Plugins', 'filter' ), $params ) );
			}
		}
		$this->errors = $valid;
		return $valid;
	}

	/**
	 * Output any validation errors on this control using the supplied format
	 * $this->validate must be called first!
	 *
	 * @params string $format A sprintf()-style format string to format the validation error
	 * @params string $format A sprintf()-style format string to wrap the returned error, only if at least one error exists
	 * @return boolean true if the control has errors
	 */
	public function errors_out( $format, $wrap = '%s' )
	{
		echo $this->errors_get( $format, $wrap );
	}

	/**
	* Return any validation errors on this control using the supplied format
	 * $this->validate must be called first!
	 *
	 * @params string $format A sprintf()-style format string to format the validation error
	 * @params string $format A sprintf()-style format string to wrap the returned error, only if at least one error exists
	 * @return boolean true if the control has errors
	 */
	public function errors_get( $format, $wrap = '%s' )
	{
		$out = '';
		foreach ( $this->errors as $error ) {
			$out .= sprintf( $format, $error );
		}
		if ( $out != '' ) {
			$out = sprintf( $wrap, $out );
		}
		return $out;
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
	public function __get( $name )
	{
		switch ( $name ) {
			case 'field':
				// must be same every time, no spaces
				return isset( $this->id ) ? $this->id : sprintf( '%x', crc32( $this->name ) );
			case 'value':
				if ( isset( $_POST[$this->field ] ) ) {
					return $this->raw ? $_POST->raw( $this->field ) : $_POST[$this->field];
				}
				else {
					return $this->get_default();
				}
		}
		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}
		if ( isset($this->properties[$name])) {
			return $this->properties[$name];
		}
		return null;
	}

	/**
	 * Magic function __isset returns whether properties exist for this object.
	 * Potential valid properties:
	 * field: A valid unique name for this control in HTML.
	 * value: The value of the control, whether the default or submitted in the form
	 *
	 * @param string $name The parameter to retrieve
	 * @return boolean True if the property exists
	 */
	public function __isset( $name )
	{
		switch ( $name ) {
			case 'field':
			case 'value':
				return true;
		}
		if ( property_exists( $this, $name ) ) {
			return true;
		}
		if ( isset( $this->properties[$name] ) ) {
			return true;
		}
		return false;
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
		if ( is_string( $this->storage ) ) {
			$storage = explode( ':', $this->storage, 2 );
			switch ( count( $storage ) ) {
				case 2:
					list( $type, $location ) = $storage;
					break;
				default:
					return false;
			}

			if ( $type == 'user' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Magic property setter for FormControl and its descendants
	 *
	 * @param string $name The name of the property
	 * @param mixed $value The value to set the property to
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'value':
				$this->default = $value;
				break;
			case 'container':
				if ( $this->container != $value && isset( $this->container ) ) {
					$this->container->remove( $this );
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
				
				// add the template to the list of css classes - keyed so subsequent changes overwrite it, rather than append
				$this->class['template'] = $value;
				
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
	protected function get_theme( $forvalidation )
	{
		return $this->container->get_theme( $forvalidation, $this );
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
		$validator = reset( $args );
		if ( is_array( $validator ) ) {
			$index = ( is_object( $validator[0] ) ? get_class( $validator[0] ) : $validator[0]) . ':' . $validator[1];
		}
		else {
			$index = $validator;
		}
		$this->validators[$index] = $args;
		return $this;
	}

	/**
	 * Removes a validation function from this control
	 *
	 * @param string $name The name of the validator to remove
	 */
	public function remove_validator( $name )
	{
		if ( is_array( $name ) ) {
			$index = ( is_object( $name[0] ) ? get_class( $name[0] ) : $name[0] ) . ':' . $name[1];
		}
		else {
			$index = $name;
		}
		unset( $this->validators[$index] );
	}

	/**
	 * Move this control inside of the target
	 * In the end, this will use FormUI::move()
	 *
	 * @param object $target The target control to move this control before
	 */
	function move_into( $target )
	{
		$this->container->move_into( $this, $target );
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
		$this->container->remove( $this );
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

/**
 * A text control based on FormControl for output via a FormUI.
 */
class FormControlText extends FormControl
{
	/**
	 * FormControlText constructor - set initial settings of the control
	 *
	 * @param string $storage The storage location for this control
	 * @param string $default The default value of the control
	 * @param string $caption The caption used as the label when displaying a control
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $storage, $caption, $template ) = array_merge( $args, array_fill( 0, 4, null ) );
		parent::__construct($name, $storage, $caption, $template);
		$this->properties['type'] = 'text';
	}

}

/**
 * A submit control based on FormControl for output via FormUI
 */
class FormControlSubmit extends FormControlNoSave
{
// Placeholder class
}

/**
 * A button control based on FormControl for output via FormUI
 */
class FormControlButton extends FormControlNoSave
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
	public function get( $forvalidation = true )
	{
		return $this->caption;
	}
}

/**
 * A control to display a single tag for output via FormUI
 */
class FormControlTag extends FormControl
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
		list( $name, $tag, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->tag = $tag;
		$this->template = isset( $template ) ? $template : 'tabcontrol_tag';
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		$max = Tags::vocabulary()->max_count();

		$tag = $this->tag;

		$theme->class = 'tag_'.$tag->term;
		$theme->id = $tag->id;
		$theme->weight = $max > 0 ? round( ( $tag->count * 10 )/$max ) : 0;
		$theme->caption = $tag->term_display;
		$theme->count = $tag->count;

		return $theme->fetch( $this->get_template(), true );
	}

}

/**
 * A password control based on FormControlText for output via a FormUI.
 */
class FormControlTags extends FormControlText
{
	public static $outpre = false;

	public function pre_out()
	{
		$out = '';
		if ( !FormControlTextMulti::$outpre ) {
			FormControlTextMulti::$outpre = true;
			$out = <<< TAGS_PRE_OUT
<script type="text/javascript">
$(function(){
	$('input.tags_control').each(function(){

		for(var z in tc_tags=$(this).val().split(/\s*,\s*/)) {
			tc_tags[z]=tc_tags[z].replace(/^(["'])(.*)\1$/, '$2');
		}
		console.log(tc_tags);

		\$this = $(this);
		ajax_url = $(this).data('ajax_url');
		console.log(ajax_url);
		\$this.select2({
			tags: tc_tags,
			placeholder: "Tags",
			minimumInputLength: 1,
			ajax: {
				url: ajax_url,
				dataType: 'json',
				quietMillis: 100,
				data: function (term, page) {
					return { q: term };
				},
				results: function (data, page) {
					var results = {};
					for(var z in data.data) {
						results[parseInt(z)] = {id: parseInt(z), text: data.data[z]};
					}
					return {results: results, more: false};
				},
				formatSelection: function(item) {
					return item.text;
				},
				formatResult: function(item) {
					return item.text;
				}
			}
		});

	});
});
</script>
TAGS_PRE_OUT;
		}
		return $out;
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
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		foreach ( $this->properties as $prop => $value ) {
			$theme->$prop = $value;
		}

		$theme->caption = $this->caption;
		$theme->id = $this->name;
		$theme->control = $this;
		$theme->outvalue = $this->value == '' ? '' : substr( md5( $this->value ), 0, 8 );

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Magic function __get returns properties for this object, or passes it on to the parent class
	 * Potential valid properties:
	 * value: The value of the control, whether the default or submitted in the form
	 *
	 * @param string $name The paramter to retrieve
	 * @return mixed The value of the parameter
	 */
	public function __get( $name )
	{
		$default = $this->get_default();
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field] ) ) {
					if ( $_POST[$this->field] == substr( md5( $default ), 0, 8 ) ) {
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
				return parent::__get( $name );
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
		if ( !FormControlTextMulti::$outpre ) {
			FormControlTextMulti::$outpre = true;
			$out .= '
				<script type="text/javascript">
				controls.textmulti = {
					add: function(e, field){
						$(e).before(" <span class=\"textmulti_item\"><input type=\"text\" name=\"" + field + "[]\"> <a href=\"#\" onclick=\"return controls.textmulti.remove(this);\" title=\"'. _t( 'Remove item' ).'\" class=\"textmulti_remove opa50\">[' . _t( 'remove' ) . ']</a></span>");
						return false;
					},
					remove: function(e){
						if (confirm("' . _t( 'Remove this item?' ) . '")) {
							if ( $(e).parent().parent().find("input").length == 1) {
								field = $(e).prev().attr("name");
								$(e).parent().prev().before("<input type=\"hidden\" name=\"" + field + "\" value=\"\">");
							}
							$(e).parent().prev("input").remove();
							$(e).parent().remove();
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
	 * @param string $template
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $storage, $caption, $options, $template ) = array_merge( $args, array_fill( 0, 5, null ) );

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
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );

		foreach ( $this->properties as $prop => $value ) {
			$theme->$prop = $value;
		}

		$theme->options = $this->options;
		$theme->multiple = $this->multiple;
		$theme->size = $this->size;
		$theme->id = $this->name;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
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
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		$theme->options = $this->options;
		$theme->id = $this->name;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field . '_submitted'] ) ) {
					if ( isset( $_POST[$this->field] ) ) {
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
		return parent::__get( $name );
	}

}

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlTree extends FormControlSelect
{
	public $options = array();
	public static $outpre = false;

	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name
	 * @param string $caption
	 * @param array $options
	 * @param string $template
	 * @param array $config
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $storage, $caption, $template, $config ) = array_merge( $args, array_fill( 0, 5, null ) );

		$this->name = $name;
		$this->storage = $storage;
		$this->caption = $caption;
		$this->template = $template;
		$this->config = empty($config) ? array() : $config;
	}
	
		/**
	 * Return the HTML/script required for this control.  Do it only once.
	 * @return string The HTML/javascript required for this control.
	 */
	public function pre_out()
	{
		$out = '';
		if ( !FormControlTree::$outpre ) {
			FormControlTree::$outpre = true;
			$out = <<<  CUSTOM_TREE_JS
				<script type="text/javascript">
controls.init(function(){
	$('ol.tree').nestedSortable({
		disableNesting: 'no-nest',
		forcePlaceholderSize: true,
		handle: 'div',
		items: 'li.treeitem',
		opacity: .6,
		placeholder: 'placeholder',
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div'
	});

	$('.tree_submitted').closest('form').submit(function(){
		var tree_input = $('.tree_submitted', this);
		var data = tree_input.siblings().nestedSortable('toArray', {startDepthCount: 1});
		var comma = '';
		var v = '';
		for(var i in data) {
			if(data[i].item_id != 'root') {
				v += comma + '{"id":"' + parseInt(data[i].item_id) + '","left":"' + (parseInt(data[i].left)-1) + '","right":"' + (parseInt(data[i].right)-1) + '"}';
				comma = ',';
			}
		}
		v = '[' + v + ']';
		tree_input.val(v);
	});
});
				</script>
CUSTOM_TREE_JS;
		}
		return $out;
	}

	/**
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		$theme->options = $this->value;
		$theme->id = $this->name;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
	}

	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get( $name )
	{
		static $posted = null;
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field . '_submitted'] ) ) {
					if(!isset($posted)) {
						$valuesj = $_POST->raw( $this->field . '_submitted');
						$values = json_decode($valuesj);
						$terms = array();
						foreach($this->get_default() as $term) {
							$terms[$term->id] = $term;
						}
						foreach($values as $value) {
							$terms[$value->id]->mptt_left = $value->left;
							$terms[$value->id]->mptt_right = $value->right;
						}
						$terms = new Terms($terms);
						$posted = $terms->tree_sort();
					}
					return $posted;
				}
				else {
					return $this->get_default();
				}
		}
		return parent::__get( $name );
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
	public function __get( $name )
	{
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field . '_submitted'] ) ) {
					if ( isset( $_POST[$this->field] ) ) {
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
		return parent::__get( $name );
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
	public function get( $forvalidation = true )
	{
		$output = '<input type="hidden" name="' . $this->field . '" value="' . $this->value . '"';
		if(isset($this->id)) {
			$output .= ' id="' . $this->id . '"';
		}
		$output .= '>';
		return $output;
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
		list( $name, $caption, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->caption = $caption;
		$this->template = isset( $template ) ? $template : 'formcontrol_fieldset';
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
		list( $name, $class, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->class = $class;
		$this->caption = '';
		$this->template = isset( $template ) ? $template : 'formcontrol_wrapper';
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
		$out = '<div' . ( ( $this->class ) ? ' class="' . implode( " ", (array) $this->class ) . '"' : '' ) . ( ( $this->id ) ? ' id="' . $this->id . '"' : '' ) .'><label for="' . $this->name . '">' . $this->caption . '</label></div>';
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
		list( $name, $caption, $template ) = array_merge( $args, array_fill( 0, 3, null ) );

		$this->name = $name;
		$this->caption = $caption;
		$this->template = isset( $template ) ? $template : 'formcontrol_tabs';
	}

	/**
	 * Produce HTML output for all this fieldset and all contained controls
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation, $this );

		foreach ( $this->controls as $control ) {
			if ( $control instanceof FormContainer ) {
				$content = '';
				foreach ( $control->controls as $subcontrol ) {
					// There should be a better way to know if a control will produce actual output,
					// but this instanceof is ok for now:
					if ( $content != '' && !( $subcontrol instanceof FormControlHidden ) ) {
						$content .= '<hr>';
					}
					$content .= $subcontrol->get( $forvalidation );
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

		return $theme->fetch( $this->template, true );
	}

}

?>
