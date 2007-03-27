<?php

/**
 * @todo document 
 */      
class Themes
{
	/**
	 * Returns the theme information from the database
	 * @return array An array of Theme data
	 **/	 	 	 	 	
	public static function get_all()
	{
		$theme_dirs= glob( HABARI_PATH . '/user/themes/*', GLOB_ONLYDIR | GLOB_MARK );
		if( Site::is('multi') )
		{
			$site_dirs= glob( Site::get_dir('config') . '/themes/*', GLOB_ONLYDIR | GLOB_MARK );
			if ( is_array( $site_dirs ) && ! empty( $site_dirs ) )
			{
				$theme_dirs= array_merge( $theme_dirs, $site_dirs );
			}
		}
		return $theme_dirs;
	}
	
	/**
	 * Returns the active theme information from the database
	 * @return array An array of Theme data
	 **/	 	 	 	 	
	public static function get_active()
	{
		$theme= new QueryRecord();
		$theme->theme_dir= Options::get('theme_dir');
		$data= simplexml_load_file( $theme->theme_dir . '/theme.xml' );
		foreach ( $data as $name=>$value)
		{
			$theme->$name= (string) $value;
		}
		return $theme;
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
	public function create( $name= '', $template_engine= '', $theme_dir= '' )
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
				$themedata->name= func_get_arg( 0 );
				$themedata->template_engine= $template_engine;
				$themedata->theme_dir = $themedata->name;
				$themedata->version = 0;
				if( $theme_dir != '' ) {
					$themedata->theme_dir= $theme_dir;
				}
				else {
					$themedata->theme_dir= HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
				}
			}
			else {
				/* lookup in DB for template engine info. */
				$themedata= self::get_by_name( $name );
				if ( empty( $themedata ) ) {
					die( 'Theme not installed.' );
				}
				$themedata->theme_dir= HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
			}
		}
		else {
			// Grab the theme from the database
			$themedata= self::get_active();
			if ( empty( $themedata ) ) {
				die( 'Theme not installed.' );
			}
		}
		
		$classname= 'Theme';
		/**
		 * @todo Should we include_once a theme's theme.php file here?
		 **/
		if( file_exists( $themedata->theme_dir . 'theme.php' ) ) {
			include_once( $themedata->theme_dir . 'theme.php' );
			if( defined('THEME_CLASS') ) {
				$classname= THEME_CLASS; 
			}
		}

		return new $classname($themedata);
		
	}
}

?>
