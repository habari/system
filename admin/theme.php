<?php

namespace Habari;

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
		Stack::add( 'admin_header_javascript', 'select2' );
		Stack::add( 'admin_header_javascript', 'jquery.touch-events' );

		Stack::add( 'admin_stylesheet', 'jquery.ui-css' );
		Stack::add( 'admin_stylesheet', 'select2-css' );

		if ( defined( 'DEBUG_ADMIN' ) ) {
			Stack::add( 'admin_footer_javascript', 'less' );
			Stack::add( 'admin_stylesheet', 'admin-less' );
		}
		else {
			Stack::add( 'admin_stylesheet', 'admin-css' );
		}
	}

	/**
	 * Get the blocks for this theme in teh specified area,
	 * overrides the default handling to implement dashboard modules
	 * @param string $area The area to return blocks for
	 * @param string $scope The scope in which the blocks exist
	 * @param Theme $theme The theme for which the blocks will be returned
	 * @return array An array of Blocks
	 */
	public function get_blocks( $area, $scope, $theme )
	{
		if($area == 'dashboard') {
			$area = 'dashboard_' . User::identify()->id;
		}
		return parent::get_blocks($area, $scope, $theme);
	}

	/**
	 * When adding dashboard modules, the titles should remain as they're written in their providing plugin
	 * This function adds a value for the title of the block that is the same as the name of the type of block.
	 * The value is in _title because overwriting the main title value causes the block data to reload.
	 * @param Block $block The block that has data stored for the title
	 * @param Theme $theme The theme displaying this block
	 */
	public function action_block_content($block, $theme)
	{
		static $available_modules;
		if(!isset($available_modules)) {
			$available_modules = Plugins::filter('dashboard_block_list', array());
		}
		if( isset( $available_modules[$block->type] ) ) {
			$block->_title = $available_modules[$block->type];
		}
	}
}
?>
