<?php
	Plugins::act( 'theme_searchform_before' );
	echo $content->form;
	Plugins::act( 'theme_searchform_after' );
?>
