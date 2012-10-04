<?php

class Monolith extends Theme
{
	/**
	 * Load the stack with the scripts and css we need
	 * @param Theme $theme The current theme
	 */
	public function action_admin_header( $theme )
	{

		Stack::add( 'admin_header_javascript', 'jquery.color' );
		Stack::add( 'admin_header_javascript', 'jquery-nested-sortable' );
		Stack::add( 'admin_header_javascript', 'humanmsg' );
		Stack::add( 'admin_header_javascript', 'jquery.hotkeys' );
		Stack::add( 'admin_header_javascript', 'locale-js' );
		Stack::add( 'admin_header_javascript', 'media' );
		Stack::add( 'admin_header_javascript', 'admin-js' );

		Stack::add( 'admin_header_javascript', 'crc32' );

		Stack::add( 'admin_stylesheet', 'admin-css' );
		Stack::add( 'admin_stylesheet', 'jquery.ui-css' );
		Stack::add( 'admin_stylesheet', 'humanmsg-css' );

	}

	/**
	 * Register stacks and scripts to be added to the stacks.
	 */
	public function action_register_stackitems()
	{
		// Register default StackItems
		StackItem::register( 'jquery', Site::get_url( 'vendor' ) . "/jquery.js" );
		StackItem::register( 'jquery.ui', Site::get_url( 'vendor' ) . "/jquery-ui.min.js" )->add_dependency( 'jquery' );
		StackItem::register('jquery.color', Site::get_url( 'vendor' ) . "/jquery.color.js" )->add_dependency('jquery.ui' );
		StackItem::register( 'jquery-nested-sortable', Site::get_url( 'vendor' ) . "/jquery.ui.nestedSortable.js" ) ->add_dependency('jquery.ui' );
		StackItem::register( 'humanmsg', Site::get_url( 'vendor' ) . "/humanmsg/humanmsg.js" )->add_dependency( 'jquery' );
		StackItem::register( 'jquery.hotkeys', Site::get_url( 'vendor' ) . "/jquery.hotkeys.js" )->add_dependency( 'jquery' );
		StackItem::register( 'locale-js', URL::get( 'admin', 'page=locale' ) );
		StackItem::register( 'media', Site::get_url( 'admin_theme' ) . "/js/media.js" )->add_dependency( 'jquery' )->add_dependency( 'locale' );
		StackItem::register( 'admin-js', Site::get_url( 'admin_theme' ) . "/js/admin.js" )->add_dependency( 'jquery' )->add_dependency( 'locale' );
		StackItem::register( 'crc32', Site::get_url( 'vendor' ) . "/crc32.js" );

		StackItem::register( 'admin-css', array( Site::get_url( 'admin_theme' ) . '/css/admin.css', 'screen' ) );
		StackItem::register( 'jquery.ui-css', array( Site::get_url( 'admin_theme' ) . '/css/jqueryui.css', 'screen' ) );
		StackItem::register( 'humanmsg-css', array( Site::get_url( 'vendor' ) . '/humanmsg/humanmsg.css', 'screen' ) );
	}
}
?>
