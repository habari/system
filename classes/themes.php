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

			$themes = array_filter( $themes, create_function( '$a', 'return file_exists( $a . "/theme.xml" );' ) );
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
			$themedata = array();
			foreach ( self::get_all() as $theme_dir => $theme_path ) {
				$themedata['dir'] = $theme_dir;
				$themedata['path'] = $theme_path;

				$themedata['info'] = simplexml_load_file( $theme_path . '/theme.xml' );
				if ( $themedata['info']->getName() != 'pluggable' || (string) $themedata['info']->attributes()->type != 'theme' ) {
					$themedata['screenshot'] = Site::get_url( 'admin_theme' ) . "/images/screenshot_default.png";
					$themedata['info']->description = '<span class="error">' . _t( 'This theme is a legacy theme that is not compatible with Habari ' ) . Version::get_habariversion() . '. <br><br>Please update your theme.</span>';
					$themedata['info']->license = '';
				}
				else {
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

		if ( !isset( $themes[$theme_dir] ) ) {
			$theme_exists = false;
			foreach ( $themes as $themedir ) {
				if ( file_exists( Utils::end_in_slash( $themedir ) . 'theme.xml' ) ) {
					$theme_dir = basename( $themedir );
					Options::set( 'theme_dir', basename( $themedir ) );
					$theme_exists = true;
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
	 * @return array An array of Theme data
	 **/
	public static function get_active( $nopreview = false )
	{
		$theme = new QueryRecord();
		$theme->theme_dir = Themes::get_active_theme_dir( $nopreview );

		$data = simplexml_load_file( Utils::end_in_slash( $theme->theme_dir ) . 'theme.xml' );
		foreach ( $data as $name=>$value ) {
			$theme->$name = (string) $value;
		}
		$theme->xml = $data;
		return $theme;
	}

	private static function get_by_name($name) {
		$themes = self::get_all_data();
		foreach($themes as $theme) {
			if($name == $theme['info']->name) {
				return $theme;			}
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
	 * function activate_theme
	 * Updates the database with the name of the new theme to use
	 * @param string the name of the theme
	**/
	public static function activate_theme( $theme_name, $theme_dir )
	{
		$ok = true;
		$ok = Plugins::filter( 'activate_theme', $ok, $theme_name ); // Allow plugins to reject activation
		if($ok) {
			$old_active_theme = Themes::create();
			Plugins::act_id( 'theme_deactivated', $old_active_theme->plugin_id(), $old_active_theme->name, $old_active_theme ); // For the theme itself to react to its deactivation
			Plugins::act( 'theme_deactivated_any', $old_active_theme->name, $old_active_theme ); // For any plugin to react to its deactivation
			Options::set( 'theme_name', $theme_name );
			Options::set( 'theme_dir', $theme_dir );
			$new_active_theme = Themes::create();
			
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
		$_SESSION['user_theme_name'] = $theme_name;
		$_SESSION['user_theme_dir'] = $theme_dir;
		// Execute the theme's activated action
		$preview_theme = Themes::create();
		Plugins::act_id( 'theme_activated', $preview_theme->plugin_id(), $theme_name, $preview_theme );
		EventLog::log( _t( 'Previewed Theme: %s', array( $theme_name ) ), 'notice', 'theme', 'habari' );
	}
	
	/**
	 * Cancel the viewing of any preview theme
	 */
	public static function cancel_preview()
	{
		if ( isset( $_SESSION['user_theme_name'] ) ) {
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
	public static function create( $name = '', $template_engine = '', $theme_dir = '' )
	{
		if ( $name != '' ) {
			/*
			 * A theme name ( or more information ) was supplied.  This happens when we
			 * want to use a pre-installed theme ( for instance, the
			 * installer theme. )
			 */
			if ( $template_engine != '' ) {
				/* we load template engine from specified args, not DB */
				$themedata = new QueryRecord();
				$themedata->name = func_get_arg( 0 );
				$themedata->template_engine = $template_engine;
				$themedata->theme_dir = $themedata->name;
				$themedata->version = 0;
				if ( $theme_dir != '' ) {
					$themedata->theme_dir = $theme_dir;
				}
				else {
					$themedata->theme_dir = HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
				}
			}
			else {
				/* lookup in DB for template engine info. */
				$themedata = self::get_by_name( $name );
				if ( empty( $themedata ) ) {
					die( _t( 'Theme not installed.' ) );
				}
				$themedata->theme_dir = HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
			}
		}
		else {
			// Grab the theme from the database
			$themedata = self::get_active();
			if ( empty( $themedata ) ) {
				die( _t( 'Theme not installed.' ) );
			}
		}

		// Set the default theme file
		$themefile = 'theme.php';
		if(isset($themedata->class['file']) && (string)$themedata->xml->class['file'] != '') {
			$themefile = (string)$themedata->xml->class['file'];
		}

		if ( file_exists( $themedata->theme_dir . $themefile ) ) {
			include_once( $themedata->theme_dir . $themefile );
		}

		if ( isset( $themedata->class ) ) {
			$classname = $themedata->class;
		}
		else {
			$classname = self::class_from_filename( $themedata->theme_dir . $themefile );
		}

		// the final fallback, for the admin "theme"
		if ( $classname == '' ) {
			$classname = 'Theme';
		}

		$created_theme = new $classname( $themedata );
		$created_theme->upgrade();
		Plugins::act_id( 'init_theme', $created_theme->plugin_id(), $created_theme );
		Plugins::act( 'init_theme_any', $created_theme );
		return $created_theme;

	}

	public static function class_from_filename( $file, $check_realpath = false )
	{
		if ( $check_realpath ) {
			$file = realpath( $file );
		}

		foreach ( self::get_theme_classes() as $theme ) {
			$class = new ReflectionClass( $theme );
			$classfile = str_replace( '\\', '/', $class->getFileName() );
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

	public static function get_theme_classes()
	{
		$classes = get_declared_classes();
		return array_filter( $classes, array( 'Themes', 'extends_theme' ) );
	}

	public static function extends_theme( $class )
	{
		$parents = class_parents( $class, false );
		return in_array( 'Theme', $parents );
	}
}

?>
