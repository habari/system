<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }
Config::set( 'db_connection', array(
	'connection_string'=>'pgsql:host={$db_host} dbname={$db_schema}',
	'username'=>'{$db_user}',
	'password'=>'{$db_pass}',
	'prefix'=>'{$table_prefix}'
));

// Config::set('locale', '{$locale}');
?>
