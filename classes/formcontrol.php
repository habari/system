<?php

namespace Habari;

abstract class FormControl
{
	/** @var string $name The name of the conrol for the purposes of manipulating it from the container object */
	public $name;
	/** @var FormStorage|null $storage The storage object for this form */
	public $storage;
	/** @var string  */
	public $caption;
	/** @var array $properties Contains an array of properties used to assign to the output HTML */
	public $properties = array();
	/** @var array $settings Contains an array of settings that control the behavior of this control */
	public $settings = array();
	/** @var mixed $value This is the value of the control, which will differ depending on at what time you access it */
	public $value;
	/** @var mixed $initial_value This is the intially assigned value of the control, set and used internally */
	public $initial_value;
	/** @var FormContainer $container The container that contains this control */
	public $container;
	/** @var array $validators An array of validators to execute on this control */
	public $validators = array();
	/** @var array $vars These vars are added internally to the theme for output by the template */
	public $vars = array();
	/** @var array $errors An array of errors that is filled when the control is passed for validation */
	public $errors = array();
	/** @var bool $has_errors True when this control has errors, can be true with $errors isn't when the errors propagate to the container */
	public $has_errors = false;
	/** @var bool $value_set_manually If the value of this control was set manually, this should be true */
	public $value_set_manually = false;
	/** @var bool|string $helptext This is help text for the control, if it is set */
	public $helptext = false;
	protected $on_success = array();
	protected $on_save = array();


	/**
	 * Construct a control.
	 * @param string $name The name of the control
	 * @param FormStorage|string|null $storage A storage location for the data collected by the control
	 * @param array $properties An array of properties that apply to the output HTML
	 * @param array $settings An array of settings that apply to this control object
	 */
	public function __construct($name, $storage = 'null:null', array $properties = array(), array $settings = array())
	{
		$this->name = $name;
		$this->set_storage($storage);
		$this->set_properties($properties);
		$this->set_settings($settings);

		$this->_extend();
	}

	/**
	 * This function is called after __construct().  It does nothing, but its descendants might do something.
	 */
	public function _extend()
	{}

	/**
	 * Create a new instance of this class and return it, use the fluent interface
	 * @param string $name The name of the control
	 * @param FormStorage|string|null $storage A storage location for the data collected by the control
	 * @param array $properties An array of properties that apply to the output HTML
	 * @param array $settings An array of settings that apply to this control object
	 * @return FormControl An instance of the referenced FormControl with the supplied parameters
	 */
	public static function create($name, $storage = 'null:null', array $properties = array(), array $settings = array())
	{
		$class = get_called_class();
		$r_class = new \ReflectionClass($class);
		return $r_class->newInstanceArgs( compact('name', 'storage', 'properties', 'settings') );
	}

	/**
	 * Take an array of parameters, use the first as the FormControl class/type, and run the constructor with the rest
	 * @param array $arglist The array of arguments
	 * @return FormControl The created control.
	 */
	public static function from_args($arglist)
	{
		$arglist = array_pad($arglist, 6, null);
		list($type, $name, $storage, $properties, $settings) = $arglist;
		if(class_exists('\\Habari\\FormControl' . ucwords($type))) {
			$type = '\\Habari\\FormControl' . ucwords($type);
		}
		if(!class_exists($type)) {
			throw new \Exception(_t('The FormControl type "%s" is invalid.', array($type)));
		}
		if(is_null($properties)) {
			$properties = array();
		}
		if(is_null($settings)) {
			$settings = array();
		}

		if(is_string($properties)) {
			Utils::debug($arglist); die();
		}
		return new $type($name, $storage, $properties, $settings);
	}

	/**
	 * Set the storage for this control
	 * @param FormStorage|string|null $storage A storage location for the data collected by the control
	 * @return FormControl $this
	 */
	public function set_storage($storage)
	{
		if(is_string($storage)) {
			$storage = ControlStorage::from_storage_string($storage);
		}
		$this->storage = $storage;
		return $this;
	}

	/**
	 * Set the HTML-related properties of this control
	 * @param array $properties An array of properties that will be associated to this control's HTML output
	 * @param bool $override If true, the supplied properties completely replace the existing ones
	 * @return FormControl $this
	 */
	public function set_properties($properties, $override = false)
	{
		if($override) {
			$this->properties = $properties;
		}
		else {
			$this->properties = array_merge($this->properties, $properties);
		}
		return $this;
	}

	/**
	 * @param array $settings An array of settings that affect the behavior of this control object
	 * @return FormControl $this
	 */
	public function set_settings($settings, $override = false)
	{
		if($override) {
			$this->settings = $settings;
		}
		else {
			$this->settings = array_merge($this->settings, $settings);
		}
		return $this;
	}

	/**
	 * @param string $helptext Help text to appear for the control
	 * @return FormControl $this
	 */
	public function set_helptext($helptext)
	{
		$this->helptext = $helptext;
		return $this;
	}


	/**
	 * Retreive the Theme used to display this form component and its descendants
	 *
	 * @return Theme The theme object to display the template for the control
	 */
	public function get_theme()
	{
		static $theme_obj = null;

		if ( is_null( $theme_obj ) ) {
			$theme_obj = Themes::create( ); // Create the current theme instead of: 'admin', 'RawPHPEngine', $theme_dir
			// Add the templates for the form controls to the current theme,
			// and allow any matching templates from the current theme to override
			$control_templates_dir = Plugins::filter( 'control_templates_dir', HABARI_PATH . '/system/controls/templates', $this );
			$theme_obj->template_engine->queue_dirs($control_templates_dir);
		}
		return $theme_obj;
	}

	/**
	 * Get a list of potential templates that can render this control
	 * @return array An array of template names in fallback order
	 */
	public function get_template()
	{
		$template = $this->get_setting(
			'template',
			array(
				'control.' . $this->control_type(),
				'control',
			)
		);
		return $template;
	}

	/**
	 * Save this control's data to the initialized storage location
	 */
	public function save()
	{
		if($this->storage instanceof FormStorage) {
			$this->storage->field_save($this->name, $this->value);
		}
		foreach ( $this->on_save as $save ) {
			$callback = array_shift( $save );
			array_unshift($save, $this);
			Method::dispatch_array($callback, $save);
		}
	}

	/**
	 * Load this control's initial data from the initialized storage location
	 */
	public function load()
	{
		if(!$this->value_set_manually && $this->storage instanceof FormStorage) {
			$this->value = $this->storage->field_load($this->name);
		}
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		$this->set_value($_POST[$this->input_name()], false);
	}

	/**
	 * Obtain a unique identifier for this control that is the same every time the form is generated
	 * @return string
	 */
	public function control_id()
	{
		return $this->get_setting(
			'control_id',
			md5(get_class($this) . '-' . $this->name)
		);
	}

	/**
	 * Produce the control for display
	 * @param Theme $theme The theme that will be used to render the template
	 * @return string The output of the template
	 */
	public function get(Theme $theme)
	{
		// Start a var stack so that we can roll back to prior theme var values
		$theme->start_buffer();

		// Assign all of the vars to the theme
		foreach($this->vars as $k => $v) {
			$theme->assign($k, $v);
		}
		// Put the value of the control into the theme
		$theme->value = $this->value;

		// If there are errors, add an error class to the control
		if($this->has_errors) {
			$this->add_class('_has_error');
		}

		// Assign the control and its attributes into the theme
		$theme->_control = $this;
		$theme->_name = $this->name;
		$theme->_settings = $this->settings;
		$theme->_properties = $this->properties;
		$properties = is_array($this->properties) ? $this->properties: array();
		if(!isset($this->settings['ignore_name'])) {
			$properties = array_merge(array('name' => $this->name), $properties);
		}
		if(!isset($this->settings['internal_value'])) {
			$properties = array_merge(array('value' => $this->get_setting('html_value', $this->value)), $properties);
		}
		$theme->_attributes = Utils::html_attr($properties);

		// Do rendering
		$output = $this->get_setting('prefix_html', '');
		if(isset($this->settings['content'])) {  // Allow descendants to override the content produced entirely
			if(is_callable($this->settings['content'])) {
				$content_fn = $this->settings['content'];
				$output .= $content_fn($this);
			}
			else {
				$output .= $this->settings['content'];
			}
		}
		if(!isset($this->settings['norender'])) {  // Allow descendants to skip rendering the template for this control
			if(isset($this->settings['template_html'])) {
				// template_html can be a closure, and if so, it is called here and its value is used as the output
				if(is_callable($this->settings['template_html'])) {
					$output .= $this->settings['template_html']($theme, $this);
				}
				else {
					$output .= $this->settings['template_html'];
				}
			}
			else {
				$output .= $theme->display_fallback( $this->get_template(), 'fetch' );
			}
		}
		// Is there htlp text?  Output it, if so.
		if($this->helptext) {
			$output .= $this->wrap_by($this->get_setting('wrap_help', '<div class="helptext">%s</div>'), $this->helptext);
		}
		$output .= $this->get_setting('postfix_html', '');
		// If there are errors, wrap this control in an error div to display the errors.
		if(count($this->errors) > 0) {
			$output = $this->error_wrap($output, $this->errors);
		}
		else {
			$output = $this->wrap_by($this->get_setting('wrap', '%s'), $output, $this);
		}

		// Roll back the var stack we've been using for this control
		$theme->end_buffer();

		return $output;
	}

	/**
	 * Process a thing either using an sprintf-style string, or a closure
	 * @param Callable|string $wrapper An sprintf-style wrapper or a function that accepts the same arguments as the call to this
	 * @param mixed $thing One or more things to use as parameters to the sprintf/closure
	 * @return string The resultant string produced by applying the closure or sprintf template
	 */
	public function wrap_by($wrapper, $thing)
	{
		$args = func_get_args();
		if(is_callable($wrapper)) {
			array_shift($args);
			return call_user_func_array($wrapper, $args);
		}
		else {
			return call_user_func_array('sprintf', $args);
		}
	}

	/**
	 * Get the type of control this is (without the "FormControl" and in lower case)
	 * Usually used for template selection
	 * @return string The type of the control in lower case
	 */
	public function control_type()
	{
		static $type = null;

		if(is_null($type)) {
			$class = get_called_class();
			$type = $this->get_setting('control_type', function() use($class) {
				$type = 'unknown';
				if(preg_match('#FormControl(.+)$#i', $class, $matches)) {
					$type = strtolower($matches[1]);
				}
				return $type;
			});
		}

		return $type;
	}

	/**
	 * Set the container for this control
	 * @param FormContainer $container A container that this control is inside
	 * @return $this
	 */
	public function set_container($container)
	{
		$this->container = $container;
		return $this;
	}

	/**
	 * Set a template for use with this control
	 * @param string|array $template A template fallback list to search for this template
	 * @return FormControl $this
	 */
	public function set_template($template)
	{
		$templates = array_merge(
			Utils::single_array($template),
			array(
				'control.' . $this->control_type(),
				'control',
			)
		);
		$this->settings['template'] = $templates;
		return $this;
	}

	/**
	 * Set the value of the control
	 * @param mixed $value The initial value of the control
	 * @param bool $manually True if the value was set manually in code rather than being submitted
	 * @return FormControl $this
	 */
	public function set_value($value, $manually = true)
	{
		$this->value_set_manually = $manually;
		$this->value = $value;
		if($manually) {
			$this->initial_value = $value;
		}
		return $this;
	}

	/**
	 * Set an HTML value directly for the output of a control
	 * @param Callable|string $template The template to use for output of this control
	 * @return FormControl $this
	 */
	public function set_template_html($template)
	{
		$this->settings['template_html'] = $template;
		return $this;
	}

	/**
	 * Get the value of a setting
	 * @param string $name The name of the setting to get
	 * @param mixed $default The default value to use if the setting is not set
	 * @return mixed The value fo the setting or the default supplied
	 */
	public function get_setting($name, $default = null)
	{
		if(isset($this->settings[$name])) {
			return $this->settings[$name];
		}
		elseif(is_callable($default)) {
			return $default($name, $this);
		}
		else {
			return $default;
		}
	}

	/**
	 * Produce a unique id (not name) for this control for use with labels and such, only if one is not provided in the control properties
	 * @param bool $force_set Default to true, forcing the id to be set to the name of the control if it's not set already
	 * @return string|null The id of this control, or null if it's not set and not forced
	 */
	public function get_id($force_set = true)
	{
		if(!isset($this->properties['id']) && $force_set) {
			$this->properties['id'] = Utils::slugify($this->name, '_');
		}
		return isset($this->properties['id']) ? $this->properties['id'] : null;
	}

	/**
	 * Get the name to use for the input field
	 * @return string The name to use in the HTML for this control
	 */
	public function input_name()
	{
		return $this->get_setting(
			'input_name',
			$this->name
		);
	}

	/**
	 * Output this once when the control is first output to the page
	 *
	 * @return string
	 */
	public function pre_out()
	{
		return '';
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
		elseif( $validator instanceof \Closure ) {
			$index = microtime(true);
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
				$params = array_merge( array( $this->value, $this, $this->get_form() ), $validator );
				$valid = array_merge( $valid, call_user_func_array( $validator_fn, $params ) );
			}
			elseif ( FormValidators::have($validator_fn ) ) {
				$validator_fn = Method::create( 'Habari\FormValidators', $validator_fn );
				$params = array_merge( array( $this->value, $this, $this->get_form() ), $validator );
				$valid = array_merge( $valid, call_user_func_array( $validator_fn, $params ) );
			}
			else {
				$params = array_merge( array( $validator_fn, $valid, $this->value, $this, $this->get_form() ), $validator );
				$valid = array_merge( $valid, call_user_func_array( Method::create( '\\Habari\\Plugins', 'filter' ), $params ) );
			}
		}
		// If there are errors, propagate them to the container control if the container is a label, by default
		if($this->container instanceof FormControl) {
			$apply_errors_to = $this->get_setting('propagate_errors_to', $this->container instanceof FormControlLabel ? $this->container : $this);
			$apply_errors_to->errors = array_merge($apply_errors_to->errors, $valid);
		}
		else {
			$this->errors = array_merge($this->errors, $valid);
		}
		if(count($valid) > 0) {
			$this->has_errors = true;
		}
		return $valid;
	}

	/**
	 * Add one or more CSS classes to this control
	 * @param array|string $classes An array or a string of classes to add to this control
	 * @return FormControl $this
	 */
	public function add_class($classes)
	{
		if(!isset($this->properties['class'])) {
			$this->properties['class'] = array();
		}
		$this->properties['class'] = array_merge($this->properties['class'], explode(' ', $classes));
		return $this;
	}

	/**
	 * Remove one or more CSS classes from this control
	 * @param array|string $classes An array or a string of classes to remove from this control
	 * @return FormControl $this
	 */
	public function remove_class($classes)
	{
		if(!isset($this->properties['class'])) {
			$this->properties['class'] = array();
		}
		$this->properties['class'] = array_diff($this->properties['class'], explode(' ', $classes));
		return $this;
	}

	/**
	 * Find the form that holds this control
	 * @return FormUI The form that this control is in
	 */
	public function get_form()
	{
		$control = $this;
		while(!$control instanceof FormUI) {
			$control = $control->container;
		}
		return $control;
	}

	/**
	 * Wrap the output of a control (or some text content) with error content
	 * @param string $output The HTML to wrap
	 * @param array $errors An array of error strings
	 * @return string The returned output
	 */
	public function error_wrap($output, $errors)
	{
		$output = $this->wrap_by(
			$this->get_setting(
				'error_wrap',
				function() {
					return function($output, $errors) {
						return sprintf('<div class="_control_error">%1$s<ol class="_control_error_list"><li>%2$s</li></ol></div>', $output, implode('</li><li>', $errors));
					};
				}
			),
			$output,
			$errors
		);
		return $output;
	}

	/**
	 * Shortcut to wrap this control in a label
	 * @param string $label The caption of the label
	 * @return FormControlLabel The label control is returned.  FYI, THIS BREAKS THE FLUENT INTERFACE.
	 */
	public function label($label)
	{
		return FormControlLabel::wrap($label, $this);
	}

	/**
	 * Get a text label for this control
	 * @return string The label
	 */
	public function get_label()
	{
		if($this instanceof FormControlLabel) {
			return $this->label;
		}
		if($this->container instanceof FormControlLabel) {
			return $this->container->label;
		}
		return ucwords(str_replace('_', ' ', $this->name));
	}

	/**
	 * Set this control to its initial value
	 */
	public function clear()
	{
		$this->set_value($this->initial_value);
	}

	/**
	 * Calls the success callback for the form, and optionally saves the form values
	 * to the options table.
	 * @return boolean|string A string to replace the rendering of the form with, or false
	 */
	public function do_success()
	{
		$output = false;
		foreach ($this->on_success as $success) {
			$callback = array_shift($success);
			array_unshift($success, $this->get_form(), $this);
			$result = Method::dispatch_array($callback, $success);
			if ( is_string($result) ) {
				$output = $result;
			}
		}
		$this->save();
		return $output;
	}

	/**
	 * Set a function to call on form submission success
	 *
	 * @param mixed $callback A callback function or a plugin filter name (FormUI $form)
	 */
	public function on_success($callback)
	{
		$this->on_success[] = func_get_args();
	}

	/**
	 * Set a function to call on form submission success
	 *
	 * @param mixed $callback A callback function or a plugin filter name.
	 */
	public function on_save($callback)
	{
		$this->on_save[] = func_get_args();
	}

}
