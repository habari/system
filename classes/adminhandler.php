<?php
/**
 * Habari AdminHandler Class
 * Backbone of the admin area, handles requests and functionality.
 *
 * @package Habari
 * @todo Clean this mess up
 */

class AdminHandler extends ActionHandler
{
	/** Cached theme object for handling templates and presentation */
	private $theme = NULL;
	/** An instance of the active public theme, which allows plugin hooks to execute */
	protected $active_theme = NULL;

	/**
	 * Verifies user credentials before creating the theme and displaying the request.
	 */
	public function __construct()
	{
		$user = User::identify();
		if ( !$user->loggedin ) {
			Session::add_to_set( 'login', $_SERVER['REQUEST_URI'], 'original' );
			if( URL::get_matched_rule()->name == 'admin_ajax' ) {
				echo '{callback: function(){location.href="'.$_SERVER['HTTP_REFERER'].'"} }';
			}
			else {
				if ( !empty( $_POST ) ) {
					Session::add_to_set( 'last_form_data', $_POST, 'post' );
					Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
				}
				if ( !empty( $_GET ) ) {
					Session::add_to_set( 'last_form_data', $_GET, 'get' );
					Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
				}
				Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
			}
			exit;
		}
		/* TODO: update ACL class so that this works
		if ( !$user->can( 'admin' ) ) {
			die( _t( 'Permission denied.' ) );
		}
		//*/
		$last_form_data = Session::get_set( 'last_form_data' ); // This was saved in the "if ( !$user )" above, UserHandler transferred it properly.
		/* At this point, Controller has not created handler_vars, so we have to modify $_POST/$_GET. */
		if ( isset( $last_form_data['post'] ) ) {
			$_POST = $_POST->merge( $last_form_data['post'] );
			$_SERVER['REQUEST_METHOD']= 'POST'; // This will trigger the proper act_admin switches.
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

		// setup the stacks for javascript in the admin - it's a method so a plugin can call it externally
		self::setup_stacks();
	}

	/**
	 * Dispatches the request to the defined method. (ie: post_{page})
	 */
	public function act_admin()
	{
		$page = ( !empty($this->handler_vars['page']) ) ? $this->handler_vars['page'] : 'dashboard';
		$this->theme = $this->get_admin_theme($page);
		
		$request_method = strtolower($_SERVER['REQUEST_METHOD']);
		$admin_page = $page . 'AdminPage';
		Plugins::act("admin_theme_{$request_method}_{$page}", $this, $this->theme);
		
		if ( class_exists($admin_page) ) {
			$admin_page = new $admin_page( $request_method, $this, $this->theme );
			$action = isset($this->handler_vars['action']) ? $this->handler_vars['action'] : 'request';
			$admin_page->act( $action, $request_method );
		}
		else {
			header( 'HTTP/1.1 404 Not Found', true, 404 );
			_e('Page Not Found');
		}
	}

	/**
	 * Handle incoming requests to /admin_ajax for admin ajax requests
	 */
	public function act_admin_ajax()
	{
		$context = $this->handler_vars['context'];
		$request_method = strtolower($_SERVER['REQUEST_METHOD']);
		$admin_page = $context . 'AdminPage';
		
		if ( class_exists($admin_page) ) {
			$admin_page = new $admin_page( $request_method, $this );
			$action = isset($this->handler_vars['action']) ? $this->handler_vars['action'] : 'request';
			$admin_page->act_ajax( $action, $request_method );
		}
		else {
			header( 'HTTP/1.1 404 Not Found', true, 404 );
			_e('Page Not Found');
		}
	}
	
	protected function get_admin_theme( $page )
	{
		$type = ( isset( $this->handler_vars['content_type'] ) && !empty( $this->handler_vars['content_type'] ) ) ? $this->handler_vars['content_type'] : '';
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		// Add some default stylesheets
		Stack::add('admin_stylesheet', array(Site::get_url('admin_theme') . '/css/admin.css', 'screen'), 'admin');

	  	// Add some default template variables
		$this->get_main_menu( $theme );
		$theme->admin_type = $type;
		$theme->admin_page = $page;
		$theme->page = $page;
		$theme->admin_title = ucwords($page) . ( $type != '' ? ' ' . ucwords($type) : '' );
		
		return $theme;
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

	  Plugins::register(array($this, 'default_post_type_display'), 'filter', 'post_type_display', 4);

		$i= 1;
		foreach( Post::list_active_post_types() as $type => $typeint ) {
			if ( $typeint == 0 ) {
				continue;
			}

			if($i == 10) {
				$hotkey= 0;
			} elseif($i > 10) {
				$hotkey= FALSE;
			} else {
				$hotkey= $i;
			}

			$plural = Plugins::filter('post_type_display', $type, 'plural');
			$singular = Plugins::filter('post_type_display', $type, 'singular');

			$createmenu['create_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=publish&content_type=' . $type ), 'title' => sprintf( _t( 'Create a new %s' ), ucwords( $type ) ), 'text' => $singular );
			$managemenu['manage_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=posts&type=' . $typeint ), 'title' => sprintf( _t( 'Manage %s' ), ucwords( $type ) ), 'text' => $plural );
			$createmenu['create_' . $typeint]['hotkey']= $hotkey;
			$managemenu['manage_' . $typeint]['hotkey']= $hotkey;

			if( $page == 'publish' && isset($this->handler_vars['content_type']) && $this->handler_vars['content_type'] == $type ) {
				$createmenu['create_' . $typeint]['selected'] = TRUE;
			}
			if( $page == 'posts' && isset($this->handler_vars['type']) && $this->handler_vars['type'] == $typeint ) {
				$managemenu['manage_' . $typeint]['selected'] = TRUE;
			}
			$i++;
		}

		$adminmenu = array(
			'create' => array( 'url' => URL::get( 'admin', 'page=publish' ), 'title' => _t('Create content'), 'text' => _t('New'), 'hotkey' => 'N', 'submenu' => $createmenu ),
			'manage' => array( 'url' => URL::get( 'admin', 'page=posts' ), 'title' => _t('Manage content'), 'text' => _t('Manage'), 'hotkey' => 'M', 'submenu' => $managemenu ),
			'comments' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t( 'Manage blog comments' ), 'text' => _t( 'Comments' ), 'hotkey' => 'C' ),
			'tags' => array( 'url' => URL::get( 'admin', 'page=tags' ), 'title' => _t( 'Manage blog tags' ), 'text' => _t( 'Tags' ), 'hotkey' => 'A' ),
			'dashboard' => array( 'url' => URL::get( 'admin', 'page=' ), 'title' => _t( 'View your user dashboard' ), 'text' => _t( 'Dashboard' ), 'hotkey' => 'D' ),
			'options' => array( 'url' => URL::get( 'admin', 'page=options' ), 'title' => _t( 'View and configure blog options' ), 'text' => _t( 'Options' ), 'hotkey' => 'O' ),
			'themes' => array( 'url' => URL::get( 'admin', 'page=themes' ), 'title' => _t( 'Preview and activate themes' ), 'text' => _t( 'Themes' ), 'hotkey' => 'T' ),
			'plugins' => array( 'url' => URL::get( 'admin', 'page=plugins' ), 'title' => _t( 'Activate, deactivate, and configure plugins' ), 'text' => _t( 'Plugins' ), 'hotkey' => 'P' ),
			'import' => array( 'url' => URL::get( 'admin', 'page=import' ), 'title' => _t( 'Import content from another blog' ), 'text' => _t( 'Import' ), 'hotkey' => 'I' ),
			'users' => array( 'url' => URL::get( 'admin', 'page=users' ), 'title' => _t( 'View and manage users' ), 'text' => _t( 'Users' ), 'hotkey' => 'U' ),
			'groups' => array( 'url' => URL::get( 'admin', 'page=groups' ), 'title' => _t( 'View and manage groups' ), 'text' => _t( 'Groups' ), 'hotkey' => 'G' ),
			'logs' => array( 'url' => URL::get( 'admin', 'page=logs'), 'title' => _t( 'View system log messages' ), 'text' => _t( 'Logs' ), 'hotkey' => 'L') ,
			'logout' => array( 'url' => URL::get( 'user', 'page=logout' ), 'title' => _t( 'Log out of the administration interface' ), 'text' => _t( 'Logout' ), 'hotkey' => 'X' ),
		);

		$mainmenus = array_merge( $adminmenu );

		foreach( $mainmenus as $menu_id => $menu ) {
			// Change this to set the correct menu as the active menu
			if( !isset( $mainmenus[$menu_id]['selected'] ) ) {
				$mainmenus[$menu_id]['selected'] = false;
			}
		}

		$mainmenus = Plugins::filter( 'adminhandler_post_loadplugins_main_menu', $mainmenus );

		foreach( $mainmenus as $key => $attrs ) {
			if( $page == $key ) {
				$mainmenus[$key]['selected'] = true;
			}
		}

		$theme->assign( 'mainmenu', $mainmenus );
	}

	public function default_post_type_display($type, $foruse)
	{
		$names = array(
			'entry' => array(
				'singular' => _t('Entry'),
				'plural' => _t('Entries'),
			),
			'page' => array(
				'singular' => _t('Page'),
				'plural' => _t('Pages'),
			),
		);
		return isset($names[$type][$foruse]) ? $names[$type][$foruse] : $type;
	}

	/**
	 * Setup the default admin javascript stack here so that it can be called
	 * from plugins, etc. This is not an ideal solution, but works for now.
	 *
	 */
	public static function setup_stacks() {
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/jquery.js", 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/ui.core.js", 'ui.core', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/ui.slider.js", 'ui.slider', array('jquery', 'ui.core') );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/ui.tabs.js", 'ui.tabs', array('jquery', 'ui.core') );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/ui.sortable.js", 'ui.sortable', array('jquery', 'ui.core') );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/ui.resizable.js", 'ui.resizable', array('jquery', 'ui.core') );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/jquery.spinner.js", 'jquery.spinner', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('scripts') . "/jquery.color.js", 'jquery.color', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('habari') . "/3rdparty/humanmsg/humanmsg.js", 'humanmsg', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('habari') . "/3rdparty/hotkeys/jquery.hotkeys.js", 'jquery.hotkeys', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('admin_theme') . "/js/media.js", 'media', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url('admin_theme') . "/js/admin.js", 'admin', 'jquery' );
	}
}
?>
