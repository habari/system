<?php

class SimplePrivatePosts extends Plugin
{

	public function action_plugin_activation()
	{
		ACL::create_token('private', 'Permission to read posts marked as "private"', 'Private Posts');

		// Deny the anonymous group access to the private token, if the group hasn't been removed (why would you remove it ??)
		$anon = UserGroup::get('anonymous');
		if ( false != $anon ) {
			$anon->deny('private');
		}
	}

	public function action_plugin_deactivation( $plugin_file )
	{
		if ( Plugins::id_from_file(__FILE__) == Plugins::id_from_file($plugin_file) ) {
			ACL::destroy_token('private');
		}
	}

	public function action_form_publish($form, $post)
	{
		$form->settings->append('checkbox', 'private_post', 'null:null', _t('Private Post'), 'tabcontrol_checkbox');
		if ( $post->has_tokens('private') ) {
			$form->private_post->value = true;
		}
	}

	public function action_publish_post($post, $form)
	{
		if ( $form->private_post->value == true ) {
			$post->add_tokens('private');
		}
		else {
			$post->remove_tokens('private');
		}
	}

}
?>
