<?php
/**
 * @package Habari
 *
 */

namespace Habari;

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

	public static $registered_forms = array();

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
		if(isset(self::$registered_forms[$formtype])) {
			$callback = self::$registered_forms[$formtype];
			$callback($this, $name, $formtype);
		}
	}

	/**
	 * Register a function to use to create a new form
	 * @param string $name The name of the form to register
	 * @param Callable $build_callback The method to call to customize a form instance
	 */
	public static function register($name, $build_callback)
	{
		self::$registered_forms[$name] = $build_callback;
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
				$result = call_user_func_array( Method::create( '\Habari\Plugins', 'filter' ), $params );
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
		/** @var FormControl $control */
		foreach ( $this->controls as $control ) {
			$control->save();
		}
		foreach ( $this->on_save as $save ) {
			$callback = array_shift( $save );
			array_unshift($save, $this);
			Method::dispatch_array($callback, $save);
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
		if ( empty( $value ) || $value == '' ) {
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
