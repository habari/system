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
	 * Obtain a unique identifier for this control that is the same every time the form is generated
	 *
	 * @return string An md5 hash built using the controls contained within this container
	 */
	public function control_id()
	{
		$control = $this;
		return $this->get_setting(
			'control_id',
			function($setting_name, $control) use ($control) {
				$control_id = array($control->name);
				/** @var FormControl $control */
				foreach ( $this->controls as $control ) {
					$control_id[]= $control->control_id();
				}
				$control_id = md5(implode(',', $control_id));
				return $control_id;
			}
		);
	}

	/**
	 * Set the unique identifier for this control.
	 * This DOES NOT set the id attribute of the output HTML for this control!
	 * @param string $control_id A unique value identifying this control internally
	 * @return FormControl $this
	 */
	public function set_control_id($control_id)
	{
		$this->settings['control_id'] = $control_id;
		return $this;
	}

	/**
	 * Set a sprintf-style string that will wrap each control within this container with markup
	 * Use to create, for example, <div>%s</div> for each control in the container
	 * @param string $wrap The sprintf-style formatting string
	 * @return FormContainer $this
	 */
	public function set_wrap_each($wrap = '%s')
	{
		$this->settings['wrap_each'] = $wrap;
		return $this;
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
	function get( Theme $theme )
	{
		$content = '';
		/** @var FormControl $control */
		foreach ( $this->controls as $control ) {
			$wrap = $this->get_setting('wrap_each', '%s');
			if(isset($control->setting['wrap'])) {
				$content .= $control->get( $theme );
			}
			else {
				$content .= sprintf($wrap, $control->get( $theme ));
			}
		}
		$this->vars['content'] = $content;

		return parent::get($theme);
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
		$results = array_merge($results, parent::validate());
		return $results;
	}

	/**
	 * Load this control and its children's initial data from the initialized storage location
	 */
	public function load()
	{
		if(!$this->value_set_manually && $this->storage instanceof FormStorage) {
			$this->value = $this->storage->field_load($this->name);
		}

		/** @var FormControl $control */
		foreach($this->controls as $control) {
			$control->load();
		}
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		$this->value = $_POST[$this->input_name()];
		/** @var FormControl $control */
		foreach($this->controls as $control) {
			$control->process();
		}
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
