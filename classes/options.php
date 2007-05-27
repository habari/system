<?php

/**
 * Habari Options Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
class Options extends Singleton
{
	private $options= array();
	
	/**
	 * Enables singleton working properly
	 * 
	 * @see singleton.php
	 */
	static protected function instance()
	{
		return parent::instance( get_class() );
	}
 
	/**
	 * Shortcut to return the value of an option
	 * 
	 * <code>
	 * 	 $foo = Options::get('foo'); //or
	 * 	 list($foo, $bar, $baz) = Options::get('foo1', 'bar2', 'baz3'); //or
	 * 	 extract(Options::get('foo', 'bar', 'baz')); //or
	 * </code>
	 * 
	 * @param string $name... The string name(s) of the option(s) to retrieve
	 * @return mixed The option requested or an array of requested options 	 
	 **/	 
	public static function get( $name )
	{
		if( func_num_args() > 1 ) {
			$results= array();
			$options= array_unshift( $name, func_get_args() );
			foreach ( $options as $optname ) {
				$results[$optname]= self::instance()->$optname; 
			}
			return $results;
		}
		return self::instance()->$name;
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
	public static function set( $name, $value= '' )
	{
		self::instance()->$name= $value;
	}

	/**
	 * Retrieve an option value
	 * @param string $name Name of the option to get
	 * @return mixed Stored value for specified option
	 **/
	public function __get( $name )
	{
		if ( ! isset( $this->options[$name] ) ) {
			$result= DB::get_row( 'SELECT value, type FROM ' . DB::table( 'options' ) . ' WHERE name = ?', array( $name ), 'QueryRecord' );
			if ( Error::is_error( $result ) ) {
				$result->out();
				die();
			}
			elseif ( is_object( $result ) ) {
				if ( $result->type == 1 ) {
					$this->options[$name]= unserialize( $result->value );
				}
				else {
					$this->options[$name]= $result->value;
				}
			}
			else {
				// Return some default values here
				switch ( $name ) {
					case 'pagination':
						return 10;
					case 'comments_require_id':
						return FALSE;
					case 'pingback_send':
						return FALSE;
				}
				return NULL;
			}
		}
		
		return $this->options[$name];
	}
	
	/**
	 * Applies the option value to the options table
	 * @param string $name Name of the option to set
	 * @param mixed $value Value to set
	 **/	 	 
	public function __set( $name, $value )
	{
		$this->options[$name]= $value;
		
		if ( is_array( $value ) || is_object( $value ) ) {
			$result= DB::update( DB::table( 'options' ), array( 'name' => $name, 'value' => serialize( $value ), 'type' => 1 ), array( 'name' => $name ) ); 
		}
		else {
			$result= DB::update( DB::table( 'options' ), array( 'name' => $name, 'value' => $value, 'type' => 0 ), array( 'name' => $name ) ); 
		}
		if ( Error::is_error( $result ) ) {
			$result->out();
			die();
		}
	}
	
	/**
	 * Clears memory of cached options
	 **/
	static public function clear_cache()
	{
		self::instance()->options = array();
	}	 	

}
 
?>
