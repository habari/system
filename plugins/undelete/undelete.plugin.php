<?php

/**
 * Undelete Class
 *
 * This class provides undelete functionality for posts and comments, and
 * provides a trashcan interface for restoring items.
 *
 * @todo Document methods
 * @todo Provide an undo popup link like in gmail.
 **/

class Undelete extends Plugin
{
	/**
	 * function action_plugin_activation
	 * adds the "deleted" status type to the poststatus table
	 * when this plugin is activated.
	**/
	public function action_plugin_activation( $file )
	{
		if ( realpath( $file ) == __FILE__ ) {
			Post::add_new_status( 'deleted', true );
			Options::set( 'undelete__style', '#primarycontent .deleted { background-color: #933; text-decoration: line-through; }' );
		}
	}

	/**
	 * This function is executed when the filter "before_post_delete" is
	 * called just before a post is to be deleted.
	 * This filter should return a boolean value to indicate whether
	 * the post should be deleted or not.
	 * @param Boolean Whether to delete the post or not
	 * @param Post The post object to potentially delete
	 * @return Boolean Whether to delete the post or not
	 **/
	function filter_post_delete_allow( $result, $post )
	{
		// all we need to do is set the post status to "deleted"
		// and then return false.  The Post::delete() method will
		// see the false return value, and simply return, leaving
		// the post in the database.
		if ( $post->status != Post::status( 'deleted' ) && ACL::access_check( $post->get_access(), 'delete' ) ) {
			$post->info->prior_status = $post->status;
			$post->status = Post::status( 'deleted' );
			$post->update();
			return false;
		}
		else {
			return true;
		}
	}

	public function filter_post_actions($actions, $post)
	{
		if ( $post->status == Post::status('deleted') && ACL::access_check( $post->get_access(), 'delete' ) ) {
			$actions['remove']['label']= _t('Delete forever');
			$actions['remove']['title']= _t('Permanently delete this post');
			$remove = array_pop($actions);
			$restore = array(
				'url' => 'javascript:unDelete.post('. $post->id . ');',
				'title' => _t('Restore this item'),
				'label' => _t('Restore')
			);
			array_push($actions, $restore, $remove);
		}
		return $actions;
	}

	public function filter_posts_manage_actions( $actions )
	{
		// get all the post types
		$require_any = array( 'own_posts' => 'delete' );
		$types = Post::list_active_post_types();
		foreach ($types as $key => $value ) {
			$require_any['post_' . $key] = 'delete';
		}

		if ( User::identify()->can_any( $require_any ) ) {
			$actions[] = array( 'action' => 'itemManage.update(\'restore\');return false;', 'title' => _t( 'Restore Selected Entries' ), 'label' => _t( 'Restore Selected' ) );
		}
		return $actions;
	}

	public function filter_admin_entries_action( $status_msg, $action, $posts )
	{
		$num = 0;

		switch ( $action ) {
		case 'restore':
			foreach( $posts as $post ) {
				$result = $this->undelete_post( $post->id );
				if ( $result ) {
					$num++;
				}
			}
			if ( $num == count( $posts ) ) {
				$status_msg = sprintf( _n('Restored %d post', 'Restored %d posts', $num ), $num );
			}
			else {
				$status_msg = _t( 'You did not have permission to restore some entries.' );
			}
			break;
		}

		return $status_msg;
	}

	/**
	 * function undelete_post
	 * This function reverts a post's status from 'deleted' to whatever
	 * it previously was.
	**/
	private function undelete_post( $post_id )
	{
		$post = Post::get( array( 'id' => $post_id, 'status' => Post::status('any') ) );
		if ( $post->status == Post::status('deleted') && ACL::access_check( $post->get_access(), 'delete' ) ) {
			$post->status = $post->info->prior_status ? $post->info->prior_status : Post::status( 'draft' );
			unset( $post->info->prior_status );
			$post->update();

			EventLog::log(
				sprintf(_t('Post %1$s (%2$s) restored.'), $post->id, $post->slug),
				'info', 'content', 'habari'
			);
			//scheduled post
			if ( $post->status == Post::status( 'scheduled' ) ) {
				Posts::update_scheduled_posts_cronjob();
			}
			return true;
		}
		else {
			return false;
		}
	}

	public function action_auth_ajax_undelete()
	{
		if ( $this->undelete_post($_POST['id']) ) {
			_e('Restored post %d', array($_POST['id']) );
		}
		else {
			_e( 'Could not restore post %d', array($_POST['id']) );
		}
	}

	private function get_perms()
	{
		$type_perms = array();
		$types = Post::list_active_post_types();
		foreach( $types as $key => $value ) {
			$perm = array( 'post_' . $key => ACL::get_bitmask( 'delete' ) );
			$types_perms = array_merge( $type_perms, $perm );
		}
		$perms = array( 'own_posts' => ACL::get_bitmask( 'delete' ), 'post_any' => ACL::get_bitmask( 'delete' ) );
		$perms = array_merge( $perms, $type_perms );
		return $perms;
	}

	public function filter_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions[]= _t( 'Configure' );
			if ( User::identify()->can_any( $this->get_perms() ) ) {
				$actions[]= _t( 'Empty Trash' );
			}
		}
		return $actions;
	}

	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
				case _t( 'Configure' ):
					$ui = new FormUI( strtolower( get_class( $this ) ) );
					$ui->append( 'textarea', 'style', 'option:undelete__style', _t( 'Style declaration for deleted content:' ) );
					$ui->append( 'submit', 'save', _t( 'Save' ) );
					$ui->on_success( array( $this, 'updated_config' ) );
					$ui->out();
					break;

				case _t( 'Empty Trash' ):
					$ui = new FormUI( strtolower( get_class( $this ) ) );
					$ui->append( 'static', 'explanation', _t('Pressing this button will permanently delete all posts from the virtual trash can. You cannot undo this.') );
					$ui->append( 'submit', 'delete', _t( 'Delete All' ) );
					$ui->on_success( array( $this, 'deleted_all' ) );
					$ui->out();
					break;
			}

		}
	}

	public function updated_config( $ui )
	{
		$ui->save();
		return false;
	}

	public function deleted_all( $ui )
	{
		$count = self::delete_all();

		Session::notice(sprintf( _t('Permanently deleted %d posts'), $count));
		return false;
	}

	// This method will permanently delete all posts stored in the trash can
	private function delete_all()
	{
		$posts = Posts::get(array('status' => Post::status('deleted'), 'nolimit' => true));

		$count = 0;

		foreach($posts as $post) {
			if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
				$post->delete();
				$count++;
			}
		}

		return $count;
	}

	/**
	 * this method will inject some CSS into the <head>
	 * of the public facing theme so that deleted posts
	 * will show up differently
	 */
	public function action_template_header()
	{
		// only show the style to logged in users
		if ( User::identify()->loggedin ) {
			echo '<style type="text/css">';
			Options::out( 'undelete__style' );
			echo '</style>';
		}
	}

	public function action_admin_header( $theme )
	{
		if ( $theme->page == 'posts' ) {
			Stack::add( 'admin_stylesheet', array($this->get_url() . '/undelete.css', 'screen') );
			$url = URL::get( 'auth_ajax', array('context' => 'undelete') );
			$script = <<<JS
var unDelete = {
	post : function(id) {
		spinner.start();
		\$.post(
			'$url',
			{'id':id},
			function( result ) {
				spinner.stop();
				human_msg.display_msg( result );
				if ( $('.timeline').length ) {
					var loupeInfo = timeline.getLoupeInfo();
					itemManage.fetch( 0, loupeInfo.limit, true );
					timeline.updateLoupeInfo();
				}
				else {
					itemManage.fetch( 0, 20, false );
				}
			}
		);
	}
}
JS;
			Stack::add( 'admin_header_javascript', $script, 'undelete', 'admin' );
		}
	}
}

?>
