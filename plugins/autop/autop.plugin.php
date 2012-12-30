<?php

namespace Habari;

if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }

class AutoP extends Plugin
{
	
	public function action_init_atom()
	{
		Format::apply( 'autop', 'post_content_atom' );
	}
	
	public function action_init_theme_any( $theme )
	{
		Format::apply( 'autop', 'post_content_out' );
	}
	
}

?>
