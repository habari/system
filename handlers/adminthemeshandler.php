<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminThemesHandler Class
 * Handles theme-related actions in the admin
 *
 */
class AdminThemesHandler extends AdminHandler
{
	/**
	 * Handles GET requests for the theme listing
	 */
	public function get_themes()
	{
		$all_themes = Themes::get_all_data();
		$theme_names = Utils::array_map_field($all_themes, 'name');

		$available_updates = Options::get( 'updates_available', array() );

		foreach ( $all_themes as $name => $theme ) {

			// only themes with a guid can be checked for updates
			if ( isset( $theme['info']->guid ) ) {
				if ( isset( $available_updates[ (string)$theme['info']->guid ] ) ) {
					// @todo this doesn't use the URL and is therefore worthless
					$all_themes[ $name ]['info']->update = $available_updates[ (string)$theme['info']->guid ]['latest_version'];
				}
			}

			// If this theme requires a parent to be present and it's not, send an error
			if(isset($theme['info']->parent) && !in_array((string)$theme['info']->parent, $theme_names)) {
				$all_themes[$name]['req_parent'] = $theme['info']->parent;
			}
		}

		$this->theme->all_themes = $all_themes;

		$this->theme->active_theme = Themes::get_active_data( true );
		$this->theme->active_theme_dir = $this->theme->active_theme['path'];

		// If the active theme is configurable, allow it to configure
		$this->theme->active_theme_name = $this->theme->active_theme['info']->name;
		$this->theme->configurable = Plugins::filter( 'theme_config', false, $this->active_theme );
		$this->theme->assign( 'configure', Controller::get_var( 'configure' ) );

		$this->theme->areas = $this->get_areas(0);
		$this->theme->previewed = Themes::get_theme_dir( false );

		$this->theme->help = isset($this->theme->active_theme['info']->help) ? $this->theme->active_theme['info']->help : false;
		$this->theme->help_active = Controller::get_var('help') == $this->theme->active_theme['dir'];

		$this->prepare_block_list();

		$blocks_areas_t = DB::get_results( 'SELECT b.*, ba.scope_id, ba.area, ba.display_order FROM {blocks} b INNER JOIN {blocks_areas} ba ON ba.block_id = b.id ORDER BY ba.scope_id ASC, ba.area ASC, ba.display_order ASC', array() );
		$blocks_areas = array();
		foreach ( $blocks_areas_t as $block ) {
			if ( !isset( $blocks_areas[$block->scope_id] ) ) {
				$blocks_areas[$block->scope_id] = array();
			}
			$blocks_areas[$block->scope_id][$block->area][$block->display_order] = $block;
		}
		$this->theme->blocks_areas = $blocks_areas;

		$scopes = DB::get_results( 'SELECT * FROM {scopes} ORDER BY name ASC;' );
		$scopes = Plugins::filter( 'get_scopes', $scopes );
		$this->theme->scopes = $scopes;
		$this->theme->scopeid = 0;

		$this->theme->theme_loader = Plugins::filter( 'theme_loader', '', $this->theme );

		$this->theme->display( 'themes' );
	}

	/**
	 * A POST handler for the admin themes page that simply passes those options through.
	 */
	public function post_themes()
	{
		return $this->get_themes();
	}

	/**
	 * Activates a theme.
	 */
	public function get_activate_theme()
	{
		$theme_name = $this->handler_vars['theme_name'];
		$theme_dir = $this->handler_vars['theme_dir'];
		$activated = false;
		if ( isset( $theme_name ) && isset( $theme_dir ) ) {
			$activated = Themes::activate_theme( $theme_name, $theme_dir );
		}
		if($activated) {
			Session::notice( _t( "Activated theme '%s'", array( $theme_name ) ) );
		}
		Utils::redirect( URL::get( 'admin', 'page=themes' ) );
	}

	/**
	 * Configures a theme to be active for the current user's session.
	 */
	public function get_preview_theme()
	{
		$theme_name = $this->handler_vars['theme_name'];
		$theme_dir = $this->handler_vars['theme_dir'];
		if ( isset( $theme_name )  && isset( $theme_dir ) ) {
			if ( Themes::get_theme_dir() == $theme_dir ) {
				Themes::cancel_preview();
				Session::notice( _t( "Ended the preview of the theme '%s'", array( $theme_name ) ) );
			}
			else {
				if(Themes::preview_theme( $theme_name, $theme_dir )) {
					Session::notice( _t( "Previewing theme '%s'", array( $theme_name ) ) );
				}
			}
		}
		Utils::redirect( URL::get( 'admin', 'page=themes' ) );
	}

	/**
	 * Get the block configuration form to show in a modal iframe on the themes page
	 *
	 */
	public function get_configure_block()
	{
		Utils::check_request_method( array( 'GET', 'POST' ) );

		$block = DB::get_row( 'SELECT b.* FROM {blocks} b WHERE id = :id ORDER BY b.title ASC', array( 'id' => $_GET['blockid'] ), 'Block' );
		$block_form = $block->get_form();
		$block_form->set_option( 'success_message', '</div><div class="humanMsg" id="humanMsg" style="display: block;top: auto;bottom:-50px;"><div class="imsgs"><div id="msgid_2" class="msg" style="display: block; opacity: 0.8;"><p>' . _t( 'Saved block configuration.' ) . '</p></div></div></div>
<script type="text/javascript">
		$("#humanMsg").animate({bottom: "5px"}, 500, function(){ window.setTimeout(function(){$("#humanMsg").animate({bottom: "-50px"}, 500)},3000) })
		parent.refresh_block_forms();
</script>
<div style="display:none;">
');

		$first_control = reset ( $block_form->controls );
		if ( $first_control ) {
			$block_form->insert( $first_control->name, 'fieldset', 'block_admin', _t( 'Block Display Settings' ) );
		}
		else {
			$block_form->append( 'fieldset', 'block_admin', _t( 'Block Display Settings' ) );
		}

		$block_form->block_admin->append( 'text', '_title', array( 'configure_block_title', $block ), _t( 'Block Title:' ) );
		$block_form->_title->value = $block->title;
		$block_form->_title->add_validator( 'validate_required' );
		$block_form->block_admin->append( 'checkbox', '_show_title', $block, _t( 'Display Block Title:' ) );
		$block_form->append( 'submit', 'save', _t( 'Save' ) );

		Plugins::register( array( $this, 'action_configure_block_title' ), 'action', 'configure_block_title' );

		$this->theme->content = $block_form->get();

		$this->display( 'block_configure' );
	}

	function action_configure_block_title( $value, $name, $storage )
	{
		$storage[0]->title = $value;
		return false;
	}

	/**
	 * A POST handler for the block configuration form
	 *
	 * @see AdminHandler::get_configure_block
	 * @return
	 */
	public function post_configure_block()
	{
		$this->get_configure_block();
	}

	/**
	 * Called from the themes page to create a new block instace
	 *
	 * @param mixed $handler_vars
	 */
	public function ajax_add_block( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$this->setup_admin_theme( '' );

		$title = $_POST['title'];
		$type = $_POST['type'];

		if ( !isset( $_POST['title'] ) ) {
			$this->theme->blocks = Plugins::filter( 'block_list', array() );
			$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
			$this->theme->active_theme = Themes::get_active_data( true );

			$this->display( 'block_instances' );
		}
		elseif ( $title == '' ) {
			$this->theme->blocks = Plugins::filter( 'block_list', array() );
			$this->theme->block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
			$this->theme->active_theme = Themes::get_active_data( true );

			$this->display( 'block_instances' );

			$msg = json_encode( _t( 'A new block must first have a name.' ) );

			echo '<script type="text/javascript">
				alert(' . $msg . ');
			</script>';
		}
		else {
			$block = new Block( array( 'title' => $title, 'type' => $type ) );
			$block->insert();

			$this->prepare_block_list();
			$this->theme->active_theme = Themes::get_active_data( true );

			$this->display( 'block_instances' );

			$msg = json_encode( _t( 'Added new block "%1s" of type "%2s".', array( $title, $type ) ) );

			echo '<script type="text/javascript">
				human_msg.display_msg(' . $msg . ');
				spinner.stop();
			</script>';
		}
	}

	/**
	 * Called from the themes page to delete a block instance
	 *
	 * @param mixed $handler_vars
	 */
	public function ajax_delete_block( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$this->setup_admin_theme( '' );

		$block_id = $_POST['block_id'];
		$block = DB::get_row( 'SELECT b.* FROM {blocks} b WHERE id = :block_id', array( 'block_id' => $block_id ), 'Block' );
		if ( $block->delete() ) {
			$msg = json_encode( _t( 'Deleted block "%1s" of type "%2s".', array( $block->title, $block->type ) ) );
		}
		else {
			$msg = json_encode( _t( 'Failed to delete block "%1s" of type "%2s".', array( $block->title, $block->type ) ) );
		}

		$this->prepare_block_list();

		$this->theme->active_theme = Themes::get_active_data( true );

		$this->display( 'block_instances' );

		echo '<script type="text/javascript">
			human_msg.display_msg(' . $msg . ');
			spinner.stop();
			themeManage.change_scope();
		</script>';
	}

	/**
	 * Called from the themes page to save the blocks instances into areas
	 *
	 * @param mixed $handler_vars
	 * @return
	 */
	public function ajax_save_areas( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$scope = $_POST['scope'];

		$msg = '';

		$response = new AjaxResponse();
		if ( isset( $_POST['area_blocks'] ) ) {
			$area_blocks = $_POST['area_blocks'];
			DB::query( 'DELETE FROM {blocks_areas} WHERE scope_id = :scope_id', array( 'scope_id' => $scope ) );

			foreach ( (array)$area_blocks as $area => $blocks ) {
				$display_order = 0;

				// if there are no blocks for a given area, skip it
				if ( empty( $blocks ) ) {
					continue;
				}

				foreach ( $blocks as $block ) {
					$display_order++;
					DB::query( 'INSERT INTO {blocks_areas} (block_id, area, scope_id, display_order) VALUES (:block_id, :area, :scope_id, :display_order)', array( 'block_id'=>$block, 'area'=>$area, 'scope_id'=>$scope, 'display_order'=>$display_order ) );
				}
			}

//			$msg = json_encode( _t( 'Saved block areas settings.' ) );
//			$msg = '<script type="text/javascript">
//				human_msg.display_msg(' . $msg . ');
//				spinner.stop();
//			</script>';
			$response->message = _t( 'Saved block areas settings.' );
		}

		$this->setup_admin_theme( '' );

		$blocks_areas_t = DB::get_results( 'SELECT b.*, ba.scope_id, ba.area, ba.display_order FROM {blocks} b INNER JOIN {blocks_areas} ba ON ba.block_id = b.id ORDER BY ba.scope_id ASC, ba.area ASC, ba.display_order ASC', array() );
		$blocks_areas = array();
		foreach ( $blocks_areas_t as $block ) {
			if ( !isset( $blocks_areas[$block->scope_id] ) ) {
				$blocks_areas[$block->scope_id] = array();
			}
			$blocks_areas[$block->scope_id][$block->area][$block->display_order] = $block;
		}
		$this->theme->blocks_areas = $blocks_areas;
		$this->theme->scopeid = $scope;
		$this->theme->areas = $this->get_areas($scope);
		$scopes = DB::get_results( 'SELECT * FROM {scopes} ORDER BY name ASC;' );
		$scopes = Plugins::filter( 'get_scopes', $scopes );
		$this->theme->scopes = $scopes;
		$this->theme->active_theme = Themes::get_active_data( true );

		$output = $this->theme->fetch( 'block_areas' );
		$response->html('block_areas', $output);

		$response->out();
	}


	function get_areas($scope) {
		$activedata = Themes::get_active_data( true );
		$areas = array();
		if ( isset( $activedata['info']->areas->area ) ) {
			foreach ( $activedata['info']->areas->area as $area ) {
				$detail = array();
				if(isset($area['title'])) {
					$detail['title'] = (string)$area['title'];
				}
				else {
					$detail['title'] = (string)$area['name'];
				}
				$detail['description'] = (string)$area->description;
				$areas[(string)$area['name']] = $detail;
			}
		}
		$areas = Plugins::filter('areas', $areas, $scope);
		return $areas;
	}

	/**
	 * Load the block types and block instances into the appropriate structures for the theme to output
	 */
	function prepare_block_list() {
		$block_types = Plugins::filter( 'block_list', array() );
		$dash_blocks = Plugins::filter( 'dashboard_block_list', array() );
		$block_types = array_diff_key($block_types, $dash_blocks);
		$all_block_instances = DB::get_results( 'SELECT b.* FROM {blocks} b ORDER BY b.title ASC', array(), 'Block' );
		$block_instances = array();
		$invalid_block_instances = array();
		foreach($all_block_instances as $instance) {
			if(isset($block_types[$instance->type])) {
				$block_instances[] = $instance;
			}
			elseif(isset($dash_blocks[$instance->type])) {
				// Do not add this dashboard block to the block instance list on the theme page
			}
			else {
				$instance->invalid_message = _t('This data is for a block of type "%s", which is no longer provided by a theme or plugin.', array($instance->type));
				$invalid_block_instances[] = $instance;
			}
		}
		$this->theme->blocks = $block_types;
		$this->theme->block_instances = $block_instances;
		$this->theme->invalid_block_instances = $invalid_block_instances;
	}
}
?>
