<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php

/**
 * InstallerTheme is a custom Theme class for the installer.
 *
 * @package Habari
 */

// We must tell Habari to use MyTheme as the custom theme class:
define( 'THEME_CLASS', 'InstallerTheme' );

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