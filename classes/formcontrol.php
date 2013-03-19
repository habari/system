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
	public $settings;
	/** @var mixed $value This is the value of the control, which will differ depending on at what time you access it */
	public $value;
	/** @var FormContainer $container The container that contains this control */
	public $container;
	/** @var array $validators An array of validators to execute on this control */
	public $validators;
	/** @var array $vars These vars are added internally to the theme for output by the template */
	public $vars = array();


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
		list($type, $name, $storage, $caption, $properties, $settings) = $arglist;
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

		return new $type($name, $storage, $caption, $properties, $settings);
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
	 * @return FormControl $this
	 */
	public function set_properties($properties)
	{
		$this->properties = $properties;
		return $this;
	}

	/**
	 * @param array $settings An array of settings that affect the behavior of this control object
	 * @return FormControl $this
	 */
	public function set_settings($settings)
	{
		$this->settings = $settings;
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
		$this->storage->field_save($this->name, $this->value);
	}

	/**
	 * Load this control's initial data from the initialized storage location
	 */
	public function load()
	{
		$this->value = $this->storage->field_load($this->name);
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		$this->value = $_POST[$this->input_name()];
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


		// Assign the control and its attributes into the theme
		$theme->_control = $this;
		$properties = $this->properties;
		if(!isset($this->settings['ignore_name'])) {
			$properties = array_merge($properties, array('name' => $this->name));
		}
		$theme->_attributes = Utils::html_attr($properties);

		// Do rendering
		$output = $this->get_setting('prefix_html', '');
		if(isset($this->settings['content'])) {  // Allow descendants to override the content produced
			$output .= $this->settings['content'];
		}
		if(!isset($this->settings['norender'])) {  // Allow descendants to skip rendering the template for this control
			if(isset($this->settings['template_html'])) {
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
		$output .= $this->get_setting('postfix_html', '');

		// Roll back the var stack we've been using for this control
		$theme->end_buffer();

		return $output;
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
			$type = 'unknown';
			if(preg_match('#FormControl(.+)$#i', $class, $matches)) {
				$type = strtolower($matches[1]);
			}
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
	 * @return $this
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
			return $default($name);
		}
		else {
			return $default;
		}
	}

	/**
	 * Produce a unique id (not name) for this control for use with labels and such, only if one is not provided in the control properties
	 * @return string The id of this control
	 */
	public function get_id()
	{
		if(!isset($this->properties['id'])) {
			$this->properties['id'] = $this->name;
		}
		return $this->properties['id'];
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

	public function pre_out()
	{
		return '';
	}

	/**
	 * @param string|Closure $validator
	 * @return FormControl $this
	 * @todo Implement this
	 */
	public function add_validator($validator) {
		return $this;
	}
}
