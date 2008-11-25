<?php

class OptionsAdminPage extends AdminPage
{
	/**
	 * Handles get requests from the options admin page.
	 * Send all POST request to GET so FormUI can proccess them.
	 */
	public function act_request_post()
	{
		$this->act_request_get();
	}

	/**
	 * Handles posts requests from the options admin page
	 */
	public function act_request_get()
	{
		$option_items = array();
		$timezones = DateTimeZone::listIdentifiers();
		$timezones = array_merge( array( ''=>'' ), array_combine( array_values( $timezones ), array_values( $timezones ) ) );

		$option_items[_t('Name & Tagline')] = array(
			'title' => array(
				'label' => _t('Site Name'),
				'type' => 'text',
				'helptext' => '',
				),
			'tagline' => array(
				'label' => _t('Site Tagline'),
				'type' => 'text',
				'helptext' => '',
				),
			);

		$option_items[_t('Publishing')] = array(
			'pagination' => array(
				'label' => _t('Items per Page'),
				'type' => 'text',
				'helptext' => '',
				),
			'atom_entries' => array(
				'label' => _t('Entries to show in Atom feed'),
				'type' => 'text',
				'helptext' => '',
				),
			'comments_require_id' => array(
				'label' => _t('Require Comment Author Info'),
				'type' => 'checkbox',
				'helptext' => '',
				),
			);

		$option_items[_t('Time & Date')] = array(
			/*'presets' => array(
				'label' => _t('Presets'),
				'type' => 'select',
				'selectarray' => array(
					'europe' => _t('Europe')
					),
				'helptext' => '',
				),*/
			'timezone' => array(
				'label' => _t('Time Zone'),
				'type' => 'select',
				'selectarray' => $timezones,
				'helptext' => 'Current Date Time: ' . HabariDateTime::date_create()->format(),
				),
			'dateformat' => array(
				'label' => _t('Date Format'),
				'type' => 'text',
				'helptext' => 'Current Date: ' . HabariDateTime::date_create()->date
				),
			'timeformat' => array(
				'label' => _t('Time Format'),
				'type' => 'text',
				'helptext' => 'Current Time: ' . HabariDateTime::date_create()->time,
				)
			);

		$option_items[_t('Language')] = array(
			'locale' => array(
				'label' => _t( 'Locale' ),
				'type' => 'select',
				'selectarray' => array_merge( array( '' => 'default' ), array_combine( Locale::list_all(), Locale::list_all() ) ),
				'helptext' => 'International language code',
			),
			'system_locale' => array(
				'label' => _t('System Locale'),
				'type' => 'text',
				'helptext' => 'The appropriate locale code for your server',
			),
		);

		$option_items[_t('Logging')] = array(
			'log_backtraces' => array(
				'label' => _t( 'Log Backtraces' ),
				'type' => 'checkbox',
				'helptext' => _t( 'Logs error backtraces to the log tables\' data column. Can drastically increase log size!' ),
			),
		);

			/*$option_items[_t('Presentation')] = array(
			'encoding' => array(
				'label' => _t('Encoding'),
				'type' => 'select',
				'selectarray' => array(
					'UTF-8' => 'UTF-8'
					),
				'helptext' => '',
				),
			);*/

		$option_items = Plugins::filter( 'admin_option_items', $option_items );

		$form = new FormUI('Admin Options');
		$tab_index = 3;
		foreach ( $option_items as $name => $option_fields ) {
			$fieldset = $form->append( 'wrapper', Utils::slugify( $name ), $name );
			$fieldset->class = 'container settings';
			$fieldset->append( 'static', $name, '<h2>' . htmlentities( $name, ENT_COMPAT, 'UTF-8' ) . '</h2>' );
			foreach ( $option_fields as $option_name => $option ) {
				$field = $fieldset->append( $option['type'], $option_name, $option_name, $option['label'] );
				$field->template = 'optionscontrol_' . $option['type'];
				$field->class = 'item clear';
				if ( $option['type'] == 'select' && isset( $option['selectarray'] ) ) {
					$field->options = $option['selectarray'];
				}
				$field->tabindex = $tab_index;
				$tab_index++;
				$field->helptext = $option['helptext'];
				if ( isset( $option['helptext'] ) ) {
					$field->helptext = $option['helptext'];
				}
				else {
					$field->helptext = '';
				}
				// @todo: do something with helptext
			}
		}

		/* @todo: filter for additional options from plugins
		 * We could either use existing config forms and simply extract
		 * the form controls, or we could create something different
		 */

		$submit = $form->append( 'submit', 'apply', _t('Apply'), 'admincontrol_submit' );
		$submit->tabindex = $tab_index;
		$form->on_success( array( $this, 'form_options_success' ) );

		$this->theme->form = $form->get();
		$this->theme->option_names = array_keys( $option_items );
		$this->theme->display( 'options' );
	}

	/**
	 * Display a message when the site options are saved, and save those options
	 *
	 * @param FormUI $form The successfully submitted form
	 */
	public function form_options_success( FormUI $form )
	{
		$wsse = Utils::WSSE( $form->nonce->value, $form->timestamp->value );
		if ( isset($form->digest) && $form->digest->value == $wsse['digest'] ) {
			Session::notice( _t( 'Successfully updated options' ) );
			$form->save();
		}
		else {
			Session::error( _t('WSSE authentication failed. Could not update options.') );
		}
	}
}
?>