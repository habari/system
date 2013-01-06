<?php
/**
 * @package Habari
 *
 */

namespace Habari;
/**
 * Habari Options Class
 *
 */
class Options extends Singleton
{
	private $options = null;

	/**
	 * Enables singleton working properly
	 *
	 * @see singleton.php
	 */
	protected static function instance()
	{
		return self::getInstanceOf( __CLASS__ );
	}

	/**
	 * Shortcut to return the value of an option
	 *
	 * <code>
	 * 	 $foo = Options::get('foo'); // returns null if the option 'foo' does not exist
	 *   list($foo, $bar, $baz) = Options::get( array('foo1', 'bar2', 'baz3') ); // useful with array_keys()
	 *   $bar = Options::get('foo', 'bar'); // returns 'bar' if the option 'foo' does not exist. useful for avoiding if/then blocks to detect unset options
	 * </code>
	 *
	 * @param string|array $name The name or an array of names of the option to fetch.
	 * @param mixed $default_value The value to return for an option if it does not exist.
	 * @return mixed The option requested or an array of requested options, $default_value for each if the option does not exist
	 **/
	public static function get( $name, $default_value = null )
	{
		if ( is_array( $name ) ) {
			$results = array();
			foreach ( $name as $key ) {
				$results[ $key ] = Options::get( $key );	// recursively wrap around ourselves!
			}
			return $results;
		}
		
		if ( isset( self::instance()->$name ) ) {
			return self::instance()->$name;
		}
		else {
			return $default_value;
		}
	}

	/**
	 * Fetch a group of options with a specific prefix
	 * Useful for plugins that use FormUI to automatically store options with the plugin's prefix.
	 *
	 * @param string $prefix The prefix to fetch
	 * @return array An associative array of all options with that prefix
	 */
	public static function get_group( $prefix )
	{

		if ( substr( $prefix, -2 ) != '__' ) {
			$prefix .= '__';
		}
		
		$results = array();
		foreach ( array_keys( self::instance()->options ) as $key ) {
			
			if ( strpos( $key, $prefix ) === 0 ) {
				$results[ substr( $key, strlen( $prefix ) ) ] = Options::get( $key );
			}
			
		}
		
		return $results;
		
	}
	
	/**
	 * Set a group of options with a specific prefix
	 * 
	 * <code>
	 *   Options::set_group( 'foo', array( 'bar' => 'baz', 'qux' => 'quux' ) );
	 *   // results in 2 options: foo__bar == baz and foo__qux == quux
	 * </code>
	 *
	 * @param string $prefix The prefix to set
	 * @param array $values An associative array of all options to be set with that prefix
	 */
	public static function set_group ( $prefix, $values )
	{
		
		if ( substr( $prefix, -2 ) != '__' ) {
			$prefix .= '__';
		}
		
		// loop through each option, setting it
		foreach ( $values as $k => $v ) {
			
			Options::set( $prefix . $k, $v );
			
		}
		
	}
	
	/**
	 * Delete a group of options with a specific prefix
	 * 
	 * <code>
	 *   Options::delete_group( 'foo' );
	 *   // would delete all foo__* option names
	 * </code>
	 * 
	 * @param string $prefix The prefix to delete
	 */
	public static function delete_group ( $prefix )
	{
		
		if ( substr( $prefix, -2 ) != '__' ) {
			$prefix .= '__';
		}
		
		foreach ( array_keys( self::instance()->options ) as $key ) {
			
			if ( strpos( $key, $prefix ) === 0 ) {
				Options::delete( $key );
			}
			
		}
		
	}

	/**
	 * Shortcut to output the value of an option
	 *
	 * <code>Options::out('foo');</code>
	 *
	 * @param string $name Name of the option to output
	 **/
	public static function out( $name )
	{
		echo self::instance()->get( $name );
	}

	/**
	 * function set
	 * Shortcut to set the value of an option
	 *
	 * <code>
	 *   Options::set('foo', 'newvalue');
	 *   Options::set( array( 'foo' => 'bar', 'baz' => 'qux' ) );
	 * </code>
	 *
	 * @param string|array $name Name of the option to set or an array of name => value options to set.
	 * @param mixed $value New value of the option to store. If first parameter is an array, $value is ignored.
	 **/
	public static function set( $name, $value = '' )
	{
		
		if ( is_array( $name ) ) {
			foreach ( $name as $k => $v ) {
				Options::set( $k, $v );	// recursively wrap around ourselves!
			}
		}
		else {
			self::instance()->$name = $value;
		}
		
	}


	/**
	 * Shortcut to unset an option in the options table
	 *
	 * @param string|array $name The name of the option or an array of names to delete.
	 */
	public static function delete( $name )
	{
		
		if ( is_array( $name ) ) {
			foreach ( $name as $key ) {
				Options::delete( $key ); // recursively wrap around ourselves!
			}
		}
		else {
			unset( self::instance()->$name );
		}
		
	}

	/**
	 * Retrieve an option value
	 * @param string $name Name of the option to get
	 * @return mixed Stored value for specified option
	 **/
	public function __get( $name )
	{
		if ( ! isset( $this->options ) ) {
			$this->get_all_options();
		}
		$option_value = isset( $this->options[$name] ) ? $this->options[$name] : null;
		$option_value = Plugins::filter( 'option_get_value', $option_value, $name );
		return $option_value;
	}

	public function __isset ( $name ) {

		if ( !isset( $this->options ) ) {
			$this->get_all_options();
		}

		return isset( $this->options[ $name ] );

	}

	/**
	 * Fetch all options from the options table into local storage
	 */
	public function get_all_options()
	{
		// Set some defaults here
		$this->options = array(
			'pagination' => 10,
			'comments_require_id' => false,
		);
		if(Config::exists('default_options')) {
			$this->options = array_merge($this->options, Config::get('default_options'));
		}
		if(DB::is_connected()) {
			$results = DB::get_results( 'SELECT name, value, type FROM {options}', array() );
			foreach ( $results as $result ) {
				if ( $result->type == 1 ) {
					$this->options[$result->name] = unserialize( $result->value );
				}
				else {
					$this->options[$result->name] = $result->value;
				}
			}
		}
		if(Config::exists('static_options')) {
			$this->options = array_merge($this->options, Config::get('static_options'));
		}
	}

	/**
	 * Applies the option value to the options table
	 * @param string $name Name of the option to set
	 * @param mixed $value Value to set
	 **/
	public function __set( $name, $value )
	{
		if ( ! isset( $this->options ) ) {
			$this->get_all_options();
		}

		// if the option already exists and has the same value, there is nothing to update and we shouldn't waste a db hit
		// i can't think of any negative implications of this, but that's no guarantee
		if ( isset( $this->options[ $name ] ) && $this->options[ $name ] == $value ) {
			return;
		}

		$value = Plugins::filter( 'option_set_value', $value, $name, isset( $this->options[$name] ) ? $this->options[$name] : null );
		$this->options[$name] = $value;

		// we now serialize everything, not just arrays and objects
		$result = DB::update( DB::table( 'options' ), array( 'name' => $name, 'value' => serialize( $value ), 'type' => 1 ), array( 'name' => $name ) );

		if ( Error::is_error( $result ) ) {
			$result->out();
			die();
		}
	}

	/**
	 * Removes an option from the options table
	 *
	 * @param string $name The name of the option
	 */
	public function __unset( $name )
	{
		unset( $this->options[$name] );
		DB::delete( DB::table( 'options' ), array( 'name' => $name ) );
	}

	/**
	 * Clears memory of cached options
	 **/
	public static function clear_cache()
	{
		self::instance()->options = null;
	}

	/**
	 * Check if an option was set via the config, making it unsettable
	 * @static
	 * @param string $name The name of the option to check
	 * @return bool True if the option is set in the config
	 */
	public static function is_static($name)
	{
		if($static_options = Config::exists('static_options')) {
			if(isset($static_options[$name])) {
				return true;
			}
		}
		return false;
	}

}

?>
