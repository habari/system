<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }
	Plugins::act( 'theme_searchform_before' );
	echo $content->form;
	Plugins::act( 'theme_searchform_after' );
?>
