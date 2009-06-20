<?php

class SimplePrivatePosts extends Plugin
{

	public function help()
	{
		return <<< END_HELP
<p>The "private" token is automatically set to <em>deny</em> for the "Anonymous" group, visitors to your site who are not logged in, which prevents them from seeing posts with the private token. You can select other groups to deny permission to posts with the "private" token on the groups page. </p>
<p>On the post creation page, a new checkbox labeled "Private Post" will appear in the settings area. Check this box to make the post private.</p>
END_HELP;
	}

	public function action_plugin_activation()
	{
		ACL::create_token('private', 'Permissions on posts marked as "private"');

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
		if ( $post->content_type == Post::type('entry') ) {
			$form->settings->append('checkbox', 'private_post', 'null:null', _t('Private Post'), 'tabcontrol_checkbox');
			if ( $post->has_tokens('private') ) {
				$form->private_post->value = true;
			}
		}
	}

	public function action_publish_post($post, $form)
	{
		if ( $post->content_type == Post::type('entry') ) {
			if ( $form->private_post->value == true ) {
				$post->add_tokens('private');
			}
			else {
				$post->remove_tokens('private');
			}
		}
	}

}
?>