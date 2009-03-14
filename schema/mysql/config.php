<?php
Config::set( 'db_connection', array(
	'connection_string'=>'mysql:host={$db_host};dbname={$db_schema}',
	'username'=>'{$db_user}',
	'password'=>'{$db_pass}',
	'prefix'=>'{$table_prefix}'
));

// $locale = '{$locale}';
?>
