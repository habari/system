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
	 * Handles ajax searching from admin/tags
	 * @param type $handler_vars The variables passed to the page by the server
	 * @return AjaxResponse The updated data for the tags page, with any messages
	 */
	public function ajax_get_tags( $handler_vars )
	{
		Utils::check_request_method( array( 'GET', 'HEAD' ) );
		$response = new AjaxResponse();

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			$response->message = _t( 'WSSE authentication failed.' );
			$response->out();
			return;
		}

		$this->create_theme();

		$search = $handler_vars['search'];

		$this->theme->tags = Tags::vocabulary()->get_search( $search, 'term_display asc' );
		$this->theme->max = Tags::vocabulary()->max_count();
		$response->data = $this->theme->fetch( 'tag_collection' );
		$response->out();
	}

	/**
	 * Handles AJAX from /admin/tags
	 * Used to delete and rename tags
	 */
	public function ajax_tags( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );
		$response = new AjaxResponse();

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			$response->message = _t( 'WSSE authentication failed.' );
			$response->out();
			return;
		}

		$tag_names = array();
		$this->create_theme();
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
				$response->message = _n( _t( 'Tag %s has been deleted.', array( implode( '', $tag_names ) ) ), _t( '%d tags have been deleted.', array( count( $tag_names ) ) ), count( $tag_names ) );
				break;

			case 'rename':
				if ( !isset( $this->handler_vars['master'] ) ) {
					$response->message = _t( 'Error: New name not specified.' );
					$response->out();
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
				$response->message = sprintf(
					_n('Tag %1$s has been renamed to %2$s.',
						'Tags %1$s have been renamed to %2$s.',
							count( $tag_names )
					), implode( $tag_names, ', ' ), $master
				);
				break;

		}
		$this->theme->tags = Tags::vocabulary()->get_tree( 'term_display ASC' );
		$this->theme->max = Tags::vocabulary()->max_count();
		$response->data = $this->theme->fetch( 'tag_collection' );
		$response->out();
	}

}
?>
