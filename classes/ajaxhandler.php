<?php

/**
 * Handles Ajax requests, sending them to plugin sinks
 *
 * @package Habari
 */

class AjaxHandler extends ActionHandler
{

	/**
	 * Handles incoming ajax requests for which the user need not be authenticated.
	 * Forwards the request to plugin actions for the "context" portion of the URL. 
	 * 
	 */
	public function act_ajax()
	{
		Plugins::act('ajax_' . $this->handler_vars['context'], $this);
	}

	/**
	 * Handles incoming ajax requests for which the user must be authenticated.
	 * Forwards the request to plugin actions for the "context" portion of the URL. 
	 * 
	 */
	public function act_auth_ajax()
	{
		$user = User::identify();
		if ($user !== FALSE) {
			Plugins::act('auth_ajax_' . $this->handler_vars['context'], $this);
			exit;
		}
	}

}

?>