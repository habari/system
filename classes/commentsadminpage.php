<?php

class CommentsAdminPage extends AdminPage
{
	public function act_request_get()
	{
		$this->act_request_post();
	}

	/**
	 * Handles the submission of the comment moderation form.
	 * @todo Separate delete from "delete until purge"
	 */
	public function act_request_post()
	{
		// Get special search statuses
		$statuses = Comment::list_comment_statuses();
		$statuses = array_combine(
			$statuses,
			array_map(
				create_function('$a', 'return "status:{$a}";'),
				$statuses
			)
		);

		// Get special search types
		$types = Comment::list_comment_types();
		$types = array_combine(
			$types,
			array_map(
				create_function('$a', 'return "type:{$a}";'),
				$types
			)
		);

		$this->theme->special_searches = array_merge($statuses, $types);

		$this->fetch_comments();
		$this->display( 'comments' );
	}
	
		/**
	 * Handles ajax requests from the manage comments page
	 */
	public function act_ajax_post()
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

	public function fetch_comments( $params = array() )
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals = array(
			'do_delete' => false,
			'do_spam' => false,
			'do_approve' => false,
			'do_unapprove' => false,
			'comment_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'mass_spam_delete' => null,
			'mass_delete' => null,
			'type' => 'All',
			'limit' => 20,
			'offset' => 0,
			'search' => '',
			'status' => 'All',
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : (isset($params[$varname]) ? $params[$varname] : $default);
			$this->theme->{$varname}= $$varname;
		}

		// Setting these mass_delete options prevents any other processing.  Desired?
		if ( isset( $mass_spam_delete ) && $status == Comment::STATUS_SPAM ) {
			// Delete all comments that have the spam status.
			Comments::delete_by_status( Comment::STATUS_SPAM );
			// let's optimize the table
			$result = DB::query('OPTIMIZE TABLE {comments}');
			Session::notice( _t( 'Deleted all spam comments' ) );
			Utils::redirect();
		}
		elseif ( isset( $mass_delete ) && $status == Comment::STATUS_UNAPPROVED ) {
			// Delete all comments that are unapproved.
			Comments::delete_by_status( Comment::STATUS_UNAPPROVED );
			Session::notice( _t( 'Deleted all unapproved comments' ) );
			Utils::redirect();
		}
		// if we're updating posts, let's do so:
		elseif ( ( $do_delete || $do_spam || $do_approve || $do_unapprove ) && isset( $comment_ids )) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				if ( $do_delete ) {
					$action = 'delete';
				}
				elseif ( $do_spam ) {
					$action = 'spam';
				}
				elseif ( $do_approve ) {
					$action = 'approve';
				}
				elseif ( $do_unapprove ) {
					$action = 'unapprove';
				}
				$ids = array();
				foreach ( $comment_ids as $id => $id_value ) {
					if ( ! isset( ${'$comment_ids['.$id.']'} ) ) { // Skip unmoderated submitted comment_ids
						$ids[]= $id;
					}
				}
				$to_update = Comments::get( array( 'id' => $ids ) );
				$modstatus = array( 'Deleted %d comments' => 0, 'Marked %d comments as spam' => 0, 'Approved %d comments' => 0, 'Unapproved %d comments' => 0, 'Edited %d comments' => 0 );
				Plugins::act( 'admin_moderate_comments', $action, $to_update, $this );

				switch ( $action ) {

					case 'delete':
						// This comment was marked for deletion
						Comments::delete_these( $to_update );
						$modstatus['Deleted %d comments'] = count( $to_update );
						break;

					case 'spam':
							// This comment was marked as spam
						Comments::moderate_these( $to_update, Comment::STATUS_SPAM );
						$modstatus['Marked %d comments as spam'] = count( $to_update );
						break;

					case 'approve':
					case 'approved':
						// Comments marked for approval
						Comments::moderate_these( $to_update, Comment::STATUS_APPROVED );
						$modstatus['Approved %d comments'] = count( $to_update );
						foreach( $to_update as $comment ) {
									$modstatus['Approved comments on these posts: %s']= (isset($modstatus['Approved comments on these posts: %s'])? $modstatus['Approved comments on these posts: %s'] . ' &middot; ' : '') . '<a href="' . $comment->post->permalink . '">' . $comment->post->title . '</a> ';
						}
						break;

					case 'unapprove':
					case 'unapproved':
						// This comment was marked for unapproval
						Comments::moderate_these( $to_update, Comment::STATUS_UNAPPROVED );
						$modstatus['Unapproved %d comments'] = count ( $to_update );
						break;

					case 'edit':
						foreach ( $to_update as $comment ) {
							// This comment was edited
							if( $_POST['name_' . $comment->id] != NULL ) {
								$comment->name = $_POST['name_' . $comment->id];
							}
							if( $_POST['email_' . $comment->id] != NULL ) {
								$comment->email = $_POST['email_' . $comment->id];
							}
							if( $_POST['url_' . $comment->id] != NULL ) {
								$comment->url = $_POST['url_' . $comment->id];
							}
							if( $_POST['content_' . $comment->id] != NULL ) {
								$comment->content = $_POST['content_' . $comment->id];
							}
						}
						$modstatus['Edited %d comments'] = count( $to_update );
						break;

				}

				foreach ( $modstatus as $key => $value ) {
					if ( $value ) {
						Session::notice( sprintf( _t( $key ), $value ) );
					}
				}

			}

			Utils::redirect();

		}

		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse = Utils::WSSE();

		$arguments = array(
			'type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => $offset,
		);

		// there is no explicit 'all' type/status for comments, so we need to unset these arguments
		// if that's what we want. At the same time we can set up the search field
		$this->theme->search_args = '';
		if ( $type == 'All') {
			unset( $arguments['type'] );
		}
		else {
			$this->theme->search_args = 'type:' . Comment::type_name( $type ) . ' ';
		}

		if ( $status == 'All') {
			unset ( $arguments['status'] );
		}
		else {
			$this->theme->search_args .= 'status:' . Comment::status_name( $status );
		}

		if ( '' != $search ) {
			$arguments = array_merge( $arguments, Comments::search_to_get( $search ) );
		}

		$this->theme->comments = Comments::get( $arguments );
		$monthcts = Comments::get( array_merge( $arguments, array( 'month_cts' => 1 ) ) );
		$years = array();
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
			}
			else
			{
				$years[$month->year]= array( $month );
			}
		}
		$this->theme->years = $years;

		$baseactions = array();
		$statuses = Comment::list_comment_statuses();
		foreach($statuses as $statusid => $statusname) {
			$baseactions[$statusname]= array('url' => 'javascript:itemManage.update(\'' . $statusname . '\',__commentid__);', 'title' => _t('Change this comment\'s status to %s', array($statusname)), 'label' => Comment::status_action($statusid));
		}

		/* Standard actions */
		$baseactions['delete']= array('url' => 'javascript:itemManage.update(\'delete\',__commentid__);', 'title' => _t('Delete this comment'), 'label' => _t('Delete'));
		$baseactions['edit']= array('url' => URL::get('admin', 'page=comment&id=__commentid__'), 'title' => _t('Edit this comment'), 'label' => _t('Edit'));

		/* Actions for inline edit */
		$baseactions['submit']= array('url' => 'javascript:inEdit.update();', 'title' => _t('Submit changes'), 'label' => _t('Update'), 'nodisplay' => TRUE);
		$baseactions['cancel']= array('url' => 'javascript:inEdit.deactivate();', 'title' => _t('Cancel changes'), 'label' => _t('Cancel'), 'nodisplay' => TRUE);

		/* Allow plugins to apply actions */
		$actions = Plugins::filter('comments_actions', $baseactions, $this->theme->comments);

		foreach($this->theme->comments as $comment) {
			$menu= $actions;
			unset($menu[Comment::status_name($comment->status)]);
			$comment->menu= Plugins::filter('comment_actions', $menu, $comment);
		}
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
}

?>