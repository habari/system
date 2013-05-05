<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari AdminCommentsHandler Class
 * Handles comment-related actions in the admin
 *
 */
class AdminCommentsHandler extends AdminHandler
{
	/**
	 * Construct a form for a comment.
	 * @return FormUI The comment's form.
	 */
	public function form_comment( $comment, $actions )
	{
		$form = new FormUI( 'comment' );

		$user = User::identify();

		// Create the top description
		$top = $form->append( FormControlWrapper::create( 'buttons_1', null, array( 'class' => array( 'container', 'buttons', 'comment', 'overview' ) ) ) );
		$top->append( FormControlStatic::create( 'overview', null )->set_static( $this->theme->fetch( 'comment.overview' ) ) );
		$buttons_1 = $top->append( FormControlWrapper::create( 'buttons_1', null, array( 'class' => array( 'item', 'buttons' ) ) ) );

		foreach ( $actions as $status => $action ) {
			$id = $action . '_1';
			$buttons_1->append( FormControlSubmit::create( $id , null, array( 'class' => array('status', 'button', $action ) ) )->set_caption( MUltiByte::ucfirst( _t( $action ) ) ) );
			if ( Comment::status_name( $comment->status ) == $status ) {
				$buttons_1->$id->add_class( 'active' );
				$buttons_1->$id->set_properties( array( 'disabled' => true ) );
			}
			else {
				$buttons_1->$id->set_properties( array( 'disabled' => false ) );
			}
		}

		// Content
		$form->append( FormControlWrapper::create( 'content_wrapper' ) );
		$form->content_wrapper->append( FormControlLabel::wrap( _t( 'Comment' ), FormControlTextArea::create( 'content', null, array( 'class' => 'resizable' ) )->set_value( $comment->content ) ) );

		// Create the splitter
		$comment_controls = $form->append( FormControlTabs::create( 'comment_controls' ) );

		// Create the author info
		$author = $comment_controls->append( FormControlFieldset::create( 'authorinfo' )->set_caption( _t( 'Author' ) ) );

		$author->append( FormControlLabel::wrap( _t( 'Author Name' ), FormControlText::create( 'author_name' )->set_value( $comment->name ) ) );
		$author->append( FormControlLabel::wrap( _t( 'Author Email' ), FormControlText::create( 'author_email' )->set_value( $comment->email ) ) );
		$author->append( FormControlLabel::wrap( _t( 'Author URL' ), FormControlText::create( 'author_url' )->set_value( $comment->url ) ) );
		$author->append( FormControlLabel::wrap( _t( 'IP Address:' ), FormControlText::create( 'author_ip' )->set_value( $comment->ip ) ) );


		// Create the advanced settings
		$settings = $comment_controls->append( FormControlFieldset::create( 'settings' )->set_caption( _t( 'Settings' ) ) );

		$settings->append( FormControlLabel::wrap( _t( 'Date:' ), FormControlText::create( 'comment_date' )->set_value( $comment->date->get( 'Y-m-d H:i:s' ) ) ) );
		$settings->append( FormControlLabel::wrap( _t( 'Post ID:' ), FormControlText::create( 'comment_post' )->set_value( $comment->post->id ) ) );

		$statuses = Comment::list_comment_statuses( false );
		$statuses = Plugins::filter( 'admin_publish_list_comment_statuses', $statuses );
		$settings->append( FormControlLabel::wrap( _t( 'Status' ), FormControlSelect::create( 'comment_status' )->set_options( $statuses)->set_value( $comment->status ) ) );

		// // Create the stats
		// $comment_controls->append('fieldset', 'stats_tab', _t('Stats'));
		// $stats = $form->stats_tab->append('wrapper', 'tags_buttons');
		// $stats->class = 'container';
		//
		// $stats->append('static', 'post_count', '<div class="container"><p class="pct25">'._t('Comments on this post:').'</p><p><strong>' . Comments::count_by_id($comment->post->id) . '</strong></p></div><hr />');
		// $stats->append('static', 'ip_count', '<div class="container"><p class="pct25">'._t('Comments from this IP:').'</p><p><strong>' . Comments::count_by_ip($comment->ip) . '</strong></p></div><hr />');
		// $stats->append('static', 'email_count', '<div class="container"><p class="pct25">'._t('Comments by this author:').'</p><p><strong>' . Comments::count_by_email($comment->email) . '</strong></p></div><hr />');
		// $stats->append('static', 'url_count', '<div class="container"><p class="pct25">'._t('Comments with this URL:').'</p><p><strong>' . Comments::count_by_url($comment->url) . '</strong></p></div><hr />');

		// Create the second set of action buttons
		$buttons_2 = $form->append( FormControlWrapper::create( 'buttons_2', null, array( 'class'=> array( 'container', 'buttons', 'comment' ) ) ) );

		foreach ( $actions as $status => $action ) {
			$id = $action . '_2';
			$buttons_2->append( FormControlSubmit::create( $id , null, array( 'class' => array('status', 'button', $action ) ) )->set_caption( MUltiByte::ucfirst( _t( $action ) ) ) );
			if ( Comment::status_name( $comment->status ) == $status ) {
				$buttons_2->$id->add_class( 'active' );
				$buttons_2->$id->set_properties( array( 'disabled' => true ) );
			}
			else {
				$buttons_2->$id->set_properties( array( 'disabled' => false ) );
			}
		}

		// Allow plugins to alter form
		Plugins::act( 'form_comment_edit', $form, $comment );

		return $form;
	}

	/**
	 * Handles GET requests for an individual comment.
	 */
	public function get_comment( $update = false )
	{
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
				// Get the $_POSTed values
				$form->process();

				foreach ( $actions as $key => $action ) {
					$id_one = $action . '_1';
					$id_two = $action . '_2';
					if ( $form->$id_one->value != null || $form->$id_two->value != null ) {
						if ( $action == 'delete' ) {
							$comment->delete();
							Utils::redirect( URL::get( 'display_comments' ) );
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

				$comment->content = $form->content->value;
				$comment->name = $form->author_name->value;
				$comment->url = $form->author_url->value;
				$comment->email = $form->author_email->value;
				$comment->ip = $form->author_ip->value;

				$comment->date = DateTime::create( $form->comment_date->value );
				$comment->post_id = $form->comment_post->value;

				if ( ! isset( $set_status ) ) {
					$comment->status = $form->comment_status->value;
				}

				$comment->update();

				Plugins::act( 'comment_edit', $comment, $form );

				Utils::redirect();
			}

			$comment->content = $form;
			$this->theme->form = $form;

			$this->display( 'comment' );
		}
		else {
			Utils::redirect( URL::get( 'display_comments' ) );
		}
	}

	/**
	 * Handles POST requests for an individual comment.
	 */
	public function post_comment()
	{
		$this->get_comment( true );
	}

	/**
	 * Handles GET requests for the comments  page.
	 */
	public function get_comments()
	{
		$this->post_comments();
	}

	/**
	 * Handles the submission of the comment moderation form.
	 * @todo Separate delete from "delete until purge"
	 */
	public function post_comments()
	{
		// Get special search statuses
		$statuses = Comment::list_comment_statuses();
		$labels = array_map(
			function($a) {return MultiByte::ucfirst(Plugins::filter("comment_status_display", $a));},
			$statuses
		);
		$terms = array_map(
			function($a) {return "status:{$a}";},
			$statuses
		);
		$statuses = array_combine( $terms, $labels );

		// Get special search types
		$types = Comment::list_comment_types();
		$labels = array_map(
			function($a) {return MultiByte::ucfirst(Plugins::filter("comment_type_display", $a, "singular")) ;},
			$types
		);
		$terms = array_map(
			function($a) {return "type:{$a}";},
			$types
		);
		$types = array_combine( $terms, $labels );

		$this->theme->special_searches = array_merge( $statuses, $types );

		$this->fetch_comments();
		$this->display( 'comments' );
	}

	/**
	 * Retrieve comments.
	 */
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
			'password_digest' => '',
			'mass_spam_delete' => null,
			'mass_delete' => null,
			'type' => 'All',
			'limit' => 20,
			'offset' => 0,
			'search' => '',
			'status' => 'All',
			'orderby' => 'date DESC',
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : ( isset( $params[$varname] ) ? $params[$varname] : $default );
			$this->theme->{$varname} = $$varname;
		}

		// Setting these mass_delete options prevents any other processing.  Desired?
		if ( isset( $mass_spam_delete ) && $status == 'spam' ) {
			// Delete all comments that have the spam status.
			Comments::delete_by_status( 'spam' );
			// let's optimize the table
			$result = DB::query( 'OPTIMIZE TABLE {comments}' );
			Session::notice( _t( 'Deleted all spam comments' ) );
			EventLog::log( _t( 'Deleted all spam comments' ), 'info' );
			Utils::redirect();
		}
		elseif ( isset( $mass_delete ) && $status == 'unapproved' ) {
			// Delete all comments that are unapproved.
			Comments::delete_by_status( 'unapproved' );
			Session::notice( _t( 'Deleted all unapproved comments' ) );
			EventLog::log( _t( 'Deleted all unapproved comments' ), 'info' );
			Utils::redirect();
		}
		// if we're updating posts, let's do so:
		elseif ( ( $do_delete || $do_spam || $do_approve || $do_unapprove ) && isset( $comment_ids ) ) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $password_digest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $password_digest != $wsse['digest'] ) {
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
						$ids[] = $id;
					}
				}
				$to_update = Comments::get( array( 'id' => $ids ) );
				$modstatus = array(
					_t( 'Deleted %d comments' ) => 0,
					_t( 'Marked %d comments as spam' ) => 0,
					_t( 'Approved %d comments' ) => 0,
					_t( 'Unapproved %d comments' ) => 0,
					_t( 'Edited %d comments' ) => 0
				);
				Plugins::act( 'admin_moderate_comments', $action, $to_update, $this );

				switch ( $action ) {

					case 'delete':
						// This comment was marked for deletion
						$to_update = $this->comment_access_filter( $to_update, 'delete' );
						Comments::delete_these( $to_update );
						$modstatus[_t( 'Deleted %d comments' )] = count( $to_update );
						break;

					case 'spam':
						// This comment was marked as spam
						$to_update = $this->comment_access_filter( $to_update, 'edit' );
						Comments::moderate_these( $to_update, 'spam' );
						$modstatus[_t( 'Marked %d comments as spam' )] = count( $to_update );
						break;

					case 'approve':
					case 'approved':
						// Comments marked for approval
						$to_update = $this->comment_access_filter( $to_update, 'edit' );
						Comments::moderate_these( $to_update, 'approved' );
						$modstatus[_t( 'Approved %d comments' )] = count( $to_update );
						foreach ( $to_update as $comment ) {
									$modstatus[_t( 'Approved comments on these posts: %s' )] = ( isset( $modstatus[_t( 'Approved comments on these posts: %s' )] )? $modstatus[_t( 'Approved comments on these posts: %s' )] . ' &middot; ' : '' ) . '<a href="' . $comment->post->permalink . '">' . $comment->post->title . '</a> ';
						}
						break;

					case 'unapprove':
					case 'unapproved':
						// This comment was marked for unapproval
						$to_update = $this->comment_access_filter( $to_update, 'edit' );
						Comments::moderate_these( $to_update, 'unapproved' );
						$modstatus[_t( 'Unapproved %d comments' )] = count( $to_update );
						break;

					case 'edit':
						$to_update = $this->comment_access_filter( $to_update, 'edit' );
						foreach ( $to_update as $comment ) {
							// This comment was edited
							if ( $_POST['name_' . $comment->id] != null ) {
								$comment->name = $_POST['name_' . $comment->id];
							}
							if ( $_POST['email_' . $comment->id] != null ) {
								$comment->email = $_POST['email_' . $comment->id];
							}

							if ( $_POST['url_' . $comment->id] != null ) {
								$comment->url = $_POST['url_' . $comment->id];
							}
							if ( $_POST['content_' . $comment->id] != null ) {
								$comment->content = $_POST['content_' . $comment->id];
							}

							$comment->update();
						}
						$modstatus[_t( 'Edited %d comments' )] = count( $to_update );
						break;

				}

				foreach ( $modstatus as $key => $value ) {
					if ( $value ) {
						Session::notice( sprintf( $key, $value ) );
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
			'orderby' => $orderby,
		);

		// only get comments the user is allowed to manage
		if ( !User::identify()->can( 'manage_all_comments' ) ) {
			$arguments['post_author'] = User::identify()->id;
		}

		// there is no explicit 'all' type/status for comments, so we need to unset these arguments
		// if that's what we want. At the same time we can set up the search field
		$this->theme->search_args = '';
		if ( $type == 'All' ) {
			unset( $arguments['type'] );
		}
		else {
			$this->theme->search_args = 'type:' . Comment::type_name( $type ) . ' ';
		}

		if ( $status == 'All' ) {
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
		foreach ( $monthcts as $month ) {
			if ( isset( $years[$month->year] ) ) {
				$years[$month->year][] = $month;
			}
			else {
				$years[$month->year] = array( $month );
			}
		}
		$this->theme->years = $years;

		$baseactions = array();
		$statuses = Comment::list_comment_statuses();
		foreach ( $statuses as $statusid => $statusname ) {
			$baseactions[$statusname] = array( 'url' => 'javascript:itemManage.update(\'' . $statusname . '\',__commentid__);', 'title' => _t( 'Change this comment\'s status to %s', array( $statusname ) ), 'label' => Comment::status_action( $statusid ), 'access' => 'edit' );
		}

		/* Standard actions */
		$baseactions['delete'] = array( 'url' => 'javascript:itemManage.update(\'delete\',__commentid__);', 'title' => _t( 'Delete this comment' ), 'label' => _t( 'Delete' ), 'access' => 'delete' );
		$baseactions['edit'] = array( 'url' => URL::get( 'edit_comment', 'id=__commentid__' ), 'title' => _t( 'Edit this comment' ), 'label' => _t( 'Edit' ), 'access' => 'edit' );

		/* Allow plugins to apply actions */
		$actions = Plugins::filter( 'comments_actions', $baseactions, $this->theme->comments );

		foreach ( $this->theme->comments as $comment ) {
			// filter the actions based on the user's permissions
			$comment_access = $comment->get_access();
			$menu = array();
			foreach ( $actions as $name => $action ) {
				if ( !isset( $action['access'] ) || ACL::access_check( $comment_access, $action['access'] ) ) {
					$menu[$name] = $action;
				}
			}
			// remove the current status from the dropmenu
			unset( $menu[Comment::status_name( $comment->status )] );
			$comment->menu = Plugins::filter( 'comment_actions', $menu, $comment );
		}
	}

	/**
	 * A helper function for fetch_comments()
	 * Filters a list of comments by ACL access
	 * @param object $comments an array of Comment objects
	 * @param string $access the access type to check for
	 * @return a filtered array of Comment objects.
	 */
	public function comment_access_filter( $comments, $access )
	{
		$result = array();
		foreach ( $comments as $comment ) {
			if ( ACL::access_check( $comment->get_access(), $access ) ) {
				$result[] = $comment;
			}
		}
		return $result;
	}

	/**
	 * Handles AJAX requests from the manage comments page.
	 */
	public function ajax_comments()
	{
		Utils::check_request_method( array( 'GET', 'HEAD' ) );

		$this->create_theme();
		$this->theme->theme = $this->theme;

		$params = $_GET;

		$this->fetch_comments( $params );
		$items = $this->theme->fetch( 'comments_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach ( $this->theme->comments as $comment ) {
			$item_ids['p' . $comment->id] = 1;
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
	 * Handles AJAX requests to update comments, comment moderation
	 */
	public function ajax_update_comment( $handler_vars )
	{

		Utils::check_request_method( array( 'POST' ) );
		$ar = new AjaxResponse();

		// check WSSE authentication
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			$ar->message = _t( 'WSSE authentication failed.' );
			$ar->out();
			return;
		}

		$ids = array();

		foreach ( $_POST as $id => $update ) {
			// skip POST elements which are not comment ids
			if ( preg_match( '/^p\d+$/', $id ) && $update ) {
				$ids[] = (int) substr( $id, 1 );
			}
		}

		if ( ( ! isset( $ids ) || empty( $ids ) ) && $handler_vars['action'] == 'delete' ) {
			$ar->message = _t( 'No comments selected.' );
			$ar->out();
			return;
		}

		$comments = Comments::get( array( 'id' => $ids, 'nolimit' => true ) );
		Plugins::act( 'admin_moderate_comments', $handler_vars['action'], $comments, $this );
		$status_msg = _t( 'Unknown action "%s"', array( $handler_vars['action'] ) );

		switch ( $handler_vars['action'] ) {
			case 'delete_spam':
				Comments::delete_by_status( 'spam' );
				$status_msg = _t( 'Deleted all spam comments' );
				break;
			case 'delete_unapproved':
				Comments::delete_by_status( 'unapproved' );
				$status_msg = _t( 'Deleted all unapproved comments' );
				break;
			case 'delete':
				// Comments marked for deletion
				Comments::delete_these( $comments );
				$status_msg = sprintf( _n( 'Deleted %d comment', 'Deleted %d comments', count( $ids ) ), count( $ids ) );
				break;
			case 'spam':
				// Comments marked as spam
				Comments::moderate_these( $comments, 'spam' );
				$status_msg = sprintf( _n( 'Marked %d comment as spam', 'Marked %d comments as spam', count( $ids ) ), count( $ids ) );
				break;
			case 'approve':
			case 'approved':
				// Comments marked for approval
				Comments::moderate_these( $comments, 'approved' );
				$status_msg = sprintf( _n( 'Approved %d comment', 'Approved %d comments', count( $ids ) ), count( $ids ) );
				break;
			case 'unapprove':
			case 'unapproved':
				// Comments marked for unapproval
				Comments::moderate_these( $comments, 'unapproved' );
				$status_msg = sprintf( _n( 'Unapproved %d comment', 'Unapproved %d comments', count( $ids ) ), count( $ids ) );
				break;
			default:
				// Specific plugin-supplied action
				$status_msg = Plugins::filter( 'admin_comments_action', $status_msg, $handler_vars['action'], $comments );
				break;
		}

		$ar->message = $status_msg;
		$ar->out();
	}

}
?>
