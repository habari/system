<?php
/**
 * URL class which handles creation of URLs based on the rewrite
 * rules in the database.  Uses rules to construct pretty URLs for use
 * by the system and especially the theme's template engine
 * 
 * @package Habari
 */
class URL extends Singleton {
  private $rules= null; // static collection of rules (pulled from RewriteController)
 
  /**
   * Enables singleton working properly
   * 
   * @see singleton.php
   */
  static protected function instance() {
    return parent::instance(get_class());
  }

 
  /**
   * A simple caching mechanism to avoid reloading rule array
   */
  private function load_rules() {
    if (URL::instance()->rules != NULL)
      return;
    URL::instance()->rules= RewriteRules::get_active();
  }

  /** 
   * Builds the required pretty URL given a supplied
   * rule name and a set of placeholder replacement
   * values and returns the built URL.
   * 
   * <code>
   * URL::get('display_posts_by_date', 
   *  array('year'=>'2000'
   *    , 'month'=>'05'
   *    , 'day'=>'01');
   * </code>
   * 
   * @param rule  string identifier for the rule which would build the URL
   * @param args  (optional) array of placeholder replacement values
   */
  static public function get($rule_name, $args= array()) {
    /*
     * This code is here for backwards compatibility with the old
     * URL API which allowed passing arguments as a querystring-style
     * long string or an array.  Would be nice to remove this and standardize
     * on one method or the other...
     * @todo  Standardize the argument input.
     */
    if (! is_array($args))
      parse_str($args, $args); 
    $writer= URL::instance();
    $writer->load_rules();
    if (isset($writer->rules[$rule_name])) {
      $rule= $writer->rules[$rule_name];
      $url= $rule->build_str;
      foreach ($rule->named_args as $replace) {
        $url= str_replace('{$' . $replace . '}', $args[$replace], $url);
        /* 
         * Remove from the argument list so we can append 
         * any outlier args as query string args
         */
        unset($args[$replace]);
      }
      /*
       * OK, now append any outliers passed in to the function
       * as query string arguments
       */
      if (count($args) > 0) {
        $url.= '?';
        foreach ($args as $key=>$value)
          $url .= $key . '=' . $value . '&';
        $url= rtrim($url, '&');
      }
      return 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '') . 
        '://' . $_SERVER['HTTP_HOST'] . '/' . Controller::get_base_url() . $url;
    }   
  }

  /**
   * Helper wrapper function.  Outputs the URL via echo.
   */
  static public function out($rule_name, $args= array()) {
    echo URL::get($rule_name, $args);
  }
}
?>
