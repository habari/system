<?php
/**
 * @package Habari
 *
 */

/**
 * SuperGlobals class
 *
 */
class SuperGlobal extends ArrayIterator
{
	protected $values = array();
	protected $raw_values = array();

	public function __construct( $array )
	{
		if ( !is_array( $array ) && !$array instanceof SuperGlobal ) {
			throw new Exception( 'Parameter must be array or SuperGlobal' );
		}
		parent::__construct( $array );
	}

	/**
	 * Convert $_GET, $_POST and $_SERVER into SuperGlobal instances, also kill $_REQUEST
	 *
	 * @return
	 */
	public static function process_gps()
	{
		/* We should only revert the magic quotes once per page hit */
		static $revert = true;

		if ( !$revert ) {
			// our work has already been done
			return;
		}

		if ( get_magic_quotes_gpc() ) {
			$_GET = Utils::stripslashes( $_GET );
			$_POST = Utils::stripslashes( $_POST );
		}

		$_GET = new SuperGlobal( $_GET );
		$_POST = new SuperGlobal( $_POST );
		$_SERVER = new SuperGlobal( $_SERVER );
		unset( $_REQUEST );

		$revert = false;
	}

	/**
	 * Convert $_COOKIE into SuperGlobal instance
	 *
	 */
	public static function process_c()
	{
		/* We should only revert the magic quotes once per page hit */
		static $revert = true;

		if ( !$revert ) {
			// our work has already been done
			return;
		}

		if ( get_magic_quotes_gpc() ) {
			$_COOKIE = Utils::stripslashes( $_COOKIE );
		}

		$_COOKIE = new SuperGlobal( $_COOKIE );

		$revert = false;
	}

	/**
	 * Return the raw, unfiltered value of the requested index
	 *
	 * @param mixed $index The index of the value
	 * @return mixed The unfiltered value
	 */
	public function raw( $index )
	{
		if ( isset( $this->raw_values[$index] ) ) {
			return $this->raw_values[$index];
		}
		$cp = $this->get_array_copy_raw();
		if ( isset( $cp[$index] ) ) {
			$this->raw_values[$index] = $cp[$index];
			return $this->raw_values[$index];
		}
	}
	
	/**
	 * Return a copy of the filtered array. Implments ArrayIterator::getArrayCopy()
	 */
	public function getArrayCopy()
	{
		return array_map( array( $this, 'base_filter' ), parent::getArrayCopy() );
	}
	
	/**
	 * Return a copy of the unfiltered array.
	 */
	public function get_array_copy_raw()
	{
		return parent::getArrayCopy();
	}
	
	/**
	 * Return the current array element, filtered. Implements ArrayIterator::current()
	 */
	public function current()
	{
		return $this->offsetGet( parent::key() );
	}

	/**
	 * Return the value of an array offset.  Allows the values to be filtered
	 *
	 * @param mixed $index The index of the array
	 * @return mixed The filtered value at the array index
	 */
	public function offsetGet( $index )
	{
		if ( isset( $this->values[$index] ) ) {
			return $this->values[$index];
		}
		$cp = $this->get_array_copy_raw();
		if ( isset( $cp[$index] ) ) {
			$this->values[$index] = $this->base_filter( $cp[$index] );
			return $this->values[$index];
		}
	}
	
	/**
	 * Set the value of the array, clear caches for that index
	 *
	 * @param mixed $index The array index
	 * @param mixed $value Tha value to store
	 */
	public function offsetSet( $index, $value )
	{
		unset( $this->values[$index] );
		unset( $this->raw_values[$index] );
		parent::offsetSet( $index, $value );
	}

	/**
	 * Recursively filter array values and strings using InputFilter::filter()
	 *
	 * @param mixed $value A value to filter
	 * @return mixes The filtered value
	 */
	protected function base_filter( $value )
	{
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'base_filter' ), $value );
		}
		elseif ( is_string( $value ) ) {
			return InputFilter::filter( $value );
		}
		else {
			return $value;
		}
	}

	/**
	 * Merges the contents of one or more arrays or ArrayObjects with this SuperGlobal
	 *
	 * @param mixed One or more array-like structures to merge into this array.
	 * @return SuperGlobal The merged array
	 */
	public function merge()
	{
		$args = func_get_args();
		$cp = $this->get_array_copy_raw();
		foreach ( $args as $ary ) {
			if ( is_array( $ary ) ) {
				foreach ( $ary as $key => $value ) {
					if ( is_numeric( $key ) ) {
						$cp[] = $value;
					}
					else {
						$cp[$key] = $value;
					}
				}
			}
			elseif ( $ary instanceof SuperGlobal ) {
				// loop to get raw data.
				while ( $ary->valid() ) {
					if ( is_numeric( $ary->key() ) ) {
						$cp[] = $ary->raw( $ary->key() );
					}
					else {
						$cp[$ary->key()] = $ary->raw( $ary->key() );
					}
					$ary->next();
				}
			}
			elseif ( $ary instanceof ArrayObject || $ary instanceof ArrayIterator ) {
				$arycp = $ary->getArrayCopy();  // Don't trigger offsetGet for ArrayObject
				foreach ( $arycp as $key => $value ) {
					if ( is_numeric( $key ) ) {
						$cp[] = $value;
					}
					else {
						$cp[$key] = $value;
					}
				}
			}
			else {
				$cp[] = $ary;
			}
		}
		return new SuperGlobal( $cp );
	}

	/**
	 * Filters this SuperGlobal based on an array or arrays of keys
	 *
	 * @param mixed An array of key values that should be returned, or a string of a key value to be returned
	 * @return SuperGlobal The values from this array that match the supplied keys
	 */
	public function filter_keys()
	{
		$keys = array();
		$args = func_get_args();
		foreach ( $args as $ary ) {
			if ( !is_array( $ary ) ) {
				$ary = array( $ary );
			}
			$keys = array_merge( $keys, array_values( $ary ) );
		}
		$cp = $this->get_array_copy_raw();
		$cp = array_intersect_key( $cp, array_flip( $keys ) );
		return new SuperGlobal( $cp );
	}
	
	/**
	 * Apply a map function to this array, like array_map()
	 * @param callback $fn the name of the function to map through
	 * @return SuperGlobal the result of the mapping
	 **/
	public function map( $fn )
	{
		return new SuperGlobal( array_map( $fn, $this->get_array_copy_raw() ) );
	}
	
	/**
	 * Use a regular expression replacement to change the keys in an array
	 * @param string $replacement (optional) The regex replacement value
	 * @param string $search_regex (optional) The regex search value
	 * @return SuperGlobal The re-keyed array
	 **/
	public function rekey( $replacement = '{\$$0}', $search_regex = '/^.*$/' )
	{
		$output = array();
		foreach ( $this->get_array_copy_raw() as $k => $v ) {
			$output[preg_replace( $search_regex, $replacement, $k )] = $v;
		}
		return new SuperGlobal( $output );
	}
}

?>
