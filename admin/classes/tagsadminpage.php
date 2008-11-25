<?php

class TagsAdminPage extends AdminPage
{
	/**
	 * Handle GET requests for /admin/tags to display the tags
	 */
	public function act_request_get()
	{
		$this->theme->wsse = Utils::WSSE(); /* @TODO: What the heck is this doing here? */

		$this->theme->tags = Tags::get();
		$this->theme->max = Tags::max_count();

		$this->display( 'tags' );
	}

	/**
	 * handles AJAX from /admin/tags
	 * used to delete and rename tags
	 */
	public function act_ajax_get( $handler_vars)
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
					$this->theme->tags = Tags::get();
					$this->theme->max = Tags::max_count();
					echo json_encode( array(
						'msg' => Session::messages_get( true, 'array' ),
						'tags' => $this->theme->fetch( 'tag_collection' ),
						) );
				}
				break;
		}
	}
}

?>