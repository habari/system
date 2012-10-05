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

		// if this is the locale context, serve the locale javascript
		if ( $this->handler_vars['context'] == 'locale' ) {
			$this->locale_js();
		}
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
			Plugins::act( 'auth_ajax_verify',  $this );
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

	/**
	 * Serves the locale Javascript to translate javascript strings.
	 */
	public function locale_js() {
		header('Expires: ' . gmdate('D, d M Y H:i:s ', time() + 432000) . 'GMT');
		header('content-type: text/javascript');

		$domain = HabariLocale::get_messages();
		$domain_json = json_encode($domain);

		$js = <<<TEEHEE
function _t() {
    var domain = {$domain_json};
    var s = arguments[0];

    if(domain[s] != undefined) {
        s = domain[s][1][0];
    }

    for(var i = 1; i <= arguments.length; i++) {
        r = new RegExp('%' + (i) + '\\\\\$s', 'g');
        if(!s.match(r)) {
            r = new RegExp('%s');
        }
        s = s.replace(r, arguments[i]);
    }
    return s;
}
TEEHEE;
		echo Plugins::filter('locale_js', $js);
	}

}

?>
