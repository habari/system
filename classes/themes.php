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
		$query= '
			SELECT id, name, version, template_engine, theme_dir, is_active
			FROM ' . DB::table( 'themes' );
		return DB::get_results( $query, array(), 'QueryRecord' );
	}
	
	/**
	 * Returns the active theme information from the database
	 * @return array An array of Theme data
	 **/	 	 	 	 	
	public static function get_active()
	{
		$query = '
			SELECT id, name, version, template_engine, theme_dir 
          	FROM ' . DB::table( 'themes' ) . '
          	WHERE is_active=1';
		return DB::get_row( $query, array(), 'QueryRecord' );
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
	public function create()
	{
		if ( func_num_args() > 0 ) {
			/* 
			 * A theme name ( or more information ) was supplied.  This happens when we
			 * want to use a pre-installed theme ( for instance, the
			 * installer theme. )
			 */
			if ( func_num_args() >= 2 ) {
				/* we load template engine from specified args, not DB */
				$themedata = new QueryRecord();
				$themedata->name= func_get_arg( 0 );
				$themedata->template_engine= func_get_arg( 1 );
				$themedata->theme_dir = $themedata->name;
				$themedata->version = 0;
				if( func_num_args() == 3 ) {
					$themedata->theme_dir= func_get_arg( 2 );
				}
				else {
					$themedata->theme_dir= HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
				}
			}
			else {
				/* lookup in DB for template engine info. */
				$themedata= self::get_by_name( func_get_arg( 0 ) );
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
			$themedata->theme_dir= HABARI_PATH . '/user/themes/' . $themedata->theme_dir . '/';
		}
		
		$classname = 'Theme';
		/**
		 * @todo Should we include_once a theme's theme.php file here?
		 **/
		/*		 		
		if( file_exists( $themedata->theme_dir . 'theme.php' ) ) {
			include_once( $themedata->theme_dir . 'theme.php' );
		}
		*/
		/**
		 * If a theme provides a descendant class, it should be used instead of Theme.		 
		 **/		 		
		return new $classname($themedata);
		
	}
}

?>
