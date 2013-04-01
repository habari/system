<?php

namespace Habari;

if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); }

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
		/** @var FormControlCheckbox $private_post  */
		$private_post = FormControlCheckbox::create('private_post');
		$private_post->set_returned_value(true);
		$form->post_settings->append($private_post->label( _t('Private Post') ));
		if ( $post->has_tokens('private') ) {
			$private_post->set_value(true);
		}
		else {
			$private_post->set_value(false);
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
