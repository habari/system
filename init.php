<?php
/**
 * Habari Initialization Class
 *
 * Requires PHP5.0.4 or later
 * @package Habari
 */

function __autoload($class_name) {
	if(file_exists(HABARI_PATH . '/user/classes/' . strtolower($class_name) . '.php'))
		require_once HABARI_PATH . '/user/classes/' . strtolower($class_name) . '.php';
	else
		require_once HABARI_PATH . '/system/classes/' . strtolower($class_name) . '.php';
}

if(file_exists(HABARI_PATH . '/config.php')) {
	require_once HABARI_PATH . '/config.php';
} else {
	die('There are no database connection details.  Please rename config-sample.php to config.php and edit the settings therein.');	
}

$db = new habari_db( $db_connection['connection_string'], $db_connection['username'], $db_connection['password'] );
unset($db_connection);
//$db->install_habari();  // Only need to do this once, and here's convenient for now.
/*
$mypost = new Post(array('slug'=>'my-new-post', 'title'=>'My New Post', 'content'=>'This is my new content')); 
$mypost->insert();
$mypost->publish();
*/
?>