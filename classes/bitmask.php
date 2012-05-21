<?php
/**
 * @package Habari
 *
 */

/**
 * Class to wrap around bitmap field functionality
 *
 * @property integer $value The internal numeric value of the Bitmask
 * @property integer $full The internal value of a fully-on Bitmask
 */
class Bitmask
{
	protected $flags = array();  // set of flag bit masks
	protected $full = 0;         // maximum integer value of the bitmask
	protected $value = 0;        // internal integer value of the bitmask

	/**
	 * Constructor.  Takes an optional array parameter
	 * of bit flags to mask on.
	 *
	 * @param array $flags An array of flag names
	 * @param integer $value (optional) a combined bitmask value
	 * @throws InvalidArgumentException
	 */
	public function __construct( $flags = null, $value = null )
	{
		if ( ! is_array( $flags ) ) {
			// @locale Habari tried to create a Bitmask with the wrong arguments
			throw new InvalidArgumentException( _t( 'Bitmask constructor expects either no arguments or an array as a first argument' ) );
		}
		
		if ( count( $flags ) > ( PHP_INT_MAX >> 1 ) ) {
			// @locale Habari tried to create a new Bitmask with too many flags
			throw new InvalidArgumentException( _t( 'Bitmask can have a maximum of PHP_INT_MAX >> 1 flags' ) );
		}

		$this->flags = $flags;
		$this->full = ( 1 << ( count( $this->flags ) ) ) - 1;
		if ( ! is_null( $value ) ) {
			if ( $value === 'full' ) {
				$this->value = $this->full;
			}
			elseif ( (string) (int) $value === (string) $value && $value >= 0 && $value <= $this->full ) {
				$this->value = $value;
			}
			elseif ( is_string( $value ) && in_array( $value, $flags ) ) {
				// This calls the setter directly to deal with non-public
				// properties to make sure we have the same behaviour as the
				// normal API.
				$this->__set( $value, true );
			}
			else {
				// @locale Habari tried to create a new Bitmask object with invalid values in the second argument
				throw new InvalidArgumentException( _t( 'Bitmask constructor second argument must either be an integer within the valid range, the name of a flag, or full' ) );
			}
		}

	}

	/**
	 * Magic setter method for flag values.
	 *
	 * @param string $bit The name of the Bitmask part to set
	 * @param mixed $on The value to set the bit to
	 * @throws InvalidArgumentException
	 * @return mixed The set value
	 */
	public function __set( $bit, $on )
	{
		switch ( $bit ) {
			case 'full':
				if ( ! is_bool( $on ) ) {
					// @locale Habari tried to set the wrong type of value
					throw new InvalidArgumentException( _t( 'Bitmask full toggle must be boolean' ) );
				}
					
				if ( $on ) {
					$this->value = $this->full;
				}
				else {
					$this->value = 0;
				}
				break;
			case 'value':
				if ( is_array( $on ) ) {
					if ( count( $on ) !== count( $this->flags ) ) {
						// @locale Habari tried to set the wrong kind of value
						throw new InvalidArgumentException( _t( 'Setting bitmask value by array must use array with same length as number of flags' ) );
					}
					$this->value = 0;
					foreach ( $on as $flag ) {
						if ( ! is_bool( $flag ) ) {
							// @locale Habari tried to set the wrong kind of value
							throw new InvalidArgumentException( _t( 'Bitmask values must be boolean' ) );
						}
						$this->value <<= 1;
						$this->value |= (int) $flag;
					}
				}
				elseif ( (string) (int) $on === (string) $on && $on >= 0 && $on <= $this->full ) {
					$this->value = $on;
				}
				elseif ( empty($on) ) {
					$this->value = 0;
				}
				else {
					// @locale Habari tried to set the wrong kind of value
					throw new InvalidArgumentException( _t( 'Bitmask value must either be an integer within the valid range or an array of booleans' ) );
				}
				break;
			default:
				if ( ! is_bool( $on ) ) {
					// @locale Habari tried to set the wrong kind of value
					throw new InvalidArgumentException( _t( 'Bitmask values must be boolean' ) );
				}
					
				$bit = array_search( $bit, $this->flags );
				
				if ( $bit === false ) {
					// @locale Habari tried to set a value that doesn't exist
					throw new InvalidArgumentException( _t( 'Bitmask cannot set non-existent flag' ) );
				}
				
				if ( $on ) {
					$this->value |= 1 << $bit;
				}
				else {
					$this->value &= ~( 1 << $bit );
				}
				break;
		}
		return $on;
	}

	/**
	 * Magic getter method for flag status
	 *
	 * @param int $bit representing the mask bit to test
	 * @throws InvalidArgumentException
	 * @return boolean
	 */
	public function __get( $bit )
	{
		if ( $bit === 'value' ) {
			return intval( $this->value );
		}
		elseif ( $bit === 'full' ) {
			return intval( $this->full );
		}
		elseif ( ( $bit = array_search( $bit, $this->flags ) ) !== false ) {
			return ( intval( $this->value ) & ( 1 << $bit ) ) !== 0;
		}
		else {
			// @locale Habari tried to get a flag that doesn't exist
			throw new InvalidArgumentException( _t( 'Bitmask cannot get non-existent flag' ) );
		}
	}

	/**
	 * Magic check-whether-flag-exists method
	 *
	 * @param string $flag of flag name
	 * @return boolean
	 */
	public function __isset( $flag )
	{
		return $flag === 'full' || $flag === 'value' || in_array( $flag, $this->flags );
	}

	/**
	 * Convert this Bitmask into a string, based ont he flags that are set
	 * @return string Converted bitmask value
	 */
	public function __tostring()
	{
		if ( $this->value === $this->full ) {
			return _t( 'full' );
		}
		$output = array();
		foreach ( $this->flags as $flag ) {
			if ( $this->$flag ) {
				$output[] = $flag;
			}
		}
		if ( count( $output ) === 0 ) {
			return _t( 'none' );
		}
		return implode( ',', $output );
	}

}
?>