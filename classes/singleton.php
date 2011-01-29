<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Singleton class
 *
 * Singleton base class for subclassing generic singleton pattern
 * classes
 */
abstract class Singleton
{
	// Single instance of class available.
	private static $instances = array();

	/**
	 * Declarations that extend this method must have the same signature
	 * (arguments and returned types) to pass E_STRICT
	 *
	 * @return object instance
	 */
	protected static function instance()
	{
		/*
		 * It is important to note that subclasses MUST override this
		 * method, as get_class will ALWAYS return 'Singleton' when
		 * subclasses call this method through inheritance
		 * return self::getInstanceOf( get_class() );
		 */
		trigger_error( _t( 'Not implemented: instance' ), E_USER_WARNING );
		return null;
	}
	
	/**
	 * Returns the single shared static instance variable
	 * which facilitates the Singleton pattern
	 *
	 * @note  each subclass should implement an instance() method which
	 * passes the class name to the parent::getInstanceOf() function.
	 *
	 * @return object instance
	 */
	/*
	 * The overridden methods can't have a different signature, so there needs
	 * to be two functions: one, internal to a class, that calls this one
	 * (a singleton factory) that produces objects of the requested classes
	 */
	protected static function getInstanceOf( $class )
	{
		if ( ! isset( self::$instances[$class] ) ) {
			self::$instances[$class] = new $class();
		}
		return self::$instances[$class];
	}

	/**
	 * Prevent instance construction and cloning (copying of object
	 * instance)
	 */
	protected final function __construct() {}
	private final function __clone() {}
}

?>
