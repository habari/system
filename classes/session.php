<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari Session class
 *
 * Manages sessions for the PHP session routines
 *
 */
class Session extends Singleton
{
	/*
	 * The initial data. Used to determine whether we should write anything.
	 */
	private static $lifetime;

	const HABARI_SESSION_COOKIE_NAME = 'habari_session';


	/**
	 * Initialize the session handlers
	 */
	public static function init()
	{

		// the default path for the session cookie is /, but let's make that potentially more restrictive so no one steals our cookehs
		// we also can't use 'null' when we set a secure-only value, because that doesn't mean the same as the default like it should
		$path = Site::get_path( 'base', true );

		// the default is not to require a secure session
		$secure = false;

		// if we want to always require secure
		if ( Config::get( 'force_secure_session' ) == true ) {
			$secure = true;
		}

		// if this is an HTTPS connection by default we will
		// IIS sets HTTPS == 'off', so we have to check the value too
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) {
			$secure = true;
		}

		// but if we have explicitly disabled it, don't
		// note the ===. not setting it (ie: null) should not be the same as setting it to false
		if ( Config::get( 'force_secure_session' ) === false ) {
			$secure = false;
		}

		// now we've got a path and secure, so set the cookie values
		session_set_cookie_params( null, $path, null, $secure );

		// figure out the session lifetime and let plugins change it
		$lifetime = ini_get( 'session.gc_maxlifetime' );

		self::$lifetime = Plugins::filter( 'session_lifetime', $lifetime );

		$_SESSION = new SessionStorage();

		if ( isset( $_COOKIE[ self::HABARI_SESSION_COOKIE_NAME ] ) ) {
			$_SESSION->id = $_COOKIE[ self::HABARI_SESSION_COOKIE_NAME ];
			self::read();
		}

		// make sure we check whether or not we should write the session after the page is rendered
		register_shutdown_function( Method::create( '\Habari\Session', 'shutdown' ) );

		return true;
	}

	/**
	 * Start a new session - that is, generate an ID and set the session cookie
	 */
	public static function start ( ) {

		$_SESSION->id = UUID::get();

		// we are actually starting a session, so let's send that cookie.
		$expiration = DateTime::create('+' . self::$lifetime . ' seconds')->int;

		$cookie_params = session_get_cookie_params();
		setcookie( self::HABARI_SESSION_COOKIE_NAME, $_SESSION->id, $expiration, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly'] );

	}

	/**
	 * Read session data from the database and load it into the $_SESSION global.
	 * Verifies against a number of parameters for security purposes.
	 *
	 * @return bool Whether a session was loaded or not
	 */
	public static function read()
	{
		$remote_address = Utils::get_ip();
		// not always set, even by real browsers
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$session = DB::get_row( 'SELECT * FROM {sessions} WHERE token = ?', array( $_SESSION->id ) );

		// Verify session exists
		if ( !$session ) {
			return false;
		}

		// @todo i just realized that if i fake someone's session cookie, but my user agent and subnet do not match, i will cause their session to be deleted... remind me to fix that, later
		// we should probably just not load the session, and let garbage collection clean it up later, if it isn't used
		$dodelete = false;

		if ( Config::get( 'sessions_skip_subnet' ) != true ) {
			// Verify on the same subnet
			$subnet = self::get_subnet( $remote_address );
			if ( $session->ip != $subnet ) {
				$dodelete = true;
			}
		}

		// Verify expiry
		if ( DateTime::create()->int > $session->expires ) {
			if ( $session->user_id ) {
				Session::error( _t( 'Your session expired.' ), 'expired_session' );
			}
			$dodelete = true;
		}

		// Verify User Agent
		if ( $user_agent != $session->ua ) {
			$dodelete = true;
		}

		// Let plugins ultimately decide
		$dodelete = Plugins::filter( 'session_read', $dodelete, $session, $_SESSION->id );

		if ( $dodelete ) {
			$sql = 'DELETE FROM {sessions} WHERE token = ?';
			$args = array( $_SESSION->id );
			$sql = Plugins::filter( 'sessions_clean', $sql, 'read', $args );
			DB::query( $sql, $args );
			return false;
		}

		// but if the expiration is close (less than half the session lifetime away), null it out so the session always gets written so we extend the session
		if ( ( $session->expires - DateTime::create()->int ) < ( self::$lifetime / 2 ) ) {
			$_SESSION->changed = true;
		}

		// Unserialize the data and set it into the internal session array
		$data = unserialize($session->data);
		foreach($data as $key => $value) {
			$_SESSION[ $key ] = $value;
		}

		return true;
	}

	public static function shutdown()
	{
		self::write();
	}

	/**
	 * Commit $_SESSION data to the database for this user.
	 */
	public static function write()
	{

		$remote_address = Utils::get_ip();
		// not always set, even by real browsers
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		// get the data from the ArrayObject
		$data = $_SESSION->getArrayCopy();

		// but let a plugin make the final decision. we may want to ignore search spiders, for instance
		$dowrite = Plugins::filter( 'session_write', $_SESSION->changed, $_SESSION->id, $data );

		if ( $dowrite ) {
			// DB::update() checks if the record key exists, and inserts if not
			$record = array(
				'ip' => self::get_subnet( $remote_address ),
				'expires' => DateTime::create()->int + self::$lifetime,
				'ua' => MultiByte::substr( $user_agent, 0, 255 ),
				'data' => serialize($data),
			);
			DB::update(
				DB::table( 'sessions' ),
				$record,
				array( 'token' => $_SESSION->id )
			);

			// @todo somehow track whether or not we should send the cookie - we need to make sure it's updated every few page loads so it does not expire
		}
	}

	/**
	 * Destroy stored session data by session id
	 */
	public static function destroy( )
	{
		$sql = 'DELETE FROM {sessions} WHERE token = ?';
		$args = array( $_SESSION->id );
		$sql = Plugins::filter( 'sessions_clean', $sql, 'destroy', $args );
		DB::query( $sql, $args );

		// get rid of the session cookie, too.
		$cookie_params = session_get_cookie_params();
		setcookie( self::HABARI_SESSION_COOKIE_NAME, null, -1, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly'] );

		return true;
	}

	/**
	 * Session garbage collection deletes expired sessions
	 */
	public static function gc()
	{
		$sql = 'DELETE FROM {sessions} WHERE expires < ?';
		$args = array( DateTime::create()->int );
		$sql = Plugins::filter( 'sessions_clean', $sql, 'gc', $args );
		DB::query( $sql, $args );
		return true;
	}

	/**
	 * Sets the user_id attached to the current session
	 *
	 * @param integer $user_id The user id of the current user
	 */
	public static function set_userid( $user_id )
	{
		if(!Plugins::filter('session_handlers', false)) {
			DB::query( 'UPDATE {sessions} SET user_id = ? WHERE token = ?', array( $user_id, $_SESSION->id ) );
		}
	}


	/**
	 * Clear the user_id attached to sessions, delete other sessions that are associated to the user_id
	 * @param integer $user_id The user_id to clear.
	 */
	public static function clear_userid( $user_id )
	{
		if(!Plugins::filter('session_handlers', false)) {
			DB::query( 'DELETE FROM {sessions} WHERE user_id = ? AND token <> ?', array( $user_id, $_SESSION->id ) );
			DB::query( 'UPDATE {sessions} SET user_id = NULL WHERE token = ?', array( $_SESSION->id ) );
		}
	}

	/**
	 * Adds a value to a session set
	 *
	 * @param string $set Name of the set
	 * @param mixed $value value to store
	 * @param string $key Optional unique key for the set under which to store the value
	 */
	public static function add_to_set( $set, $value, $key = null )
	{
		if ( !isset( $_SESSION[$set] ) ) {
			$_SESSION[$set] = array();
		}
		if ( $key ) {
			$_SESSION[$set][$key] = $value;
		}
		else {
			$_SESSION[$set][] = $value;
		}
	}

	/**
	 * Store a notice message in the user's session
	 *
	 * @param string $notice The notice message
	 * @param string $key An optional id that would guarantee a single unique message for this key
	 */
	public static function notice( $notice, $key = null )
	{
		self::add_to_set( 'notices', $notice, $key );
	}

	/**
	 * Store an error message in the user's session
	 *
	 * @param string $error The error message
	 * @param string $key An optional id that would guarantee a single unique message for this key
	 */
	public static function error( $error, $key = null )
	{
		self::add_to_set( 'errors', $error, $key );
	}

	/**
	 * Return a set of messages
	 *
	 * @param string $set The name of the message set
	 * @param boolean $clear true to clear the messages from the session upon receipt
	 * @return array An array of message strings
	 */
	public static function get_set( $set, $clear = true )
	{
		if ( isset($_SESSION[$set] ) ) {
			$set_array = $_SESSION[$set];
			if($clear) {
				unset($_SESSION[$set]);
			}
		}
		else {
			$set_array = array();
		}
		return $set_array;
	}

	/**
	 * Get all notice messages from the user session
	 *
	 * @param boolean $clear true to clear the messages from the session upon receipt
	 * @return array And array of notice messages
	 */
	public static function get_notices( $clear = true )
	{
		return self::get_set( 'notices', $clear );
	}

	/**
	 * Retrieve a specific notice from stored errors.
	 *
	 * @param string $key ID of the notice to retrieve
	 * @param boolean $clear true to clear the notice from the session upon receipt
	 * @return string Return the notice message
	 */
	public static function get_notice( $key, $clear = true )
	{
		$notices = self::get_notices( false );
		if ( isset( $notices[$key] ) ) {
			$notice = $notices[$key];
			if ( $clear ) {
				self::remove_notice( $key );
			}
			return $notice;
		}
	}

	/**
	 * Get all error messages from the user session
	 *
	 * @param boolean $clear true to clear the messages from the session upon receipt
	 * @return array And array of error messages
	 */
	public static function get_errors( $clear = true )
	{
		return self::get_set( 'errors', $clear );
	}

	/**
	 * Retrieve a specific error from stored errors.
	 *
	 * @param string $key ID of the error to retrieve
	 * @param boolean $clear true to clear the error from the session upon receipt
	 * @return string Return the error message
	 */
	public static function get_error( $key, $clear = true )
	{
		$errors = self::get_errors( false );
		if ( isset( $errors[$key] ) ) {
			$error = $errors[$key];
			if ( $clear ) {
				self::remove_error( $key );
			}
			return $error;
		}
	}

	/**
	 * Removes a specific notice from the stored notices.
	 *
	 * @param string $key ID of the notice to remove
	 * @return boolean True or false depending if the notice was removed successfully.
	 */
	public static function remove_notice( $key )
	{
		unset( $_SESSION['notices'][$key] );
		return ( !isset( $_SESSION['notices'][$key] ) ? true : false );
	}

	/**
	 * Removes a specific error from the stored errors.
	 *
	 * @param string $key ID of the error to remove
	 * @return boolean True or false depending if the error was removed successfully.
	 */
	public static function remove_error( $key )
	{
		unset( $_SESSION['errors'][$key] );
		return ( !isset( $_SESSION['errors'][$key] ) ? true : false );
	}

	/**
	 * Return output of notice and error messages
	 *
	 * @param boolean $clear true to clear the messages from the session upon receipt
	 * @param array $callback a reference to a callback function for formatting the the messages or the string 'array' to get a raw array
	 * @return mixed output of messages
	 */
	public static function messages_get( $clear = true, $callback = null )
	{
		$errors = self::get_errors( $clear );
		$notices = self::get_notices( $clear );

		// if callback is 'array', then just return the raw data
		if ( $callback == 'array' ) {
			$output = array_merge( $errors, $notices );
		}
		// if a function is passed in $callback, call it
		else if ( isset( $callback ) && is_callable( $callback ) ) {
			$output = call_user_func( $callback, $notices, $errors );
		}
		// default to html output
		else {
			$output = Format::html_messages( $notices, $errors );
		}

		return $output;
	}

	/**
	 * Output notice and error messages
	 *
	 * @param bool $clear true to clear the messages from the session upon receipt
	 * @param null|callable $callback A reference to a callback function for formatting the messages
	 */
	public static function messages_out( $clear = true, $callback = null )
	{
		echo self::messages_get( $clear, $callback );
	}

	/**
	 * Determine if there are messages that should be displayed
	 * Messages are not cleared when calling this function.
	 *
	 * @return boolean true if there are messages to display.
	 */
	public static function has_messages()
	{
		return ( count( self::get_notices( false ) + self::get_errors( false ) ) ) ? true : false;
	}

	/**
	 * Determine if there are error messages to display
	 *
	 * @param string $key Optional key of the unique error message
	 * @return boolean true if there are errors, false if not
	 */
	public static function has_errors( $key = null )
	{
		if ( isset( $key ) ) {
			return isset( $_SESSION['errors'][$key] );
		}
		else {
			return count( self::get_errors( false ) ) ? true : false;
		}
	}

	/**
	 * Helper function to find the Class A, B, or C subnet of the given IP address.
	 *
	 * We use this to store subnets for each IPv4 session, rather than distinct IPs, which could be pooled or rotated on large networks.
	 *
	 * @param string $remote_address The remote host's IP address.
	 * @return int|string The numeric subnet, if IPv4. The complete address, as passed, if a valid IPv6.
	 */
	protected static function get_subnet( $remote_address = '' )
	{

		// if it's an ipv6 address, we just use that and don't try to determine the subnet
		$is_v6 = filter_var( $remote_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );

		if ( $is_v6 !== false ) {
			return $remote_address;
		}

		$long_addr = ip2long( $remote_address );

		if ( $long_addr >= ip2long( '0.0.0.0' ) && $long_addr <= ip2long( '127.255.255.255' ) ) {
			// class A
			return sprintf( "%u", $long_addr ) >> 24;
		}
		else if ( $long_addr >= ip2long( '128.0.0.0' ) && $long_addr <= ip2long( '191.255.255.255' ) ) {
			// class B
			return sprintf( "%u", $long_addr ) >> 16;
		}
		else {
			// class C, D, or something we missed
			return sprintf( "%u", $long_addr ) >> 8;
		}

	}

	/**
	 * Sends HTTP headers that limit the cacheability of the page.
	 *
	 * @link http://php.net/session_cache_limiter
	 * @param string $type The type of caching headers to send. One of: 'public', 'private_no_expire', 'private', 'nocache', or ''.
	 * @return void
	 */
	public static function cache_limiter ( $type = 'nocache' ) {

		$expires = DateTime::create( '+' . session_cache_expire() . ' seconds' )->format( DateTime::RFC1123 );
		$last_modified = DateTime::create()->format( DateTime::RFC1123 );

		switch ( $type ) {
			case 'public':
				header( 'Expires: ' . $expires, true );
				header( 'Cache-Control: public, max-age=' . $expires, true );
				header( 'Last-Modified: ' . $last_modified, true );
				break;

			case 'private_no_expire':
				header( 'Cache-Control: private, max-age=' . $expires . ', pre-check=' . $expires, true );
				header( 'Last-Modified: ' . $last_modified, true );
				break;

			case 'private':
				header( 'Expires: Thu, 19 Nov 1981 08:52:00 GMT', true );
				header( 'Cache-Control: private max-age=' . $expires . ', pre-check=' . $expires, true );
				header( 'Last-Modified: ' . $last_modified, true );
				break;

			case 'nocache':
				header( 'Expires: Thu, 19 Nov 1981 08:52:00 GMT', true );
				header( 'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true );
				header( 'Pragma: no-cache', true );
				break;

			case '':
				return;
				break;
		}

	}
}

?>