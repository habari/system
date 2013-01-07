<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Themes class
 *
 */
class Themes
{
	private static $all_themes = null;
	private static $all_data = null;

	/**
	 * Returns the theme dir and path information
	 * @return array An array of Theme data
	 **/
	public static function get_all()
	{
		if ( !isset( self::$all_themes ) ) {
			$dirs = array( HABARI_PATH . '/system/themes/*' , HABARI_PATH . '/3rdparty/themes/*', HABARI_PATH . '/user/themes/*' );
			if ( Site::is( 'multi' ) ) {
				$dirs[] = Site::get_dir( 'config' ) . '/themes/*';
			}
			$themes = array();
			foreach ( $dirs as $dir ) {
				$themes = array_merge( $themes, Utils::glob( $dir, GLOB_ONLYDIR | GLOB_MARK ) );
			}

			$themes = array_filter( $themes, function($a) {return file_exists( $a . "/theme.xml" );} );
			$themefiles = array_map( 'basename', $themes );
			self::$all_themes = array_combine( $themefiles, $themes );
		}
		return self::$all_themes;
	}

	/**
	 * Returns all theme information -- dir, path, theme.xml, screenshot url
	 * @return array An array of Theme data
	 **/
	public static function get_all_data()
	{
		if ( !isset( self::$all_data ) ) {
			foreach ( self::get_all() as $theme_dir => $theme_path ) {
				$themedata = array();
				$themedata['dir'] = $theme_dir;
				$themedata['path'] = $theme_path;
				$themedata['theme_dir'] = $theme_path;

				$themedata['info'] = simplexml_load_file( $theme_path . '/theme.xml' );
				if ( $themedata['info']->getName() != 'pluggable' || (string) $themedata['info']->attributes()->type != 'theme' ) {
					$themedata['screenshot'] = Site::get_url( 'admin_theme' ) . "/images/screenshot_default.png";
					$themedata['info']->description = '<span class="error">' . _t( 'This theme is a legacy theme that is not compatible with Habari ' ) . Version::get_habariversion() . '. <br><br>Please update your theme.</span>';
					$themedata['info']->license = '';
				}
				else {
					foreach ( $themedata['info'] as $name=>$value ) {
						if($value->count() == 0) {
							$themedata[$name] = (string) $value;
						}
						else {
							$themedata[$name] = $value->children();
						}
					}

					if ( $screenshot = Utils::glob( $theme_path . '/screenshot.{png,jpg,gif}', GLOB_BRACE ) ) {
						$themedata['screenshot'] = Site::get_url( 'habari' ) . dirname( str_replace( HABARI_PATH, '', $theme_path ) ) . '/' . basename( $theme_path ) . "/" . basename( reset( $screenshot ) );
					}
					else {
						$themedata['screenshot'] = Site::get_url( 'admin_theme' ) . "/images/screenshot_default.png";
					}
				}

				self::$all_data[$theme_dir] = $themedata;
			}
		}
		return self::$all_data;
	}

	/**
	 * Returns the name of the active or previewed theme
	 *
	 * @params boolean $nopreview If true, return the real active theme, not the preview
	 * @return string the current theme or previewed theme's directory name
	 */
	public static function get_theme_dir( $nopreview = false )
	{
		if ( !$nopreview && isset( $_SESSION['user_theme_dir'] ) ) {
			$theme_dir = $_SESSION['user_theme_dir'];
		}
		else {
			$theme_dir = Options::get( 'theme_dir' );
		}
		$theme_dir = Plugins::filter( 'get_theme_dir', $theme_dir );
		return $theme_dir;
	}

	/**
	 * Returns the active theme's full directory path.
	 * @params boolean $nopreview If true, return the real active theme, not the preview
	 * @return string The full path to the active theme directory
	 */
	private static function get_active_theme_dir( $nopreview = false )
	{
		$theme_dir = self::get_theme_dir( $nopreview );
		$themes = Themes::get_all();

		// If our active theme directory has gone missing, iterate through the others until we find one we can use and activate it.
		if ( !isset( $themes[$theme_dir] ) ) {
			$theme_exists = false;
			foreach ( $themes as $themedir ) {
				if ( file_exists( Utils::end_in_slash( $themedir ) . 'theme.xml' ) ) {
					$theme_dir = basename( $themedir );
					EventLog::log( _t( "Active theme directory no longer available.  Falling back to '{$theme_dir}'" ), 'err', 'theme', 'habari' );
					Options::set( 'theme_dir', basename( $themedir ) );
					$fallback_theme = Themes::create();
					Plugins::act_id( 'theme_activated', $fallback_theme->plugin_id(), $theme_dir, $fallback_theme );
					$theme_exists = true;
					// Refresh to the newly "activated" theme to ensure it takes the options that have just been set above and doesn't produce any errors.
					Utils::redirect();
					break;
				}
			}
			if ( !$theme_exists ) {
				die( _t( 'There is no valid theme currently installed.' ) );
			}
		}
		return $themes[$theme_dir];
	}

	/**
	 * Returns the active theme information from the database
	 * @params boolean $nopreview If true, return the real active theme, not the preview
	 * @return QueryRecord An array of Theme data
	 **/
	public static function get_active( $nopreview = false )
	{
		$theme = array();
		$theme['theme_dir'] = Themes::get_active_theme_dir( $nopreview );

		$data = simplexml_load_file( Utils::end_in_slash( $theme['theme_dir'] ) . 'theme.xml' );
		foreach ( $data as $name=>$value ) {
			$theme[$name] = (string) $value;
		}
		$theme['xml'] = $data;
		return $theme;
	}

	private static function get_by_name($name) {
		$themes = self::get_all_data();
		foreach($themes as $theme) {
			if($name == $theme['info']->name) {
				return $theme;
			}
		}
		return false;
	}


	/**
	 * Returns theme information for the active theme -- dir, path, theme.xml, screenshot url
	 * @params boolean $nopreview If true, return the real active theme, not the preview
	 * @return array An array of Theme data
	 */
	public static function get_active_data( $nopreview = false )
	{
		$all_data = Themes::get_all_data();
		$active_theme_dir = basename( Themes::get_active_theme_dir( $nopreview ) );
		$active_data = $all_data[$active_theme_dir];
		return $active_data;
	}

	/**
	 * Ensure that a theme meets requirements for activation/preview
	 * @static
	 * @param string $theme_dir the directory of the theme
	 * @return bool True if the theme meets all requirements
	 */
	public static function validate_theme( $theme_dir )
	{
		$all_themes = Themes::get_all_data();
		// @todo Make this a closure in php 5.3
		$theme_names = Utils::array_map_field($all_themes, 'name');

		$theme_data = $all_themes[$theme_dir];

		$ok = true;

		if(isset($theme_data['info']->parent) && !in_array((string)$theme_data['info']->parent, $theme_names)) {
			Session::error(_t('This theme requires the parent theme named "%s" to be present prior to activation.', array($theme_data['info']->parent)));
			$ok = false;
		}

		if(isset($theme_data['info']->requires)) {
			$provided = Plugins::provided();
			foreach($theme_data['info']->requires->feature as $requirement) {
				if(!isset($provided[(string)$requirement])) {
					Session::error(_t('This theme requires the feature "<a href="%2$s">%1$s</a>" to be present prior to activation.', array((string)$requirement, $requirement['url'])));
					$ok = false;
				}
			}
		}

		return $ok;
	}
	/**
	 * function activate_theme
	 * Updates the database with the name of the new theme to use
	 * @param string the name of the theme
	**/
	public static function activate_theme( $theme_name, $theme_dir )
	{
		$ok = Themes::validate_theme($theme_dir);

		$ok = Plugins::filter( 'activate_theme', $ok, $theme_name ); // Allow plugins to reject activation
		if($ok) {
			$old_active_theme = Themes::create();
			Plugins::act_id( 'theme_deactivated', $old_active_theme->plugin_id(), $old_active_theme->name, $old_active_theme ); // For the theme itself to react to its deactivation
			Plugins::act( 'theme_deactivated_any', $old_active_theme->name, $old_active_theme ); // For any plugin to react to its deactivation
			Options::set( 'theme_name', $theme_name );
			Options::set( 'theme_dir', $theme_dir );
			$new_active_theme = Themes::create($theme_name);

			// Set version of theme if it wasn't installed before
			$versions = Options::get( 'pluggable_versions' );
			if(!isset($versions[get_class($new_active_theme)])) {
				$versions[get_class($new_active_theme)] = $new_active_theme->get_version();
				Options::set( 'pluggable_versions', $versions );
			}

			// Run activation hooks for theme activation
			Plugins::act_id( 'theme_activated', $new_active_theme->plugin_id(), $theme_name, $new_active_theme ); // For the theme itself to react to its activation
			Plugins::act( 'theme_activated_any', $theme_name, $new_active_theme ); // For any plugin to react to its activation
			EventLog::log( _t( 'Activated Theme: %s', array( $theme_name ) ), 'notice', 'theme', 'habari' );
		}
		return $ok;
	}

	/**
	 * Sets a theme to be the current user's preview theme
	 *
	 * @param string $theme_name The name of the theme to preview
	 * @param string $theme_dir The directory of the theme to preview
	 */
	public static function preview_theme( $theme_name, $theme_dir )
	{
		$ok = Themes::validate_theme($theme_dir);

		if($ok) {
			$_SESSION['user_theme_name'] = $theme_name;
			$_SESSION['user_theme_dir'] = $theme_dir;
			// Execute the theme's activated action
			$preview_theme = Themes::create();
			Plugins::act_id( 'theme_activated', $preview_theme->plugin_id(), $theme_name, $preview_theme );
			EventLog::log( _t( 'Previewed Theme: %s', array( $theme_name ) ), 'notice', 'theme', 'habari' );
		}

		return $ok;
	}

	/**
	 * Cancel the viewing of any preview theme
	 */
	public static function cancel_preview()
	{
		if ( isset( $_SESSION['user_theme_name'] ) ) {
			// Execute the theme's deactivated action
			$preview_theme = Themes::create();
			Plugins::act_id( 'theme_deactivated', $preview_theme->plugin_id() );
			EventLog::log( _t( 'Canceled Theme Preview: %s', array( $_SESSION['user_theme_name'] ) ), 'notice', 'theme', 'habari' );
			unset( $_SESSION['user_theme_name'] );
			unset( $_SESSION['user_theme_dir'] );
		}
	}

	/**
	 * Returns a named Theme descendant.
	 * If no parameter is supplied, then
	 * load the active theme from the database.
	 *
	 * If no theme option is set, a fatal error is thrown
	 *
	 * @param name            ( optional ) override the default theme lookup
	 * @param template_engine ( optional ) specify a template engine
	 * @param theme_dir       ( optional ) specify a theme directory
	 **/
	public static function create( $name = null, $template_engine = null, $theme_dir = null )
	{
		static $bound = array();

		$hash = md5(serialize(array($name, $template_engine, $theme_dir)));
		if(isset($bound[$hash])) {
			return $bound[$hash];
		}

 		// If the name is not supplied, load the active theme
		if(empty($name)) {
			$themedata = self::get_active();
			if ( empty( $themedata ) ) {
				die( _t( 'Theme not installed.' ) );
			}
		}
		// Otherwise, try to load the named theme from user themes that are present
		else {
			$themedata = self::get_by_name( $name );
		}
		// If a theme wasn't found by name, create a blank object
		if(!$themedata) {
			$themedata = array();
			$themedata['name'] = $name;
			$themedata['version'] = 0;
		}
		// If a specific template engine was supplied, use it
		if(!empty($template_engine)) {
			$themedata['template_engine'] = $template_engine;
		}
		// If a theme directory was supplied, use the directory that was supplied
		if(!empty($theme_dir)) {
			$themedata['theme_dir'] = $theme_dir;
		}

		// Set the default theme file
		$themefile = 'theme.php';
		if(isset($themedata['info']->class['file']) && (string)$themedata['info']->class['file'] != '') {
			$themefile = (string)$themedata->xml->class['file'];
		}

		// Convert themedata to QueryRecord for legacy purposes
		// @todo: Potentially break themes by sending an array to the constructor instead of this QueryRecord
		$themedata = new QueryRecord($themedata);


		// Deal with parent themes
		if(isset($themedata->parent)) {
			// @todo If the parent theme doesn't exist, provide a useful error
			$parent_data = self::get_by_name( $themedata->parent );
			$parent_themefile = 'theme.php';
			if(isset($parent_data['info']->class['file']) && (string)$parent_data['info']->class['file'] != '') {
				$parent_themefile = (string)$parent_data['info']->class['file'];
			}
			include_once($parent_data['theme_dir'] . $parent_themefile);

			$themedata->parent_theme_dir = Utils::single_array($parent_data['theme_dir']);
			$themedata->theme_dir = array_merge(Utils::single_array($themedata->theme_dir), $themedata->parent_theme_dir);
		}
		$primary_theme_dir = $themedata->theme_dir;
		$primary_theme_dir = is_array($primary_theme_dir) ? reset($primary_theme_dir) : $primary_theme_dir;

		// Include the theme class file
		if ( file_exists( $primary_theme_dir . $themefile ) ) {
			include_once( $primary_theme_dir . $themefile );
		}

		if ( isset( $themedata->class ) ) {
			$classname = $themedata->class;
		}
		else {
			$classname = self::class_from_filename( $primary_theme_dir . $themefile );
		}

		// the final fallback, for the admin "theme"
		if ( $classname == '' ) {
			$classname = 'Theme';
		}

		$created_theme = new $classname( $themedata );
		$created_theme->upgrade();
		Plugins::act_id( 'init_theme', $created_theme->plugin_id(), $created_theme );
		Plugins::act( 'init_theme_any', $created_theme );

		$bound[$hash] = $created_theme;
		return $created_theme;
	}

	public static function class_from_filename( $file, $check_realpath = false )
	{
		if ( $check_realpath ) {
			$file = realpath( $file );
		}

		$theme_classes = self::get_theme_classes();
		foreach ( $theme_classes as $theme ) {
			$class = new ReflectionClass( $theme );
			$classfile = str_replace( '\\', '/', $class->getFileName() );
			$file = str_replace( '\\', '/', $file );
			if ( $classfile == $file ) {
				return $theme;
			}
		}
		// if we haven't found the plugin class, try again with realpath resolution:
		if ( $check_realpath ) {
			// really can't find it
			return false;
		}
		else {
			return self::class_from_filename( $file, true );
		}
	}

	/**
	 * Get a list of classes that extend Theme
	 * @static
	 * @return array List of string names of classes that extend Theme
	 */
	public static function get_theme_classes()
	{
		$classes = get_declared_classes();
		foreach($classes as $class) {
			$parents = class_parents( $class, false );
			if(count($parents) > 0) {
				$class_parents[$class] = $parents;
			}
		}

		$theme_classes = array();
		do {
			$delta = count($theme_classes);
			foreach($class_parents as $class => $parents) {
				if(count(array_intersect($theme_classes + array('Theme'), $parents))>0) {
					$theme_classes[$class] = $class;
				}
			}
		} while($delta != count($theme_classes));
		return $theme_classes;
	}
}

?>
