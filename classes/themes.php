<?php
/**
 * Habari Themes Class
 *
 * @package Habari
 */
 

/**
 * 
 */      
class Themes extends ArrayObject
{
	/**
	 * Returns the active theme information from the database
	 * @return array An array of Theme data
	 **/	 	 	 	 	
	static function get_active()	{
		
		$query = 'SELECT id, name, version, template_engine, theme_dir 
          		FROM ' . DB::o()->themes . '
          		WHERE is_active=1';
		$results = DB::get_row($query);
    return $results;
	}
}
?>

