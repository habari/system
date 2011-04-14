<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminTagsHandler Class
 * Handles tag-related actions in the admin
 *
 */
class AdminTagsHandler extends AdminHandler
{
	/**
	 * Handle GET requests for /admin/tags to display the tags.
	 */
	public function get_tags()
	{
		$this->theme->wsse = Utils::WSSE();

		$this->theme->tags = Tags::vocabulary()->get_tree( 'term_display asc' );
		$this->theme->max = Tags::vocabulary()->max_count();

		$this->display( 'tags' );
	}

	/**
	 * Handles AJAX from /admin/tags
	 * Used to delete and rename tags
	 */
	public function ajax_tags( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t( 'WSSE authentication failed.' ) );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$tag_names = array();
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', true ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
		$action = $this->handler_vars['action'];
		switch ( $action ) {
			case 'delete':
				foreach ( $_POST as $id => $delete ) {
					// skip POST elements which are not tag ids
					if ( preg_match( '/^tag_\d+/', $id ) && $delete ) {
						$id = substr( $id, 4 );
						$tag = Tags::get_by_id( $id );
						$tag_names[] = $tag->term_display;
						Tags::vocabulary()->delete_term( $tag );
					}
				}
				$msg_status = _n( _t( 'Tag %s has been deleted.', array( implode( '', $tag_names ) ) ), _t( '%d tags have been deleted.', array( count( $tag_names ) ) ), count( $tag_names ) );
				Session::notice( $msg_status );
				break;

			case 'rename':
				if ( !isset( $this->handler_vars['master'] ) ) {
					Session::error( _t( 'Error: New name not specified.' ) );
					echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
					return;
				}
				$master = $this->handler_vars['master'];
				$tag_names = array();
				foreach ( $_POST as $id => $rename ) {
					// skip POST elements which are not tag ids
					if ( preg_match( '/^tag_\d+/', $id ) && $rename ) {
						$id = substr( $id, 4 );
						$tag = Tags::get_by_id( $id );
						$tag_names[] = $tag->term_display;
					}
				}
				Tags::vocabulary()->merge( $master, $tag_names );
				$msg_status = sprintf(
					_n('Tag %1$s has been renamed to %2$s.',
						'Tags %1$s have been renamed to %2$s.',
							count( $tag_names )
					), implode( $tag_names, ', ' ), $master
				);
				Session::notice( $msg_status );
				break;

		}
		$this->theme->tags = Tags::vocabulary()->get_tree();
		$this->theme->max = Tags::vocabulary()->max_count();
		echo json_encode( array(
			'msg' => Session::messages_get( true, 'array' ),
			'tags' => $this->theme->fetch( 'tag_collection' ),
		) );
	}

}
?>
