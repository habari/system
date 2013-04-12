<?php
/**
 * @package Habari
 *
 */

namespace Habari;

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

		$domain = Locale::get_messages();
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

	/**
	 * Register a lambda/closure as an ajax dispatch function
	 * @param string $name The context of the ajax URL
	 * @param callable $fn The function to dispatch to
	 */
	public static function register_ajax($name, $fn)
	{
		Plugins::register($fn, 'action', 'ajax_' . $name);
	}

	/**
	 * Register a lambda/closure as an auth_ajax dispatch function
	 * @param string $name The context of the ajax URL
	 * @param callable $fn The function to dispatch to
	 */
	public static function register_auth_ajax($name, $fn)
	{
		Plugins::register($fn, 'action', 'auth_ajax_' . $name);
	}


	/**
	 * Register plugin hooks
	 * @static
	 */
	public static function __static()
	{
		/*
		 * These registration functions are here rather than the classes that directly provide
		 * their data (like Tags for tag_list or Posts for post_list) because putting them in
		 * their respective classes would require that they be autoloaded on every page load
		 * so that these functions could register their ajax endpoints.
		 */
		// Registers an auth_ajax endpoint for tag autocompletion
		self::register_auth_ajax('tag_list', function(){
			$tags = Tags::search($_GET['q']);
			$results = array();
			foreach($tags as $tag) {
				$results[] = array('id' => $tag->term_display, 'text' => $tag->term_display);
			}
			$ar = new AjaxResponse();
			$ar->data = array(
				'results' => $results,
				'more' => false,
				'context' => array()
			);
			$ar->out();
		});

		// Registers an auth_ajax endpoint for post autocompletion
		self::register_auth_ajax('post_list', function(){
			$posts = Posts::get(array('title_search' => $_GET['q']));
			$results = array();
			foreach($posts as $post) {
				$results[] = array('id' => $post->id, 'text' => $post->title);
			}
			$ar = new AjaxResponse();
			$ar->data = array(
				'results' => $results,
				'more' => false,
				'context' => array()
			);
			$ar->out();
		});

	}

}

?>
