<?php

class PostsAdminPage extends AdminPage
{
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
			'PasswordDigest' => '',
			'change' => '',
			'user_id' => 0,
			'type' => Post::type( 'entry' ),
			'status' => Post::status( 'any' ),
			'limit' => 20,
			'offset' => 0,
			'search' => '',
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : (isset($params[$varname]) ? $params[varname] : $default);
			$this->theme->{$varname}= $$varname;
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
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				foreach ( $post_ids as $id ) {
					$ids[]= array( 'id' => $id );
				}
				$to_update = Posts::get( array( 'where' => $ids, 'nolimit' => 1 ) );
				foreach ( $to_update as $post ) {
					switch( $change ) {
					case 'delete':
						$post->delete();
						break;
					case 'publish':
						$post->publish();
						break;
					case 'unpublish':
						$post->status = Post::status( 'draft' );
						$post->update();
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
			$this->theme->search_args.= 'type:' . Post::type_name( $type ) . ' ';
		}
		if ( $user_id != 0 ) {
			$this->theme->search_args.= 'author:' . User::get_by_id( $user_id )->username;
		}

		$monthcts = Posts::get( array_merge( $arguments, array( 'month_cts' => 1 ) ) );
		$years = array();
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
	}
			else {
				$years[$month->year]= array( $month );
			}
		}
		if(isset($years)) {
			$this->theme->years = $years;
		}

	}

	/**
	 * Handles GET requests to /admin/entries
	 *
	 */
	public function act_request_get()
	{
		$this->act_request_post();
	}

	/**
	 * handles POST values from /manage/entries
	 * used to control what content to show / manage
	**/
	public function act_request_post()
	{
		$this->fetch_posts();
		// Get special search statuses
		$statuses = array_keys(Post::list_post_statuses());
		array_shift($statuses);
		$statuses = array_combine(
			$statuses,
			array_map(
				create_function('$a', 'return "status:{$a}";'),
				$statuses
			)
		);

		// Get special search types
		$types = array_keys(Post::list_active_post_types());
		array_shift($types);
		$types = array_combine(
			$types,
			array_map(
				create_function('$a', 'return "type:{$a}";'),
				$types
			)
		);
		$this->theme->admin_page = _t('Manage Posts');
		$this->theme->admin_title = _t('Manage Posts');
		$this->theme->special_searches = array_merge($statuses, $types);
		$this->display( 'posts' );
	}

	/**
	 * Handles ajax requests from the manage posts page
	 */
	public function act_ajax_post()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params = $_POST;

		$this->fetch_posts( $params );
		$items = $this->theme->fetch( 'posts_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach($this->theme->posts as $post) {
			$item_ids['p' . $post->id]= 1;
		}

		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}
	
		/**
	 * handles AJAX from /manage/entries
	 * used to delete entries
	 */
	public function act_ajax_delete($handler_vars)
	{
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$ids = array();
		foreach($_POST as $id => $delete) {
			// skip POST elements which are not post ids
			if ( preg_match( '/^p\d+/', $id ) && $delete ) {
				$ids[] = substr($id, 1);
			}
		}
		$posts = Posts::get( array( 'id' => $ids, 'nolimit' => true ) );
		foreach ( $posts as $post ) {
			$post->delete();
		}

		Session::notice( sprintf( _t('Deleted %d entries.'), count($posts) ) );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}
	
	
	/**
	 * Deletes a post from the database.
	 */
	public function post_delete_post()
	{
		$extract = $this->handler_vars->filter_keys('id', 'nonce', 'timestamp', 'PasswordDigest');
		foreach($extract as $key => $value) {
			$$key = $value;
		}

		$okay = TRUE;
		if ( empty( $id ) || empty( $nonce ) || empty( $timestamp ) || empty( $PasswordDigest ) ) {
			$okay = FALSE;
		}
		$wsse = Utils::WSSE( $nonce, $timestamp );
		if ( $digest != $wsse['digest'] ) {
			$okay = FALSE;
		}
		if ( !$okay )	{
			Utils::redirect( URL::get( 'admin', 'page=posts&type='. Post::status( 'any' ) ) );
		}
		$post = Post::get( array( 'id' => $id, 'status' => Post::status( 'any' ) ) );
		$post->delete();
		Session::notice( sprintf( _t( 'Deleted the %1$s titled "%2$s".' ), Post::type_name( $post->content_type ), $post->title ) );
		Utils::redirect( URL::get( 'admin', 'page=posts&type=' . Post::status( 'any' ) ) );
	}
}

?>