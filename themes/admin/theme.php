<?php
define( 'THEME_CLASS', 'Monolith' );

class Monolith extends Theme
{
	/**
	 * Assigns the main menu to $mainmenu into the theme.
		*/
	protected function set_admin_template_vars( $theme )
	{
		$this->get_main_menu( $theme );
	}
	
	/**
	 * Assembles the main menu for the admin area.
	 * @param Theme $theme The theme to add the menu to
	 */
	protected function get_main_menu( $theme )
	{
		$page = ( isset( $this->handler_vars['page'] ) && !empty( $this->handler_vars['page'] ) ) ? $this->handler_vars['page'] : 'dashboard';

		// These need to be replaced with submenus, but access to them is provided temporarily
		$createmenu = array();
		$managemenu = array();

		$i= 1;
		foreach( Post::list_active_post_types() as $type => $typeint ) {
			if ( $typeint == 0 ) {
				continue;
			}

			if($i == 10) {
				$hotkey= 0;
			} elseif($i > 10) {
				$hotkey= FALSE;
			} else {
				$hotkey= $i;
			}

			$createmenu['create_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=publish&content_type=' . $type ), 'title' => sprintf( _t( 'Create a new %s' ), ucwords( $type ) ), 'text' => ucwords( $type ) );
			$managemenu['manage_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=posts&type=' . $typeint ), 'title' => sprintf( _t( 'Manage %s' ), ucwords( $type ) ), 'text' => ucwords( $type ) );
			$createmenu['create_' . $typeint]['hotkey']= $hotkey;
			$managemenu['manage_' . $typeint]['hotkey']= $hotkey;

			if( $page == 'publish' && isset($this->handler_vars['content_type']) && $this->handler_vars['content_type'] == $type ) {
				$createmenu['create_' . $typeint]['selected'] = TRUE;
			}
			if( $page == 'posts' && isset($this->handler_vars['type']) && $this->handler_vars['type'] == $typeint ) {
				$managemenu['manage_' . $typeint]['selected'] = TRUE;
			}
			$i++;
		}

		$adminmenu = array(
			'create' => array( 'url' => URL::get( 'admin', 'page=publish' ), 'title' => _t('Create content'), 'text' => _t('New'), 'hotkey' => 'N', 'submenu' => $createmenu ),
			'manage' => array( 'url' => URL::get( 'admin', 'page=posts' ), 'title' => _t('Manage content'), 'text' => _t('Manage'), 'hotkey' => 'M', 'submenu' => $managemenu ),
			'comments' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t( 'Manage blog comments' ), 'text' => _t( 'Comments' ), 'hotkey' => 'C' ),
			'tags' => array( 'url' => URL::get( 'admin', 'page=tags' ), 'title' => _t( 'Manage blog tags' ), 'text' => _t( 'Tags' ), 'hotkey' => 'A' ),
			'dashboard' => array( 'url' => URL::get( 'admin', 'page=' ), 'title' => _t( 'View your user dashboard' ), 'text' => _t( 'Dashboard' ), 'hotkey' => 'D' ),
			'options' => array( 'url' => URL::get( 'admin', 'page=options' ), 'title' => _t( 'View and configure blog options' ), 'text' => _t( 'Options' ), 'hotkey' => 'O' ),
			'themes' => array( 'url' => URL::get( 'admin', 'page=themes' ), 'title' => _t( 'Preview and activate themes' ), 'text' => _t( 'Themes' ), 'hotkey' => 'T' ),
			'plugins' => array( 'url' => URL::get( 'admin', 'page=plugins' ), 'title' => _t( 'Activate, deactivate, and configure plugins' ), 'text' => _t( 'Plugins' ), 'hotkey' => 'P' ),
			'import' => array( 'url' => URL::get( 'admin', 'page=import' ), 'title' => _t( 'Import content from another blog' ), 'text' => _t( 'Import' ), 'hotkey' => 'I' ),
			'users' => array( 'url' => URL::get( 'admin', 'page=users' ), 'title' => _t( 'View and manage users' ), 'text' => _t( 'Users' ), 'hotkey' => 'U' ),
			'groups' => array( 'url' => URL::get( 'admin', 'page=groups' ), 'title' => _t( 'View and manage groups' ), 'text' => _t( 'Groups' ), 'hotkey' => 'G' ),
			'logs' => array( 'url' => URL::get( 'admin', 'page=logs'), 'title' => _t( 'View system log messages' ), 'text' => _t( 'Logs' ), 'hotkey' => 'L') ,
			'logout' => array( 'url' => URL::get( 'user', 'page=logout' ), 'title' => _t( 'Log out of the administration interface' ), 'text' => _t( 'Logout' ), 'hotkey' => 'X' ),
		);

		$mainmenus = array_merge( $adminmenu );

		foreach( $mainmenus as $menu_id => $menu ) {
			// Change this to set the correct menu as the active menu
			if( !isset( $mainmenus[$menu_id]['selected'] ) ) {
				$mainmenus[$menu_id]['selected'] = false;
			}
		}

		$mainmenus = Plugins::filter( 'adminhandler_post_loadplugins_main_menu', $mainmenus );

		foreach( $mainmenus as $key => $attrs ) {
			if( $page == $key ) {
				$mainmenus[$key]['selected'] = true;
			}
		}

		$theme->assign( 'mainmenu', $mainmenus );
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

		// Create the tags field
		$form->append('text', 'tags', 'null:null', _t('Tags, separated by, commas'), 'admincontrol_text');
		$form->tags->tabindex = 3;
		$form->tags->value = implode(', ', $post->tags);

		// Create the splitter
		$publish_controls = $form->append('tabs', 'publish_controls');

		// Create the tags selector
		$tagselector = $publish_controls->append('fieldset', 'tagselector', _t('Tags'));

		$tags_buttons = $tagselector->append('wrapper', 'tags_buttons');
		$tags_buttons->class = 'container';
		$tags_buttons->append('static', 'clearbutton', '<p class="span-5"><input type="button" value="'._t('Clear').'" id="clear"></p>');

		$tags_list = $tagselector->append('wrapper', 'tags_list');
		$tags_list->class = ' container';
		$tags_list->append('static', 'tagsliststart', '<ul id="tag-list" class="span-19">');

		$tags = Tags::get();
		$max = Tags::max_count();
		foreach ($tags as $tag) {
			$tags_list->append('tag', 'tag_'.$tag->slug, $tag, 'tabcontrol_text');
		}

		$tags_list->append('static', 'tagslistend', '</ul>');

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
	
	public function form_comment($comment, $actions) {
		$form = new FormUI( 'comment' );

		$user = User::identify();

		// Create the top description
		$top = $form->append('wrapper', 'buttons_1');
		$top->class = 'container buttons comment overview';

		$top->append('static', 'overview', $this->theme->fetch('comment.overview'));

		$buttons_1 = $top->append('wrapper', 'buttons_1');
		$buttons_1->class = 'item buttons';


		foreach($actions as $status => $action) {
			$id = $action . '_1';
			$buttons_1->append('submit', $id, _t(ucfirst($action)));
			$buttons_1->$id->class = 'button ' . $action;
			if(Comment::status_name($comment->status) == $status) {
				$buttons_1->$id->class = 'button active ' . $action;
				$buttons_1->$id->disabled = true;
			} else {
				$buttons_1->$id->disabled = false;
			}
		}

		// Content
		$form->append('wrapper', 'content_wrapper');
		$content = $form->content_wrapper->append('textarea', 'content', 'null:null', _t('Comment'), 'admincontrol_textarea');
		$content->class = 'resizable';
		$content->value = $comment->content;

		// Create the splitter
		$comment_controls = $form->append('tabs', 'comment_controls');

		// Create the author info
		$author = $comment_controls->append('fieldset', 'authorinfo', _t('Author'));

		$author->append('text', 'author_name', 'null:null', _t('Author Name'), 'tabcontrol_text');
		$author->author_name->value = $comment->name;

		$author->append('text', 'author_email', 'null:null', _t('Author Email'), 'tabcontrol_text');
		$author->author_email->value = $comment->email;

		$author->append('text', 'author_url', 'null:null', _t('Author URL'), 'tabcontrol_text');
		$author->author_url->value = $comment->url;

		$author->append('text', 'author_ip', 'null:null', _t('IP Address:'), 'tabcontrol_text');
		$author->author_ip->value = long2ip($comment->ip);

		// Create the advanced settings
		$settings = $comment_controls->append('fieldset', 'settings', _t('Settings'));

		$settings->append('text', 'comment_date', 'null:null', _t('Date:'), 'tabcontrol_text');
		$settings->comment_date->value = $comment->date->get('Y-m-d H:i:s');



		$settings->append('text', 'comment_post', 'null:null', _t('Post ID:'), 'tabcontrol_text');
		$settings->comment_post->value = $comment->post->id;

		$statuses = Comment::list_comment_statuses( false );
		$statuses = Plugins::filter( 'admin_publish_list_comment_statuses', $statuses );
		$settings->append('select', 'comment_status', 'null:null', _t('Status'), $statuses, 'tabcontrol_select');
		$settings->comment_status->value = $comment->status;

		// // Create the stats
		// $comment_controls->append('fieldset', 'stats_tab', _t('Stats'));
		// $stats= $form->stats_tab->append('wrapper', 'tags_buttons');
		// $stats->class='container';
		//
		// $stats->append('static', 'post_count', '<div class="container"><p class="pct25">'._t('Comments on this post:').'</p><p><strong>' . Comments::count_by_id($comment->post->id) . '</strong></p></div><hr />');
		// $stats->append('static', 'ip_count', '<div class="container"><p class="pct25">'._t('Comments from this IP:').'</p><p><strong>' . Comments::count_by_ip($comment->ip) . '</strong></p></div><hr />');
		// $stats->append('static', 'email_count', '<div class="container"><p class="pct25">'._t('Comments by this author:').'</p><p><strong>' . Comments::count_by_email($comment->email) . '</strong></p></div><hr />');
		// $stats->append('static', 'url_count', '<div class="container"><p class="pct25">'._t('Comments with this URL:').'</p><p><strong>' . Comments::count_by_url($comment->url) . '</strong></p></div><hr />');

		// Create the second set of action buttons
		$buttons_2 = $form->append('wrapper', 'buttons_2');
		$buttons_2->class = 'container buttons comment';

		foreach($actions as $status => $action) {
			$id = $action . '_2';
			$buttons_2->append('submit', $id, _t(ucfirst($action)));
			$buttons_2->$id->class = 'button ' . $action;
			if(Comment::status_name($comment->status) == $status) {
				$buttons_2->$id->class = 'button active ' . $action;
				$buttons_2->$id->disabled = true;
			} else {
				$buttons_2->$id->disabled = false;
			}
		}

		// Allow plugins to alter form
		Plugins::act('form_comment_edit', $form, $comment);

		return $form;
	}
	
	/**
	 * Adds a module to the user's dashboard
	 * @param object form FormUI object
	 */
	public function dash_additem( $form )
	{
		$new_module = $form->module->value;
		Modules::add( $new_module );

		// return false to redisplay the form
		return false;
	}
}
?>