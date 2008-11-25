<?php

class PluginsAdminPage extends AdminPage
{
	/**
	 * A POST handler for the admin plugins page that simply passes those options through.
	 */
	public function act_request_post()
	{
		return $this->act_request_get();
	}

	public function act_request_get()
	{
		$all_plugins = Plugins::list_all();
		$active_plugins = Plugins::get_active();

		$sort_active_plugins = array();
		$sort_inactive_plugins = array();

		foreach ( $all_plugins as $file ) {
			$plugin = array();
			$plugin_id = Plugins::id_from_file( $file );
			$plugin['plugin_id']= $plugin_id;
			$plugin['file']= $file;

			$error = '';
			if ( Utils::php_check_file_syntax( $file, $error ) ) {
				$plugin['debug']= false;
				if ( array_key_exists( $plugin_id, $active_plugins ) ) {
					$plugin['verb']= _t( 'Deactivate' );
					$pluginobj = $active_plugins[$plugin_id];
					$plugin['active']= true;
					$plugin_actions = array();
					$plugin['actions']= Plugins::filter( 'plugin_config', $plugin_actions, $plugin_id );
				}
				else {
					// instantiate this plugin
					// in order to get its info()
					include_once( $file );
					Plugins::get_plugin_classes( true );
					$pluginobj = Plugins::load( $file, false );
					$plugin['active']= false;
					$plugin['verb']= _t( 'Activate' );
					$plugin['actions']= array();
				}
				$plugin['info']= $pluginobj->info;
			}
			else {
				$plugin['debug']= true;
				$plugin['error']= $error;
				$plugin['active']= false;
			}
			if ($plugin['active']) {
				$sort_active_plugins[$plugin_id]= $plugin;
			}
			else {
				$sort_inactive_plugins[$plugin_id]= $plugin;
			}
		}

		//$this->theme->plugins= array_merge($sort_active_plugins, $sort_inactive_plugins);
		$this->theme->assign( 'configaction', Controller::get_var('configaction') );
		$this->theme->assign( 'configure', Controller::get_var('configure') );
		$this->theme->active_plugins = $sort_active_plugins;
		$this->theme->inactive_plugins = $sort_inactive_plugins;

		$this->display( 'plugins' );
	}
	
	/**
	 * Handles plugin activation or deactivation.
	 */
	public function get_plugin_toggle()
	{
		$extract = $this->handler_vars->filter_keys('plugin_id', 'action');
		foreach($extract as $key => $value) {
			$$key = $value;
		}

		$plugins = Plugins::list_all();
		foreach($plugins as $file) {
			if(Plugins::id_from_file($file) == $plugin_id) {
				switch ( strtolower($action) ) {
					case 'activate':
						if ( Plugins::activate_plugin($file) ) {
							$plugins = Plugins::get_active();
							Session::notice(
								_t( "Activated plugin '%s'", array($plugins[Plugins::id_from_file( $file )]->info->name) ),
								$plugins[Plugins::id_from_file($file)]->plugin_id
							);
						}
					break;
					case 'deactivate':
						if ( Plugins::deactivate_plugin($file) ) {
							$plugins = Plugins::get_active();
							Session::notice(
								_t( "Deactivated plugin '%s'", array($plugins[Plugins::id_from_file( $file )]->info->name) ),
								$plugins[Plugins::id_from_file($file)]->plugin_id
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
}

?>