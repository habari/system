<?php

class PublishAdminPage extends AdminPage
{
	/**
	 * Handles post requests from the publish page.
	 */
	public function act_request_post()
	{
		$form = $this->form_publish( new Post(), false );

		// check to see if we are updating or creating a new post
		if ( $form->post_id->value != 0 ) {
			$post = Post::get( array( 'id' => $form->post_id->value, 'status' => Post::status( 'any' ) ) );
			$post->title = $form->title->value;
			if ( $form->newslug->value == '' ) {
				Session::notice( _e('A post slug cannot be empty. Keeping old slug.') );
			}
			elseif ( $form->newslug->value != $form->slug->value ) {
				$post->slug = $form->newslug->value;
			}
			$post->tags = $form->tags->value;

			$post->content = $form->content->value;
			$post->content_type = $form->content_type->value;
			// if not previously published and the user wants to publish now, change the pubdate to the current date/time
			// if the post pubdate is <= the current date/time.
			if ( ( $post->status != Post::status( 'published' ) )
				&& ( $form->status->value == Post::status( 'published' ) )
				&& ( HabariDateTime::date_create( $form->pubdate->value )->int <= HabariDateTime::date_create()->int )
				) {
				$post->pubdate = HabariDateTime::date_create();
			}
			// else let the user change the publication date.
			//  If previously published and the new date is in the future, the post will be unpublished and scheduled. Any other status, and the post will just get the new pubdate.
			// This will result in the post being scheduled for future publication if the date/time is in the future and the new status is published.
			else {
				$post->pubdate = HabariDateTime::date_create( $form->pubdate->value );
			}

			$post->status = $form->status->value;
		}
		else {
			$postdata = array(
				'slug' => $form->newslug->value,
				'title' => $form->title->value,
				'tags' => $form->tags->value,
				'content' => $form->content->value,
				'user_id' => User::identify()->id,
				'pubdate' => HabariDateTime::date_create($form->pubdate->value),
				'status' => $form->status->value,
				'content_type' => $form->content_type->value,
			);

			$post = Post::create( $postdata );
		}

		if( $post->pubdate->int > HabariDateTime::date_create()->int && $post->status == Post::status( 'published' ) ) {
			$post->status = Post::status( 'scheduled' );
		}

		$post->info->comments_disabled = !$form->comments_enabled->value;

		Plugins::act('publish_post', $post, $form);

		$post->update( $form->minor_edit->value );

		Session::notice( sprintf( _t( 'The post %1$s has been saved as %2$s.' ), sprintf('<a href="%1$s">\'%2$s\'</a>', $post->permalink, $post->title), Post::status_name( $post->status ) ) );
		Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
	}

	public function act_request_get( $template = 'publish')
	{
		$extract = $this->handler_vars->filter_keys('id', 'content_type');
		foreach($extract as $key => $value) {
			$$key = $value;
		}

		if ( isset( $id ) ) {
			$post = Post::get( array( 'id' => $id, 'status' => Post::status( 'any' ) ) );
			$this->theme->post = $post;
			$this->theme->newpost = false;
		}
		else {
			$post = new Post();
			$this->theme->post = $post;
			$post->content_type = Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );
			$this->theme->newpost = true;
		}

		$this->theme->admin_page = sprintf(_t('Publish %s'), ucwords(Post::type_name($post->content_type)));

		$statuses = Post::list_post_statuses( false );
		$this->theme->statuses = $statuses;

		$this->theme->form = $this->form_publish($post, $this->theme->newpost );

		$this->theme->wsse = Utils::WSSE();

		$this->display( $template );
	}

	public function form_publish($post, $newpost = true)
	{
		$form = new FormUI('create-content');
		$form->set_option( 'form_action', URL::get('admin', 'page=publish' ) );
		$form->class[] = 'create';

		if( isset( $this->handler_vars['id'] ) ) {
			$post_links = $form->append('wrapper', 'post_links');
			$post_links->append('static', 'post_permalink', '<a href="'.$post->permalink.( $post->statusname == 'draft' ? '?preview=1' : '' ).'" class="viewpost" onclick="$(this).attr(\'target\', \'preview\');">'.( $post->statusname == 'draft' ? _t('Preview Post') : _t('View Post') ).'</a>');
			$post_links->class ='container';
		}

		// Create the Title field
		$form->append('text', 'title', 'null:null', _t('Title'), 'admincontrol_text');
		$form->title->class = 'important';
		$form->title->tabindex = 1;
		$form->title->value = $post->title;
		$this->theme->admin_page = sprintf(_t('Publish %s'), ucwords(Post::type_name($post->content_type)));
		// Create the silos
		if ( count( Plugins::get_by_interface( 'MediaSilo' ) ) ) {
			$form->append('silos', 'silos');
			$form->silos->silos = Media::dir();
		}

		// Create the Content field
		$form->append('textarea', 'content', 'null:null', _t('Content'), 'admincontrol_textarea');
		$form->content->class[] = 'resizable';
		$form->content->tabindex = 2;
		$form->content->value = $post->content;
		$form->content->raw = true;

		// Create the tags field
		$form->append('text', 'tags', 'null:null', _t('Tags, separated by, commas'), 'admincontrol_text');
		$form->tags->tabindex = 3;
		$form->tags->value = implode(', ', $post->tags);

		// Create the splitter
		$publish_controls = $form->append('tabs', 'publish_controls');

		// Create the publishing controls
		// pass "false" to list_post_statuses() so that we don't include internal post statuses
		$statuses = Post::list_post_statuses( false );
		unset( $statuses[array_search( 'any', $statuses )] );
		$statuses = Plugins::filter( 'admin_publish_list_post_statuses', $statuses );

		$settings = $publish_controls->append('fieldset', 'settings', _t('Settings'));

		$settings->append('select', 'status', 'null:null', _t('Content State'), array_flip($statuses), 'tabcontrol_select');
		$settings->status->value = $post->status;

		if ( $newpost ) {
			// hide the field
			$settings->append('hidden', 'minor_edit', 'null:null');
			$settings->minor_edit->value = false;
		}
		else {
			$settings->append('checkbox', 'minor_edit', 'null:null', _t('Minor Edit'), 'tabcontrol_checkbox');
			$settings->minor_edit->value = true;
		}

		$settings->append('checkbox', 'comments_enabled', 'null:null', _t('Comments Allowed'), 'tabcontrol_checkbox');
		$settings->comments_enabled->value = $post->info->comments_disabled ? false : true;

		$settings->append('text', 'pubdate', 'null:null', _t('Publication Time'), 'tabcontrol_text');
		$settings->pubdate->value = $post->pubdate->format('Y-m-d H:i:s');

		$settings->append('text', 'newslug', 'null:null', _t('Content Address'), 'tabcontrol_text');
		$settings->newslug->value = $post->slug;

		// Create the button area
		$buttons = $form->append('fieldset', 'buttons');
		$buttons->template = 'admincontrol_buttons';
		$buttons->class[] = 'container';
		$buttons->class[] = 'buttons';
		$buttons->class[] = 'publish';

		// Create the Save button
		$buttons->append('submit', 'save', _t('Save'), 'admincontrol_submit');
		$buttons->save->tabindex = 4;

		// Add required hidden controls
		$form->append('hidden', 'content_type', 'null:null');
		$form->content_type->value = $post->content_type;
		$form->append('hidden', 'post_id', 'null:null');
		$form->post_id->id = 'id';
		if ( $newpost ) {
			$form->post_id->value= 0;
		} else {
			$form->post_id->value= $this->handler_vars['id'];
		}
		$form->append('hidden', 'slug', 'null:null');
		$form->slug->value = $post->slug;

		// Let plugins alter this form
		Plugins::act('form_publish', $form, $post);

		// Put the form into the theme
		$this->theme->form = $form->get();
		return $form;
	}
}

?>