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
		return self::getInstanceOf( get_class() );
	}

	/**
	 * Shortcut to return the value of an option
	 *
	 * <code>
	 * 	 $foo = Options::get('foo'); //or
	 * 	 list($foo, $bar, $baz) = Options::get('foo1', 'bar2', 'baz3'); //or
	 * 	 extract(Options::get('foo', 'bar', 'baz')); //or
	 *   list($foo, $bar, $baz) = Options::get(array('foo1', 'bar2', 'baz3')); // useful with array_keys()
	 * </code>
	 *
	 * @param string|array $name... The name(s) of the option(s) to retrieve
	 * @return mixed The option requested or an array of requested options, null if it does not exist
	 **/
	public static function get( $name )
	{
		if ( func_num_args() > 1 ) {
			$name = func_get_args();
		}
		if ( is_array( $name ) ) {
			return array_intersect_key( self::instance()->options, array_flip( $name ) );
		}
		return self::instance()->$name;
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
		$results = array();
		if( substr( $prefix, -2 ) != '__' ) {
			$prefix .= '__';
		}
		foreach( self::instance()->options as $key => $value ) {
			if( strpos( $key, $prefix ) === 0 ) {
				$results[substr( $key, strlen( $prefix ) )] = $value;
			}
		}
		return $results;
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
	 * <code>Options::set('foo', 'newvalue');</code>
	 *
	 * @param string $name Name of the option to set
	 * @param mixed $value New value of the option to store
	 **/
	public static function set( $name, $value = '' )
	{
		self::instance()->$name = $value;
	}


	/**
	 * Shortcut to unset an option in the options table
	 *
	 * @param string $name The name of the option
	 */
	public static function delete( $name )
	{
		unset(self::instance()->$name);
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
		$option_value = isset($this->options[$name]) ? $this->options[$name] : null;
		$option_value = Plugins::filter('option_get_value', $option_value, $name);
		return $option_value;
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
		$results = DB::get_results( 'SELECT name, value, type FROM {options}', array(), 'QueryRecord' );
		foreach($results as $result) {
			if ( $result->type == 1 ) {
				$this->options[$result->name] = unserialize( $result->value );
			}
			else {
				$this->options[$result->name] = $result->value;
			}
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
		$value = Plugins::filter( 'option_set_value', $value, $name, isset($this->options[$name]) ? $this->options[$name] : null );
		$this->options[$name] = $value;

		if ( is_array( $value ) || is_object( $value ) ) {
			$result = DB::update( DB::table( 'options' ), array( 'name' => $name, 'value' => serialize( $value ), 'type' => 1 ), array( 'name' => $name ) );
		}
		else {
			$result = DB::update( DB::table( 'options' ), array( 'name' => $name, 'value' => $value, 'type' => 0 ), array( 'name' => $name ) );
		}
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
		unset($this->options[$name]);
		DB::delete( DB::table( 'options' ), array( 'name' => $name ) );
	}

	/**
	 * Clears memory of cached options
	 **/
	public static function clear_cache()
	{
		self::instance()->options = null;
	}

}

?>
