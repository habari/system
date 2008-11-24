<?php

class CommentAdminPage extends AdminPage
{
	public function act_request_get($update = FALSE) {
		if ( isset( $this->handler_vars['id'] ) && $comment = Comment::get( $this->handler_vars['id'] ) ) {
			$this->theme->comment = $comment;

			// Convenience array to output actions twice
			$actions = array(
				'deleted' => 'delete',
				'spam' => 'spam',
				'unapproved' => 'unapprove',
				'approved' => 'approve',
				'saved' => 'save'
				);

			$form = $this->form_comment( $comment, $actions );

			if ( $update ) {
				foreach ( $actions as $key => $action ) {
					$id_one = $action . '_1';
					$id_two = $action . '_2';
					if ( $form->$id_one->value != NULL || $form->$id_two->value != NULL ) {
						if ( $action == 'delete' ) {
							$comment->delete();
							Utils::redirect(URL::get('admin', 'page=comments'));
						}
						if ( $action != 'save' ) {
							foreach ( Comment::list_comment_statuses() as $status ) {
								if ( $status == $key ) {
									$comment->status = Comment::status_name( $status );
									$set_status = true;
								}
							}
						}
					}
				}

				$comment->content = $form->content;
				$comment->name = $form->author_name;
				$comment->url = $form->author_url;
				$comment->email = $form->author_email;
				$comment->ip = ip2long( $form->author_ip );

				$comment->date = HabariDateTime::date_create( $form->comment_date );
				$comment->post_id = $form->comment_post;

				if ( ! isset($set_status) ) {
					$comment->status = $form->comment_status->value;
				}

				$comment->update();

				Plugins::act('comment_edit', $comment, $form);

				Utils::redirect();
			}

			$comment->content = $form;
			$this->theme->form = $form;

			$this->display('comment');
		} else {
			Utils::redirect(URL::get('admin', 'page=comments'));
		}
	}

	public function act_request_post() {
		$this->act_request_get(true);
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
		case 'approve':
		case 'approved':
			// Comments marked for approval
			Comments::moderate_these( $comments, Comment::STATUS_APPROVED );
			$status_msg = sprintf( _n('Approved %d comment', 'Approved %d comments', count( $ids ) ), count( $ids ) );
			break;
		case 'unapprove':
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
	
		public function form_comment($comment, $actions) {
		$form = new FormUI( 'comment' );

		$user = User::identify();

		// Create the top description
		$top = $form->append('wrapper', 'buttons_1');
		$top->class = 'container buttons comment overview';

		$top->append('static', 'overview', $this->theme->fetch('comment.overview'));

		$buttons_1 = $top->append('wrapper', 'buttons_1');
		$buttons_1->class = 'item buttons';


		foreach($actions as $status => $action) {
			$id = $action . '_1';
			$buttons_1->append('submit', $id, _t(ucfirst($action)));
			$buttons_1->$id->class = 'button ' . $action;
			if(Comment::status_name($comment->status) == $status) {
				$buttons_1->$id->class = 'button active ' . $action;
				$buttons_1->$id->disabled = true;
			} else {
				$buttons_1->$id->disabled = false;
			}
		}

		// Content
		$form->append('wrapper', 'content_wrapper');
		$content = $form->content_wrapper->append('textarea', 'content', 'null:null', _t('Comment'), 'admincontrol_textarea');
		$content->class = 'resizable';
		$content->value = $comment->content;

		// Create the splitter
		$comment_controls = $form->append('tabs', 'comment_controls');

		// Create the author info
		$author = $comment_controls->append('fieldset', 'authorinfo', _t('Author'));

		$author->append('text', 'author_name', 'null:null', _t('Author Name'), 'tabcontrol_text');
		$author->author_name->value = $comment->name;

		$author->append('text', 'author_email', 'null:null', _t('Author Email'), 'tabcontrol_text');
		$author->author_email->value = $comment->email;

		$author->append('text', 'author_url', 'null:null', _t('Author URL'), 'tabcontrol_text');
		$author->author_url->value = $comment->url;

		$author->append('text', 'author_ip', 'null:null', _t('IP Address:'), 'tabcontrol_text');
		$author->author_ip->value = long2ip($comment->ip);

		// Create the advanced settings
		$settings = $comment_controls->append('fieldset', 'settings', _t('Settings'));

		$settings->append('text', 'comment_date', 'null:null', _t('Date:'), 'tabcontrol_text');
		$settings->comment_date->value = $comment->date->get('Y-m-d H:i:s');



		$settings->append('text', 'comment_post', 'null:null', _t('Post ID:'), 'tabcontrol_text');
		$settings->comment_post->value = $comment->post->id;

		$statuses = Comment::list_comment_statuses( false );
		$statuses = Plugins::filter( 'admin_publish_list_comment_statuses', $statuses );
		$settings->append('select', 'comment_status', 'null:null', _t('Status'), $statuses, 'tabcontrol_select');
		$settings->comment_status->value = $comment->status;

		// // Create the stats
		// $comment_controls->append('fieldset', 'stats_tab', _t('Stats'));
		// $stats= $form->stats_tab->append('wrapper', 'tags_buttons');
		// $stats->class='container';
		//
		// $stats->append('static', 'post_count', '<div class="container"><p class="pct25">'._t('Comments on this post:').'</p><p><strong>' . Comments::count_by_id($comment->post->id) . '</strong></p></div><hr />');
		// $stats->append('static', 'ip_count', '<div class="container"><p class="pct25">'._t('Comments from this IP:').'</p><p><strong>' . Comments::count_by_ip($comment->ip) . '</strong></p></div><hr />');
		// $stats->append('static', 'email_count', '<div class="container"><p class="pct25">'._t('Comments by this author:').'</p><p><strong>' . Comments::count_by_email($comment->email) . '</strong></p></div><hr />');
		// $stats->append('static', 'url_count', '<div class="container"><p class="pct25">'._t('Comments with this URL:').'</p><p><strong>' . Comments::count_by_url($comment->url) . '</strong></p></div><hr />');

		// Create the second set of action buttons
		$buttons_2 = $form->append('wrapper', 'buttons_2');
		$buttons_2->class = 'container buttons comment';

		foreach($actions as $status => $action) {
			$id = $action . '_2';
			$buttons_2->append('submit', $id, _t(ucfirst($action)));
			$buttons_2->$id->class = 'button ' . $action;
			if(Comment::status_name($comment->status) == $status) {
				$buttons_2->$id->class = 'button active ' . $action;
				$buttons_2->$id->disabled = true;
			} else {
				$buttons_2->$id->disabled = false;
			}
		}

		// Allow plugins to alter form
		Plugins::act('form_comment_edit', $form, $comment);

		return $form;
	}
}

?>