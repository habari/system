<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php

/**
 * InstallerTheme is a custom Theme class for the installer.
 *
 * @package Habari
 */

// We must tell Habari to use MyTheme as the custom theme class:

/**
 * A custom theme for the installer
 */
class InstallerTheme extends Theme
{

/**
	 * Add additional template variables to the template output.
	 * For the installer, we don't want any extra template variables added.
	 *
	 */
	public function add_template_vars()
	{

	}

}

?>
