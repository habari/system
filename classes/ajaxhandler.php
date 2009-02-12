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
 * Handles Ajax requests, sending them to plugin sinks.
 *
 */
class AjaxHandler extends ActionHandler
{

	/**
	 * Handles incoming ajax requests for which the user need not be authenticated.
	 * Forwards the request to plugin actions for the "context" portion of the URL.
	 * The following example would set the context of 'foo' and trigger
	 * the plugin action 'ajax_foo'.
	 *
	 * <code>URL::get( 'ajax', 'context=foo' );</code>
	 *
	 */
	public function act_ajax()
	{
		/**
		 * Triggers the ajax plugin action for the context.
		 *
		 * @see AjaxHandler::act_ajax()
		 * @action ajax_{$context}
		 */
		Plugins::act('ajax_' . $this->handler_vars['context'], $this);
	}

	/**
	 * Handles incoming ajax requests for which the user must be authenticated.
	 * Forwards the request to plugin actions for the "context" portion of the URL.
	 *
	 * @see act_ajax()
	 */
	public function act_auth_ajax()
	{
		$user = User::identify();
		if ($user->loggedin) {
			/**
			 * Triggers the ajax plugin action for the context if user is authenticated.
			 *
			 * @see act_auth_ajax()
			 * @action ajax_auth_{$context}
			 */
			Plugins::act('auth_ajax_' . $this->handler_vars['context'], $this);
			exit;
		}
	}

}

?>
