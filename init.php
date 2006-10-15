<?php
/**
 * Habari Initialization Class
 *
 * Requires PHP5.0.4 or later
 * @package Habari
 */

/**
 * function __autoload
 * Autoloads class files for undeclared classes.
 **/  
function __autoload($class_name) {
	if(file_exists(HABARI_PATH . '/user/classes/' . strtolower($class_name) . '.php'))
		require_once HABARI_PATH . '/user/classes/' . strtolower($class_name) . '.php';
	else if(file_exists(HABARI_PATH . '/system/classes/' . strtolower($class_name) . '.php'))
		require_once HABARI_PATH . '/system/classes/' . strtolower($class_name) . '.php';
	else
		die( 'Could not include class file ' . strtolower($class_name) . '.php' );
}

// Load the config
if(file_exists(HABARI_PATH . '/config.php')) {
	require_once HABARI_PATH . '/config.php';
} else {
	die('There are no database connection details.  Please rename config-sample.php to config.php and edit the settings therein.');	
}

// Connect to the database or fail informatively
try {
	$db = new habari_db( $db_connection['connection_string'], $db_connection['username'], $db_connection['password'] );
}
catch( Exception $e) {
	die( 'Could not connect to database using the supplied credentials.  Please check config.php for the correct values. Further information follows: ' .  $e->getMessage() );		
}
unset($db_connection);

$options = new Options();

Installer::install();

/*
$mypost = new Post(array('slug'=>'my-new-post', 'title'=>'My New Post', 'content'=>'This is my new content')); 
$mypost->insert();
$mypost->publish();
*/
?>
