<?php
/**
 * Habari Options Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
 
class Options extends Singleton
{
	private $options = array();
	
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
	 * function get
	 * Shortcut to return the value of an option
	 * 
	 * <code>
	 * 	 $foo = Options::get('foo'); //or
	 * 	 list($foo, $bar, $baz) = Options::get('foo1', 'bar2', 'baz3'); //or
	 * 	 extract(Options::get('foo', 'bar', 'baz')); //or
	 * </code>
	 * 	 	 	 
	 * @param	string $option,... The string name(s) of the option(s) to retrieve
	 * @return mixed The option requested or an array of requested options 	 
	 **/	 
	public static function get( $option )
	{
		if( func_num_args() > 1 ) {
			$results = array();
			$options = array_unshift( $option, func_get_args() );
			foreach( $options as $optname ) {
				$results[$optname] = self::$instance->$optname; 
			}
			return $results;
		}
		return self::instance()->$option;
	}
	
	/**
	 * function out
	 * Shortcut to output the value of an option
	 * 
	 * <code>Options::out('foo');</code>
	 * 	 	 	 
	 * @param	string Name of the option to output
	 **/	 
	public static function out( $option )
	{
		echo self::instance()->get( $option );
	}
	
	/**
	 * function set
	 * Shortcut to set the value of an option
	 * 
	 * <code>Options::set('foo', 'newvalue');</code>
	 * 	 	 	 
	 * @param	string Name of the option to set
	 * @param mixed New value of the option to store
	 **/	 
	public static function set( $option, $value = '')
	{
		self::instance()->$option = $value;
	}

	/**
	 * function __get
	 * Allows retrieval of option values
	 * @param string Name of the option to get
	 * @return mixed Stored value for specified option
	 **/
	public function __get($name)
	{
		// Non-overrideable defaults:
		switch($name) {
		case 'hostname':
			return $_SERVER['SERVER_NAME'];
		case 'theme_url':
			$theme = Themes::get_active();
			return Site::get_user_url() . '/themes/' . $theme->theme_dir;
		}
		
		if(!isset($this->options[$name])) {
			$result = DB::get_row('SELECT value, type FROM ' . DB::table('options') . ' WHERE name = ?', array($name), 'QueryRecord');
			if ( Error::is_error( $result ) ) {
				$result->out();
				die();
			}
			elseif ( is_object( $result ) ) {
				if($result->type == 1) {
					$this->options[$name] = unserialize($result->value);
				}
				else {
					$this->options[$name] = $result->value;
				}
			}
			else {
				// Return some default values here
				switch($name) {
				case 'pagination':
					return 10;
				case 'habari_host':
					return Site::get_host();
				case 'habari_url':
					// use Utils::glue_url?
					return $this->habari_host . Site::get_base_url();
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
	 * function __set
	 * Applies the option value to the options table
	 * @param string Name of the option to set
	 * @param mixed Value to set
	 **/	 	 
	public function __set($name, $value) {
		$this->options[$name] = $value;
		
		if(is_array($value) || is_object($value)) {
			$result = DB::update( DB::table('options'), array('name'=>$name, 'value'=>serialize($value), 'type'=>1), array('name'=>$name) ); 
		}
		else {
			$result = DB::update( DB::table('options'), array('name'=>$name, 'value'=>$value, 'type'=>0), array('name'=>$name) ); 
		}
		if( Error::is_error($result) ) {
			$result->out();
			die();
		}
	}

}
 
?>
