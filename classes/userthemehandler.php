<?php
/**
 * Class for handling default user theme actions.
 *
 * @note Any theme will be able to override these default actions by registering an override function with Theme->act_some_function()
 */
class UserThemeHandler extends ActionHandler
{
	private $theme= null;

	/**
	 * Constructor for the default theme handler.  Here, we
	 * automatically load the active theme for the installation,
	 * and create a new Theme instance.
	 */
	public function __construct()
	{
		$this->theme= Themes::create();
	}

	/**
	 * The UserThemeHandler's act() method differs from ActionHandler's
	 * act() method in one distinct way: if the Handler's theme variable
	 * registers an override action via Theme->register_action(), then
	 * that function is called instead of the default handler action.
	 *
	 * @param   action  the action that was in the URL rule
	 * @return  bool    did the action succeed?
	 */
	public function act( $action )
	{
		$this->action= $action;
		$this->theme->assign('matched_rule', URL::get_matched_rule());
		$request = new StdClass();
		foreach(RewriteRules::get_active() as $rule) {
			$request->{$rule->name} = ($rule->name == URL::get_matched_rule()->name);
		}
		$this->theme->assign('request', $request);

		$action_method= 'act_' . $action;
		$before_action_method= 'before_' . $action_method;
		$after_action_method= 'after_' . $action_method;

		$this->theme->$before_action_method();
		try {
			$handled = false;
			$handled = Plugins::filter('theme_act_' . $action, $handled, $this->theme);
			if(!$handled) {
				$this->theme->$action_method();
			}
		}
		catch(exception $e) {
			EventLog::log($e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine() , 'error', 'theme', 'habari', print_r($e, 1) );
			if(DEBUG) {
				Utils::debug($e);
			}
		}
		$this->theme->$after_action_method();
	}

	/**
	 * Helper function which automatically assigns all handler_vars
	 * into the theme and displays a theme template
	 *
	 * @param template_name Name of template to display ( note: not the filename )
	 */
	protected function display( $template_name )
	{
		/*
		 * Assign internal variables into the theme ( and therefore into the theme's template
		 * engine.  See Theme::assign().
		 */
		foreach ( $this->handler_vars as $key => $value ) {
			$this->theme->assign( $key, $value );
		}
		try {
			$this->theme->display( $template_name );
		}
		catch(exception $e) {
			EventLog::log($e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine() , 'error', 'theme', 'habari', print_r($e, 1) );
		}
	}
}

?>
