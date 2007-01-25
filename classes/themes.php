<?php
/**
 * 
 */      
class Themes {
	/**
	 * Returns the theme information from the database
	 * @return array An array of Theme data
	 **/	 	 	 	 	
	public static function get_all()	{
		
		$query = 'SELECT id, name, version, template_engine, theme_dir, is_active 
          		FROM ' . DB::table('themes');
		return DB::get_results($query, array(), 'QueryRecord');
	}
	/**
	 * Returns the active theme information from the database
	 * @return array An array of Theme data
	 **/	 	 	 	 	
	public static function get_active()	{
		
		$query = 'SELECT id, name, version, template_engine, theme_dir 
          		FROM ' . DB::table('themes') . '
          		WHERE is_active=1';
		return DB::get_row($query, array(), 'QueryRecord');
	}
}
?>

