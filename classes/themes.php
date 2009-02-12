<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
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
			$dirs = array( HABARI_PATH . '/system/themes/*' , HABARI_PATH . '/3rdparty/themes/*', HABARI_PATH . '/user/themes/*');
			if( Site::is('multi') ) {
				$dirs[] = Site::get_dir('config') . '/themes/*';
			}
			$themes = array();
			foreach($dirs as $dir) {
				$themes = array_merge( $themes, Utils::glob( $dir, GLOB_ONLYDIR | GLOB_MARK ) );
			}

			$themes = array_filter( $themes, create_function('$a', 'return file_exists($a . "/theme.xml");') );
			$themefiles = array_map('basename', $themes);
			self::$all_themes = array_combine($themefiles, $themes);
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
			foreach(self::get_all() as $theme_dir => $theme_path ) {
				$themedata['dir'] = $theme_dir;
				$themedata['path'] = $theme_path;

				$themedata['info'] = simplexml_load_file( $theme_path . '/theme.xml' );

				if ( $screenshot = Utils::glob( $theme_path . '/screenshot.{png,jpg,gif}' , GLOB_BRACE) ) {
					$themedata['screenshot'] = Site::get_url( 'habari' ) . dirname(str_replace( HABARI_PATH, '', $theme_path )) . '/' . basename( $theme_path ) . "/" . basename(reset($screenshot));
				}
				else {
					$themedata['screenshot'] = Site::get_url( 'habari' ) . "/system/admin/images/screenshot_default.png";
				}

				self::$all_data[$theme_dir] = $themedata;
			}
		}
		return self::$all_data;
	}

	/**
	 * Returns the active theme's full directory path.
	 * @return string The full path to the active theme directory
	 */
	private static function get_active_theme_dir()
	{
		$theme_dir = Options::get('theme_dir');
		$themes = Themes::get_all();

		if (!isset($themes[$theme_dir]))
		{
			$theme_exists = false;
			foreach ($themes as $themedir) {
				if (file_exists(Utils::end_in_slash($themedir) . 'theme.xml')) {
					$theme_dir = basename($themedir);
					Options::set('theme_dir', basename($themedir));
					$theme_exists = true;
					break;
				}
			}
			if (!$theme_exists) {
				die( _t('There is no valid theme currently installed.') );
			}
		}
		return $themes[$theme_dir];
	}

	/**
	 * Returns the active theme information from the database
	 * @return array An array of Theme data
	 **/
	public static function get_active()
	{
		$theme = new QueryRecord();
		$theme->theme_dir = Themes::get_active_theme_dir();

		$data = simplexml_load_file( Utils::end_in_slash($theme->theme_dir) . 'theme.xml' );
		foreach ( $data as $name=>$value) {
			$theme->$name = (string) $value;
		}
		return $theme;
	}

	/**
	 * Returns theme information for the active theme -- dir, path, theme.xml, screenshot url
	 * @return array An array of Theme data
	 */
	public static function get_active_data()
	{
		$all_data = Themes::get_all_data();
		$active_theme_dir = basename(Themes::get_active_theme_dir());
		$active_data = $all_data[$active_theme_dir];
		return $active_data;
	}


	/**
	 * functiona activate_theme
	 * Updates the database with the name of the new theme to use
	 * @param string the name of the theme
	**/
	public static function activate_theme( $theme_name, $theme_dir )
	{
		Options::set( 'theme_name', $theme_name );
		Options::set( 'theme_dir', $theme_dir );
		EventLog::log( _t( 'Activated Theme: %s', array( $theme_name ) ), 'notice', 'theme', 'habari' );
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
				if( $theme_dir != '' ) {
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
					die( _t('Theme not installed.') );
				}
				$themedata->theme_dir = HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
			}
		}
		else {
			// Grab the theme from the database
			$themedata = self::get_active();
			if ( empty( $themedata ) ) {
				die( _t('Theme not installed.') );
			}
		}

		$classname = 'Theme';
		/**
		 * @todo Should we include_once a theme's theme.php file here?
		 **/
		if( file_exists( $themedata->theme_dir . 'theme.php' ) ) {
			include_once( $themedata->theme_dir . 'theme.php' );
			if( defined('THEME_CLASS') ) {
				$classname = THEME_CLASS;
			}
		}

		$created_theme = new $classname($themedata);
		Plugins::act('init_theme');
		return $created_theme;

	}
}

?>
