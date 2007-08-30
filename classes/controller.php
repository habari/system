<?php
/**
 * Class which handles incoming requests and drives the 
 * MVC strategy for building the model and assigning to 
 * a view.
 * 
 * @package Habari 
 */
class Controller extends Singleton {
  public $base_url= '';        // base url for site
  private $stub= '';            // stub supplied by rewriter
  private $action= '';          // action name (string)
  private $handler= null;       // the action handler object

  /**
   * Enables singleton working properly
   * 
   * @see singleton.php
   */
  static protected function instance() {
    return parent::instance(get_class());
  }

  /**
   * Returns the base URL
   *
   * @return string base URL
   */
  public function get_base_url() {
    return Controller::instance()->base_url;
  }

  /**
   * Returns the stub in its entirety
   *
   * @return  string  the URL incoming stub
   */
  public function get_stub() {
    return Controller::instance()->stub;
  }

  /**
   * Returns the action
   *
   * @return  string name of action
   */
  public function get_action() {
    return Controller::instance()->action;
  }
  
  /**
   * Returns the action handler
   * 
   * @return  object  handler object
   */
  public function get_handler() {
    return Controller::instance()->handler;
  }
  
  /**
   * A convenience method for returning a handler variable (handler_var).
   * This includes parameters set on the url, and fields submitted by POST.
   * The alternative to this, while possible to write, is just too long.
   * @param string $name The name of the variable to return.
   * @return mixed The value of that variable in the handler
   **/	  	 	   
  public static function get_var( $name )
  {
  	return Controller::instance()->handler->handler_vars[ $name ];
  }

  /**
   * Parses the requested URL.  Automatically 
   * translates URLs coming in from mod_rewrite and parses
   * out any action and parameters in the slug.
   */
  static public function parse_request() {
    /* Local scope variable caching */
    $controller= Controller::instance();

    /* Grab the base URL from the Site class */
    $controller->base_url= Site::get_path('base', true);

    /* Start with the entire URL coming from web server... */
    $start_url= ( isset($_SERVER['REQUEST_URI']) 
      ? $_SERVER['REQUEST_URI'] 
      : $_SERVER['SCRIPT_NAME'] . 
        ( isset($_SERVER['PATH_INFO']) 
        ? $_SERVER['PATH_INFO'] 
        : '') . 
        ( (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != '')) 
          ? '?' . $_SERVER['QUERY_STRING'] 
          : ''));
    
    /* Strip out the base URL from the requested URL */
    /* but only if the base URL isn't / */
    if ( '/' != $controller->base_url)
	    $start_url= str_replace($controller->base_url, '', $start_url);
    
    /* Trim off any leading or trailing slashes */
    $start_url= trim($start_url, '/');

    /* Remove the querystring from the URL */
    if ( strpos($start_url, '?') !== FALSE )
      list($start_url, $query_string)= explode('?', $start_url);

    /* Return $_GET values to their proper place */
    if( !empty($query_string) )
        parse_str($query_string, $_GET);

    /* Undo what magic_quotes_gpc might have wrought */
    Utils::revert_magic_quotes_gpc();

    $controller->stub= $start_url;

    /* Grab the URL filtering rules from DB */
    $matched_rule= URL::parse($controller->stub);

    if ($matched_rule !== FALSE) {
      /* OK, we have a matching rule.  Set the action and create a handler */
      $controller->action= $matched_rule->action;
      $controller->handler= new $matched_rule->handler();
      
      /* Insert the regexed submatches as the named parameters */
      $controller->handler->handler_vars['entire_match']= $matched_rule->entire_match; // The entire matched string is returned at index 0
      foreach ($matched_rule->named_arg_values as $named_arg_key=>$named_arg_value)
        $controller->handler->handler_vars[$named_arg_key]= $named_arg_value;

      /* Also, we musn't forget to add the GET and POST vars into the action's settings array */
      $controller->handler->handler_vars= array_merge($controller->handler->handler_vars, $_GET, $_POST);
      return true;
    }
    else {
      die('Unmatched rule: ' . print_r(Controller::instance())); /** @todo Standard error handling */
    }
  }

  /**
   * Handle the requested action by firing off the matched handler action(s)
   */
  public function dispatch_request() {
    /* OK, set the wheels in motion... */
    Controller::instance()->handler->act(Controller::instance()->action);
  }
}

?>
