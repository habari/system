<?php

class Monolith extends Theme
{
	/**
	 * Load the stack with the scripts and css we need
	 * @param Theme $theme The current theme
	 */
	public function action_admin_header( $theme )
	{

		// Since this is the core admin theme, all of these named StackItems should have been added via Stack::action_register_stackitems()

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

	}

	public function get_blocks( $area, $scope, $theme )
	{
		if($area == 'dashboard') {
			$area = 'dashboard_' . User::identify()->id;
		}
		return parent::get_blocks($area, $scope, $theme);
	}


}
?>
