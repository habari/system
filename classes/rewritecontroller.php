<?php
/**
 * Class which handles translation of URLshttp://www.website.com/index.php
 */
class RewriteController {
  public $base_url;
  public $stub;
  public $action;
  
  /**
   * Constructor for RewriteController.  Automatically 
   * translates URLs coming in from mod_rewrite and parses
   * out any action and parameters in the slug.
   */
  public function __construct() {

    /* Grab the base URL from the DB */
    $this->base_url= Options::get('base_url');

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
    $start_url= str_replace($this->base_url, '', $start_url);
    
    /* Trim off any trailing slashes */
    $start_url= rtrim($start_url, '/');

    /* Remove the querystring from the URL */
    if ( strpos($start_url, '?') !== FALSE )
      list($start_url, )= explode('?', $start_url);

    $this->stub= $start_url;

    /* Grab the URL filtering rules from DB */
    $rules= $this->get_rules();

    /* 
     * Run the stub through the regex matcher
     */
    $pattern_matches= array();
    $rule_count= count($rules);
    $i=0;
    for ($i=0;$i<$rule_count;++$i) {
      if ( 1 == preg_match(
                $rules[$i]['regex']
                , $this->stub
                , $pattern_matches) ) {

        /* OK, we have a matching rule.  Fire off the actions */
        $this->action= new $rules[$i]['action']();

        /* Insert the regexed submatches as the named parameters */
        $submatches_count= count($pattern_matches);
        $this->action->entire_match= $pattern_matches[0]; // The entire matched string is returned at index 0
        for ($j=1;$j<$submatches_count;++$j) {
          $this->action->settings[$rules[$i]['named_args'][($j - 1)]]= $pattern_matches[$j];
        }
        
        /* Also, we musn't forget to add the GET and POST vars into the action's settings array */
        $this->action->settings= array_merge($this->action->settings, $_GET, $_POST);
        break;
     }
    }
  }

  /**
   * Handle the requested action by firing off the matched action(s)
   */
  public function dispatch_request() {
    /* OK, set the wheels in motion... */
    $this->action->act();
  }

  /**
   * Return a set of active URL filtering rules
   *
   * 
   * @note  We return the rules, as opposed to storing the rules as
   *        as either a global variable or a class member because once
   *        processed, the rules aren't valuable anymore and should go
   *        out of scope and therefore the memory gets released.
   * @todo  Store filters in database so users can freely edit
   */
  private function get_rules() {
   /**
     * Below is a sample set of regular expressions which 
     * are used to match against the incoming stub.
     * Rules are matched from top to bottom here, 
     * as this is likely to be the order in which 
     * pages naturally will be queried against the app
     * but the order of rules could easily be set in DB
     */
    $rules= array();
    $rules[]= array(
        'regex'=>'/^page\/([\d]+)[\/]{0,1}$/i'
      , 'action'=>'DisplayPosts'
      , 'named_args'=>array('page')
    );
    $rules[]= array(
        'regex'=>'/([1,2]{1}[\d]{3})\/([\d]{2})\/([\d]{2})[\/]{0,1}$/'
      , 'action'=>'DisplayPostsByDate'
      , 'named_args'=>array('year','month','day')
    );
    $rules[]= array(
        'regex'=>'/([1,2]{1}[\d]{3})\/([\d]{2})[\/]{0,1}$/' 
      , 'action'=>'DisplayPostsByMonth'
      , 'named_args'=>array('year','month')
    );
    $rules[]= array(
        'regex'=>'/([1,2]{1}[\d]{3})[\/]{0,1}$/'
      , 'action'=>'DisplayPostsByYear'
      , 'named_args'=>array('year')
    );
    $rules[]= array(
        'regex'=>'/^feed\/(atom|rs[sd])[\/]{0,1}$/i'
      , 'action'=>'DisplayFeed'
      , 'named_args'=>array('feed_type')
    );
    $rules[]= array(
        'regex'=>'/^tag\/([^\/]*)[\/]{0,1}$/i'
      , 'action'=>'DisplayPostsByTag'
      , 'named_args'=>array('tag')
    );
    $rules[]= array(
        'regex'=>'/^admin\/([^\/]*)[\/]{0,1}$/i'
      , 'action'=>'AdminPage'
      , 'named_args'=>array('action')
    );
    $rules[]= array(
        'regex'=>'/^user\/([^\/]*)[\/]{0,1}$/i'
      , 'action'=>'UserPage'
      , 'named_args'=>array('action')
    );
    $rules[]= array(
        'regex'=>'/([^\/]+)[\/]{0,1}$/i'
      , 'action'=>'DisplayPostBySlug'
      , 'named_args'=>array('slug')
    );
    $rules[]= array(
        'regex'=>'//'
      , 'action'=>'DisplayPosts'
      , 'named_args'=>array()
    );
     
    return $rules;
  }
}
?>
