<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }

/**
 * CoreDashModules - Provides a core set of dashboard modules for the dashboard.
 */

class CoreDashModules extends Plugin
{
	private $theme;

		/**
	 * action_plugin_activation
	 * Registers the core modules with the Modules class. Add these modules to the
	 * dashboard if the dashboard is currently empty.
	 * @param string $file plugin file
	 */
	function action_plugin_activation( $file )
	{
		if ( Plugins::id_from_file($file) == Plugins::id_from_file(__FILE__) ) {
			Modules::add( 'Latest Entries' );
			Modules::add( 'Latest Comments' );
			Modules::add( 'Latest Log Activity' );
			Modules::add( 'Post Types and Statuses' );
		}
	}

	/**
	 * action_plugin_deactivation
	 * Unregisters the core modules.
	 * @param string $file plugin file
	 */
	function action_plugin_deactivation( $file )
	{
		if ( Plugins::id_from_file($file) == Plugins::id_from_file(__FILE__) ) {
			Modules::remove_by_name( 'Latest Entries' );
			Modules::remove_by_name( 'Latest Comments' );
			Modules::remove_by_name( 'Latest Log Activity' );
			Modules::remove_by_name( 'Post Types and Statuses' );
		}
	}

	/**
	 * filter_dash_modules
	 * Registers the core modules with the Modules class. 
	 */
	function filter_dash_modules( $modules )
	{
		$modules[] = 'Latest Entries';
		if (User::identify()->can('manage_all_comments')) {
			$modules[] = 'Latest Comments';
		}
		if (User::identify()->can('manage_logs')) {
			$modules[] = 'Latest Log Activity';
		}
		$modules[] = 'Post Types and Statuses';
		$this->add_template( 'dash_logs', dirname( __FILE__ ) . '/dash_logs.php' );
		$this->add_template( 'dash_latestentries', dirname( __FILE__ ) . '/dash_latestentries.php' );
		$this->add_template( 'dash_latestcomments', dirname( __FILE__ ) . '/dash_latestcomments.php' );
		$this->add_template( 'dash_posttypes', dirname( __FILE__ ) . '/dash_posttypes.php' );
		
		return $modules;
	}
	
	/**
	 * filter_dash_module_latest_log_activity
	 * Sets theme variables and handles logic for the
	 * dashboard's log history module.
	 * @param string $module_id
	 * @return string The contents of the module
	 */
	public function filter_dash_module_latest_log_activity( $module, $module_id, $theme )
	{
		if ( false === ( $num_logs = Modules::get_option( $module_id, 'logs_number_display' ) ) ) {
			$num_logs = 8;
		}

		$params = array(
			'where' => array(
				'user_id' => User::identify()->id
			),
			'orderby' => 'id DESC', /* Otherwise, exactly same timestamp values muck it up... Plus, this is more efficient to sort on the primary key... */
			'limit' => $num_logs,
		);
		$theme->logs = EventLog::get( $params );
		
		// Create options form
		/* Commented out until fully implemented or it's decided to drop completely. See https://trac.habariproject.org/habari/ticket/1233
		$form = new FormUI( 'dash_logs' );
		$form->append( 'text', 'logs_number_display', 'option:' . Modules::storage_name( $module_id, 'logs_number_display' ), _t('Number of items') );
		$form->append( 'submit', 'submit', _t('Submit') );
		$form->properties['onsubmit'] = "dashboard.updateModule({$module_id}); return false;";
		 */
		
		$module['title'] = ( User::identify()->can( 'manage_logs' ) ? '<a href="' . Site::get_url('admin') . '/logs">' . _t('Latest Log Activity') . '</a>' : _t('Latest Log Activity') );
		//$module['options'] = $form->get();
		$module['content'] = $theme->fetch( 'dash_logs' );
		return $module;
	}
	
	/**
	 * filter_dash_module_latest_entries
	 * Gets the latest entries module
	 * @param string $module_id
	 * @return string The contents of the module
	 */
	public function filter_dash_module_latest_entries( $module, $module_id, $theme )
	{
		$theme->recent_posts = Posts::get( array( 'status' => 'published', 'limit' => 8, 'type' => Post::type('entry') ) );
		
		$module['title'] = ( User::identify()->can( 'manage_entries' ) ? '<a href="' . Site::get_url('admin') . '/posts?type=1">' . _t('Latest Entries') . '</a>' : _t('Latest Entries') );
		$module['content'] = $theme->fetch( 'dash_latestentries' );
		return $module;
	}

	/**
	 * filter_dash_module_latest_comments
	 * Function used to set theme variables to the latest comments dashboard widget
	 * @param string $module_id
	 * @return string The contents of the module
	 */
	public function filter_dash_module_latest_comments( $module, $module_id, $theme )
	{
		$post_ids = DB::get_results( 'SELECT DISTINCT post_id FROM ( SELECT date, post_id FROM {comments} WHERE status = ? AND type = ? ORDER BY date DESC, post_id ) AS post_ids LIMIT 5', array( Comment::STATUS_APPROVED, Comment::COMMENT ), 'Post' );
		$posts = array();
		$latestcomments = array();

		foreach( $post_ids as $comment_post ) {
			$post = DB::get_row( 'select * from {posts} where id = ?', array( $comment_post->post_id ) , 'Post' );
			$comments = DB::get_results( 'SELECT * FROM {comments} WHERE post_id = ? AND status = ? AND type = ? ORDER BY date DESC LIMIT 5;', array( $comment_post->post_id, Comment::STATUS_APPROVED, Comment::COMMENT ), 'Comment' );
			$posts[] = $post;
			$latestcomments[$post->id] = $comments;
		}

		$theme->latestcomments_posts = $posts;
		$theme->latestcomments = $latestcomments;
		
		$module['title'] = ( User::identify()->can( 'manage_comments' ) ? '<a href="' . Site::get_url('admin') . '/comments">' . _t('Latest Comments') . '</a>' : _t('Latest Comments') );
		$module['content'] = $theme->fetch( 'dash_latestcomments' );
		return $module;
	}

	/**
	 * filter_dash_module_post_types
	 * Function used to set theme variables to the post types dashboard widget
	 * @param string $module_id
	 * @return string The contents of the module
	 */
	public function filter_dash_module_post_types_and_statuses( $module, $module_id, $theme )
	{
		$messages = array();
		$user = User::identify();

		$post_types = Post::list_active_post_types();
		array_shift( $post_types );
		$post_statuses = array_values( Post::list_post_statuses() );
		array_shift( $post_statuses );

		foreach( $post_types as $type => $type_id ) {
			$plural = Plugins::filter( 'post_type_display', $type, 'plural' );
			foreach( $post_statuses as $status => $status_id ) {
				$site_count = Posts::get( array( 'content_type' => $type_id, 'count' => true, 'status' => $status_id ) );
				$user_count = Posts::get( array( 'content_type' => $type_id, 'count' => true, 'status' => $status_id, 'user_id' => $user->id ) );

				// @locale First variable is the post status, second is the post type
				$message['label'] = _t( '%1$s %2$s ', array( ucfirst( Post::status_name( $status_id ) ), $plural ) );

				if( ! $site_count ) {
					$message['site_count'] = '';
				}
				if( $user->cannot( 'post_unpublished' ) && Post::status_name( $status_id ) != 'published' ) {
					$message['site_count'] = '';
				}
				else {
					$message['site_count'] = $site_count;
				}
				$perms = array(
					'post_any' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
					'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
					'post_' . $type => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				);
				if ( $user->can_any( $perms ) && $message['site_count'] ) {
					$message['site_count'] = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( $type ), 'status' => $status_id ) ) ) . '">' . Utils::htmlspecialchars( $message['site_count'] ) . '</a>';
				}

				if( ! $user_count ) {
					$message['user_count'] = '';
				}
				else {
					$message['user_count'] = $user_count;
				}
				// @locale First variable is the post status, second is the post type
				$perms = array(
					'own_posts' => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
					'post_' . $type => array( ACL::get_bitmask( 'delete' ), ACL::get_bitmask( 'edit' ) ),
				);
				if ( $user->can_any( $perms )  && $message['user_count'] ) {
					$message['user_count'] = '<a href="' . Utils::htmlspecialchars( URL::get( 'admin', array( 'page' => 'posts', 'type' => Post::type( $type ), 'status' => $status_id, 'user_id' => $user->id ) ) ) . '">' . Utils::htmlspecialchars( $message['user_count'] ) . '</a>';
				}

				if( $message['site_count'] || $message['user_count'] ) {
					$messages[] = $message;
				}
			}
		}

		$theme->type_messages = $messages;

		$module['title'] = _t( 'Post Types and Statuses' );
		$module['content'] = $theme->fetch( 'dash_posttypes' );
		return $module;
	}

	/**
	* Adds the podcast stylesheet to the admin header,
	* Adds menu items to the Habari silo for mp3 files
	* for each feed so the mp3 can be added to multiple 
	* feeds.
	*
	* @param Theme $theme The current theme being used.
	*/
	public function action_admin_header( $theme )
	{
		$vars = Controller::get_handler_vars();
		if( 'dashboard' == $theme->page ) {
			$css = <<< MODULE_CSS
.dashboard .post-types-and-statuses-module .modulecore{
	overflow: auto;
}
MODULE_CSS;
			Stack::add( 'admin_stylesheet', array( $css, 'screen' ), 'dash_modules', array( 'admin' ) );
		}
	}

}

?>
