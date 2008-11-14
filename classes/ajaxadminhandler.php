<?php
class AjaxAdminHandler extends ActionHandler
{
	/**
	 * Verifies user credentials before creating the theme and displaying the request.
	 * @TODO Ajax doesn't need most of this stuff!
	 */
	public function __construct()
	{
		$user = User::identify();
		if ( !$user ) {
			Session::error( _t('Your session expired.'), 'expired_session' );
			Session::add_to_set( 'login', $_SERVER['REQUEST_URI'], 'original' );
			if( URL::get_matched_rule()->name == 'admin_ajax' ) {
				echo '{callback: function(){location.href="'.$_SERVER['HTTP_REFERER'].'"} }';
			}
			else {
			if ( !empty( $_POST ) ) {
				Session::add_to_set( 'last_form_data', $_POST, 'post' );
				Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
			}
			if ( !empty( $_GET ) ) {
				Session::add_to_set( 'last_form_data', $_GET, 'get' );
				Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
			}
			Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
			}
			exit;
		}
		/* TODO: update ACL class so that this works
		if ( !$user->can( 'admin' ) ) {
			die( _t( 'Permission denied.' ) );
		}
		//*/
		$last_form_data = Session::get_set( 'last_form_data' ); // This was saved in the "if ( !$user )" above, UserHandler transferred it properly.
		/* At this point, Controller has not created handler_vars, so we have to modify $_POST/$_GET. */
		if ( isset( $last_form_data['post'] ) ) {
			$_POST = array_merge( $_POST, $last_form_data['post'] );
			$_SERVER['REQUEST_METHOD']= 'POST'; // This will trigger the proper act_admin switches.
			Session::remove_error( 'expired_form_submission' );
		}
		if ( isset( $last_form_data['get'] ) ) {
			$_GET = array_merge( $_GET, $last_form_data['get'] );
			Session::remove_error( 'expired_form_submission' );
			// No need to change REQUEST_METHOD since GET is the default.
		}
		$user->remember();

		// Create an instance of the active public theme so that its plugin functions are implemented
		$this->active_theme = Themes::create();
	}
	
	/**
	 * Handle incoming requests to /admin_ajax for admin ajax requests
	 */
	public function act_admin_ajax()
	{
		$context = $this->handler_vars['context'];
		if ( method_exists( $this, 'ajax_' . $context ) ) {
			call_user_func( array( $this, 'ajax_' . $context ), $this->handler_vars );
		}
		else {
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}
	}
	
	/**
	 * handles AJAX from /users
	 * used to delete users and fetch new ones
	 */
	public function ajax_update_users($handler_vars)
	{
		echo json_encode( $this->update_users( $handler_vars ) );
	}
	
	/**
	 * Handles ajax requests from the dashboard
	 */
	public function ajax_dashboard( $handler_vars )
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
	 * Handles ajax requests from the manage posts page
	 */
	public function ajax_posts()
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
	 * Handles ajax requests from the manage comments page
	 */
	public function ajax_comments()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
		$this->theme->theme = $this->theme;

		$params = $_POST;

		$this->fetch_comments( $params );
		$items = $this->theme->fetch( 'comments_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach($this->theme->comments as $comment) {
			$item_ids['p' . $comment->id]= 1;
		}

		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}

	/**
	 * Handles ajax requests from the manage users page
	 */
	public function ajax_users()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$this->theme->currentuser = User::identify();
		$items = $this->theme->fetch( 'users_items' );

		$output = array(
			'items' => $items,
		);
		echo json_encode($output);
	}

	/**
	 * handles AJAX from /comments
	 * used to edit comments inline
	 */
	public function ajax_in_edit($handler_vars)
	{

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$comment = Comment::get($handler_vars['id']);

		if(isset($handler_vars['author']) && $handler_vars['author'] != '') {
			$comment->name = $handler_vars['author'];
		}
		if(isset($handler_vars['url']) && $handler_vars['url'] != '') {
			$comment->url = $handler_vars['url'];
		}
		if(isset($handler_vars['email']) && $handler_vars['email'] != '') {
			$comment->email = $handler_vars['email'];
		}
		if(isset($handler_vars['content']) && $handler_vars['content'] != '') {
			$comment->content = $handler_vars['content'];
		}
		if(isset($handler_vars['time']) && $handler_vars['time'] != '' && isset($handler_vars['date']) && $handler_vars['date'] != '') {
			$seconds = date('s', strtotime($comment->date));
			$date = date('Y-m-d H:i:s', strtotime($handler_vars['date'] . ' ' . $handler_vars['time'] . ':' . $seconds));
			$comment->date = $date;
		}

		$comment->update();

		Session::notice( _t('Updated 1 comment.') );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

	/**
	 * handles AJAX from /manage/entries
	 * used to delete entries
	 */
	public function ajax_delete_entries($handler_vars)
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
			if ( preg_match( '/^p\d+/', $id )  && $delete ) {
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
	 * handles AJAX from /logs
	 * used to delete logs
	 */
	public function ajax_delete_logs($handler_vars)
	{
		$count = 0;

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		foreach($_POST as $id => $delete) {
			// skip POST elements which are not log ids
			if ( preg_match( '/^p\d+/', $id ) && $delete ) {
				$id = substr($id, 1);

				$ids[]= array( 'id' => $id );

			}
		}

		$to_delete = EventLog::get( array( 'date' => 'any', 'where' => $ids, 'nolimit' => 1 ) );

		$logstatus = array( 'Deleted %d logs' => 0 );
		foreach ( $to_delete as $log ) {
			$log->delete();
			$count++;
	}
		foreach ( $logstatus as $key => $value ) {
			if ( $value ) {
				Session::notice( sprintf( _t( $key ), $value ) );
			}
		}

		Session::notice( sprintf( _t('Deleted %d logs.'), $count ) );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

	public function ajax_update_comment( $handler_vars )
	{
		// check WSSE authentication
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$ids = array();

		foreach($_POST as $id => $update) {
			// skip POST elements which are not comment ids
			if ( preg_match( '/^p\d+/', $id )  && $update ) {
				$ids[] = substr($id, 1);
			}
		}

		$comments = Comments::get( array( 'id' => $ids, 'nolimit' => true ) );
		if ( $comments === FALSE ) {
			Session::notice( _t('No comments selected.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		Plugins::act( 'admin_moderate_comments', $handler_vars['action'], $comments, $this );
		$status_msg = _t('Unknown action "%s"', array($handler_vars['action']));

		switch ( $handler_vars['action'] ) {
		case 'delete':
			// Comments marked for deletion
			Comments::delete_these( $comments );
			$status_msg = sprintf( _n('Deleted %d comment', 'Deleted %d comments', count( $ids ) ), count( $ids ) );
			break;
		case 'spam':
			// Comments marked as spam
			Comments::moderate_these( $comments, Comment::STATUS_SPAM );
			$status_msg = sprintf( _n('Marked %d comment as spam', 'Marked %d comments as spam', count( $ids ) ), count( $ids ) );
			break;
		case 'approved':
			// Comments marked for approval
			Comments::moderate_these( $comments, Comment::STATUS_APPROVED );
			$status_msg = sprintf( _n('Approved %d comment', 'Approved %d comments', count( $ids ) ), count( $ids ) );
			break;
		case 'unapproved':
			// Comments marked for unapproval
			Comments::moderate_these( $comments, Comment::STATUS_UNAPPROVED );
			$status_msg = sprintf( _n('Unapproved %d comment', 'Unapproved %d comments', count( $ids ) ), count( $ids ) );
			break;
		default:
			// Specific plugin-supplied action
			$status_msg = Plugins::filter( 'admin_comments_action', $status_msg, $handler_vars['action'], $comments );
			break;
		}

		Session::notice( $status_msg );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}
	
	/**
	 * Handles ajax requests from the logs page
	 */
	public function ajax_logs()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params = $_POST;

		$this->fetch_logs( $params );
		$items = $this->theme->fetch( 'logs_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach($this->theme->logs as $log) {
			$item_ids['p' . $log->id]= 1;
		}

		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}
	
	/**
	 * handles AJAX from /admin/tags
	 * used to delete and rename tags
	 */
	public function ajax_tags( $handler_vars)
	{
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$tag_names = array();
		$action = $this->handler_vars['action'];
		switch ( $action ) {
			case 'delete':
				foreach($_POST as $id => $delete) {
					// skip POST elements which are not tag ids
					if ( preg_match( '/^tag_\d+/', $id ) && $delete ) {
						$id = substr($id, 4);
						$tag = Tags::get_by_id($id);
						$tag_names[]= $tag->tag;
						Tags::delete($tag);
					}
				}
				$msg_status = sprintf(
					_n('Tag %s has been deleted.',
							'Tags %s have been deleted.',
							count($tag_names)
					), implode($tag_names, ', ')
				);
				Session::notice( $msg_status );
				echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
				break;
			case 'rename':
				if ( isset($this->handler_vars['master']) ) {
					$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
					$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
					$master = $this->handler_vars['master'];
					$tag_names = array();
					foreach($_POST as $id => $rename) {
						// skip POST elements which are not tag ids
						if ( preg_match( '/^tag_\d+/', $id ) && $rename ) {
							$id = substr($id, 4);
							$tag = Tags::get_by_id($id);
							$tag_names[]= $tag->tag;
						}
					}
					Tags::rename($master, $tag_names);
					$msg_status = sprintf(
						_n('Tag %s has been renamed to %s.',
							 'Tags %s have been renamed to %s.',
							 count($tag_names)
						), implode($tag_names, ', '), $master
					);
					Session::notice( $msg_status );
					echo json_encode( array(
						'msg' => Session::messages_get( true, 'array' ),
						'tags' => $this->theme->fetch( 'tag_collection' ),
						) );
				}
				break;
		}
	}

	public function ajax_media( $handler_vars )
	{
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
				$output['dirs'][$asset->basename]= $asset->get_props();
			}
			else {
				$output['files'][$asset->basename]= $asset->get_props();
			}
		}
		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, '' );
		$output['controls']= '<li>' . implode( '</li><li>', $controls ) . '</li>';

		echo json_encode( $output );
	}

	public function ajax_media_panel( $handler_vars )
	{
		$path = $handler_vars['path'];
		$panelname = $handler_vars['panel'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel = '';
		$panel = Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );
		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls = '<li>' . implode( '</li><li>', $controls ) . '</li>';
		$output = array(
			'controls' => $controls,
			'panel' => $panel,
		);

		header( 'content-type:text/javascript' );
		echo json_encode( $output );
	}
	
	/**
	 * Function used to set theme variables to the add module dashboard widget
	 */
	public function ajax_dash_module_add_item( $module, $id, $theme )
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
}
?>