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
	protected $flags = array();  // set of flag bit masks
	protected $full = 0;         // maximum integer value of the bitmask
	protected $value = 0;        // internal integer value of the bitmask

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
			throw new InvalidArgumentException( _t( 'Bitmask constructor expects either no arguments or an array as a first argument' ) );
		}
		
		if ( count( $flags ) > ( PHP_INT_MAX >> 1 ) ) {
			throw new InvalidArgumentException( _t( 'Bitmask can have max PHP_INT_MAX >> 1 flags' ) );
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
				throw new InvalidArgumentException( _t( 'Bitmask constructor second argument must either be an integer within the valid range, the name of a flag, or full' ) );
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
		switch ( $bit ) {
			case 'full':
				if ( ! is_bool( $on ) ) {
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
						throw new InvalidArgumentException( _t( 'Setting bitmask value by array must use array with same length as number of flags' ) );
					}
					$this->value = 0;
					foreach ( $on as $flag ) {
						if ( ! is_bool( $flag ) ) {
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
					throw new InvalidArgumentException( _t( 'Bitmask value must either be an integer within the valid range or an array of booleans' ) );
				}
				break;
			default:
				if ( ! is_bool( $on ) ) {
					throw new InvalidArgumentException( _t( 'Bitmask values must be boolean' ) );
				}
					
				$bit = array_search( $bit, $this->flags );
				
				if ( $bit === false ) {
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
	 * @param bit integer representing the mask bit to test
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
			throw new InvalidArgumentException( _t( 'Bitmask cannot get non-existent flag' ) );
		}
	}
	
	/**
	 * Magic check-whether-flag-exists method
	 * 
	 * @param flag string of flag name
	 * @return boolean
	 */
	public function __isset( $flag )
	{
		return $flag === 'full' || $flag === 'value' || in_array( $flag, $this->flags );
	}

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
