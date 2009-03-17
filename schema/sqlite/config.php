<?php
Config::set( 'db_connection', array(
	'connection_string'=>'sqlite:{$db_file}',
	'username'=>'',
	'password'=>'',
	'prefix'=>'{$table_prefix}'
));

//$locale = '{$locale}';
?>
