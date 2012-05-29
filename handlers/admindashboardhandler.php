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
		if ( $firstpostdate ) {
			$this->theme->active_time = HabariDateTime::date_create( $firstpostdate );
		}

		// check to see if we have updates to display
		$this->theme->updates = Options::get( 'updates_available', array() );

		// collect all the stats we display on the dashboard
		$user = User::identify();
		$this->theme->stats = array(
			'author_count' => Users::get( array( 'count' => 1 ) ),
			'post_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'published' ) ) ),
			'comment_count' => Comments::count_total( Comment::STATUS_APPROVED, false ),
			'tag_count' => Tags::vocabulary()->count_total(),
			'user_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'draft' ), 'user_id' => $user->id ) ),
			'unapproved_comment_count' => User::identify()->can( 'manage_all_comments' ) ? Comments::count_total( Comment::STATUS_UNAPPROVED, false ) : Comments::count_by_author( User::identify()->id, Comment::STATUS_UNAPPROVED ),
			'spam_comment_count' => $user->can( 'manage_all_comments' ) ? Comments::count_total( Comment::STATUS_SPAM, false ) : Comments::count_by_author( $user->id, Comment::STATUS_SPAM ),
			'user_scheduled_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'scheduled' ), 'user_id' => $user->id ) ),
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
			$modules[$id] = $this->get_module( $id, $module_name );
		}

		$this->theme->modules = $modules;
	}
	
	/**
	 * A simple helper to build out a module
	 */
	private function get_module( $id, $module_name )
	{		
		$slug = Utils::slugify( (string) $module_name, '_' );
		$module = array(
			'name' => $module_name,
			'title' => $module_name,
			'content' => ''
			);
			
		$module['options'] = new FormUI( 'dash_module_options_' . $id );
		$module['options']->ajax = true;
		$module['options']->append( 'hidden', 'module_id', 'null:null' );
		$module['options']->module_id->id = 'module_id';
		$module['options']->module_id->value = $id;
		$module['options']->append( 'submit', 'save', _t('Save') );
		
		$module = Plugins::filter( 'dash_module_' . $slug, $module, $id, $this->theme );
		
		if( $module['options'] instanceof FormUI )
		{
			if( count($module['options']->controls) > 1 )
			{				
				// we've added controls, so display the options form
				$module['form'] = $module['options'];
				$module['options'] = $module['options']->get();
			}
			else
			{
				// we haven't done anything, so there are no options
				$module['options'] = false;
			}
			
		}
		
		return $module;
	}

	/**
	 * Handles AJAX requests from the dashboard
	 */
	public function ajax_dashboard( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$this->create_theme();

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
			case 'updateModule':
				$slugger = explode( ':', $handler_vars['slugger'], 2);
				
				$module = $this->get_module( $slugger[0], $slugger[1] );
				
				print_r( $module['form']->content );
				
				$ar = new AjaxResponse( 200, _t( 'Module updated.' ) );
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
