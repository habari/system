<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminPluginsHandler Class
 * Handles plugin-related actions in the admin
 *
 */
class AdminPluginsHandler extends AdminHandler
{
	/**
	 * Display the plugin administration page
	 */
	public function get_plugins()
	{
		$all_plugins = Plugins::list_all();
		$active_plugins = Plugins::get_active();

		$sort_active_plugins = array();
		$sort_inactive_plugins = array();
		$providing = array();
		$available = array();

		foreach ( $all_plugins as $file ) {
			$plugin = array();
			$plugin_id = Plugins::id_from_file( $file );
			$plugin['plugin_id'] = $plugin_id;
			$plugin['file'] = $file;

			$error = '';

			if ( Utils::php_check_file_syntax( $file, $error ) ) {
				$plugin['debug'] = false;
				$plugin['info'] = Plugins::load_info( $file );
				if ( array_key_exists( $plugin_id, $active_plugins ) ) {
					$plugin['verb'] = _t( 'Deactivate' );
					$pluginobj = $active_plugins[$plugin_id];
					$plugin['active'] = true;
					$plugin_actions = array();
					$plugin_actions1 = Plugins::filter_id( 'plugin_config', $plugin_id, $plugin_actions, $plugin_id );
					$plugin_actions = Plugins::filter( 'plugin_config_any', $plugin_actions1, $plugin_id );
					$plugin['actions'] = array();
					foreach ( $plugin_actions as $plugin_action => $plugin_action_caption ) {
						if ( is_numeric( $plugin_action ) ) {
							$plugin_action = $plugin_action_caption;
						}
						$action = array(
							'caption' => $plugin_action_caption,
							'action' => $plugin_action,
						);
						$urlparams = array( 'page' => 'plugins', 'configure'=>$plugin_id);
						$action['url'] = URL::get( 'admin', $urlparams );

						// @locale Displayed as an icon indicating there is help text available for a plugin.
						if ( $action['caption'] == _t( '?' ) ) {
							if ( isset( $_GET['configaction'] ) ) {
								$urlparams['configaction'] = $_GET['configaction'];
							}
							if ( $_GET['help'] != $plugin_action ) {
								$urlparams['help'] = $plugin_action;
							}
							$action['url'] = URL::get( 'admin', $urlparams );
							$plugin['help'] = $action;
						}
						else {
							if ( isset( $_GET['help'] ) ) {
								$urlparams['help'] = $_GET['help'];
							}
							$urlparams['configaction'] = $plugin_action;
							$action['url'] = URL::get( 'admin', $urlparams );
							$plugin['actions'][$plugin_action] = $action;
						}
					}
					$plugin['actions']['deactivate'] = array(
						'url' =>  URL::get( 'admin', 'page=plugin_toggle&plugin_id=' . $plugin['plugin_id'] . '&action=deactivate' ),
						'caption' => _t( 'Deactivate' ),
						'action' => 'Deactivate',
					);

					if ( isset( $plugin['info']->provides ) ) {
						foreach ( $plugin['info']->provides->feature as $feature ) {
							$providing[(string) $feature] = (string)$feature;
						}
					}
				}
				else {
					// instantiate this plugin
					// in order to get its info()
					$plugin['active'] = false;
					$plugin['verb'] = _t( 'Activate' );
					$plugin['actions'] = array(
						'activate' => array(
							'url' =>  URL::get( 'admin', 'page=plugin_toggle&plugin_id=' . $plugin['plugin_id'] . '&action=activate' ),
							'caption' => _t( 'Activate' ),
							'action' => 'activate',
						),
					);
					if ( isset( $plugin['info']->help ) ) {
						if ( isset( $_GET['configaction'] ) ) {
							$urlparams['configaction'] = $_GET['configaction'];
						}
						if ( $_GET['help'] != '_help' ) {
							$urlparams['help'] = '_help';
						}
						// @locale Displayed as an icon indicating there is help text available for a plugin.
						$action['caption'] = _t( '?' );
						$action['action'] = '_help';
						$urlparams = array( 'page' => 'plugins', 'configure' => $plugin_id );
						$action['url'] = URL::get( 'admin', $urlparams );
						$plugin['help'] = $action;
					}
					if ( isset( $plugin['info']->provides ) ) {
						foreach ( $plugin['info']->provides->feature as $feature ) {
							$available[(string) $feature][$plugin_id] = $plugin['info']->name;
						}
					}
				}
			}
			else {
				$plugin['debug'] = true;
				$plugin['error'] = $error;
				$plugin['active'] = false;
			}
			if ( isset( $this->handler_vars['configure'] ) && ( $this->handler_vars['configure'] == $plugin['plugin_id'] ) ) {
				if ( isset( $plugin['help'] ) && Controller::get_var( 'configaction' ) == $plugin['help']['action'] ) {
					$this->theme->config_plugin_caption = _t( 'Help' );
				}
				else {
					if ( isset( $plugin['actions'][Controller::get_var( 'configaction' )] ) ) {
						$this->theme->config_plugin_caption = $plugin['actions'][Controller::get_var( 'configaction' )]['caption'];
					}
					else {
						$this->theme->config_plugin_caption = Controller::get_var( 'configaction' );
					}
				}
				unset( $plugin['actions'][Controller::get_var( 'configaction' )] );
				$this->theme->config_plugin = $plugin;
			}
			else if ( $plugin['active'] ) {
				$sort_active_plugins[$plugin_id] = $plugin;
			}
			else {
				$sort_inactive_plugins[$plugin_id] = $plugin;
			}
		}

		// Get the features that the current theme provides
		$themeinfo = Themes::get_active_data();
		if ( isset( $themeinfo['info']->provides ) ) {
			foreach ( $themeinfo['info']->provides->feature as $feature ) {
				$providing[(string) $feature] = $feature;
			}
		}
		$providing = Plugins::filter( 'provided', $providing );

		foreach ( $sort_inactive_plugins as $plugin_id => $plugin ) {
			if ( isset( $plugin['info']->requires ) ) {
				foreach ( $plugin['info']->requires->feature as $feature ) {
					if ( !isset( $providing[(string) $feature] ) ) {
						if( isset( $available[(string) $feature] ) ) {
							$sort_inactive_plugins[$plugin_id]['available'][(string) $feature] = $available[(string) $feature];
							if(count($sort_inactive_plugins[$plugin_id]['available'][(string) $feature]) > 1) {
								unset( $sort_inactive_plugins[$plugin_id]['actions']['activate'] );
							}
						}
						else {
							if ( !isset( $sort_inactive_plugins[$plugin_id]['missing'] ) ) {
								$sort_inactive_plugins[$plugin_id]['missing'] = array();
							}
							$sort_inactive_plugins[$plugin_id]['missing'][(string) $feature] = isset( $feature['url'] ) ? $feature['url'] : '';
							unset( $sort_inactive_plugins[$plugin_id]['actions']['activate'] );
						}
					}
				}
			}
		}

		//$this->theme->plugins = array_merge($sort_active_plugins, $sort_inactive_plugins);
		$this->theme->assign( 'configaction', Controller::get_var( 'configaction' ) );
		$this->theme->assign( 'helpaction', Controller::get_var( 'help' ) );
		$this->theme->assign( 'configure', Controller::get_var( 'configure' ) );
		uasort($sort_active_plugins, array( $this, 'compare_names' ) );
		uasort($sort_inactive_plugins, array( $this, 'compare_names' ) );
		$this->theme->active_plugins = $sort_active_plugins;
		$this->theme->inactive_plugins = $sort_inactive_plugins;

		$this->theme->plugin_loader = Plugins::filter( 'plugin_loader', '', $this->theme );

		$this->display( 'plugins' );
	}

	/**
	 * A POST handler for the admin plugins page that simply passes those options through.
	 */
	public function post_plugins()
	{
		return $this->get_plugins();
	}

	/**
	 * Handles plugin activation or deactivation.
	 */
	public function get_plugin_toggle()
	{
		$extract = $this->handler_vars->filter_keys( 'plugin_id', 'action' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}

		$plugins = Plugins::list_all();
		foreach ( $plugins as $file ) {
			if ( Plugins::id_from_file( $file ) == $plugin_id ) {
				switch ( strtolower( $action ) ) {
					case 'activate':
						if ( Plugins::activate_plugin( $file ) ) {
							$plugins = Plugins::get_active();
							Session::notice(
								_t( "Activated plugin '%s'", array( $plugins[Plugins::id_from_file( $file )]->info->name ) ),
								$plugins[Plugins::id_from_file( $file )]->plugin_id
							);
						}
						break;
					case 'deactivate':
						if ( Plugins::deactivate_plugin( $file ) ) {
							$plugins = Plugins::get_active();
							Session::notice(
								_t( "Deactivated plugin '%s'", array( $plugins[Plugins::id_from_file( $file )]->info->name ) ),
								$plugins[Plugins::id_from_file( $file )]->plugin_id
							);
						}
						break;
					default:
						Plugins::act(
							'adminhandler_get_plugin_toggle_action',
							$action,
							$file,
							$plugin_id,
							$plugins
						);
						break;
				}
			}
		}
		Utils::redirect( URL::get( 'admin', 'page=plugins' ) );
	}

	/*
	 * Compare function for uasort()
	 * @param array $a The first element to compare
	 * @param array $b The second element to compare
	 * @return integer. 0 if the strings are equal, <0 if the first parameter is less than the second,
	 * and >0 if the first parameter is greater than the second
	 */
	protected function compare_names( $a, $b )
	{
		$aname = isset($a['info']) ? $a['info']->name : '';
		$bname = isset($b['info']) ? $b['info']->name : '';
		return strcmp( MultiByte::strtolower( $aname), MultiByte::strtolower( $bname ) );
	}

}
