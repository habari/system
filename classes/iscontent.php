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
 * @package Habari
 *
 */

/**
 * IsContent Interface for Habari
 * Defines an interface for classes to return the type of content they are
 *
 * @version $Id$
 * @copyright 2008
 */
interface IsContent
{

	/**
	 * Returns the content type of the object instance
	 *
	 * @return string The content type of the object instance
	 */
	function content_type();

}

?>
