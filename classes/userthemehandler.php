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
 * Class for handling default user theme actions.
 *
 * @note Any theme will be able to override these default actions by registering an override function with Theme->act_some_function()
 */
class UserThemeHandler extends ActionHandler
{
	private $theme = null;

	/**
	 * Constructor for the default theme handler.  Here, we
	 * automatically load the active theme for the installation,
	 * and create a new Theme instance.
	 */
	public function __construct()
	{
		$this->theme = Themes::create();
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
		$this->action = $action;
		$this->theme->assign('matched_rule', URL::get_matched_rule());
		$request = new StdClass();
		foreach(RewriteRules::get_active() as $rule) {
			$request->{$rule->name}= false;
		}
		$request->{$this->theme->matched_rule->name}= true;
		$this->theme->assign('request', $request);

		$action_method = 'act_' . $action;
		$before_action_method = 'before_' . $action_method;
		$after_action_method = 'after_' . $action_method;

		$this->theme->$before_action_method();
		try {
			$handled = false;
			$handled = Plugins::filter('theme_act_' . $action, $handled, $this->theme);
			if(!$handled) {
				$this->theme->$action_method();
			}
		}
		catch(Error $e) {
			EventLog::log($e->humane_error(), 'error', 'theme', 'habari', print_r($e, 1) );
			Session::error($e->humane_error()); //Should we display any error here?
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
		catch(Error $e) {
			EventLog::log($e->humane_error(), 'error', 'theme', 'habari', print_r($e, 1) );
		}
	}
}

?>
