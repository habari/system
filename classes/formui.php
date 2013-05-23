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

	public $success = false;
	public $submitted = false;

	public $formtype = '';

	public $properties = array(
		'action' => '',
		'onsubmit' => '',
		'enctype' => 'application/x-www-form-urlencoded',
		'accept-charset' => 'UTF-8',
		'method' => 'POST',
	);

	/** @var bool|string If this is not false, this value should be rendered instead of the form as a success response */
	public $success_render = false;

	public static $registered_forms = array();
	public $dom;

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
			$callback($this, $name, $formtype, $extra_data);
		}
		$this->set_settings(array('data' => $extra_data));

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
	 * @param string $name The name of the form to build
	 * @param string $formtype The type of the form
	 * @param array $extra_data Extra data to pass to the form for configuration purposes
	 * @return FormUI The instance of the created form
	 */
	public static function build($name, $formtype = null, $extra_data = array())
	{
		$class = get_called_class();
		$r_class = new \ReflectionClass($class);
		return $r_class->newInstanceArgs( compact('name', 'formtype', 'extra_data') );
	}

	/**
	 * Produce a form "duplicate" that does not process the form, display output, or include one-time-javascripts
	 *
	 * @param Theme $theme The theme to render the controls into
	 * @return string HTML form generated from all controls assigned to this form
	 */
	public function dupe( Theme $theme = null )
	{
		static $dupe_count = 0;
		$dupe_count++;
		$this->settings['is_dupe'] = true;
		$this->settings['id_prefix'] = 'dupe_' . $dupe_count . '_';
		$this->each(function(FormControl $control) use ($dupe_count) {
			$control->settings['id_prefix'] = 'dupe_' . $dupe_count . '_';
		});
		$result = $this->get($theme);
		$this->each(function(FormControl $control) use ($dupe_count) {
			unset($control->settings['id_prefix']);
		});
		$this->settings['id_prefix'] = '';
		$this->settings['is_dupe'] = false;
		return $result;
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
		// If the action of this form wasn't explicitly set, unset it to avoid validation errors in output
		if(empty($this->properties['action'])) {
			unset($this->properties['action']);
		}

		// Add the control ID to the template output for the form
		$this->vars['_control_id'] = $this->control_id();

		// Load all of the initial values of controls from their storage locations
		$this->load();

		// If there was an error submitting this form before, set the values of the controls to the old ones to retry
		$this->set_from_error_values();

		// Is this form not a same-page duplicate?
		if(!$this->get_setting('is_dupe', false)) {
			// Was the form submitted?
			if( isset( $_POST['_form_id'] ) && $_POST['_form_id'] == $this->control_id()) {
				$this->submitted = true;

				// Process all of the submitted values into the controls
				$this->process();

				// Do any of the controls fail validation?  This call alters the wrap
				$validation_errors = $this->validate();
				if(count($validation_errors) == 0) {
					// All of the controls validate
					$this->success = true;
					// If do_success() returns anything, it should be output instead of the form.
					$this->success_render = $this->do_success($this);
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
			if($this->success && isset($this->settings['success_message'])) {
				$output .= $this->settings['success_message'];
			}
		}
		else {
			$output = parent::get($theme);
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
	 * Process a form, then redirect, saving control values on errors for redisplay
	 * @param string $url The URL to redirect to, presumably with the original form on it
	 * @return string The form output, if needed
	 */
	public function post_redirect($url = null)
	{
		$result = $this->get();
		if($this->submitted) {
			if(!$this->success) {
				// Store the form values in the session prior to redirection so that they can be re-displayed
				$_SESSION['forms'][$this->control_id()]['error_data'] = $this->get_values();
			}
			if(empty($url)) {
				$url = $_SESSION['forms'][$this->control_id()]['url'];
			}
			Utils::redirect($url);
		}
		return $result;
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
		/** @var FormControl $control */
		foreach($this->controls as $control) {
			if($value = $control->value) {
				$_POST[$control->input_name()] = $value;
			}
		}
		foreach($data as $key => $value) {
			$_POST[$key] = $value;
		}
	}

	/**
	 * Return the form control HTML.
	 *
	 * @param Theme $theme The theme used to render the controls
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
		/** @var FormControl $control */
		foreach ( $this->controls as $control ) {
			$out .= $control->pre_out( );
		}
		return $this->controls_js($out);
	}

	/**
	 * Configure all the options necessary to make this form work inside a media bar panel
	 * @param string $path Identifies the silo
	 * @param string $panel The panel in the silo to submit to
	 * @param string $callback Javascript function to call on form submission
	 */
	public function media_panel( $path, $panel, $callback )
	{
		// @todo fix this
//		$this->options['ajax'] = true;
		$this->properties['action'] = URL::get( 'admin_ajax', array( 'context' => 'media_panel' ) );
		$this->properties['onsubmit'] = "habari.media.submitPanel('$path', '$panel', this, '{$callback}');return false;";
	}

	/**
	 * Redirect the user back to the stored URL value in session
	 */
	public function bounce($keep_hash = true)
	{
		$_SESSION['forms'][$this->control_id()]['error_data'] = $this->get_values();
		$url = $_SESSION['forms'][$this->control_id()]['url'];
		if(!$keep_hash) {
			$url = preg_replace('/#.+$/', '', $url);
		}
		Utils::redirect( $url );
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

	/**
	 * Create a form with controls from HTML
	 * @param string $name Name of the form
	 * @param string $html HTML of a form
	 * @return FormUI The form created from the supplied HTML
	 */
	public static function from_html($name, $html)
	{
		$dom = new HTMLDoc($html);
		$form = new FormUI($name);

		// Set the form to render using the output of the HTMLDoc
		$form->settings['norender'] = true;
		$form->settings['content'] = function(/* unused $form */) use($dom) {
			return $dom->get();
		};
		$form->dom = $dom;
		// This form's id is an MD5 hash of the original HTML
		$form->settings['control_id'] = md5($html);

		// Add the _form_id and WSSE elements to the form
		// Note that these additional fields must be XML-safe, or they won't be added
		$dom->find_one('form')->append_html(Utils::setup_wsse());

		$dom->find_one('form')->append_html('<input type="hidden" name="_form_id" value="' . $form->control_id() . '" />');

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

	/**
	 * Get a string that will be used to generate a component of a control's HTML id
	 * @return string
	 */
	public function get_id_component()
	{
		return $this->name;
	}


}

?>
