<?php
/**
 * Habari Index
 *
 * This is where all the magic happens:
 * 1. Validate the installation
 * 2. Set the locale
 * 3. Load the active plugins
 * 4. Parse and handle the incoming request
 * 5. Run the cron jobs
 * 6. Dispatch the request to the found handler
 *
 * @package Habari
 */

// Fail out if not included from root
if ( !defined( 'HABARI_PATH' ) ) {
	header( 'HTTP/1.1 403 Forbidden', true, 403 );
	die();
}

// Compares PHP version against our requirement.
if ( !version_compare( PHP_VERSION, '5.2.0', '>=' ) ) {
	die( 'Habari needs PHP 5.2.x or higher to run. You are currently running PHP ' . PHP_VERSION . '.' );
}

// Increase the error reporting level: E_ALL, E_NOTICE, and E_STRICT
error_reporting( E_ALL | E_NOTICE | E_STRICT );

// set a default timezone for PHP. Habari will base off of this later on
date_default_timezone_set( 'UTC' );

/**
 * Start the profile time
 */
$profile_start = microtime( true );

/**
 * Make GLOB_BRACE available on platforms that don't have it. Use Utils::glob().
 */
if ( !defined( 'GLOB_BRACE' ) ) {
	define( 'GLOB_NOBRACE', true );
	define( 'GLOB_BRACE', 128 );
}

// We start up output buffering in order to take advantage of output compression,
// as well as the ability to dynamically change HTTP headers after output has started.
ob_start();

require( dirname( __FILE__ ) . '/autoload.php' );
spl_autoload_register( 'habari_autoload' );

// Replace all of the $_GET, $_POST and $_SERVER superglobals with object
// representations of each.  Unset $_REQUEST, which is evil.
// $_COOKIE must be set after sessions start
SuperGlobal::process_gps();

// Use our own error reporting class.
if ( !defined( 'SUPPRESS_ERROR_HANDLER' ) ) {
	Error::handle_errors();
}

/**
 * Initiate install verifications
 */

// Retrieve the configuration file's path.
$config = Site::get_dir( 'config_file' );

/**
 * We make sure the configuration file exist.
 * If it does, we load it and check its validity.
 *
 * @todo Call the installer from the database classes.
 */
if ( file_exists( $config ) ) {
	require_once( $config );

	// Set the default locale.
	HabariLocale::set( isset( $locale ) ? $locale : 'en-us' );

	if ( !defined( 'DEBUG' ) ) {
		define( 'DEBUG', false );
	}

	// Make sure we have a DSN string and database credentials.
	// db_connection is an array with necessary informations to connect to the database.
	if ( !Config::exists( 'db_connection' ) ) {
		$installer = new InstallHandler();
		$installer->begin_install();
	}

	// The connection details are registered. Try to connect to the database.
	try {
		DB::connect();
		// Make sure Habari is installed properly.
		// If the 'installed' option is missing, we assume the database tables are missing or corrupted.
		// @todo Find a decent solution, we have to compare tables and restore or upgrade them.
		if ( !@ Options::get( 'installed' ) ) {
			$installer = new InstallHandler();
			$installer->begin_install();
		}
	}
	catch( PDOException $e ) {
		// Error template.
		$error_template = '<html><head><title>%s</title><link rel="stylesheet" type="text/css" href="' . Site::get_url( 'system' ) . '/admin/css/admin.css" media="screen"></head><body><div id="page"><div class="container"><h2>%s</h2><p>%s</p></div></div></body></html>';

		// Format page with localized messages.
		$error_page = sprintf( $error_template,
			_t( "Habari General Error" ), // page title
			_t( "An error occurred" ), // H1 tag
			( defined( 'DEBUG' ) && DEBUG == true ) ?
				_t( "Unable to connect to database.  Error message: %s", array( $e->getMessage() ) ) :
				_t( "Unable to connect to database." ) 
			// Error message, more detail if DEBUG is defined.
		);

		// Set correct HTTP header and die.
		header( 'HTTP/1.1 500 Internal Server Error', true, 500 );
		die( $error_page );
	}
}
else {
	if ( !defined( 'DEBUG' ) ) {
		define( 'DEBUG', false );
	}

	// The configuration file does not exist.
	// Therefore we load the installer to create the configuration file and install a base database.
	$installer = new InstallHandler();
	$installer->begin_install();
}

/* Habari is installed and we established a connection with the database */

// Set the locale from database or default locale
if ( Options::get( 'locale' ) ) {
	HabariLocale::set( Options::get( 'locale' ) );
}
else {
	HabariLocale::set( 'en-us' );
}
if ( Options::get( 'system_locale' ) ) {
	HabariLocale::set_system_locale( Options::get( 'system_locale' ) );
}

// Verify if the database has to be upgraded.
if ( Version::requires_upgrade() ) {
	$installer = new InstallHandler();
	$installer->upgrade_db();
}

// If we're doing unit testing, stop here
if ( defined( 'UNIT_TEST' ) ) {
	return;
}

// if this is an asyncronous call, ignore abort.
if ( isset( $_GET['asyncronous'] ) && Utils::crypt( Options::get( 'GUID' ), $_GET['asyncronous'] ) ) {
	ignore_user_abort( true );
}

// Send the Content-Type HTTP header.
// @todo Find a better place to put this.
header( 'Content-Type: text/html;charset=utf-8' );


// Load and upgrade all the active plugins.
spl_autoload_register( array( 'Plugins', '_autoload' ) );
Plugins::load_active();
Plugins::upgrade();

// All plugins loaded, tell the plugins.
Plugins::act( 'plugins_loaded' );

// Start the session.
Session::init();

// Replace the $_COOKIE superglobal with an object representation
SuperGlobal::process_c();

// Initiating request handling, tell the plugins.
Plugins::act( 'init' );

if ( defined( 'SUPPRESS_REQUEST' ) ) {
	return;
}

// Parse and handle the request.
Controller::parse_request();

// Run the cron jobs asyncronously.
CronTab::run_cron( true );

// Dispatch the request (action) to the matched handler.
Controller::dispatch_request();

// Flush (send) the output buffer.
$buffer = ob_get_clean();
$buffer = Plugins::filter( 'final_output', $buffer );
echo $buffer;
?>
