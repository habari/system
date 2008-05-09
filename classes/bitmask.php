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
		$flags= func_get_arg(0);
		if (! is_array($flags))
			throw new InvalidArgumentException(_t('Bitmask constructor expects either no arguments or an array as a first argument'));

		$this->flags= $flags;
  }

  /**
   * Magic setter method for flag values.
   *
   * @param bit   integer representing the mask bit
   * @param on    on or off?
   */
  public function __set($bit, $on) {
    if ($bit == 'value') {
      // To set the actual value of the bitmask (i.e. from the DB)
      $this->value= $on;
      return true;
    }
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
    return (($this->value & $this->flags[$bit]) == $this->flags[$bit]);
  }
    
}
?>
