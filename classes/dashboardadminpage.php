<?php

class DashboardAdminPage extends AdminPage
{
	public function act_request_get()
	{
		// Not sure how best to determine this yet, maybe set an option on install, maybe do this:
		$firstpostdate = DB::get_value('SELECT min(pubdate) FROM {posts} WHERE status = ?', array(Post::status('published')));
		if ( intval( $firstpostdate ) !== 0 ) $firstpostdate = time() - $firstpostdate;
		$this->theme->active_time = array(
			'years' => floor($firstpostdate / 31556736),
			'months' => floor(($firstpostdate % 31556736) / 2629728),
			'days' => round(($firstpostdate % 2629728) / 86400),
		);

		// if the active plugin list has changed, expire the updates cache
		if ( Cache::has( 'dashboard_updates' ) && ( Cache::get( 'dashboard_updates_plugins' ) != Options::get( 'active_plugins' ) ) ) {
			Cache::expire( 'dashboard_updates' );
		}

		/*
		 * Check for updates to core and any hooked plugins
		 * cache the output so we don't make a request every load but can still display updates
		 */
		if ( Cache::has( 'dashboard_updates' ) ) {
			$this->theme->updates = Cache::get( 'dashboard_updates' );
		}
		else {
			$updates = Update::check();

			if ( !Error::is_error( $updates ) ) {
				Cache::set( 'dashboard_updates', $updates );
				$this->theme->updates = $updates;

				// cache the set of plugins we just used to check for
				Cache::set( 'dashboard_updates_plugins', Options::get( 'active_plugins' ) );
			}
			else {
				$this->theme->updates = array();
			}
		}

		$this->theme->stats = array(
			'author_count' => Users::get( array( 'count' => 1 ) ),
			'page_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('page'), 'status' => Post::status('published') ) ),
			'entry_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('entry'), 'status' => Post::status('published') ) ),
			'comment_count' => Comments::count_total( Comment::STATUS_APPROVED, FALSE ),
			'tag_count' => DB::get_value('SELECT count(id) FROM {tags}'),
			'page_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('page'), 'status' => Post::status('draft'), 'user_id' => User::identify()->id ) ),
			'entry_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('entry'), 'status' => Post::status('draft'), 'user_id' => User::identify()->id ) ),
			'unapproved_comment_count' => Comments::count_total( Comment::STATUS_UNAPPROVED, FALSE ),
			'user_entry_scheduled_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'entry'), 'status' => Post::status( 'scheduled' ), 'user_id' => User::identify()->id ) ),
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

		// append the 'Add Item' module
		$modules['nosort'] = _t('Add Item');

		// register the 'Add Item' filter
		Plugins::register( array( $this, 'filter_dash_module_add_item' ), 'filter', 'dash_module_add_item');

		foreach( $modules as $id => $module_name ) {
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
	 * Function used to set theme variables to the add module dashboard widget
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
		$form->append( 'submit', 'submit', _t('+') );
		//$form->on_success( array( $this, 'dash_additem' ) );
		$form->properties['onsubmit'] = "dashboard.add(); return false;";
		$theme->additem_form = $form->get();

		$module['content'] = $theme->fetch( 'dash_additem' );
		return $module;
	}
	
	/**
	 * Handles ajax requests from the dashboard
	 */
	public function act_ajax_post( $handler_vars )
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		switch ( $handler_vars['action'] ) {
		case 'updateModules':
			$modules = array();
			foreach($_POST as $key => $module ) {
				// skip POST elements which are not module names
				if ( preg_match( '/^module\d+$/', $key ) ) {
					list( $module_id, $module_name ) = split( ':', $module, 2 );
					// remove non-sortable modules from the list
					if ( $module_id != 'nosort' ) {
						$modules[$module_id] = $module_name;
					}
				}
			}

			Modules::set_active( $modules );
			echo json_encode( true );
			break;
		case 'addModule':
			$id = Modules::add( $handler_vars['module_name'] );
			$this->fetch_dashboard_modules();
			$result = array(
				'message' => "Added module {$handler_vars['module_name']}.",
				'modules' => $this->theme->fetch( 'dashboard_modules' ),
			);
			echo json_encode( $result );
			break;
		case 'removeModule':
			Modules::remove( $handler_vars['moduleid'] );
			$this->fetch_dashboard_modules();
			$result = array(
				'message' => 'Removed module',
				'modules' => $this->theme->fetch( 'dashboard_modules' ),
			);
			echo json_encode( $result );
			break;
		}
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