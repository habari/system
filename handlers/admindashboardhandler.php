<?php
/**
 * @package Habari
 *
 */

namespace Habari;

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
			$this->theme->active_time = DateTime::create( $firstpostdate );
		}

		// check to see if we have updates to display
		$this->theme->updates = Options::get( 'updates_available', array() );

		// collect all the stats we display on the dashboard
		$user = User::identify();
		$this->theme->stats = array(
			'author_count' => Users::get( array( 'count' => 1 ) ),
			'post_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'published' ) ) ),
			'comment_count' => Comments::count_total( 'approved', false ),
			'tag_count' => Tags::vocabulary()->count_total(),
			'user_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'draft' ), 'user_id' => $user->id ) ),
			'unapproved_comment_count' => User::identify()->can( 'manage_all_comments' ) ? Comments::count_total( 'unapproved', false ) : Comments::count_by_author( User::identify()->id, Comment::status('unapproved') ),
			'spam_comment_count' => $user->can( 'manage_all_comments' ) ? Comments::count_total( 'spam', false ) : Comments::count_by_author( $user->id, Comment::status('spam') ),
			'user_scheduled_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'any' ), 'status' => Post::status( 'scheduled' ), 'user_id' => $user->id ) ),
		);

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

		$this->get_additem_form();

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
	 * Add the Add Item form to the theme for display
	 */
	public function get_additem_form()
	{
		/** @var FormUI $additem_form */
		$additem_form = FormUI::create( 'dash_additem' )->set_properties(array('onsubmit' => 'dashboard.add(); return false;'));
		$additem_form->append( FormControlSelect::create('module')->set_options(Plugins::filter( 'dashboard_block_list', array() )) );
		$additem_form->append( FormControlSubmit::create('submit')->set_caption(_t('+')));
		$this->theme->additem_form = $additem_form->get();
	}

	/**
	 * Handles AJAX requests from the dashboard
	 */
	public function ajax_dashboard( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$this->create_theme();
		$this->get_additem_form();
		$available_modules = Plugins::filter('dashboard_block_list', array());
		$user_id = User::identify()->id;
		$dashboard_area = 'dashboard_' . $user_id;

		switch ( $handler_vars['action'] ) {
			case 'updateModules':
				$modules = $_POST['moduleOrder'];
				$order = 0;
				foreach ( $modules as $module ) {
					$order++;
					DB::query('UPDATE {blocks_areas} SET display_order = :display_order WHERE block_id = :id AND area = :dashboardarea', array('display_order' => $order, 'id' => $module, 'dashboardarea' => $dashboard_area));
				}
				$ar = new AjaxResponse( 200, _t( 'Modules updated.' ) );
				break;
			case 'addModule':
				$type = $handler_vars['module_name'];
				$title = $available_modules[$type];
				$block = new Block( array( 'title' => $title, 'type' => $type ) );
				$block->insert();
				$max_display_order = DB::get_value('SELECT max(display_order) FROM {blocks_areas} WHERE area = :dashboardarea and scope_id = 0;', array('dashboardarea' => $dashboard_area));
				$max_display_order++;
				DB::query( 'INSERT INTO {blocks_areas} (block_id, area, scope_id, display_order) VALUES (:block_id, :dashboardarea, 0, :display_order)', array( 'block_id'=>$block->id, 'display_order'=>$max_display_order, 'dashboardarea' => $dashboard_area ) );

				$ar = new AjaxResponse( 200, _t( 'Added module %s.', array( $title ) ) );
				$ar->html( 'modules', $this->theme->fetch( 'dashboard_modules' ) );
				break;
			case 'removeModule':
				$block_id = $handler_vars['moduleid'];
				DB::delete('{blocks}', array('id' => $block_id));
				DB::delete('{blocks_areas}', array('block_id' => $block_id));
				$ar = new AjaxResponse( 200, _t( 'Removed module.' ) );
				$ar->html( 'modules', $this->theme->fetch( 'dashboard_modules' ) );
				break;
			case 'configModule':
				$block_id = $handler_vars['moduleid'];

				$block = DB::get_row('SELECT * FROM {blocks} b WHERE b.id = :id', array('id' => $block_id), 'Block');

				/** Block $block */
				$form = $block->get_form();
//				$form->_ajax = true;
				// @todo There's got to be a better way
				$form->set_settings( array( 'success_message' => '<p  class="form_message">' . _t('Module Configuration Saved.') . '</p>' . '<script type="text/javascript">window.setTimeout(function(){$(".form_message").fadeOut();}, 2000);</script>' ));

				$form->append( FormControlHidden::create( 'moduleid', null, array( 'id' => 'moduleid' ) )->set_value( $block->id ) );
				$form->append( FormControlHidden::create( 'action', null, array( 'id' => 'action' ) )->set_value( 'configModule' ) );

				$form->out();
				$form_id = $form->name;
				exit;
				break;
		}

		$ar->out();
	}
}
