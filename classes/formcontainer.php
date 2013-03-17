<?php

namespace Habari;

class FormContainer extends FormControl
{
	public $controls = array();


	/**
	 * Append a control to the end of this container
	 *
	 * @param FormControl $control A control to append to the end of this container
	 * @return FormControl The provided FormControl object, fluid
	 */
	public function append($control)
	{
		if(!$control instanceof FormControl) {
			$control = FormControl::from_args(func_get_args());
		}
		$control->set_container($this);
		$this->controls[$control->name] = $control;

		return $control;
	}

	/**
	 * @param $before_index
	 * @param $control
	 * @return FormControl
	 */
	public function insert($before, $control)
	{
		if(!$control instanceof FormControl) {
			$args = func_get_args();
			$before = array_shift($args);
			$control = FormControl::from_args($args);
		}
		$this->append($control);
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
		/** @var FormControl $control */
		$results = array();
		foreach ( $this->controls as $control ) {
			if ( $result = $control->validate() ) {
				$results = array_merge($results, $result);
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
		/** @var FormControl $control */
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
	 * @param string $format A sprintf()-style format string to format the validation error
	 * @param string $wrap A sprintf()-style format string to wrap the returned error, only if at least one error exists
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
