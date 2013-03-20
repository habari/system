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

		// Set the ID of the control explicitly if it's not already set
		$this->get_id();

		// If the form template wasn't explicitly set, set it, because this class' name can't be used to determine its template
		if(!isset($this->settings['template'])) {
			$this->set_template('control.form');
		}

		// Add the control ID to the template output for the form
		$this->vars['_control_id'] = $this->control_id();

		$output = parent::get($theme);
		if(class_exists('\tidy')) {
			$t = new \tidy();
			$t->parseString($output, array('indent' => true, 'wrap' => 80, 'show-body-only' => true));
			$output = (string) $t;
		}
		return $output;

		// Should we be validating?
		if ( isset( $_POST['FormUI'] ) && $_POST['FormUI'] == $this->control_id() ) {
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
				if ( !isset( $_SESSION['forms'][$this->control_id()]['url'] ) ) {
					$_SESSION['forms'][$this->control_id()]['url'] = Site::get_url( 'habari', true ) . Controller::get_stub() . '#' . $this->properties['id'];
				}
			}
		}
		else {
			$_SESSION['forms'][$this->control_id()]['url'] = Site::get_url( 'habari', true ) . Controller::get_stub() . '#' . $this->properties['id'];
		}
		if ( isset( $_SESSION['forms'][$this->control_id()]['error_data'] ) ) {
			foreach ( $_SESSION['forms'][$this->control_id()]['error_data'] as $key => $value ) {
				$_POST[$key] = $value;
			}
			unset( $_SESSION['forms'][$this->control_id()]['error_data'] );
			$forvalidation = true;
		}

		$out = '';

		$theme->controls = $this->output_controls( $theme );
		$theme->form = $this;

		foreach ( $this->properties as $prop => $value ) {
			$theme->$prop = $value;
		}

		$theme->_control = $this;

		$theme->class = Utils::single_array( $this->class );
		$this->action = $this->options['form_action'];
		$theme->salted_name = $this->control_id();
		$theme->pre_out = $this->pre_out_controls();

		$out = $this->prefix . $theme->display_fallback( $this->get_template(), 'fetch' ) . $this->postfix;
		//$out = $this->prefix . $theme->controls . $this->postfix;
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
		$_SESSION['forms'][$this->control_id()]['error_data'] = $_POST;
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
}

?>
