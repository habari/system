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

/**
 * FormUI Class
 * This will generate the <form> structure and call subsequent controls
 *
 * For a list of options to customize its output or behavior see FormUI::set_option()
 */
class FormUI extends FormContainer implements IsContent
{
	protected $on_success = array();
	protected $on_save = array();

	public $success = false;
	public $submitted = false;

	private static $outpre = false;
	public $formtype = '';

	public $properties = array(
		'action' => '',
		'onsubmit' => '',
		'enctype' => 'application/x-www-form-urlencoded',
		'accept_charset' => 'UTF-8',
		'method' => 'POST',
	);

	/** @var bool|string If this is not false, this value should be rendered instead of the form as a success response */
	public $success_render = false;

	public static $registered_forms = array();

	/**
	 * FormUI's constructor, called on instantiation.
	 *
	 * @param string $name The name of the form, used to differentiate multiple forms.
	 * @param string $formtype The type of the form, used to classify form types for plugin modification
	 * @param array $extra_data An array of extra data that can be passed into the form
	 */
	public function __construct( $name, $formtype = null, $extra_data = array() )
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

		// Add WSSE validator so that it can be altered in form-building code
		$this->add_validator('validate_wsse');
	}

	/**
	 * Register a function to use to create a new form
	 * @param string $name The name of the form to register
	 * @param Callable $build_callback The method to call to customize a form instance (FormUI $form, string $name, string $form_type)
	 */
	public static function register($name, $build_callback)
	{
		self::$registered_forms[$name] = $build_callback;
	}

	/**
	 * Create a new instance of this class and return it, use the fluent interface
	 * @param string $name
	 * @param string $formtype
	 * @return FormUI
	 */
	public static function build($name, $formtype = null)
	{
		$class = get_called_class();
		$r_class = new \ReflectionClass($class);
		return $r_class->newInstanceArgs( compact('name', 'formtype') );
	}

	/**
	 * Produce a form with the contained fields.
	 *
	 * @param Theme $theme The theme to render the controls into
	 * @return string HTML form generated from all controls assigned to this form
	 */
	public function get( Theme $theme = null )
	{
		// Allow plugins to modify the form
		Plugins::act( 'modify_form_' . Utils::slugify( $this->formtype, '_' ), $this );
		Plugins::act( 'modify_form', $this );

		// Get the theme used to render the form
		if ( is_null( $theme ) ) {
			$theme = $this->get_theme();
		}

		$theme->start_buffer();
		$theme->success = false;
		$this->success = false;
		$this->submitted = false;
		$this->success_render = false;

		// Set the ID of the control explicitly if it's not already set
		$this->get_id();

		// If the form template wasn't explicitly set, set it, because this class' name can't be used to determine its template
		if(!isset($this->settings['template'])) {
			$this->set_template('control.form');
		}

		// Add the control ID to the template output for the form
		$this->vars['_control_id'] = $this->control_id();

		// Load all of the initial values of controls from their storage locations
		$this->load();

		// If there was an error submitting this form before, set the values of the controls to the old ones to retry
		$this->set_from_error_values();

		// Was the form submitted?
		if( isset( $_POST['_form_id'] ) && $_POST['_form_id'] == $this->control_id() ) {
			$this->submitted = true;

			// Process all of the submitted values into the controls
			$this->process();

			// Do any of the controls fail validation?  This call alters the wrap
			$validation_errors = $this->validate();
			if(count($validation_errors) == 0) {
				// All of the controls validate
				$this->success = true;
				// If do_success() returns anything, it should be output instead of the form.
				$this->success_render = $this->do_success();
			}
			else {
				if(isset($this->settings['use_session_errors']) && $this->settings['use_session_errors']) {
					$this->each(function($control) {
						$control->errors = array();
					});
					foreach($validation_errors as $error) {
						Session::error($error);
					}
				}
			}

			// Save the values submitted into this form
			// $this->store_submission();
		}
		else {
			// Store the location at which this form was loaded, so we can potentially redirect to it later
			if ( !$this->has_session_data() || !isset( $_SESSION['forms'][$this->control_id()]['url'] ) ) {
				$_SESSION['forms'][$this->control_id()]['url'] = Site::get_url( 'habari', true ) . Controller::get_stub() . '#' . $this->get_id(false);
			}
		}

		$output = $this->pre_out_controls();
		if($this->success_render) {
			$output .= $this->success_render;
		}
		else {
			$output .= parent::get($theme);
		}


		if(class_exists('\tidy')) {
			$t = new \tidy();
			$t->parseString($output, array('indent' => true, 'wrap' => 80, 'show-body-only' => true));
			//$output = (string) $t;
		}
		return $output;
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
	 * Simulate posting data to this form
	 * @param array $data An associative array of data to simultae adding to the $_POST array
	 * @param bool $do_wsse_and_id Default is false.  If true, add this form's id and correct WSSE values to the $_POST array
	 */
	public function simulate($data, $do_wsse_and_id = false)
	{
		if($do_wsse_and_id) {
			$_POST['_form_id'] = $this->control_id();
			foreach(Utils::WSSE() as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		foreach($data as $key => $value) {
			$_POST[$key] = $value;
		}
	}

	/**
	 * Return the form control HTML.
	 *
	 * @param boolean $forvalidation True if the controls should output additional information based on validation.
	 * @return string The output of controls' HTML.
	 */
	public function output_controls( Theme $theme )
	{
		/** @var FormControl $control */
		$out = '';
		foreach ( $this->controls as $control ) {
			$out .= $control->get( $theme );
		}
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
	 * Set a function to call on form submission success
	 *
	 * @param mixed $callback A callback function or a plugin filter name (FormUI $form)
	 */
	public function on_success( $callback )
	{
		$this->on_success[] = func_get_args();
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
	 * @return boolean|string A string to replace the rendering of the form with, or false
	 */
	public function do_success()
	{
		$output = false;
		foreach ( $this->on_success as $success ) {
			$callback = array_shift( $success );
			array_unshift($success, $this);
			$result = Method::dispatch_array($callback, $success);
			if(is_string($result)) {
				$output = $result;
			}
		}
		$this->save();
		return $output;
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
		$_SESSION['forms'][$this->control_id()]['error_data'] = $this->get_values();
		Utils::redirect( $_SESSION['forms'][$this->control_id()]['url'] );
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

	public static function from_html($name, $html)
	{
		$dom = new HTMLDoc($html);
		$form = new FormUI($name);

		// Set the form to render using the output of the HTMLDoc
		$form->settings['norender'] = true;
		$form->settings['content'] = function($form) use($dom) {
			return $dom->get();
		};
		$form->dom = $dom;
		// This form's id is an MD5 hash of the original HTML
		$form->settings['control_id'] = md5($html);

		// Add the _form_id and WSSE elements to the form
		// Note that these additional fields must be XML-safe, or they won't be added
		$dom->find('form')->append_html(Utils::setup_wsse());

		$dom->find('form')->append_html('<input type="hidden" name="_form_id" value="' . $form->control_id() . '" />');

		// Add synthetic controls for any found inputs
		foreach($dom->find('input') as $input) {
			/** @var FormControl $control */
			$form->append($control = FormControlDom::create($input->name)->set_node($input));
			if($input->data_validators) {
				foreach(explode(' ', $input->data_validators) as $validator) {
					$control->add_validator($validator);
				}
			}
		}
		foreach($dom->find('textarea') as $input) {
			/** @var FormControl $control */
			$form->append($control = FormControlDom::create($input->name)->set_node($input));
			if(!empty($input->data_validators)) {
				foreach(explode(' ', $input->data_validators) as $validator) {
					$control->add_validator($validator);
				}
			}
		}

		return $form;
	}

	/**
	 * Get whether there is session data stored for this form
	 * @return bool True if this form has session data set
	 */
	public function has_session_data()
	{
		return isset( $_SESSION['forms'] ) && isset( $_SESSION['forms'][$this->control_id()] );
	}

	/**
	 * Set the values of the form controls from their session error values, if stored
	 */
	public function set_from_error_values()
	{
		// Put any error data back into the form
		if ( $this->has_session_data() && isset( $_SESSION['forms'][$this->control_id()]['error_data'] ) ) {
			foreach ( $_SESSION['forms'][$this->control_id()]['error_data'] as $key => $value ) {
				$this->$key->value = $value;
			}
			unset( $_SESSION['forms'][$this->control_id()]['error_data'] );
		}
	}
}

?>
