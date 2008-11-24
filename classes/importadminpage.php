<?php

class ImportAdminPage extends AdminPage
{
	/**
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 */
	public function post_import()
	{
		if ( !isset( $_REQUEST['importer'] ) ) {
			Utils::redirect( URL::get( 'admin', 'page=import' ) );
		}

		$importer = isset( $_POST['importer'] ) ? $_POST['importer'] : '';
		$stage = isset( $_POST['stage'] ) ? $_POST['stage'] : '';

		$this->theme->enctype = Plugins::filter( 'import_form_enctype', 'application/x-www-form-urlencoded', $importer, $stage );

		$this->display( 'import' );
	}
}

?>