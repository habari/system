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
 * Habari PluginHandler Class
 *
 */
class PluginHandler
{
	/**
	 * Name of action to trigger
	 *
	 * @var string
	 * @see act()
	 */
	public $action = '';

	/**
	* Instance of the current theme
	* @var string
	* @see act()
	*/
	public $theme = null;

	/**
	 * Internal array of handler variables (state info)
	 *
	 * @var SuperGlobal
	 */
	public $handler_vars = null;

	/**
	 * All handlers must implement act() to conform to handler API.
	 * This is the default implementation of act(), which attempts
	 * to call a class member method of $this->act_$action().  Any
	 * subclass is welcome to override this default implementation.
	 *
	 * @param string $action the action that was in the URL rule
	 */
	public function act($action)
	{
		if (null === $this->handler_vars) {
			$this->handler_vars = new SuperGlobal(array());
		}
		$this->action = $action;
		$this->theme = Themes::create();

		$action_hook = 'plugin_act_' . $action;
		$before_action_hook = 'before_' . $action_hook;
		$after_action_hook = 'after_' . $action_hook;

		Plugins::act( $before_action_hook, $this );
		Plugins::act( $action_hook, $this );
		Plugins::act( $after_action_hook );
	}

}

?>
