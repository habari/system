<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminDashboardHandler Class
 * Handles dashboard-related actions in the admin
 *
 */
class AdminDashboardHandler extends AdminHandler
{
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
	 * Handles POST requests from the dashboard.
	 */
	public function post_dashboard()
	{
		$this->get_dashboard();
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

}
