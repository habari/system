<?php

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
		if( Plugins::id_from_file($file) == Plugins::id_from_file(__FILE__) ) {
			Modules::add( 'Latest Entries' );
			Modules::add( 'Latest Comments' );
			Modules::add( 'Latest Log Activity' );
		}
	}

	/**
	 * action_plugin_deactivation
	 * Unregisters the core modules.
	 * @param string $file plugin file
	 */
	function action_plugin_deactivation( $file )
	{
		if( Plugins::id_from_file($file) == Plugins::id_from_file(__FILE__) ) {
			Modules::remove_by_name( 'Latest Entries' );
			Modules::remove_by_name( 'Latest Comments' );
			Modules::remove_by_name( 'Latest Log Activity' );
		}
	}

	/**
	 * filter_dash_modules
	 * Registers the core modules with the Modules class. 
	 */
	function filter_dash_modules( $modules )
	{
		array_push( $modules, 'Latest Entries', 'Latest Comments', 'Latest Log Activity' );
		
		$this->add_template( 'dash_logs', dirname( __FILE__ ) . '/dash_logs.php' );
		$this->add_template( 'dash_latestentries', dirname( __FILE__ ) . '/dash_latestentries.php' );
		$this->add_template( 'dash_latestcomments', dirname( __FILE__ ) . '/dash_latestcomments.php' );
		
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
		if ( FALSE === ( $num_logs = Modules::get_option( $module_id, 'logs_number_display' ) ) ) {
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
		$form = new FormUI( 'dash_logs' );
		$form->append( 'text', 'logs_number_display', 'option:' . Modules::storage_name( $module_id, 'logs_number_display' ), _t('Number of items') );
		$form->append( 'submit', 'submit', _t('Submit') );
		$form->properties['onsubmit'] = "dashboard.updateModule({$module_id}); return false;";
		
		$module['title'] = ( User::identify()->can( 'manage_logs' ) ? '<a href="' . Site::get_url('admin') . '/logs">' . _t('Latest Log Activity') . '</a>' : _t('Latest Log Activity') );
		$module['options'] = $form->get();
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
}

?>