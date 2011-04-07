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
		Plugins::act( 'ajax_' . $this->handler_vars['context'], $this );
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
		if ( $user->loggedin ) {
			/**
			 * Triggers the ajax plugin action for the context if user is authenticated.
			 *
			 * @see act_auth_ajax()
			 * @action ajax_auth_{$context}
			 */
			Plugins::act( 'auth_ajax_' . $this->handler_vars['context'], $this );
			exit;
		}
	}

}

?>
