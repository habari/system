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
class Theme {
  private $name= null;
  private $version= null;
  public $template_engine= null;
  public $theme_dir= null;
  public $config_vars= array();
  
	/**
	 * Constructor for theme
   * 
   * If no parameter is supplied, then the constructor 
   * Loads the active theme from the database.
   * 
   * If no theme option is set, a fatal error is thrown
   * 
   * @param name            (optional) override the default theme lookup
   * @param template_engine (optional) specify a template engine
   * @param theme_dir       (optional) specify a theme directory
	 */	 	 	 	 	
	public function __construct() {
    if (func_num_args() > 0) {
      /* 
       * A theme name (or more information) was supplied.  This happens when we
       * want to use a pre-installed theme (for instance, the
       * installer theme.)
       */
      if (func_num_args() >= 2) {
        /* we load template engine from specified args, not DB */
        $this->name= func_get_arg(0);
        $template_engine= func_get_arg(1);
        $directory= HABARI_PATH . '/system/themes/' . $this->name . '/';
        $directory= (func_num_args()==3 ? func_get_arg(2) : $directory);
        $this->template_engine= new $template_engine();
        $this->template_engine->set_template_dir($directory);
        return;
      }
      else {
        /* lookup in DB for template engine info. */
        $theme= Themes::get_by_name(func_get_arg(0));
      }
     }
    else {
      // Grab the theme from the database
      $theme= Themes::get_active();
    }
    if ( empty($theme) )
      die('Theme not installed.');
    $this->name= $theme->name;
    $this->version= $theme->version;
    $this->theme_dir= HABARI_PATH . '/user/themes/' . $theme->theme_dir . '/';
    //    $this->config_vars= $theme['config_vars'];
    // Set up the corresponding engine to handle the templating
    $this->template_engine= new $theme->template_engine();
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
    $info_file= HABARI_PATH . '/user/themes/' . $theme . '.info';
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

  /**
   * Helper passthru function to avoid having to always
   * call $theme->template_engine->display('template_name');
   */
  public function display($template_name) {
    $this->template_engine->display($template_name);
  }

  /**
   * Helper passthru function to avoid having to always
   * call $theme->template_engine->assign('key', 'value');
   */
  public function assign($key, $value) {
    $this->template_engine->assign($key, $value);
  }
}
?>
