<?php
/**
 * @package Habari
 *
 */

/**
 * Class which handles incoming requests and drives the
 * MVC strategy for building the model and assigning to
 * a view.
 *
 */
class Controller extends Singleton
{
	public $base_url = '';        // base url for site
	private $stub = '';            // stub supplied by rewriter
	private $action = '';          // action name (string)
	private $handler = null;       // the action handler object

	/**
	 * Enables singleton working properly
	 *
	 * @see singleton.php
	 */
	protected static function instance()
	{
		return self::getInstanceOf( get_class() );
	}

	/**
	 * Returns the base URL
	 *
	 * @return string base URL
	 */
	public static function get_base_url()
	{
		return Controller::instance()->base_url;
	}

	/**
	 * Returns the stub in its entirety
	 *
	 * @return  string  the URL incoming stub
	 */
	public static function get_stub()
	{
		return Controller::instance()->stub;
	}

	/**
	 * Returns the full requested URL
	 *
	 * @return string The full requested URL
	 */
	public static function get_full_url()
	{
		return self::get_base_url() . self::get_stub();
	}

	/**
	 * Returns the action
	 *
	 * @return  string name of action
	 */
	public static function get_action()
	{
		return Controller::instance()->action;
	}

	/**
	 * Returns the action handler
	 *
	 * @return  ActionHandler  handler object
	 */
	public static function get_handler()
	{
		return Controller::instance()->handler;
	}

	/**
	 * Returns the action handler's variables
	 *
	 * @return  array  variables used by handler
	 */
	public static function get_handler_vars()
	{
		return Controller::instance()->handler->handler_vars;
	}

	/**
	 * A convenience method for returning a handler variable (handler_var).
	 * This includes parameters set on the url, and fields submitted by POST.
	 * The alternative to this, while possible to write, is just too long.
	 * @param string $name The name of the variable to return.
	 * @param mixed $default A default value to return if the variable is not set.
	 * @return mixed The value of that variable in the handler
	 */
	public static function get_var( $name, $default = null )
	{
		return isset( Controller::instance()->handler->handler_vars[ $name ] ) ? Controller::instance()->handler->handler_vars[ $name ] : $default;
	}

	/**
	 * A convenience method for returning the rewrite rule that matches the requested URL
	 * @return RewriteRule|null The rule that matches the requested URL
	 */
	public static function get_matched_rule()
	{
		return isset( Controller::instance()->handler->handler_vars[ 'matched_rule' ] ) ? Controller::instance()->handler->handler_vars[ 'matched_rule' ] : null;
	}

	/**
	 * Parses the requested URL.  Automatically
	 * translates URLs coming in from mod_rewrite and parses
	 * out any action and parameters in the slug.
	 */
	public static function parse_request()
	{
		/* Local scope variable caching */
		$controller = Controller::instance();

		/* Grab the base URL from the Site class */
		$controller->base_url = Site::get_path( 'base', true );

		/* Start with the entire URL coming from web server... */
		$start_url = '';
		
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$start_url = $_SERVER['REQUEST_URI'];
		}
		else {
			$start_url = $_SERVER['SCRIPT_NAME'];
			
			if ( isset( $_SERVER['PATH_INFO'] ) ) {
				$start_url .= $_SERVER['PATH_INFO'];
			}
			
			// the query string is included in REQUEST_URI, we only need to append it if we're building the URI ourselves
			if ( isset( $_SERVER['QUERY_STRING'] ) && ( $_SERVER['QUERY_STRING'] != '' ) ) {
				$start_url .= '?' . $_SERVER['QUERY_STRING'];
			}
			
		}
		
		

		/* Strip out the base URL from the requested URL */
		/* but only if the base URL isn't / */
		if ( '/' != $controller->base_url ) {
			$start_url = str_replace( $controller->base_url, '', $start_url );
		}

		// undo &amp;s
		$start_url = str_replace( '&amp;', '&', $start_url );

		/* Trim off any leading or trailing slashes */
		$start_url = trim( $start_url, '/' );

		/* Allow plugins to rewrite the stub before it's passed through the rules */
		$start_url = Plugins::filter( 'rewrite_request', $start_url );

		$controller->stub = $start_url;

		/* Grab the URL filtering rules from DB */
		$matched_rule = URL::parse( $controller->stub );

		if ( $matched_rule === false ) {
			$matched_rule = URL::set_404();
		}

		/* OK, we have a matching rule.  Set the action and create a handler */
		$controller->action = $matched_rule->action;
		$controller->handler = new $matched_rule->handler();
		/* Insert the regexed submatches as the named parameters */
		$controller->handler->handler_vars['entire_match'] = $matched_rule->entire_match; // The entire matched string is returned at index 0
		$controller->handler->handler_vars['matched_rule'] = $matched_rule;
		foreach ( $matched_rule->named_arg_values as $named_arg_key=>$named_arg_value ) {
			$controller->handler->handler_vars[$named_arg_key] = $named_arg_value;
		}

		/* Also, we musn't forget to add the GET and POST vars into the action's settings array */
		$handler_vars = new SuperGlobal( $controller->handler->handler_vars );
		$handler_vars = $handler_vars->merge( $_GET, $_POST );
		$controller->handler->handler_vars = $handler_vars;
		return true;
	}

	/**
	 * Handle the requested action by firing off the matched handler action(s)
	 */
	public static function dispatch_request()
	{
		/* OK, set the wheels in motion... */
		Plugins::act( 'handler_' . Controller::instance()->action, Controller::get_handler_vars() );
		if ( method_exists( Controller::instance()->handler, 'act' ) ) {
			Controller::instance()->handler->act( Controller::instance()->action );
		}
	}

	/**
	 * Get an object that represents the request made
	 * @return stdClass An object with properties named after rewrite rules, which are true if those rules were used to handle the current request
	 */
	public static function get_request_obj()
	{
		$request = new StdClass();
		foreach ( URL::get_active_rules() as $rule ) {
			$request->{$rule->name} = false;
		}
		$matched_rule = URL::get_matched_rule();
		$request->{$matched_rule->name} = true;
		// Does the rule have any supplemental request types?
		if(isset($matched_rule->named_arg_values['request_types'])) {
			foreach($matched_rule->named_arg_values['request_types'] as $type) {
				$request->$type = true;
			}
		}
		$request = Plugins::filter('request_object', $request, $matched_rule);
		return $request;
	}
}

?>
