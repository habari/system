<?php
/**
 * @package Habari
 *
 */

/**
 * Handles Rest requests, sending them to plugin sinks.
 *
 */
class RestHandler extends ActionHandler
{

	/**
	 * Handles incoming REST requests for which the user need not be authenticated.
	 * Used for requests where no verification/authentication is required.
	 * Forwards the request to plugin hook registered for the RewriteRule.
	 *
	 */
	public function act_rest()
	{
		$matched_rule = Controller::get_matched_rule();
		$hookfn = $matched_rule->parameters['hook'];
		$result = call_user_func_array($hookfn, array($matched_rule->named_arg_values));
		//Utils::debug($result);
		
		if(!$result instanceof RestResponse) {
			$result = new RestResponse($result);
		}
		
		$result->out();
	}

	/**
	 * Handles incoming REST requests for which the user must be authenticated.
	 * Used for requests where tokens are provided for verification.
	 * Forwards the request to plugin hook registered for the RewriteRule.
	 *
	 * @see act_rest()
	 */
	public function act_verified_rest()
	{
		Plugins::act( 'auth_rest_verify',  $this );
		
		$matched_rule = Controller::get_matched_rule();
		$hookfn = $matched_rule->parameters['hook'];
		$result = call_user_func_array($hookfn, array($matched_rule->named_arg_values));
		
		if(!$result instanceof RestResponse) {
			$result = new RestResponse($result);
		}
		
		$result->out();
	}
}
?>