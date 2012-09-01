<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }

/**
 * CoreDashModules - Provides a core set of dashboard modules for the dashboard.
 */

class CoreDashModules extends Plugin
{
	private $theme;

	/**
	 * Add some templates to the theme for the blocks
	 */
	public function action_init()
	{
		$this->add_template( 'dashboard.block.latest_entries', __DIR__ . '/dashboard.block.latest_entries.php' );
		$this->add_template( 'dashboard.block.latest_log_activity', __DIR__ . '/dashboard.block.latest_log_activity.php' );
		$this->add_template( 'dashboard.block.latest_comments', __DIR__ . '/dashboard.block.latest_comments.php' );
		$this->add_template( 'dashboard.block.post_types_statuses', __DIR__ . '/dashboard.block.post_types_statuses.php' );
	}

	/**
	 * Add the blocks this plugin provides to the list of available blocks
	 * @param array $block_list An array of block names, indexed by unique string identifiers
	 * @return array The altered array
	 */
	public function filter_block_list($block_list)
	{
		$block_list['latest_entries'] = _t( 'Latest Entries');
		if (User::identify()->can('manage_all_comments')) {
			$block_list['latest_comments'] = _t( 'Latest Comments');
		}
		if (User::identify()->can('manage_logs')) {
			$block_list['latest_log_activity'] = _t( 'Latest Log Activity');
		}
		$block_list['post_types_statuses'] = _t( 'Post Types and Statuses');
		return $block_list;
	}

	/**
	 * Return a list of blocks that can be used for the dashboard
	 * @param array $block_list An array of block names, indexed by unique string identifiers
	 * @return array The altered array
	 */
	public function filter_dashboard_block_list($block_list)
	{
		return $this->filter_block_list($block_list);
	}

	/**
	 * Produce the content for the latest entries block
	 * @param Block $block The block object
	 * @param Theme $theme The theme that the block will be output with
	 */
	public function action_block_content_latest_entries($block, $theme)
	{
		$block->recent_posts = Posts::get( array( 'status' => 'published', 'limit' => 8, 'type' => Post::type('entry') ) );
		$block->link = URL::get('admin', array('page'=>'posts', 'type'=>Post::type('entry')));
	}

	/**
	 * Produce the content for the latest log activity block
	 * @param Block $block The block object
	 * @param Theme $theme The theme that the block will be output with
	 */
	public function action_block_content_latest_log_activity($block, $theme)
	{
		$params = array(
			'where' => array(
				'user_id' => User::identify()->id
			),
			'orderby' => 'id DESC', /* Otherwise, exactly same timestamp values muck it up... Plus, this is more efficient to sort on the primary key... */
			'limit' => isset($block->logs_number_display) ? $block->logs_number_display : 8,
		);
		$block->logs = EventLog::get( $params );
		$block->link = URL::get('admin', array('page' => 'logs'));
		$block->has_options = true;
	}

	/**
	 * Produce a form for the editing of the log activity block
	 * @param FormUI $form The form to allow editing of this block
	 * @param Block $block The block object to edit
	 */
	public function action_block_form_latest_log_activity($form, $block)
	{
		$form->append( 'text', 'logs_number_display', $block, _t('Number of items') );
		$form->append( 'submit', 'submit', _t('Submit') );
	}

	/**
	 * Produce the content for the Latest Comments block
	 * @param Block $block The block object
	 * @param Theme $theme The theme that the block will be output with
	 */
	public function action_block_content_latest_comments($block, $theme)
	{
		$comment_types = array( Comment::COMMENT );
		$query = 'SELECT {posts}.* FROM {comments}, {posts} WHERE {posts}.status = ? AND {comments}.status = ? AND ({comments}.type = ?' . str_repeat(' OR {comments}.type = ?', count($comment_types) - 1) . ') AND {posts}.id = post_id ORDER BY {comments}.date DESC LIMIT 25';
		$query_args = array_merge( array( Post::status( 'published' ), Comment::STATUS_APPROVED ), $comment_types );
		$posts = DB::get_results( $query, $query_args, 'Post' );

		$latestcomments = array();
		foreach( $posts as $post ) {
			$comments = DB::get_results( 'SELECT * FROM {comments} WHERE post_id = ? AND status = ? AND type = ? ORDER BY date DESC LIMIT 5;', array( $post->id, Comment::STATUS_APPROVED, Comment::COMMENT ), 'Comment' );
			$latestcomments[$post->id] = $comments;
		}

		$block->latestcomments_posts = $posts;
		$block->latestcomments = $latestcomments;
		$block->link = URL::get('admin', array('page' => 'comments'));
	}

	public function action_block_content_post_types_statuses($block, $theme)
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
				$status_display = MultiByte::ucfirst( Plugins::filter( 'post_status_display', Post::status_name( $status_id ) ) );
				$site_count = Posts::get( array( 'content_type' => $type_id, 'count' => true, 'status' => $status_id ) );
				$user_count = Posts::get( array( 'content_type' => $type_id, 'count' => true, 'status' => $status_id, 'user_id' => $user->id ) );
				$message = array();

				// @locale First variable is the post status, second is the post type
				$message['label'] = _t( '%1$s %2$s', array( $status_display, $plural ) );

				if( ! $site_count ) {
					$message['site_count'] = '';
				}
				else if( $user->cannot( 'post_unpublished' ) && Post::status_name( $status_id ) != 'published' ) {
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

		$block->messages = $messages;
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
				$status_display = MultiByte::ucfirst( Plugins::filter( 'post_status_display', Post::status_name( $status_id ) ) );
				$site_count = Posts::get( array( 'content_type' => $type_id, 'count' => true, 'status' => $status_id ) );
				$user_count = Posts::get( array( 'content_type' => $type_id, 'count' => true, 'status' => $status_id, 'user_id' => $user->id ) );

				// @locale First variable is the post status, second is the post type
				$message['label'] = _t( '%1$s %2$s', array( $status_display, $plural ) );

				if( ! $site_count ) {
					$message['site_count'] = '';
				}
				else if( $user->cannot( 'post_unpublished' ) && Post::status_name( $status_id ) != 'published' ) {
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
			Stack::add( 'admin_stylesheet', array( $this->get_url() . '/coredashmodules.css', 'screen' ), 'coredashmodules', array( 'admin' ) );
		}
	}

}

?>
