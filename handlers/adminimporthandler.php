<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminImportHandler Class
 * Handles import-related actions in the admin
 *
 */
class AdminImportHandler extends AdminHandler
{
	/**
	 * Handles GET requests for the import page.
	 */
	public function get_import()
	{
		// First check for troublesome plugins
		$bad_features = array(
		    'ping',
		    'pingback',
		    'spamcheck',
		);
		$troublemakers = array();
		$plugins = Plugins::list_active();
		foreach( $plugins as $plugin ) {
			$info = Plugins::load_info( $plugin );
			$provides = array();
			if( isset($info->provides ) ) {
				foreach( $info->provides->feature as $feature ) {
					$provides[] = $feature;
				}
			}
			$has_bad = array_intersect( $bad_features, $provides );
			if( count( $has_bad ) ) {
				$troublemakers[] = $info->name;
			}
		}
		if( count( $troublemakers ) ) {
			$troublemakers = implode( ', ', $troublemakers );
			$msg = _t( 'Plugins that conflict with importing are active. To prevent undesirable consequences, please de-activate the following plugins until the import is finished: ' ) . '<br>';
			$msg .= $troublemakers;
			$this->theme->conflicting_plugins = $msg;
			Session::error( $msg );
		}

		// Now get on with creating the page
		$importer = isset( $_POST['importer'] ) ? $_POST['importer'] : '';
		$stage = isset( $_POST['stage'] ) ? $_POST['stage'] : '1';
		$step = isset( $_POST['step'] ) ? $_POST['step'] : '1';

		$this->theme->enctype = Plugins::filter( 'import_form_enctype', 'application/x-www-form-urlencoded', $importer, $stage, $step );
		
		// filter to get registered importers
		$importers = Plugins::filter( 'import_names', array() );
		
		// fitler to get the output of the current importer, if one is running
		if ( $importer != '' ) {
			$output = Plugins::filter( 'import_stage', '', $importer, $stage, $step );
		}
		else {
			$output = '';
		}

		$this->theme->importer = $importer;
		$this->theme->stage = $stage;
		$this->theme->step = $step;
		$this->theme->importers = $importers;
		$this->theme->output = $output;
		
		$this->display( 'import' );

	}

	/**
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 */
	public function post_import()
	{
		if ( !isset( $_POST['importer'] ) ) {
			Utils::redirect( URL::get( 'admin', 'page=import' ) );
		}

		$this->get_import();
	}

}
