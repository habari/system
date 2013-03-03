<?php
/**
 * @package Habari
 *
 */

/**
 * Habari PluginHandler Class
 *
 */
class PluginHandler extends ActionHandler
{

	/**
	 * Constructor for the pluggable theme handler.
	 */
	public function __construct()
	{
		$this->setup_theme();
	}


	/**
	 * All handlers must implement act() to conform to handler API.
	 * This is the default implementation of act(), which attempts
	 * to call a class member method of $this->act_$action().  Any
	 * subclass is welcome to override this default implementation.
	 *
	 * @param string $action the action that was in the URL rule
	 */
	public function act( $action )
	{
		if ( null === $this->handler_vars ) {
			$this->handler_vars = new SuperGlobal( array() );
		}
		$this->action = $action;

		$this->theme->assign( 'matched_rule', URL::get_matched_rule() );
		$request = new StdClass();
		foreach ( URL::get_active_rules() as $rule ) {
			$request->{$rule->name} = false;
		}
		$request->{$this->theme->matched_rule->name} = true;
		$this->theme->assign( 'request', $request );


		$action_hook = 'plugin_act_' . $action;
		$before_action_hook = 'before_' . $action_hook;
		$theme_hook = 'route_' . $action;
		$after_action_hook = 'after_' . $action_hook;

		Plugins::act( $before_action_hook, $this );
		Plugins::act( $action_hook, $this );
		if(Plugins::implemented($theme_hook, 'theme')) {
			$theme = Themes::create();
			$rule = URL::get_matched_rule();
			Plugins::theme( $theme_hook, $theme, $rule->named_arg_values, $this );
		}
		Plugins::act( $after_action_hook );
	}

}

?>
