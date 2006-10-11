<?php
/**
 * Habari Initialization Class
 *
 * Requires PHP5.0.4 or later
 * @package Habari
 */

function __autoload($class_name) {   
    require_once 'system/classes/' . $class_name . '.php';
}

if(file_exists(dirname(__FILE__)) . '../config.php') {
	require_once '../config.php';
}
else {
	die('There are no database connection details.  Please copy default.init.php to my.init.php and edit the settings.');	
}

$db = new habari_db( $db_connection['connection_string'], $db_connection['username'], $db_connection['password'] );
unset($db_connection);
//$db->install_habari();  // Only need to do this once, and here's convenient for now.

?>