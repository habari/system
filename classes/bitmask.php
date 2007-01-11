<?php
/**
 * Class to wrap around bitmap field functionality
 */
class Bitmask {
  public $flags= array();  // set of flag bit masks
  private $value= 0;        // internal integer value of the bitmask

  /**
   * Constructor.  Takes an optional array parameter
   * of bit flags to mask on.
   *
   * @param (optional)  an array of integer flags
   */
  public function __construct() {
      $this->flags= func_get_arg(0);
  }

  /**
   * Magic setter method for flag values.
   *
   * @param bit   integer representing the mask bit
   * @param on    on or off?
   */
  public function __set($bit, $on) {
    if (!is_bool($on)) 
      return false;
    if ($on)
      $this->value |= $this->flags[$bit]; // Turn the bit ON
    else
      $this->value &= ~$this->flags[$bit]; // Turn the bit OFF
    return true;
  }

  /**
   * Magic getter method for flag status
   *
   * @param bit   integer representing th emask bit to test
   * @return boolean
   */
  public function __get($bit) {
    if (!isset($this->flags[$bit]))
      return false;
    return ($this->value & $this->flags[$bit]);
  }
    
}
?>
