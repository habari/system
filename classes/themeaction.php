<?php
/**
 * A class which handles actions that output information
 * of some kind.  These actions typically display data
 */
class ThemeAction extends Action {
  public $theme; // The theme which will handle display
  public function __construct() {
    $this->theme= new Theme(); // Theme automatically loads from DB
  }
  public function act() {
    // output the data through the theme's template engine
  }
}
?>
