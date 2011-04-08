<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminHandler Class
 * Backbone of the admin area, handles requests and functionality.
 *
 * @todo Split into page-specific controllers.
 * Discussion: See http://groups.google.com/group/habari-dev/browse_thread/thread/9c469a4fcb61c814
 * Branch: https://trac.habariproject.org/habari/browser/branches/adminhandler
 * Related branch: http://trac.habariproject.org/habari/browser/branches/handlers
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
			if ( URL::get_matched_rule()->name == 'admin_ajax' && isset( $_SERVER['HTTP_REFERER'] ) ) {
				header( 'Content-Type: text/javascript;charset=utf-8' );
				echo '{callback: function(){location.href="'.$_SERVER['HTTP_REFERER'].'"} }';
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

		// setup the stacks for javascript in the admin - it's a method so a plugin can call it externally
		self::setup_stacks();
		
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
			$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

			// Add some default stylesheets
			Stack::add( 'admin_stylesheet', array( Site::get_url( 'admin_theme' ) . '/css/admin.css', 'screen' ), 'admin' );
			Stack::add( 'admin_stylesheet', array( Site::get_url( 'admin_theme' ) . '/css/jqueryui.css', 'screen' ), 'jqueryui' );

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
		if ( isset( $this->handler_vars['content_type'] ) ) {
			$type = Plugins::filter( 'post_type_display', Post::type_name( $this->handler_vars['content_type'] ), 'singular' );
		}
		elseif ( $page == 'publish' && isset( $this->handler_vars['id'] ) ) {
			$type = Post::type_name( Post::get( array( 'status' => Post::status( 'any' ), 'id' => intval( $this->handler_vars['id'] ) ) )->content_type );
			$type = Plugins::filter( 'post_type_display', Post::type_name( Post::get( array( 'status' => Post::status( 'any' ), 'id' => intval( $this->handler_vars['id'] ) ) )->content_type ), 'singular' );
		}
		else {
			$type = '';
		}
		//$type = ( isset( $this->handler_vars['content_type'] ) && !empty( $this->handler_vars['content_type'] ) ) ? $this->handler_vars['content_type'] : '';
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
					echo sprintf( _t( '%1$s->%2$s() does not exist.' ), $classname, $fn );
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
	 * Handles POST requests from the dashboard.
	 */
	public function post_dashboard()
	{
		$this->get_dashboard();
	}

	/**
	 * Handles get requests for the dashboard
	 * @todo update check should probably be cron'd and cached, not re-checked every load
	 */
	public function get_dashboard()
	{
		// Not sure how best to determine this yet, maybe set an option on install, maybe do this:
		$firstpostdate = DB::get_value( 'SELECT min(pubdate) FROM {posts} WHERE status = ?', array( Post::status( 'published' ) ) );
		$this->theme->active_time = HabariDateTime::date_create( $firstpostdate );


		// get the active theme, so we can check it
		// @todo this should be worked into the main Update::check() code for registering beacons
		$active_theme = Themes::get_active();
		$active_theme = $active_theme->name . ':' . $active_theme->version;

		// check to see if we have updates to display
		$this->theme->updates = Options::get( 'updates_available', array() );
		
		// collect all the stats we display on the dashboard
		$this->theme->stats = array(
			'author_count' => Users::get( array( 'count' => 1 ) ),
			'page_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'page' ), 'status' => Post::status( 'published' ) ) ),
			'entry_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'entry' ), 'status' => Post::status( 'published' ) ) ),
			'comment_count' => Comments::count_total( Comment::STATUS_APPROVED, false ),
			'tag_count' => Tags::vocabulary()->count_total(),
			'page_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'page' ), 'status' => Post::status( 'draft' ), 'user_id' => User::identify()->id ) ),
			'entry_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'entry' ), 'status' => Post::status( 'draft' ), 'user_id' => User::identify()->id ) ),
			'unapproved_comment_count' => User::identify()->can( 'manage_all_comments' ) ? Comments::count_total( Comment::STATUS_UNAPPROVED, false ) : Comments::count_by_author( User::identify()->id, Comment::STATUS_UNAPPROVED ),
			'spam_comment_count' => User::identify()->can( 'manage_all_comments' ) ? Comments::count_total( Comment::STATUS_SPAM, false ) : Comments::count_by_author( User::identify()->id, Comment::STATUS_SPAM ),
			'user_entry_scheduled_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'scheduled' ), 'user_id' => User::identify()->id ) ),
		);

		$this->fetch_dashboard_modules();

		// check for first run
		$u = User::identify();
		if ( ! isset( $u->info->experience_level ) ) {
			$this->theme->first_run = true;
			$u->info->experience_level = 'user';
			$u->info->commit();
		}
		else {
			$this->theme->first_run = false;
		}

		$this->display( 'dashboard' );
	}

	/**
	 * Fetches active modules for display on the dashboard
	 */
	public function fetch_dashboard_modules()
	{

		if ( count( Modules::get_all() ) == 0 ) {
			$this->theme->modules = array();
			return;
		}

		// get the active module list
		$modules = Modules::get_active();

		if ( User::identify()->can( 'manage_dash_modules' ) ) {
			// append the 'Add Item' module
			$modules['nosort'] = 'Add Item';

			// register the 'Add Item' filter
			Plugins::register( array( $this, 'filter_dash_module_add_item' ), 'filter', 'dash_module_add_item' );
		}

		foreach ( $modules as $id => $module_name ) {
			$slug = Utils::slugify( (string) $module_name, '_' );
			$module = array(
				'name' => $module_name,
				'title' => $module_name,
				'content' => '',
				'options' => ''
				);

			$module = Plugins::filter( 'dash_module_' .$slug, $module, $id, $this->theme );

			$modules[$id] = $module;
		}

		$this->theme->modules = $modules;
	}

	/**
	 * Handles POST requests from the publish page.
	 */
	public function post_publish()
	{
		$this->get_publish();
	}

	public function form_publish_success( FormUI $form )
	{
		$post_id = 0;
		if ( isset( $this->handler_vars['id'] ) ) {
			$post_id = intval( $this->handler_vars['id'] );
		}
		// If an id has been passed in, we're updating an existing post, otherwise we're creating one
		if ( 0 !== $post_id ) {
			$post = Post::get( array( 'id' => $post_id, 'status' => Post::status( 'any' ) ) );

			$this->theme->admin_page = sprintf( _t( 'Publish %s' ), Plugins::filter( 'post_type_display', Post::type_name( $post->content_type ), 'singular' ) );

			// Verify that the post hasn't already been updated since the form was loaded
			if ( $post->modified != $form->modified->value ) {
				Session::notice( _t( 'The post %1$s was updated since you made changes.  Please review those changes before overwriting them.', array( sprintf( '<a href="%1$s">\'%2$s\'</a>', $post->permalink, Utils::htmlspecialchars( $post->title ) ) ) ) );
				Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
				exit;
			}

			// REFACTOR: this is duplicated in the insert code below, move it outside of the conditions
			// Don't try to update form values that have been removed by plugins
			$expected = array('title', 'tags', 'content');

			foreach ( $expected as $field ) {
				if ( isset( $form->$field ) ) {
					$post->$field = $form->$field->value;
				}
			}
			if ( $form->newslug->value == '' && $post->status == Post::status( 'published' ) ) {
				Session::notice( _t( 'A post slug cannot be empty. Keeping old slug.' ) );
			}
			elseif ( $form->newslug->value != $form->slug->value ) {
				$post->slug = $form->newslug->value;
			}

			// REFACTOR: the permissions checks should go before any of this other logic
			
			// sorry, we just don't allow changing posts you don't have rights to
			if ( ! ACL::access_check( $post->get_access(), 'edit' ) ) {
				Session::error( _t( 'You don\'t have permission to edit that post' ) );
				$this->get_blank();
			}
			// sorry, we just don't allow changing content types to types you don't have rights to
			$user = User::identify();
			$type = 'post_' . Post::type_name( $form->content_type->value );
			if ( $form->content_type->value != $post->content_type && ( $user->cannot( $type ) || ! $user->can_any( array( 'own_posts' => 'edit', 'post_any' => 'edit', $type => 'edit' ) ) ) ) {
				Session::error( _t( 'Changing content types is not allowed' ) );
				$this->get_blank();
			}
			$post->content_type = $form->content_type->value;

			// if not previously published and the user wants to publish now, change the pubdate to the current date/time unless a date has been explicitly set
			if ( ( $post->status != Post::status( 'published' ) )
				&& ( $form->status->value == Post::status( 'published' ) )
				&& ( HabariDateTime::date_create( $form->pubdate->value )->int == $form->updated->value )
				) {
				$post->pubdate = HabariDateTime::date_create();
			}
			// else let the user change the publication date.
			//  If previously published and the new date is in the future, the post will be unpublished and scheduled. Any other status, and the post will just get the new pubdate.
			// This will result in the post being scheduled for future publication if the date/time is in the future and the new status is published.
			else {
				$post->pubdate = HabariDateTime::date_create( $form->pubdate->value );
			}
			$minor = $form->minor_edit->value && ( $post->status != Post::status( 'draft' ) );
			$post->status = $form->status->value;
		}
		else {
			// REFACTOR: don't do this here, it's duplicated in Post::create()
			$post = new Post();

			// check the user can create new posts of the set type.
			$user = User::identify();
			$type = 'post_'  . Post::type_name( $form->content_type->value );
			if ( ACL::user_cannot( $user, $type ) || ( ! ACL::user_can( $user, 'post_any', 'create' ) && ! ACL::user_can( $user, $type, 'create' ) ) ) {
				Session::error( _t( 'Creating that post type is denied' ) );
				$this->get_blank();
			}

			// REFACTOR: why is this on_success here? We don't even display a form
			$form->on_success( array( $this, 'form_publish_success' ) );
			if ( HabariDateTime::date_create( $form->pubdate->value )->int != $form->updated->value ) {
				$post->pubdate = HabariDateTime::date_create( $form->pubdate->value );
			}

			$postdata = array(
				'slug' => $form->newslug->value,
				'user_id' => User::identify()->id,
				'pubdate' => $post->pubdate,
				'status' => $form->status->value,
				'content_type' => $form->content_type->value,
			);

			// Don't try to add form values that have been removed by plugins
			$expected = array( 'title', 'tags', 'content' );

			foreach ( $expected as $field ) {
				if ( isset( $form->$field ) ) {
					$postdata[$field] = $form->$field->value;
				}
			}

			$minor = false;

			// REFACTOR: consider using new Post( $postdata ) instead and call ->insert() manually 
			$post = Post::create( $postdata );
		}

		// REFACTOR: this should handled in the Post::insert() code, which is called by Post::create() above. should also apply to updating posts, presumably in Post::update()
		if ( $post->pubdate->int > HabariDateTime::date_create()->int && $post->status == Post::status( 'published' ) ) {
			$post->status = Post::status( 'scheduled' );
		}

		$post->info->comments_disabled = !$form->comments_enabled->value;

		// REFACTOR: admin should absolutely not have a hook for this here
		Plugins::act( 'publish_post', $post, $form );

		// REFACTOR: we should not have to update a post we just created, this should be moved to the post-update functionality above and only called if changes have been made
		// alternately, perhaps call ->update() or ->insert() as appropriate here, so things that apply to each operation (like comments_disabled) can still be included once outside the conditions above
		$post->update( $minor );

		$permalink = ( $post->status != Post::status( 'published' ) ) ? $post->permalink . '?preview=1' : $post->permalink;
		Session::notice( sprintf( _t( 'The post %1$s has been saved as %2$s.' ), sprintf( '<a href="%1$s">\'%2$s\'</a>', $permalink, Utils::htmlspecialchars( $post->title ) ), Post::status_name( $post->status ) ) );
		Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
	}

	/**
	 * Handles GET requests of the publish page.
	 */
	public function get_publish( $template = 'publish' )
	{
		$extract = $this->handler_vars->filter_keys( 'id', 'content_type' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}

		// 0 is what's assigned to new posts
		if ( isset( $id ) && ( $id != 0 ) ) {
			$post = Post::get( array( 'id' => $id, 'status' => Post::status( 'any' ) ) );
			if ( !$post ) {
				Session::error( _t( "You don't have permission to edit that post" ) );
				$this->get_blank();
			}
			if ( ! ACL::access_check( $post->get_access(), 'edit' ) ) {
				Session::error( _t( "You don't have permission to edit that post" ) );
				$this->get_blank();
			}
			$this->theme->post = $post;
		}
		else {
			$post = new Post();
			$this->theme->post = $post;
			$post->content_type = Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );

			// check the user can create new posts of the set type.
			$user = User::identify();
			$type = 'post_' . Post::type_name( $post->content_type );
			if ( ACL::user_cannot( $user, $type ) || ( ! ACL::user_can( $user, 'post_any', 'create' ) && ! ACL::user_can( $user, $type, 'create' ) ) ) {
				Session::error( _t( 'Access to create posts of type %s is denied', array( Post::type_name( $post->content_type ) ) ) );
				$this->get_blank();
			}
		}

		$this->theme->admin_page = sprintf( _t( 'Publish %s' ), Plugins::filter( 'post_type_display', Post::type_name( $post->content_type ), 'singular' ) );
		$this->theme->admin_title = sprintf( _t( 'Publish %s' ), Plugins::filter( 'post_type_display', Post::type_name( $post->content_type ), 'singular' ) );

		$statuses = Post::list_post_statuses( false );
		$this->theme->statuses = $statuses;

		$form = $post->get_form( 'admin' );
		$form->on_success( array( $this, 'form_publish_success' ) );

		$this->theme->form = $form;

		$this->theme->wsse = Utils::WSSE();
		$this->display( $template );
	}

	/**
	 * Deletes a post from the database.
	 */
	public function post_delete_post()
	{
		$extract = $this->handler_vars->filter_keys( 'id', 'nonce', 'timestamp', 'digest' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}

		$okay = true;
		if ( empty( $id ) || empty( $nonce ) || empty( $timestamp ) || empty( $digest ) ) {
			$okay = false;
		}
		$wsse = Utils::WSSE( $nonce, $timestamp );
		if ( $digest != $wsse['digest'] ) {
			$okay = false;
		}

		$post = Post::get( array( 'id' => $id, 'status' => Post::status( 'any' ) ) );
		if ( ! ACL::access_check( $post->get_access(), 'delete' ) ) {
			$okay = false;
		}

		if ( !$okay ) {
			Utils::redirect( URL::get( 'admin', 'page=posts&type='. Post::status( 'any' ) ) );
		}

		$post->delete();
		Session::notice( sprintf( _t( 'Deleted the %1$s titled "%2$s".' ), Post::type_name( $post->content_type ), Utils::htmlspecialchars( $post->title ) ) );
		Utils::redirect( URL::get( 'admin', 'page=posts&type=' . Post::status( 'any' ) ) );
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

	/**
	 * A POST handler for the admin themes page that simply passes those options through.
	 */
	public function post_themes()
	{
		return $this->get_themes();
	}

	/**
	 * Handles GET requests for the theme listing
	 */
	public function get_themes()
	{
		$all_themes = Themes::get_all_data();
		
		$available_updates = Options::get( 'updates_available', array() );
		
		foreach ( $all_themes as $name => $theme ) {
			
			// only themes with a guid can be checked for updates
			if ( isset( $theme['info']->guid ) ) {
				if ( isset( $available_updates[ (string)$theme['info']->guid ] ) ) {
					// @todo this doesn't use the URL and is therefore worthless
					$all_themes[ $name ]['info']->update = $available_updates[ (string)$theme['info']->guid ]['latest_version'];
				}
			}
			
		}
		
		$this->theme->all_themes = $all_themes;

		$this->theme->active_theme = Themes::get_active_data( true );
		$this->theme->active_theme_dir = $this->theme->active_theme['path'];

		// If the active theme is configurable, allow it to configure
		$this->theme->active_theme_name = $this->theme->active_theme['info']->name;
		$this->theme->configurable = Plugins::filter( 'theme_config', false, $this->active_theme );
		$this->theme->assign( 'configure', Controller::get_var( 'configure' ) );

		$activedata = Themes::get_active_data( true );
		$areas = array();
		if ( isset( $activedata['info']->areas->area ) ) {
			foreach ( $activedata['info']->areas->area as $area ) {
				$areas[] = (string)$area;
			}
		}
		$this->theme->areas = $areas;
		$this->theme->previewed = Themes::get_theme_dir( false );

		$this->theme->blocks = Plugins::filter( 'block_list', array() );
		$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
		$blocks_areas_t = DB::get_results( 'SELECT b.*, ba.scope_id, ba.area, ba.display_order FROM {blocks} b INNER JOIN {blocks_areas} ba ON ba.block_id = b.id ORDER BY ba.scope_id ASC, ba.area ASC, ba.display_order ASC', array() );
		$blocks_areas = array();
		foreach ( $blocks_areas_t as $block ) {
			if ( !isset( $blocks_areas[$block->scope_id] ) ) {
				$blocks_areas[$block->scope_id] = array();
			}
			$blocks_areas[$block->scope_id][$block->area][$block->display_order] = $block;
		}
		$this->theme->blocks_areas = $blocks_areas;

		$scopes = DB::get_results( 'SELECT * FROM {scopes} ORDER BY name ASC;' );
		$scopes = Plugins::filter( 'get_scopes', $scopes );
		$this->theme->scopes = $scopes;
		$this->theme->scopeid = 0;

		$this->theme->theme_loader = Plugins::filter( 'theme_loader', '', $this->theme );

		$this->theme->display( 'themes' );
	}

	/**
	 * Activates a theme.
	 */
	public function get_activate_theme()
	{
		$theme_name = $this->handler_vars['theme_name'];
		$theme_dir = $this->handler_vars['theme_dir'];
		if ( isset( $theme_name )  && isset( $theme_dir ) ) {
			Themes::activate_theme( $theme_name, $theme_dir );
		}
		Session::notice( sprintf( _t( "Activated theme '%s'" ), $theme_name ) );
		Utils::redirect( URL::get( 'admin', 'page=themes' ) );
	}

	/**
	 * Configures a theme to be active for the current user's session.
	 */
	public function get_preview_theme()
	{
		$theme_name = $this->handler_vars['theme_name'];
		$theme_dir = $this->handler_vars['theme_dir'];
		if ( isset( $theme_name )  && isset( $theme_dir ) ) {
			if ( Themes::get_theme_dir() == $theme_dir ) {
				Themes::cancel_preview();
				Session::notice( sprintf( _t( "Ended the preview of the theme '%s'" ), $theme_name ) );
			}
			else {
				Themes::preview_theme( $theme_name, $theme_dir );
				Session::notice( sprintf( _t( "Previewing theme '%s'" ), $theme_name ) );
			}
		}
		Utils::redirect( URL::get( 'admin', 'page=themes' ) );
	}

	/**
	 * Handles GET requests for the import page.
	 */
	public function get_import()
	{

		$importer = isset( $_POST['importer'] ) ? $_POST['importer'] : '';
		$stage = isset( $_POST['stage'] ) ? $_POST['stage'] : '1';
		$step = isset( $_POST['step'] ) ? $_POST['step'] : '1';

		$this->theme->enctype = Plugins::filter( 'import_form_enctype', 'application/x-www-form-urlencoded', $importer, $stage, $step );
		
		// filter to get registered importers
		$importers = Plugins::filter( 'import_names', array() );
		
		// fitler to get the output of the current importer, if one is running
		if ( $importer != '' ) {
			$output = Plugins::filter( 'import_stage', '', $importer, $stage, $step );
		}
		else {
			$output = '';
		}

		$this->theme->importer = $importer;
		$this->theme->stage = $stage;
		$this->theme->step = $step;
		$this->theme->importers = $importers;
		$this->theme->output = $output;
		
		$this->display( 'import' );

	}

	/**
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 */
	public function post_import()
	{
		if ( !isset( $_POST['importer'] ) ) {
			Utils::redirect( URL::get( 'admin', 'page=import' ) );
		}

		$this->get_import();
	}

	/**
	 * A POST handler for the admin plugins page that simply passes those options through.
	 */
	public function post_plugins()
	{
		return $this->get_plugins();
	}

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
							$providing[(string) $feature] = $feature;
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
						$action['caption'] = _t( '?' );
						$action['action'] = '_help';
						$urlparams = array( 'page' => 'plugins', 'configure' => $plugin_id );
						$action['url'] = URL::get( 'admin', $urlparams );
						$plugin['help'] = $action;
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

		foreach ( $sort_inactive_plugins as $plugin_id => $plugin ) {
			if ( isset( $plugin['info']->requires ) ) {
				foreach ( $plugin['info']->requires->feature as $feature ) {
					if ( !isset( $providing[(string) $feature] ) ) {
						if ( !isset( $sort_inactive_plugins[$plugin_id]['missing'] ) ) {
							$sort_inactive_plugins[$plugin_id]['missing'] = array();
						}
						$sort_inactive_plugins[$plugin_id]['missing'][(string) $feature] = isset( $feature['url'] ) ? $feature['url'] : '';
						unset( $sort_inactive_plugins[$plugin_id]['actions']['activate'] );
					}
				}
			}
		}

		//$this->theme->plugins = array_merge($sort_active_plugins, $sort_inactive_plugins);
		$this->theme->assign( 'configaction', Controller::get_var( 'configaction' ) );
		$this->theme->assign( 'helpaction', Controller::get_var( 'help' ) );
		$this->theme->assign( 'configure', Controller::get_var( 'configure' ) );
		$this->theme->active_plugins = $sort_active_plugins;
		$this->theme->inactive_plugins = $sort_inactive_plugins;

		$this->theme->plugin_loader = Plugins::filter( 'plugin_loader', '', $this->theme );

		$this->display( 'plugins' );
	}

	/**
	 * Assign values needed to display the entries page to the theme based on handlervars and parameters
	 *
	 */
	private function fetch_posts( $params = array() )
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals = array(
			'do_update' => false,
			'post_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'password_digest' => '',
			'change' => '',
			'user_id' => 0,
			'type' => Post::type( 'any' ),
			'status' => Post::status( 'any' ),
			'limit' => 20,
			'offset' => 0,
			'search' => '',
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : ( isset( $params[$varname] ) ? $params[$varname] : $default );
			$this->theme->{$varname} = $$varname;
		}

		// numbers submitted by HTTP forms are seen as strings
		// but we want the integer value for use in Posts::get,
		// so cast these two values to (int)
		if ( isset( $this->handler_vars['type'] ) ) {
			$type = (int) $this->handler_vars['type'];
		}
		if ( isset( $this->handler_vars['status'] ) ) {
			$status = (int) $this->handler_vars['status'];
		}

		// if we're updating posts, let's do so:
		if ( $do_update && isset( $post_ids ) ) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $password_digest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $password_digest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				foreach ( $post_ids as $id ) {
					$ids[] = array( 'id' => $id );
				}
				$to_update = Posts::get( array( 'where' => $ids, 'nolimit' => 1 ) );
				foreach ( $to_update as $post ) {
					switch ( $change ) {
						case 'delete':
							if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
								$post->delete();
							}
							break;
						case 'publish':
							if ( ACL::access_check( $post->get_access(), 'edit' ) ) {
								$post->publish();
							}
							break;
						case 'unpublish':
							if ( ACL::access_check( $post->get_access(), 'edit' ) ) {
								$post->status = Post::status( 'draft' );
								$post->update();
							}
							break;
					}
				}
				unset( $this->handler_vars['change'] );
			}
		}


		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse = Utils::WSSE();

		$arguments = array(
			'content_type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => $offset,
			'user_id' => $user_id,
		);

		if ( '' != $search ) {
			$arguments = array_merge( $arguments, Posts::search_to_get( $search ) );
		}
		$this->theme->posts = Posts::get( $arguments );

		// setup keyword in search field if a status or type was passed in POST
		$this->theme->search_args = '';
		if ( $status != Post::status( 'any' ) ) {
			$this->theme->search_args = 'status:' . Post::status_name( $status ) . ' ';
		}
		if ( $type != Post::type( 'any' ) ) {
			$this->theme->search_args .= 'type:' . Post::type_name( $type ) . ' ';
		}
		if ( $user_id != 0 ) {
			$this->theme->search_args .= 'author:' . User::get_by_id( $user_id )->username .' ';
		}
		if ( $search != '' ) {
			$this->theme->search_args .= $search;
		}

		$monthcts = Posts::get( array_merge( $arguments, array( 'month_cts' => true, 'nolimit' => true ) ) );
		$years = array();
		foreach ( $monthcts as $month ) {
			if ( isset( $years[$month->year] ) ) {
				$years[$month->year][] = $month;
			}
			else {
				$years[$month->year] = array( $month );
			}
		}

		$this->theme->years = $years;

	}

	/**
	 * Handles GET requests to /admin/entries.
	 *
	 */
	public function get_posts()
	{
		$this->post_posts();
	}

	/**
	 * Handles POST values from /manage/entries.
	 * Used to control what content to show / manage.
	 */
	public function post_posts()
	{
		$this->fetch_posts();
		// Get special search statuses
		$statuses = array_keys( Post::list_post_statuses() );
		array_shift( $statuses );
		$statuses = array_combine(
			$statuses,
			array_map(
				create_function( '$a', 'return "status:{$a}";' ),
				$statuses
			)
		);

		// Get special search types
		$types = array_keys( Post::list_active_post_types() );
		array_shift( $types );
		$types = array_combine(
			$types,
			array_map(
				create_function( '$a', 'return "type:{$a}";' ),
				$types
			)
		);
		$this->theme->admin_page = _t( 'Manage Posts' );
		$this->theme->admin_title = _t( 'Manage Posts' );
		$this->theme->special_searches = Plugins::filter( 'special_searches', array_merge( $statuses, $types ) );
		$this->display( 'posts' );
	}

	/**
	 * Handles AJAX requests from the dashboard
	 */
	public function ajax_dashboard( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		switch ( $handler_vars['action'] ) {
			case 'updateModules':
				$modules = array();
				foreach ( $_POST as $key => $module ) {
					// skip POST elements which are not module names
					if ( preg_match( '/^module\d+$/', $key ) ) {
						list( $module_id, $module_name ) = explode( ':', $module, 2 );
						// remove non-sortable modules from the list
						if ( $module_id != 'nosort' ) {
							$modules[$module_id] = $module_name;
						}
					}
				}

				Modules::set_active( $modules );
				$ar = new AjaxResponse( 200, _t( 'Modules updated.' ) );
				break;
			case 'addModule':
				$id = Modules::add( $handler_vars['module_name'] );
				$this->fetch_dashboard_modules();
				$ar = new AjaxResponse( 200, _t( 'Added module %s.', array( $handler_vars['module_name'] ) ) );
				$ar->html( 'modules', $this->theme->fetch( 'dashboard_modules' ) );
				break;
			case 'removeModule':
				Modules::remove( $handler_vars['moduleid'] );
				$this->fetch_dashboard_modules();
				$ar = new AjaxResponse( 200, _t( 'Removed module.' ) );
				$ar->html( 'modules', $this->theme->fetch( 'dashboard_modules' ) );
				break;
		}

		$ar->out();
	}

	/**
	 * Handles AJAX requests from the manage posts page.
	 */
	public function ajax_posts()
	{
		Utils::check_request_method( array( 'GET', 'HEAD' ) );

		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params = $_GET;

		$this->fetch_posts( $params );
		$items = $this->theme->fetch( 'posts_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach ( $this->theme->posts as $post ) {
			if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
				$item_ids['p' . $post->id] = 1;
			}
		}

		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode( $output );
	}

	/**
	 * Handles AJAX from /manage/entries.
	 * Used to delete entries.
	 */
	public function ajax_update_entries( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );
		$response = new AjaxResponse();

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			$response->message = _t( 'WSSE authentication failed.' );
			$response->out();
			return;
		}

		$ids = array();
		foreach ( $_POST as $id => $delete ) {
			// skip POST elements which are not post ids
			if ( preg_match( '/^p\d+$/', $id ) && $delete ) {
				$ids[] = (int) substr( $id, 1 );
			}
		}
		if ( count( $ids ) == 0 ) {
			$posts = new Posts();
		}
		else {
			$posts = Posts::get( array( 'id' => $ids, 'nolimit' => true ) );
		}

		Plugins::act( 'admin_update_posts', $handler_vars['action'], $posts, $this );
		$status_msg = _t( 'Unknown action "%s"', array( $handler_vars['action'] ) );
		switch ( $handler_vars['action'] ) {
			case 'delete':
				$deleted = 0;
				foreach ( $posts as $post ) {
					if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
						$post->delete();
						$deleted++;
					}
				}
				if ( $deleted != count( $posts ) ) {
					$response->message = _t( 'You did not have permission to delete some entries.' );
				}
				else {
					$response->message = sprintf( _n( 'Deleted %d post', 'Deleted %d posts', count( $ids ) ), count( $ids ) );
				}
				break;
			default:
				// Specific plugin-supplied action
				Plugins::act( 'admin_entries_action', $response, $handler_vars['action'], $posts );
				break;
		}

		$response->out();
		exit;
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
			$siteinfo[ _t( 'Habari Version' ) ] .= " r" . Version::get_svn_revision();
		}

		$siteinfo[ _t( 'Habari API Version' ) ] = Version::get_apiversion();
		$siteinfo[ _t( 'Habari DB Version' ) ] = Version::get_dbversion();
		$siteinfo[ _t( 'Active Theme' ) ] = Options::get( 'theme_name' );
		$siteinfo[ _t( 'Site Language' ) ] =  strlen( Options::get( 'system_locale' ) ) ? Options::get( 'system_locale' ) : 'en-us';
		$this->theme->siteinfo = $siteinfo;

		// Assemble System Info
		$sysinfo[ _t( 'PHP Version' ) ] = phpversion();
		$sysinfo[ _t( 'Server Software' ) ] = $_SERVER['SERVER_SOFTWARE'];
		$sysinfo[ _t( 'Database' ) ] = DB::get_driver_name() . ' - ' . DB::get_driver_version();
		$sysinfo[ _t( 'PHP Extensions' ) ] = implode( ', ', get_loaded_extensions() );
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
			if ( preg_match( '%[\\\\/](system|3rdparty|user)[\\\\/]plugins[\\\\/]%i', $file, $matches ) ) {
				// A plugin's info is XML, cast the element to a string. See #1026.
				$plugins[strtolower( $matches[1] )][(string)$plugin->info->name] = $file;
			}
			else {
				$plugins['other'][$plugin->info->name] = $file;
			}
		}
		$this->theme->plugins = $plugins;

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

		Plugins::register( array( $this, 'default_post_type_display' ), 'filter', 'post_type_display', 4 );

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
			'tags' => array( 'url' => URL::get( 'admin', 'page=tags' ), 'title' => _t( 'Manage tags' ), 'text' => _t( 'Tags' ), 'hotkey' => 'A', 'access'=>array( 'manage_tags'=>true ) ),
			'dashboard' => array( 'url' => URL::get( 'admin', 'page=' ), 'title' => _t( 'View your user dashboard' ), 'text' => _t( 'Dashboard' ), 'hotkey' => 'D' ),
			'options' => array( 'url' => URL::get( 'admin', 'page=options' ), 'title' => _t( 'View and configure site options' ), 'text' => _t( 'Options' ), 'hotkey' => 'O', 'access'=>array( 'manage_options'=>true ) ),
			'themes' => array( 'url' => URL::get( 'admin', 'page=themes' ), 'title' => _t( 'Preview and activate themes' ), 'text' => _t( 'Themes' ), 'hotkey' => 'T', 'access'=>array( 'manage_theme'=>true ) ),
			'plugins' => array( 'url' => URL::get( 'admin', 'page=plugins' ), 'title' => _t( 'Activate, deactivate, and configure plugins' ), 'text' => _t( 'Plugins' ), 'hotkey' => 'P', 'access'=>array( 'manage_plugins'=>true, 'manage_plugins_config' => true ) ),
			'import' => array( 'url' => URL::get( 'admin', 'page=import' ), 'title' => _t( 'Import content from another site' ), 'text' => _t( 'Import' ), 'hotkey' => 'I', 'access'=>array( 'manage_import'=>true ) ),
			'users' => array( 'url' => URL::get( 'admin', 'page=users' ), 'title' => _t( 'View and manage users' ), 'text' => _t( 'Users' ), 'hotkey' => 'U', 'access'=>array( 'manage_users'=>true ) ),
			'profile' => array( 'url' => URL::get( 'admin', 'page=user' ), 'title' => _t( 'Manage your user profile' ), 'text' => _t( 'My Profile' ), 'hotkey' => 'Y', 'access'=>array( 'manage_self'=>true, 'manage_users'=>true ) ),
			'groups' => array( 'url' => URL::get( 'admin', 'page=groups' ), 'title' => _t( 'View and manage groups' ), 'text' => _t( 'Groups' ), 'hotkey' => 'G', 'access'=>array( 'manage_groups'=>true ) ),
			'logs' => array( 'url' => URL::get( 'admin', 'page=logs' ), 'title' => _t( 'View system log messages' ), 'text' => _t( 'Logs' ), 'hotkey' => 'L', 'access'=>array( 'manage_logs'=>true ) ) ,
			'logout' => array( 'url' => URL::get( 'auth', 'page=logout' ), 'title' => _t( 'Log out of the administration interface' ), 'text' => _t( 'Logout' ), 'hotkey' => 'X' ),
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
			if ( $page == $key ) {
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
			case 'ajax_delete_entries':
			case 'ajax_update_entries':
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
	 * How to display the built-in post types.
	 */
	public function default_post_type_display( $type, $foruse )
	{
		$names = array(
			'entry' => array(
				'singular' => _t( 'Entry' ),
				'plural' => _t( 'Entries' ),
			),
			'page' => array(
				'singular' => _t( 'Page' ),
				'plural' => _t( 'Pages' ),
			),
		);
		return isset( $names[$type][$foruse] ) ? $names[$type][$foruse] : $type;
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

	/**
	 * Handles AJAX requests from media silos.
	 */
	public function ajax_media( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$path = $handler_vars['path'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo
		$assets = Media::dir( $path );
		$output = array(
			'ok' => 1,
			'dirs' => array(),
			'files' => array(),
			'path' => $path,
		);
		foreach ( $assets as $asset ) {
			if ( $asset->is_dir ) {
				$output['dirs'][$asset->basename] = $asset->get_props();
			}
			else {
				$output['files'][$asset->basename] = $asset->get_props();
			}
		}
		$rootpath = MultiByte::strpos( $path, '/' ) !== false ? MultiByte::substr( $path, 0, MultiByte::strpos( $path, '/' ) ) : $path;
		$controls = array( 'root' => '<a href="#" onclick="habari.media.fullReload();habari.media.showdir(\''. $rootpath . '\');return false;">' . _t( 'Root' ) . '</a>' );
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, '' );
		$controls_out = '';
		foreach ( $controls as $k => $v ) {
			if ( is_numeric( $k ) ) {
				$controls_out .= "<li>{$v}</li>";
			}
			else {
				$controls_out .= "<li class=\"{$k}\">{$v}</li>";
			}
		}
		$output['controls'] = $controls_out;

		echo json_encode( $output );
	}

	/**
	 * Handles AJAX requests from media panels.
	 */
	public function ajax_media_panel( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$path = $handler_vars['path'];
		$panelname = $handler_vars['panel'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel = '';
		$panel = Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );

		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls_out = '';
		foreach ( $controls as $k => $v ) {
			if ( is_numeric( $k ) ) {
				$controls_out .= "<li>{$v}</li>";
			}
			else {
				$controls_out .= "<li class=\"{$k}\">{$v}</li>";
			}
		}
		$output = array(
			'controls' => $controls_out,
			'panel' => $panel,
		);

		echo json_encode( $output );
	}

	/**
	 * Get the block configuration form to show in a modal iframe on the themes page
	 *
	 */
	public function get_configure_block()
	{
		Utils::check_request_method( array( 'GET', 'POST' ) );

		$block = DB::get_row( 'SELECT b.* FROM {blocks} b WHERE id = :id ORDER BY b.title ASC', array( 'id' => $_GET['blockid'] ), 'Block' );
		$block_form = $block->get_form();
		$first_control = reset ( $block_form->controls );
		if ( $first_control ) {
			$block_form->insert( $first_control->name, 'fieldset', 'block_admin', _t( 'Block Display Settings' ) );
		}
		else {
			$block_form->append( 'fieldset', 'block_admin', _t( 'Block Display Settings' ) );
		}

		$block_form->block_admin->append( 'text', '_title', array( 'configure_block_title', $block ), _t( 'Block Title:' ) );
		$block_form->_title->value = $block->title;
		$block_form->_title->add_validator( 'validate_required' );
		$block_form->block_admin->append( 'checkbox', '_show_title', $block, _t( 'Display Block Title:' ) );
		$block_form->append( 'submit', 'save', _t( 'Save' ) );
		
		Plugins::register( array( $this, 'action_configure_block_title' ), 'action', 'configure_block_title' );
		
		$this->theme->content = $block_form->get();

		$this->display( 'block_configure' );
	}
	
	function action_configure_block_title( $value, $name, $storage )
	{
		$storage[0]->title = $value;
		return false;
	}
	
	/**
	 * A POST handler for the block configuration form
	 *
	 * @see AdminHandler::get_configure_block
	 * @return
	 */
	public function post_configure_block()
	{
		$this->get_configure_block();
	}

	/**
	 * Called from the themes page to create a new block instace
	 *
	 * @param mixed $handler_vars
	 */
	public function ajax_add_block( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$this->setup_admin_theme( '' );

		$title = $_POST['title'];
		$type = $_POST['type'];

		if ( !isset( $_POST['title'] ) ) {
			$this->theme->blocks = Plugins::filter( 'block_list', array() );
			$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
			$this->theme->active_theme = Themes::get_active_data( true );

			$this->display( 'block_instances' );
		}
		elseif ( $title == '' ) {
			$this->theme->blocks = Plugins::filter( 'block_list', array() );
			$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
			$this->theme->active_theme = Themes::get_active_data( true );

			$this->display( 'block_instances' );

			$msg = json_encode( _t( 'A new block must first have a name.' ) );

			echo '<script type="text/javascript">
				alert(' . $msg . ');
			</script>';
		}
		else {
			$block = new Block( array( 'title' => $title, 'type' => $type ) );
			$block->insert();

			$this->theme->blocks = Plugins::filter( 'block_list', array() );
			$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
			$this->theme->active_theme = Themes::get_active_data( true );

			$this->display( 'block_instances' );

			$msg = json_encode( _t( 'Added new block "%1s" of type "%2s".', array( $title, $type ) ) );

			echo '<script type="text/javascript">
				human_msg.display_msg(' . $msg . ');
				spinner.stop();
			</script>';
		}
	}

	/**
	 * Called from the themes page to delete a block instance
	 *
	 * @param mixed $handler_vars
	 */
	public function ajax_delete_block( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$this->setup_admin_theme( '' );

		$block_id = $_POST['block_id'];
		$block = DB::get_row( 'SELECT b.* FROM {blocks} b WHERE id = :block_id', array( 'block_id' => $block_id ), 'Block' );
		if ( $block->delete() ) {
			$msg = json_encode( _t( 'Deleted block "%1s" of type "%2s".', array( $block->title, $block->type ) ) );
		}
		else {
			$msg = json_encode( _t( 'Failed to delete block "%1s" of type "%2s".', array( $block->title, $block->type ) ) );
		}

		$this->theme->blocks = Plugins::filter( 'block_list', array() );
		$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
		$this->theme->active_theme = Themes::get_active_data( true );

		$this->display( 'block_instances' );

		echo '<script type="text/javascript">
			human_msg.display_msg(' . $msg . ');
			spinner.stop();
		</script>';
	}

	/**
	 * Called from the themes page to save the blocks instances into areas
	 *
	 * @param mixed $handler_vars
	 * @return
	 */
	public function ajax_save_areas( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$scope = $_POST['scope'];
		
		$msg = '';
		
		if ( isset( $_POST['area_blocks'] ) ) {
			$area_blocks = $_POST['area_blocks'];
			DB::query( 'DELETE FROM {blocks_areas} WHERE scope_id = :scope_id', array( 'scope_id' => $scope ) );
	
			foreach ( (array)$area_blocks as $area => $blocks ) {
				$display_order = 0;
				
				// if there are no blocks for a given area, skip it
				if ( empty( $blocks ) ) {
					continue;
				}
				
				foreach ( $blocks as $block ) {
					$display_order++;
					DB::query( 'INSERT INTO {blocks_areas} (block_id, area, scope_id, display_order) VALUES (:block_id, :area, :scope_id, :display_order)', array( 'block_id'=>$block, 'area'=>$area, 'scope_id'=>$scope, 'display_order'=>$display_order ) );
				}
			}

			$msg = json_encode( _t( 'Saved block areas settings.' ) );
			$msg = '<script type="text/javascript">
				human_msg.display_msg(' . $msg . ');
				spinner.stop();
			</script>';
		}

		$this->setup_admin_theme( '' );

		$blocks_areas_t = DB::get_results( 'SELECT b.*, ba.scope_id, ba.area, ba.display_order FROM {blocks} b INNER JOIN {blocks_areas} ba ON ba.block_id = b.id ORDER BY ba.scope_id ASC, ba.area ASC, ba.display_order ASC', array() );
		$blocks_areas = array();
		foreach ( $blocks_areas_t as $block ) {
			if ( !isset( $blocks_areas[$block->scope_id] ) ) {
				$blocks_areas[$block->scope_id] = array();
			}
			$blocks_areas[$block->scope_id][$block->area][$block->display_order] = $block;
		}
		$this->theme->blocks_areas = $blocks_areas;
		$this->theme->scopeid = $scope;
		$scopes = DB::get_results( 'SELECT * FROM {scopes} ORDER BY name ASC;' );
		$scopes = Plugins::filter( 'get_scopes', $scopes );
		$this->theme->scopes = $scopes;
		$this->theme->active_theme = Themes::get_active_data( true );

		$this->display( 'block_areas' );

		echo $msg;		
	}

	/**
	 * Function used to set theme variables to the add module dashboard widget.
	 * TODO make this form use an AJAX call instead of reloading the page
	 */
	public function filter_dash_module_add_item( $module, $id, $theme )
	{
		$modules = Modules::get_all();
		if ( $modules ) {
			$modules = array_combine( array_values( $modules ), array_values( $modules ) );
		}

		$form = new FormUI( 'dash_additem' );
		$form->append( 'select', 'module', 'null:unused' );
		$form->module->options = $modules;
		$form->append( 'submit', 'submit', _t( '+' ) );
		//$form->on_success( array( $this, 'dash_additem' ) );
		$form->properties['onsubmit'] = "dashboard.add(); return false;";
		$theme->additem_form = $form->get();

		$module['content'] = $theme->fetch( 'dash_additem' );
		$module['title'] = _t( 'Add Item' );
		return $module;
	}

	/**
	 * Adds a module to the user's dashboard
	 * @param object form FormUI object
	 */
	public function dash_additem( $form )
	{
		$new_module = $form->module->value;
		Modules::add( $new_module );

		// return false to redisplay the form
		return false;
	}

	/**
	 * Setup the default admin javascript stack here so that it can be called
	 * from plugins, etc. This is not an ideal solution, but works for now.
	 *
	 */
	public static function setup_stacks()
	{
		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/jquery.js", 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/jquery-ui.min.js", 'jquery.ui', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/jquery.color.js", 'jquery.color', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/jquery.ui.nestedSortable.js", 'jquery-nested-sortable', 'jquery.ui' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/humanmsg/humanmsg.js", 'humanmsg', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/jquery.hotkeys.js", 'jquery.hotkeys', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'admin_theme' ) . "/js/media.js", 'media', 'jquery' );
		Stack::add( 'admin_header_javascript', Site::get_url( 'admin_theme' ) . "/js/admin.js", 'admin', 'jquery' );

		Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/crc32.js", 'crc32' );
	}

	public function create_theme()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
	}

}
?>
