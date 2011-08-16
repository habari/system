<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminPostsHandler Class
 * Handles posts-related actions in the admin
 *
 */
class AdminPostsHandler extends AdminHandler
{
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
	 * Assign values needed to display the posts page to the theme based on handlervars and parameters
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
	 * Handles GET requests to /admin/posts.
	 *
	 */
	public function get_posts()
	{
		$this->post_posts();
	}

	/**
	 * Handles POST values from /manage/posts.
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

		$ar = new AjaxResponse();
		$ar->data = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		$ar->out();
	}

	/**
	 * Handles AJAX from /manage/posts.
	 * Used to delete posts.
	 */
	public function ajax_update_posts( $handler_vars )
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
					$response->message = _t( 'You did not have permission to delete some posts.' );
				}
				else {
					$response->message = sprintf( _n( 'Deleted %d post', 'Deleted %d posts', count( $ids ) ), count( $ids ) );
				}
				break;
			default:
				// Specific plugin-supplied action
				Plugins::act( 'admin_posts_action', $response, $handler_vars['action'], $posts );
				break;
		}

		$response->out();
		exit;
	}
}
