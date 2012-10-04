<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminHandler Class
 * Backbone of the admin area, handles requests and functionality.
 */
class AdminHandler extends ActionHandler
{
	/** An instance of the active public theme, which allows plugin hooks to execute */
	protected $active_theme = null;

	/**
	 * Verifies user credentials before creating the theme and displaying the request.
	 */
	public function __construct()
	{
		$user = User::identify();
		if ( !$user->loggedin ) {
			Session::add_to_set( 'login', $_SERVER['REQUEST_URI'], 'original' );
			if ( URL::get_matched_rule()->action == 'admin_ajax' && isset( $_SERVER['HTTP_REFERER'] ) ) {
				 $ar = new AjaxResponse(408, _t('Your session has ended, please log in and try again.') );
				 $ar->out();
			}
			else {
				$post_raw = $_POST->get_array_copy_raw();
				if ( !empty( $post_raw ) ) {
					Session::add_to_set( 'last_form_data', $post_raw, 'post' );
					Session::error( _t( 'We saved the last form you posted. Log back in to continue its submission.' ), 'expired_form_submission' );
				}
				$get_raw = $_GET->get_array_copy_raw();
				if ( !empty( $get_raw ) ) {
					Session::add_to_set( 'last_form_data', $get_raw, 'get' );
					Session::error( _t( 'We saved the last form you posted. Log back in to continue its submission.' ), 'expired_form_submission' );
				}
				Utils::redirect( URL::get( 'auth', array( 'page' => 'login' ) ) );
			}
			exit;
		}

		$last_form_data = Session::get_set( 'last_form_data' ); // This was saved in the "if ( !$user )" above, UserHandler transferred it properly.
		/* At this point, Controller has not created handler_vars, so we have to modify $_POST/$_GET. */
		if ( isset( $last_form_data['post'] ) ) {
			$_POST = $_POST->merge( $last_form_data['post'] );
			$_SERVER['REQUEST_METHOD'] = 'POST'; // This will trigger the proper act_admin switches.
			Session::remove_error( 'expired_form_submission' );
		}
		if ( isset( $last_form_data['get'] ) ) {
			$_GET = $_GET->merge( $last_form_data['get'] );
			Session::remove_error( 'expired_form_submission' );
			// No need to change REQUEST_METHOD since GET is the default.
		}
		$user->remember();

		// Create an instance of the active public theme so that its plugin functions are implemented
		$this->active_theme = Themes::create();

		// on every page load check the plugins currently loaded against the list we last checked for updates and trigger a cron if we need to
		Update::check_plugins();
	}

	/**
	 * Create the admin theme instance
	 *
	 * @param string $page The admin page requested
	 * @param string $type The content type included in the request
	 */
	public function setup_admin_theme( $page, $type = '' )
	{
		if ( !isset( $this->theme ) ) {
			$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) );
			$this->theme = Themes::create( '_admin', 'RawPHPEngine', $theme_dir );

			// Add some default template variables
			$this->set_admin_template_vars( $this->theme );
			$this->theme->admin_type = $type;
			$this->theme->admin_page = $page;
			$this->theme->admin_page_url = ( $page == 'dashboard' ) ? URL::get( 'admin', 'page=' ) : URL::get( 'admin', 'page=' . $page );
			$this->theme->page = $page;
			$this->theme->admin_title = MultiByte::ucwords( $page ) . ( $type != '' ? ' ' . MultiByte::ucwords( $type ) : '' );
			$this->theme->admin_title =
				isset( $this->theme->mainmenu[$this->theme->admin_page]['text'] )
					? $this->theme->mainmenu[$this->theme->admin_page]['text']
					: MultiByte::ucwords( $page ) . ( $type != '' ? ' ' . MultiByte::ucwords( $type ) : '' );
		}
	}

	/**
	 * Dispatches the request to the defined method. (ie: post_{page})
	 */
	public function act_admin()
	{
		$page = ( isset( $this->handler_vars['page'] ) && !empty( $this->handler_vars['page'] ) ) ? $this->handler_vars['page'] : 'dashboard';
		$page = filter_var( $page, FILTER_SANITIZE_STRING );
		if ( isset( $this->handler_vars['content_type'] ) ) {
			$type = Plugins::filter( 'post_type_display', Post::type_name( $this->handler_vars['content_type'] ), 'singular' );
		}
		elseif ( $page == 'publish' && isset( $this->handler_vars['id'] ) ) {
			$type = Post::type_name( Post::get( array( 'status' => Post::status( 'any' ), 'id' => intval( $this->handler_vars['id'] ) ) )->content_type );
			$type = Plugins::filter( 'post_type_display', $type, 'singular' );
		}
		else {
			$type = '';
		}

		$this->setup_admin_theme( $page, $type );

		// Access check to see if the user is allowed the requested page
		Utils::check_request_method( array( 'GET', 'HEAD', 'POST' ) );
		if ( !$this->access_allowed( $page, $type ) ) {
			Session::error( _t( 'Access to that page has been denied by the administrator.' ) );
			$this->get_blank();
		}

		switch ( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				// Let plugins try to handle the page
				Plugins::act( 'admin_theme_post_' . $page, $this, $this->theme );
				// Handle POSTs to the admin pages
				$fn = 'post_' . $page;
				if ( method_exists( $this, $fn ) ) {
					$this->$fn();
				}
				else {
					$classname = get_class( $this );
					_e( '%1$s->%2$s() does not exist.', array( $classname, $fn ) );
					exit;
				}
				break;
			case 'GET':
			case 'HEAD':
				// Let plugins try to handle the page
				Plugins::act( 'admin_theme_get_' . $page, $this, $this->theme );
				// Handle GETs of the admin pages
				$fn = 'get_' . $page;
				if ( method_exists( $this, $fn ) ) {
					$this->$fn();
					exit;
				}
				// If a get_ function doesn't exist, just load the template and display it
				if ( $this->theme->template_exists( $page ) ) {
					$this->display( $page );
				}
				else {
					// The requested console page doesn't exist
					header( 'HTTP/1.1 404 Not Found', true, 404 );
					$this->get_blank( _t( 'The page you were looking for was not found.' ) );
				}
				break;
		}
	}

	/**
	 * Handle incoming requests to /admin_ajax for admin ajax requests
	 */
	public function act_admin_ajax()
	{
		header( 'Content-Type: text/javascript;charset=utf-8' );
		$context = $this->handler_vars['context'];
		if ( method_exists( $this, 'ajax_' . $context ) ) {

			$type = ( isset( $this->handler_vars['content_type'] ) && !empty( $this->handler_vars['content_type'] ) ) ? $this->handler_vars['content_type'] : '';
			// Access check to see if the user is allowed the requested page
			if ( $this->access_allowed( 'ajax_' . $context, $type ) ) {
				call_user_func( array( $this, 'ajax_' . $context ), $this->handler_vars );
			}
		}
		else {
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}
	}

	/**
	* Handles get requests for the system information page.
	*/
	public function get_sysinfo()
	{
		$sysinfo = array();
		$siteinfo = array();

		// Assemble Site Info
		$siteinfo[ _t( 'Habari Version' ) ] = Version::get_habariversion();
		if ( Version::is_devel() ) {
			$siteinfo[ _t( 'Habari Version' ) ] .= " " . Version::get_git_short_hash();
		}

		$siteinfo[ _t( 'Habari API Version' ) ] = Version::get_apiversion();
		$siteinfo[ _t( 'Habari DB Version' ) ] = Version::get_dbversion();
		$siteinfo[ _t( 'Active Theme' ) ] = Options::get( 'theme_name' );
		$siteinfo[ _t( 'System Locale' ) ] = HabariLocale::get();
		$siteinfo[ _t( 'Cache Class' ) ] = Cache::get_class();
		$this->theme->siteinfo = $siteinfo;

		// Assemble System Info
		$sysinfo[ _t( 'PHP Version' ) ] = phpversion();
		$sysinfo[ _t( 'Server Software' ) ] = $_SERVER['SERVER_SOFTWARE'];
		$sysinfo[ _t( 'Database' ) ] = DB::get_driver_name() . ' - ' . DB::get_driver_version();
		$sysinfo[ _t( 'PHP Extensions' ) ] = implode( ', ', get_loaded_extensions() );
		$sysinfo[ _t( 'PHP Configuration Settings' ) ] = implode( "<br>", Utils::get_ini_settings() );
		if ( defined( 'PCRE_VERSION' ) ) {
			$sysinfo[ _t( 'PCRE Version' ) ] = PCRE_VERSION;
		}
		else {
			// probably PHP < 5.2.4
			ob_start();
			phpinfo( 8 );
			$phpinfo = ob_get_contents();
			ob_end_clean();
			preg_match( '/PCRE Library Version.*class="v">(.*)$/mi', $phpinfo, $matches );
			$sysinfo[ _t( 'PCRE Version' ) ] = $matches[ 1 ];
		}
		$sysinfo[ _t( 'Browser' ) ] = $_SERVER[ 'HTTP_USER_AGENT' ];
		$this->theme->sysinfo = $sysinfo;

		// Assemble Class Info
		$classinfo = Utils::glob( HABARI_PATH . "/user/classes/*.php" );
		if ( count( $classinfo ) ) {
			$classinfo = array_map( 'realpath', $classinfo );
		}
		$this->theme->classinfo = $classinfo;

		// Assemble Plugin Info
		$raw_plugins = Plugins::get_active();
		$plugins = array( 'system'=>array(), 'user'=>array(), '3rdparty'=>array(), 'other'=>array() );
		foreach ( $raw_plugins as $plugin ) {
			$file = $plugin->get_file();
			// Catch plugins that are symlinked from other locations as ReflectionClass->getFileName() only returns the ultimate file path, not the symlink path, and we really want the symlink path
			$all_plugins = Plugins::list_all();
			$filename = basename( $file );
			if ( array_key_exists( $filename, $all_plugins ) && $all_plugins[$filename] != $file ) {
				$file = $all_plugins[$filename];
			}
			if ( preg_match( '%[\\\\/](system|3rdparty|user)[\\\\/]plugins[\\\\/]%i', $file, $matches ) ) {
				// A plugin's info is XML, cast the element to a string. See #1026.
				$plugins[strtolower( $matches[1] )][(string)$plugin->info->name] = $file;
			}
			else {
				// A plugin's info is XML, cast the element to a string.
				$plugins['other'][(string)$plugin->info->name] = $file;
			}
		}
		$this->theme->plugins = $plugins;
		$this->theme->admin_page = _t( 'System Information' );

		$this->display( 'sysinfo' );
	}


	/**
	 * Display a blank admin page with appropriate navigation.
	 * This function terminates execution before returning.
	 * Useful for displaying errors when permission is denied for viewing.
	 *
	 * @param string $content Optional default content to display
	 */
	public function get_blank( $content = '' )
	{
		$this->theme->content = Plugins::filter( 'admin_blank_content', $content );

		$this->display( 'blank' );
		exit();
	}

	/**
	 * Assembles the main menu for the admin area.
	 * @param Theme $theme The theme to add the menu to
	 */
	protected function get_main_menu( $theme )
	{
		$page = ( isset( $this->handler_vars['page'] ) && !empty( $this->handler_vars['page'] ) ) ? $this->handler_vars['page'] : 'dashboard';

		// These need to be replaced with submenus, but access to them is provided temporarily
		$createmenu = array();
		$managemenu = array();
		$createperms = array();
		$manageperms = array();

		$i = 1;
		foreach ( Post::list_active_post_types() as $type => $typeint ) {
			if ( $typeint == 0 ) {
				continue;
			}

			if ( $i == 10 ) {
				$hotkey = 0;
			}
			elseif ( $i > 10 ) {
				$hotkey = false;
			}
			else {
				$hotkey = $i;
			}

			$plural = Plugins::filter( 'post_type_display', $type, 'plural' );
			$singular = Plugins::filter( 'post_type_display', $type, 'singular' );

			$createperm = array( 'post_' . $type => ACL::get_bitmask( 'create' ), 'post_any' => ACL::get_bitmask( 'create' ) );
			$createmenu['create_' . $typeint] = array( 'url' => URL::get( 'admin', 'page=publish&content_type=' . $type ), 'title' => _t( 'Create a new %s', array( $singular ) ), 'text' => $singular, 'access' => $createperm );
			$createperms = array_merge( $createperms, $createperm );

			$manageperm = array( 'post_' . $type => array( ACL::get_bitmask( 'edit' ), ACL::get_bitmask( 'delete' ) ), 'own_posts'=>array( ACL::get_bitmask( 'edit' ), ACL::get_bitmask( 'delete' ) ), 'post_any'=>array( ACL::get_bitmask( 'edit' ), ACL::get_bitmask( 'delete' ) ) );
			$managemenu['manage_' . $typeint] = array( 'url' => URL::get( 'admin', 'page=posts&type=' . $typeint ), 'title' => _t( 'Manage %s', array( $plural ) ), 'text' => $plural, 'access'=> $manageperm );
			$manageperms = array_merge( $manageperms, $manageperm );

			$createmenu['create_' . $typeint]['hotkey'] = $hotkey;
			$managemenu['manage_' . $typeint]['hotkey'] = $hotkey;

			if ( $page == 'publish' && isset( $this->handler_vars['content_type'] ) && $this->handler_vars['content_type'] == $type ) {
				$createmenu['create_' . $typeint]['selected'] = true;
			}
			if ( $page == 'posts' && isset( $this->handler_vars['type'] ) && $this->handler_vars['type'] == $typeint ) {
				$managemenu['manage_' . $typeint]['selected'] = true;
			}
			$i++;
		}

		$createperms = array_merge( $createperms, array( 'own_posts'=>array( ACL::get_bitmask( 'create' ) ) ) );
		$manageperms = array_merge( $manageperms, array( 'own_posts'=>array( ACL::get_bitmask( 'edit' ), ACL::get_bitmask( 'delete' ) ) ) );

		$adminmenu = array(
			'create' => array( 'url' => '', 'title' => _t( 'Create content' ), 'text' => _t( 'New' ), 'hotkey' => 'N', 'submenu' => $createmenu ),
			'manage' => array( 'url' => '', 'title' => _t( 'Manage content' ), 'text' => _t( 'Manage' ), 'hotkey' => 'M', 'submenu' => $managemenu ),
			'comments' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t( 'Manage comments' ), 'text' => _t( 'Comments' ), 'hotkey' => 'C', 'access' => array( 'manage_all_comments' => true, 'manage_own_post_comments' => true ) ),
			'tags' => array( 'url' => URL::get( 'admin', 'page=tags' ), 'title' => _t( 'Manage tags' ), 'text' => _t( 'Tags' ), 'hotkey' => 'A', 'access'=>array( 'manage_tags'=>true ), 'class' => 'over-spacer' ),
			'dashboard' => array( 'url' => URL::get( 'admin', 'page=' ), 'title' => _t( 'View your user dashboard' ), 'text' => _t( 'Dashboard' ), 'hotkey' => 'D', 'class' => 'under-spacer' ),
			'options' => array( 'url' => URL::get( 'admin', 'page=options' ), 'title' => _t( 'View and configure site options' ), 'text' => _t( 'Options' ), 'hotkey' => 'O', 'access'=>array( 'manage_options'=>true ) ),
			'themes' => array( 'url' => URL::get( 'admin', 'page=themes' ), 'title' => _t( 'Preview and activate themes' ), 'text' => _t( 'Themes' ), 'hotkey' => 'T', 'access'=>array( 'manage_theme'=>true ) ),
			'plugins' => array( 'url' => URL::get( 'admin', 'page=plugins' ), 'title' => _t( 'Activate, deactivate, and configure plugins' ), 'text' => _t( 'Plugins' ), 'hotkey' => 'P', 'access'=>array( 'manage_plugins'=>true, 'manage_plugins_config' => true ) ),
			'import' => array( 'url' => URL::get( 'admin', 'page=import' ), 'title' => _t( 'Import content from another site' ), 'text' => _t( 'Import' ), 'hotkey' => 'I', 'access'=>array( 'manage_import'=>true ) ),
			'users' => array( 'url' => URL::get( 'admin', 'page=users' ), 'title' => _t( 'View and manage users' ), 'text' => _t( 'Users' ), 'hotkey' => 'U', 'access'=>array( 'manage_users'=>true ) ),
			'profile' => array( 'url' => URL::get( 'admin', 'page=user' ), 'title' => _t( 'Manage your user profile' ), 'text' => _t( 'My Profile' ), 'hotkey' => 'Y', 'access'=>array( 'manage_self'=>true, 'manage_users'=>true ) ),
			'groups' => array( 'url' => URL::get( 'admin', 'page=groups' ), 'title' => _t( 'View and manage groups' ), 'text' => _t( 'Groups' ), 'hotkey' => 'G', 'access'=>array( 'manage_groups'=>true ) ),
			'logs' => array( 'url' => URL::get( 'admin', 'page=logs' ), 'title' => _t( 'View system log messages' ), 'text' => _t( 'Logs' ), 'hotkey' => 'L', 'access'=>array( 'manage_logs'=>true ), 'class' => 'over-spacer' ) ,
			'logout' => array( 'url' => URL::get( 'auth', 'page=logout' ), 'title' => _t( 'Log out of the administration interface' ), 'text' => _t( 'Logout' ), 'hotkey' => 'X', 'class' => 'under-spacer' ),
		);

		$mainmenus = array_merge( $adminmenu );

		foreach ( $mainmenus as $menu_id => $menu ) {
			// Change this to set the correct menu as the active menu
			if ( !isset( $mainmenus[$menu_id]['selected'] ) ) {
				$mainmenus[$menu_id]['selected'] = false;
			}
		}

		$mainmenus = Plugins::filter( 'adminhandler_post_loadplugins_main_menu', $mainmenus );

		foreach ( $mainmenus as $key => $attrs ) {
			if ( $page == $key && !isset($mainmenus[$key]['submenu'])) {
				$mainmenus[$key]['selected'] = true;
			}
		}

		$mainmenus = $this->filter_menus_by_permission( $mainmenus );

		// Strip out import if no importers are available
		if ( !Plugins::filter( 'import_names', array() ) )
			unset( $mainmenus['import'] );

		// Make submenu links default to the first available item
		foreach ( array_keys( $mainmenus ) as $action ) {
			if ( !$mainmenus[$action]['url'] && !empty( $mainmenus[$action]['submenu'] ) ) {
				$default = current( $mainmenus[$action]['submenu'] );
				$mainmenus[$action]['url'] = $default['url'];
			}
		}

		$theme->assign( 'mainmenu', $mainmenus );
	}

	/**
	 * Remove menus for which the user does not have qualifying permissions.
	 *
	 * @param array $menuarray The master array of admin menu items
	 * @return array The modified array of admin menu items
	 */
	protected function filter_menus_by_permission( $menuarray )
	{
		$user = User::identify();
		foreach ( $menuarray as $key => $attrs ) {
			if ( isset( $attrs['access'] ) ) {
				$attrs['access'] = Utils::single_array( $attrs['access'] );
				$pass = false;
				foreach ( $attrs['access'] as $token => $masks ) {
					$masks = Utils::single_array( $masks );
					foreach ( $masks as $mask ) {
						if ( is_bool( $mask ) ) {
							if ( $user->can( $token ) ) {
							$pass = true;
							break;
							}
						}
						else {
							if ( $user->cannot( $token ) ) {
								break 2;
							}
							else {
								if ( $user->can( $token, $mask ) ) {
									$pass = true;
									break 2;

								}
							}
						}
					}
				}
				if ( !$pass ) {
					unset( $menuarray[$key] );
				}
			}
			if ( isset( $attrs['submenu'] ) && count( $attrs['submenu'] ) > 0 ) {
				$menuarray[$key]['submenu'] = $this->filter_menus_by_permission( $attrs['submenu'] );
				if ( count( $menuarray[$key]['submenu'] ) == 0 ) {
					unset( $menuarray[$key]['submenu'] );
					unset( $menuarray[$key] );
				}
			}
			if ( isset( $menuarray[$key] ) && count( $menuarray[$key] ) == 0 ) {
				unset( $menuarray[$key] );
			}
		}
		return $menuarray;
	}

	/**
	 * Checks if the currently logged in user has access to a page and post type.
	 */
	private function access_allowed( $page, $type )
	{
		$user = User::identify();
		$require_any = array();
		$result = false;

		switch ( $page ) {
			case 'comment':
			case 'comments':
			case 'ajax_comments':
			case 'ajax_in_edit':
			case 'ajax_update_comment':
				$require_any = array( 'manage_all_comments' => true, 'manage_own_post_comments' => true );
				break;

			case 'tags':
			case 'ajax_tags':
			case 'ajax_get_tags':
				$require_any = array( 'manage_tags' => true );
				break;
			case 'options':
				$require_any = array( 'manage_options' => true );
				break;
			case 'themes':
				$require_any = array( 'manage_themes' => true, 'manage_theme_config' => true );
				break;
			case 'activate_theme':
				$require_any = array( 'manage_themes' => true );
				break;
			case 'preview_theme':
				$require_any = array( 'manage_themes' => true );
				break;
			case 'plugins':
				$require_any = array( 'manage_plugins' => true, 'manage_plugins_config' => true );
				break;
			case 'plugin_toggle':
				$require_any = array( 'manage_plugins' => true );
				break;
			case 'import':
				$require_any = array( 'manage_import' => true );
				break;
			case 'users':
			case 'ajax_update_users':
			case 'ajax_users':
				$require_any = array( 'manage_users' => true );
				break;
			case 'user':
				$require_any = array( 'manage_users' => true, 'manage_self' => true );
				break;
			case 'groups':
			case 'group':
			case 'ajax_update_groups':
			case 'ajax_groups':
				$require_any = array( 'manage_groups' => true );
				break;
			case 'logs':
			case 'ajax_delete_logs':
			case 'ajax_logs':
				$require_any = array( 'manage_logs' => true );
				break;
			case 'publish':
			case 'ajax_media':
			case 'ajax_media_panel':
			case 'ajax_media_upload':
				$type = Post::type_name( $type );
				$require_any = array(
					'post_any' => array( ACL::get_bitmask( 'create' ), ACL::get_bitmask( 'edit' ) ),
					'post_' . $type => array( ACL::get_bitmask( 'create' ), ACL::get_bitmask( 'edit' ) ),
					'own_posts' => array( ACL::get_bitmask( 'create' ), ACL::get_bitmask( 'edit' ) ),
				);
				break;
			case 'delete_post':
				$type = Post::type_name( $type );
				$require_any = array(
					'post_any' => ACL::get_bitmask( 'delete' ),
					'post_' . $type => ACL::get_bitmask( 'delete' ),
					'own_posts' => ACL::get_bitmask( 'delete' ),
				);
				break;
			case 'posts':
			case 'ajax_posts':
			case 'ajax_update_posts':
				$require_any = array(
					'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
					'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				);
				foreach ( Post::list_active_post_types() as $type => $type_id ) {
					$require_any['post_' . $type] = array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) );
				}
				break;
			case 'sysinfo':
				$require_any = array( 'super_user' => true );
				break;
			case 'dashboard':
			case 'ajax_dashboard':
				$result = true;
				break;
			case 'ajax_add_block':
				$result = true;
				break;
			case 'ajax_delete_block':
				$result = true;
				break;
			case 'configure_block':
				$result = true;
				break;
			case 'ajax_save_areas':
				$result = true;
				break;
			case 'locale':
				$result = true;
				break;
			default:
				break;
		}

		$require_any = Plugins::filter( 'admin_access_tokens', $require_any, $page, $type );


		foreach ( $require_any as $token => $access ) {
			$access = Utils::single_array( $access );
			foreach ( $access as $mask ) {
				if ( is_bool( $mask ) && $user->can( $token ) ) {
					$result = true;
					break;
				}
				elseif ( $user->can( $token, $mask ) ) {
					$result = true;
					break 2;
				}
			}
		}

		$result = Plugins::filter( 'admin_access', $result, $page, $type );

		return $result;
	}

	/**
	 * Assigns the main menu to $mainmenu into the theme.
	 */
	protected function set_admin_template_vars( $theme )
	{
		$this->get_main_menu( $theme );
	}

	/**
	 * Helper function to assign all handler_vars into the theme and displays a theme template.
	 * @param template_name Name of template to display (note: not the filename)
	 */
	protected function display( $template_name )
	{
		$this->theme->display( $template_name );
	}


	public function create_theme()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
	}

}
?>
