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
		
		if ( count( $flags ) > ( PHP_INT_MAX >> 1 ) )
			throw new InvalidArgumentException( _t( 'Bitmask can have max PHP_INT_MAX >> 1 flags' ) );

		$this->flags = $flags;
		$this->full = ( 1 << ( count( $this->flags ) ) ) - 1;
		if ( ! is_null( $value ) ) {
			if ( is_numeric( $value ) ) {
				$this->value = (int) $value;
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
			case 'full':
				if ( $on ) {
					$this->value = $this->full;
				}
				else {
					$this->value = 0;
				}
				break;
			case 'value':
				if ( is_array( $on ) ) {
					if ( count( $on ) !== count( $this->flags ) )
						throw new InvalidArgumentException( _t( 'Setting bitmask value by array must use array with same length as number of flags' ) );
					$this->value = 0;
					foreach ( $on as $flag ) {
						$this->value <<= 1;
						$this->value |= (int) $flag;
					}
				}
				else {
					$this->value = (int) $on;
				}
				break;
			default:
				if ( ! is_bool( $on ) )
					throw new InvalidArgumentException( _t( 'Bitmask values must be boolean' ) );
					
				$bit = array_search( $bit, $this->flags );
				
				if ( $bit === false )
					throw new InvalidArgumentException( _t( 'Bitmask cannot set non-existent flag' ) );
				
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
			return $this->value;
		}
		else {
			$bit = array_search( $bit, $this->flags );
		}
		return ( $this->value & ( 1 << $bit ) ) !== 0;
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
