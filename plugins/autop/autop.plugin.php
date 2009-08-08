<?php

class AutoP extends Plugin
{
	
	public function action_update_check()
	{
		Update::add( $this->info->name, '21b481a3-9000-41f5-a906-2a80b3d5eb50', $this->info->version );
	}
	
	public function action_init_atom() {
		Format::apply( 'autop', 'post_content_atom' );
	}
	
	public function action_init_theme() {
		Format::apply( 'autop', 'post_content_out' );
	}
	
}

?>