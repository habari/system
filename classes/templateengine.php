<?php
/**
 * Habari TemplateEngine abstract base class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
 * The TemplateEngine is an abstract base class to allow any template
 * engine to supply templates for the UI.  For an example
 * implementation, see RawPHPEngine or SmartyEngine
 *   
 */
abstract class TemplateEngine {
  protected $template_dir= null; // directory where the template resides
  
  abstract function __construct(); // virtual - implement in derived class

  /**
   * A function which outputs the result of a transposed
   * template to the output stream
   * 
   * @param template  Name of template to display
   */
  abstract function display($template); // virtual - implement in derived class

  /** 
   * A function which returns the content of the transposed
   * template as a string
   * 
   * @param template  Name of template to fetch
   */
  abstract function fetch($template); // virtual - implement in derived class

  /** 
   * Assigns a variable to the template engine for use in 
   * constructing the template's output.
   * 
   * @param key name of variable
   * @param value value of variable
   */
  abstract function assign($key, $value=""); // virtual - implement in derived class

  /** 
   * Appends to an existing variable more values
   * 
   * @param key name of variable
   * @param value value of variable
   */
   abstract function append($key, $value=""); // virtual - implement in derived class

  /** 
   * Sets the directory for the engine to find templates
   * 
   * @param dir Directory path
   */
  public function set_template_dir($dir) {
    $this->template_dir= $dir;
  }
}


?>

