<?php
/**
 * @package Habari
 *
 */

/**
 * This interface should be applied to plugins that implement importing
 * from other blogging tools.
 *
 */
interface Importer
{

	/**
	 * Return a list of names of things that this importer imports
	 *
	 * @return array List of importables.
	 */
	public function filter_import_names( $import_names );

	/**
	 * Return the page content for a specific stage of the import
	 *
	 * @return string Content of import stage
	 */
	public function filter_import_stage( $stageoutput, $import_name, $stage, $step );

}

?>
