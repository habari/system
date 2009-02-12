<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php
/**
 * @package Habari
 *
 */

/**
 * Class to wrap around bitmap field functionality
 */
class Bitmask
{
	public $flags = array();  // set of flag bit masks
	public $value = 0;        // internal integer value of the bitmask

	/**
	 * Constructor.  Takes an optional array parameter
	 * of bit flags to mask on.
	 *
	 * @param array $flags An array of flag names
	 * @param integer $value (optional) a combined bitmask value
	 */
	public function __construct( $flags = null, $value = null )
	{
		if ( ! is_array( $flags ) ) {
			throw new InvalidArgumentException(_t('Bitmask constructor expects either no arguments or an array as a first argument'));
		}

		$this->flags = $flags;
		if ( ! is_null( $value ) ) {
			if ( is_numeric( $value ) ) {
				$this->value = $value;
			}
			elseif ( is_string( $value ) ) {
				$this->$value = true;
			}
		}

	}

	/**
	 * Magic setter method for flag values.
	 *
	 * @param bit   integer representing the mask bit
	 * @param on    on or off?
	 */
	public function __set( $bit, $on )
	{
		switch( $bit ) {
			case 'value':
				$this->value = $on;
				break;
			default:
				if ( ! is_bool( $on ) )
					throw new InvalidArgumentException(_t('Bitmask values must be boolean'));
				if ( is_string( $bit ) ) {
					$bit = array_search( $bit, $this->flags );
				}
				elseif ( ! is_int( $bit ) ) {
					throw new InvalidArgumentException(_t('Bitmask names must be pre-defined strings or bitmask indexes'));
				}
				if ( $on ) {
					$this->value |= 1 << $bit;
				}
				else {
					$this->value &= ~(1 << $bit);
				}
				break;
			}
		return $on;
	}

	/**
	 * Magic getter method for flag status
	 *
	 * @param bit integer representing the mask bit to test
	 * @return boolean
	 */
	public function __get( $bit )
	{
		if ( is_string( $bit ) ) {
			if ( $bit == 'full' ) {
				return (1 << (count($this->flags))) - 1;
			}
			else {
				$bit = array_search( $bit, $this->flags );
			}
		}
		if ( $bit === false )
			return false;
		return $this->value & (1 << $bit );
	}

	public function __tostring()
	{
		if ( $this->value == $this->full ) {
			return _t('full');
		}
		$output = array();
		foreach ( $this->flags as $flag ) {
			if ( $this->$flag ) {
				$output[] = $flag;
			}
		}
		if ( count($output) == 0 ) {
			return _t('none');
		}
		return implode(',', $output);
	}

}
?>
