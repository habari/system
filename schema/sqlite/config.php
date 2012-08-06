<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }
Config::set( 'db_connection', array(
	'connection_string'=>'sqlite:{$db_file}',
	'username'=>'',
	'password'=>'',
	'prefix'=>'{$table_prefix}'
));

// Config::set('locale', '{$locale}');
?>
