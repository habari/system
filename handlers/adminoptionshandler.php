<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari AdminOptionsHandler Class
 * Handles options-related actions in the admin
 *
 */
class AdminOptionsHandler extends AdminHandler
{
	/**
	 * Handles get requests from the options admin page
	 */
	public function get_options()
	{
		$this->post_options();
	}

	public function __construct()
	{
		parent::__construct();

		// Let's register the options page form so we can use it with ajax
		$self = $this;

		FormUI::register( 'admin_options', function( $form, $name, $extra_data ) use ( $self ) {
			$option_items = array();
			$timezones = \DateTimeZone::listIdentifiers();
			$timezones = array_merge( array( ''=>'' ), array_combine( array_values( $timezones ), array_values( $timezones ) ) );

			$option_items[_t( 'Name & Tagline' )] = array(
				'title' => array(
					'label' => _t( 'Site Name' ),
					'type' => 'text',
					'helptext' => '',
				),
				'tagline' => array(
					'label' => _t( 'Site Tagline' ),
					'type' => 'text',
					'helptext' => '',
				),
				'about'   => array(
					'label'    => _t( 'About' ),
					'type'     => 'textarea',
					'helptext' => '',
				),
			);

			$option_items[_t( 'Publishing' )] = array(
				'pagination' => array(
					'label' => _t( 'Items per Page' ),
					'type' => 'text',
					'helptext' => '',
				),
				'atom_entries' => array(
					'label' => _t( 'Entries to show in Atom feed' ),
					'type' => 'text',
					'helptext' => '',
				),
				'comments_require_id' => array(
					'label' => _t( 'Require Comment Author Email' ),
					'type' => 'checkbox',
					'helptext' => '',
				),
				'spam_percentage' => array(
					'label' => _t( 'Comment SPAM Threshold' ),
					'type' => 'text',
					'helptext' => _t('The likelihood a comment is considered SPAM, in percent.'),
				),
			);

			$option_items[_t( 'Time & Date' )] = array(
				'timezone' => array(
					'label' => _t( 'Time Zone' ),
					'type' => 'select',
					'selectarray' => $timezones,
					'helptext' => _t( 'Current Date Time: %s', array( DateTime::create()->format() ) ),
				),
				'dateformat' => array(
					'label' => _t( 'Date Format' ),
					'type' => 'text',
					'helptext' => _t( 'Current Date: %s', array( DateTime::create()->date ) ),
				),
				'timeformat' => array(
					'label' => _t( 'Time Format' ),
					'type' => 'text',
					'helptext' => _t( 'Current Time: %s', array( DateTime::create()->time ) ),
				)
			);

			$option_items[_t( 'Language' )] = array(
				'locale' => array(
					'label' => _t( 'Locale' ),
					'type' => 'select',
					'selectarray' => array_merge( array( '' => 'default' ), array_combine( Locale::list_all(), Locale::list_all() ) ),
					'helptext' => Config::exists('locale') ? _t('International language code : This value is set in your config.php file, and cannot be changed here.') : _t( 'International language code' ),
					'disabled' => Config::exists('locale'),
					'value' => Config::get('locale', Options::get( 'locale', 'en-us' )),
				),
				'system_locale' => array(
					'label' => _t( 'System Locale' ),
					'type' => 'text',
					'helptext' => _t( 'The appropriate locale code for your server' ),
				),
			);

			$option_items[_t( 'Troubleshooting' )] = array(
				'log_min_severity' => array(
					'label' => _t( 'Minimum Severity' ),
					'type' => 'select',
					'selectarray' => LogEntry::list_severities(),
					'helptext' => _t( 'Only log entries with a this or higher severity.' ),
				),
				'log_backtraces' => array(
					'label' => _t( 'Log Backtraces' ),
					'type' => 'checkbox',
					'helptext' => _t( 'Logs error backtraces to the log table\'s data column. Can drastically increase log size!' ),
				),
			);

			// This form is pretty silly.  Why doesn't this just use FormUI methods directly?  It would probably be shorter code and native.
			$option_items = Plugins::filter( 'admin_option_items', $option_items );

			$tab_index = 3;
			foreach ( $option_items as $name => $option_fields ) {
				/** @var FormControlFieldset $fieldset  */
				$fieldset = $form->append( FormControlWrapper::create( Utils::slugify( _u( $name ) ) )->set_properties( array( 'class' => 'container main settings' ) ) );
				$fieldset->append( FormControlStatic::create( $name )->set_static( '<h2 class="lead">' . htmlentities( $name, ENT_COMPAT, 'UTF-8' ) . '</h2>' ) );
				$fieldset->set_wrap_each( '<div>%s</div>' );
				foreach ( $option_fields as $option_name => $option ) {
					/** @var FormControlLabel $label */
					$label = $fieldset->append( FormControlLabel::create( 'label_for_' . $option_name, null )->set_label( $option['label'] ) );
					/** @var FormControl $field */
					$field = $label->append( $option['type'], $option_name, $option_name );
					$label->set_for( $field );
					if( isset( $option['value'] ) ) {
						$field->set_value( $option['value'] );
					}
					if( isset( $option['disabled'] ) && $option['disabled'] == true ) {
						$field->set_properties( array( 'disabled'=>'disabled' ) );
					}
					if ( $option['type'] == 'select' && isset( $option['selectarray'] ) ) {
						$field->set_options( $option['selectarray'] );
					}
					$field->tabindex = $tab_index;
					$tab_index++;
					if ( isset( $option['helptext'] ) ) {
						$field->set_helptext( $option['helptext'] );
					}
				}
			}

			/* @todo: filter for additional options from plugins
			 * We could either use existing config forms and simply extract
			 * the form controls, or we could create something different
			 */

			$buttons = $form->append( new FormControlWrapper( 'buttons', null, array( 'class' => 'container' ) ) );

			$buttons->append( FormControlSubmit::create( 'apply', null, array( 'tabindex' => $tab_index ) )->set_caption( _t( 'Apply' ) ) );
			$form->on_success( array( $self, 'form_options_success' ) );
		});
	}


	/**
	 * Handles POST requests from the options admin page
	 */
	public function post_options()
	{

		$form = new FormUI( 'Admin Options', 'admin_options');
		$this->theme->form = $form->get();

		$this->theme->display( 'options' );
		}

	/**
	 * Display a message when the site options are saved, and save those options
	 *
	 * @param FormUI $form The successfully submitted form
	 */
	public function form_options_success( $form )
	{
		Session::notice( _t( 'Successfully updated options' ) );
		$form->save();
		Utils::redirect();
	}

}
?>
