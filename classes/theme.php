<?php
/**
 * Habari Theme Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
 * The Theme class is the behind-the-scenes representation of 
 * of a set of UI files that compose the visual theme of the blog
 *   
 */
class Theme 
{
  private $name= null;
  private $version= null;
  public $template_engine= null;
  public $theme_dir= null;
  public $config_vars= array();
  
	/**
	 * Constructor for theme
   * 
   * Loads the active theme from the serialized array
   * of theme data stored in the options table.
   * 
   * If no theme option is set, a fatal error is thrown
	 */	 	 	 	 	
	public function __construct( ) {
    // Grab the theme from the database
    $theme= Themes::get_active();
    if ( empty($theme) )
      die('Theme not installed.');
    $this->name= $theme->name;
    $this->version= $theme->version;
    $this->theme_dir= HABARI_PATH . '/themes/' . $theme->theme_dir . '/';

//    $this->config_vars= $theme['config_vars'];

    // Set up the corresponding engine to handle the templating
    $this->template_engine= new $theme->template_engine;
    $this->template_engine->set_template_dir($this->theme_dir);
	}

  /**
   * Loads a theme's metadata from an INI file in theme's
   * directory.
   * 
   * @param theme Name of theme to retrieve metadata about
   * @note  This may change to an XML file format
   */
  public function info($theme) {
    $info_file= HABARI_PATH . '/themes/' . $theme . '.info';
    if ( file_exists($info_file) )
      $theme_data= parse_ini_file($info_file); // Use NO sections INI
    if (! empty($theme_data) ) {
      // Parse out the good stuff
      $named_member_vars= array('name','version','template_engine','theme_dir');
      foreach ($theme_data as $key=>$value) {
        $key= strtolower($key);
        if ( in_array($key, $named_member_vars)) 
          $this->$key= $value;
        else 
          $this->config_vars[$key]= $value;
      }
    } 
  } 
}
$test= new Theme();
$test
?>
