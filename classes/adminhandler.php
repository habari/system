<?php
/**
 * Habari AdminHandler Class
 * Backbone of the admin area, handles requests and functionality.
 *
 * @package Habari
 * @todo Clean this mess up
 */

class AdminHandler extends ActionHandler
{
	/** Cached theme object for handling templates and presentation */
	private $theme = NULL;

	/**
	 * Verifies user credentials before creating the theme and displaying the request.
	 */
	public function __construct()
	{
		$user = User::identify();
		if ( !$user ) {
			Session::error( _t('Your session expired.'), 'expired_session' );
			Session::add_to_set( 'login', $_SERVER['REQUEST_URI'], 'original' );
			if( URL::get_matched_rule()->name == 'admin_ajax' ) {
				echo '{callback: function(){location.href="'.$_SERVER['HTTP_REFERER'].'"} }';
			}
			else {
			if ( !empty( $_POST ) ) {
				Session::add_to_set( 'last_form_data', $_POST, 'post' );
				Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
			}
			if ( !empty( $_GET ) ) {
				Session::add_to_set( 'last_form_data', $_GET, 'get' );
				Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
			}
			Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
			}
			exit;
		}
		/* TODO: update ACL class so that this works
		if ( !$user->can( 'admin' ) ) {
			die( _t( 'Permission denied.' ) );
		}*/
		$last_form_data = Session::get_set( 'last_form_data' ); // This was saved in the "if ( !$user )" above, UserHandler transferred it properly.
		/* At this point, Controller has not created handler_vars, so we have to modify $_POST/$_GET. */
		if ( isset( $last_form_data['post'] ) ) {
			$_POST = array_merge( $_POST, $last_form_data['post'] );
			$_SERVER['REQUEST_METHOD']= 'POST'; // This will trigger the proper act_admin switches.
			Session::remove_error( 'expired_form_submission' );
		}
		if ( isset( $last_form_data['get'] ) ) {
			$_GET = array_merge( $_GET, $last_form_data['get'] );
			Session::remove_error( 'expired_form_submission' );
			// No need to change REQUEST_METHOD since GET is the default.
		}
		$user->remember();
	}

	/**
	 * Dispatches the request to the defined method. (ie: post_{page})
	 */
	public function act_admin()
	{
		$page = ( isset( $this->handler_vars['page'] ) && !empty( $this->handler_vars['page'] ) ) ? $this->handler_vars['page'] : 'dashboard';
		$type = ( isset( $this->handler_vars['content_type'] ) && !empty( $this->handler_vars['content_type'] ) ) ? $this->handler_vars['content_type'] : '';
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		// Add some default stylesheets
		Stack::add('admin_stylesheet', array(Site::get_url('admin_theme') . '/css/admin.css', 'screen'), 'admin');

	  // Add some default scripts


		$this->set_admin_template_vars( $this->theme );
		$this->theme->admin_type = $type;
		$this->theme->admin_page = $page;
		$this->theme->admin_title = ucwords($page) . ( $type != '' ? ' ' . ucwords($type) : '' );
		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				// Let plugins try to handle the page
				Plugins::act('admin_theme_post_' . $page, $this, $this->theme);
				// Handle POSTs to the admin pages
				$fn = 'post_' . $page;
				if ( method_exists( $this, $fn ) ) {
					$this->$fn();
				}
				else {
					$classname = get_class( $this );
					echo sprintf( _t( '%1$s->%2$s() does not exist.' ), $classname, $fn );
					exit;
				}
				break;
			default:
				// Let plugins try to handle the page
				Plugins::act('admin_theme_get_' . $page, $this, $this->theme);
				// Handle GETs of the admin pages
				$fn = 'get_' . $page;
				if ( method_exists( $this, $fn ) ) {
					$this->$fn();
					exit;
				}
				// If a get_ function doesn't exist, just load the template and display it
				if ( $this->theme->template_exists( $page ) ) {
					$this->display( $page );
				}
				else {
					// The requested console page doesn't exist
					header( 'HTTP/1.0 404 Not Found' );
					$this->header();
					_e( 'Whooops!' );
					$this->footer();
				}
				break;
		}
	}

	/**
	 * Handle incoming requests to /admin_ajax for admin ajax requests
	 */
	public function act_admin_ajax()
	{
		$context = $this->handler_vars['context'];
		if ( method_exists( $this, 'ajax_' . $context ) ) {
			call_user_func( array( $this, 'ajax_' . $context ), $this->handler_vars );
		}
		else {
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}
	}

	/**
	 * Handles get requests from the options admin page
	 */
	public function get_options()
	{
		$this->post_options();
	}

	/**
	 * Handles posts requests from the options admin page
	 */
	public function post_options()
	{
		$option_items = array();
		$timezones = DateTimeZone::listIdentifiers();
		$timezones = array_merge( array( ''=>'' ), array_combine( array_values( $timezones ), array_values( $timezones ) ) );

		$option_items[_t('Name & Tagline')] = array(
			'title' => array(
				'label' => _t('Site Name'),
				'type' => 'text',
				'helptext' => '',
				),
			'tagline' => array(
				'label' => _t('Site Tagline'),
				'type' => 'text',
				'helptext' => '',
				),
			);

		$option_items[_t('Publishing')] = array(
			'pagination' => array(
				'label' => _t('Items per Page'),
				'type' => 'text',
				'helptext' => '',
				),
			'atom_entries' => array(
				'label' => _t('Entries to show in Atom feed'),
				'type' => 'text',
				'helptext' => '',
				),
			'pingback_send' => array(
				'label' => _t('Send Pingbacks to Links'),
				'type' => 'checkbox',
				'helptext' => '',
				),
			'comments_require_id' => array(
				'label' => _t('Require Comment Author Info'),
				'type' => 'checkbox',
				'helptext' => '',
				),
			);

		$option_items[_t('Time & Date')] = array(
			/*'presets' => array(
				'label' => _t('Presets'),
				'type' => 'select',
				'selectarray' => array(
					'europe' => _t('Europe')
					),
				'helptext' => '',
				),*/
			'timezone' => array(
				'label' => _t('Time Zone'),
				'type' => 'select',
				'selectarray' => $timezones,
				'helptext' => 'Adjusts server time to 21.44',
				),
			'dateformat' => array(
				'label' => _t('Date Format'),
				'type' => 'text',
				'helptext' => 'Tuesday, Jan 15th, 2008',
				),
			'timeformat' => array(
				'label' => _t('Time Format'),
				'type' => 'text',
				'helptext' => '21:44',
				)
			);

		$option_items[_t('Language')] = array(
			'locale' => array(
				'label' => _t( 'Locale' ),
				'type' => 'select',
				'selectarray' => array_merge( array( '' => 'default' ), array_combine( Locale::list_all(), Locale::list_all() ) ),
				'helptext' => 'International language code',
			),
			'system_locale' => array(
				'label' => _t('System Locale'),
				'type' => 'text',
				'helptext' => 'The appropriate locale code for your server',
			),
		);

			/*$option_items[_t('Presentation')] = array(
			'encoding' => array(
				'label' => _t('Encoding'),
				'type' => 'select',
				'selectarray' => array(
					'UTF-8' => 'UTF-8'
					),
				'helptext' => '',
				),
			);*/
		
		$option_items = Plugins::filter( 'admin_option_items', $option_items );
		
		$form = new FormUI('Admin Options');

		foreach ( $option_items as $name => $option_fields ) {
			$fieldset = $form->append( 'wrapper', Utils::slugify( $name ), $name );
			$fieldset->class = 'container settings';
			$fieldset->append( 'static', $name, '<h2>' . htmlentities( $name, ENT_COMPAT, 'UTF-8' ) . '</h2>' );
			foreach ( $option_fields as $option_name => $option ) {
				$field = $fieldset->append( $option['type'], $option_name, $option_name, $option['label'] );
				$field->template = 'optionscontrol_' . $option['type'];
				$field->class = 'item clear';
				if ( $option['type'] == 'select' && isset( $option['selectarray'] ) ) {
					$field->options = $option['selectarray'];
			}
				// @todo: do something with helptext
		}
			}

		/* @todo: filter for additional options from plugins
		 * We could either use existing config forms and simply extract
		 * the form controls, or we could create something different
		 */

		$form->append( 'submit', 'apply', _t('Apply'), 'admincontrol_submit' );
		$form->on_success( array( $this, 'form_options_success' ) );

		$this->theme->form = $form->get();
		$this->theme->option_names = array_keys( $option_items );
		$this->theme->display( 'options' );
		}

	/**
	 * Display a message when the site options are saved, and save those options
	 *
	 * @param FormUI $form The successfully submitted form
	 */
	public function form_options_success($form)
	{
		Session::notice( _t( 'Successfully updated options' ) );
		$form->save();
		Utils::redirect();
	}

	/**
	 * Handles post requests from the dashboard.
	 */
	public function post_dashboard()
	{
		$this->get_dashboard();
	}

	/**
	 * Handles get requests for the dashboard
	 * @todo update check should probably be cron'd and cached, not re-checked every load
	 */
	public function get_dashboard()
	{
		// Not sure how best to determine this yet, maybe set an option on install, maybe do this:
		$firstpostdate = DB::get_value('SELECT min(pubdate) FROM {posts} WHERE status = ?', array(Post::status('published')));
		if ( intval( $firstpostdate ) !== 0 ) $firstpostdate = time() - $firstpostdate;
		$this->theme->active_time = array(
			'years' => floor($firstpostdate / 31556736),
			'months' => floor(($firstpostdate % 31556736) / 2629728),
			'days' => round(($firstpostdate % 2629728) / 86400),
		);

		// if the active plugin list has changed, expire the updates cache
		if ( Cache::has( 'dashboard_updates' ) && ( Cache::get( 'dashboard_updates_plugins' ) != Options::get( 'active_plugins' ) ) ) {
			Cache::expire( 'dashboard_updates' );
		}

		/*
		 * Check for updates to core and any hooked plugins
		 * cache the output so we don't make a request every load but can still display updates
		 */
		if ( Cache::has( 'dashboard_updates' ) ) {
			$this->theme->updates = Cache::get( 'dashboard_updates' );
		}
		else {
			$updates = Update::check();

			if ( !Error::is_error( $updates ) ) {
				Cache::set( 'dashboard_updates', $updates );
				$this->theme->updates = $updates;

				// cache the set of plugins we just used to check for
				Cache::set( 'dashboard_updates_plugins', Options::get( 'active_plugins' ) );
			}
		}

		$this->theme->stats = array(
			'author_count' => Users::get( array( 'count' => 1 ) ),
			'page_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('page'), 'status' => Post::status('published') ) ),
			'entry_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('entry'), 'status' => Post::status('published') ) ),
			'comment_count' => Comments::count_total( Comment::STATUS_APPROVED, FALSE ),
			'tag_count' => DB::get_value('SELECT count(id) FROM {tags}'),
			'page_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('page'), 'status' => Post::status('draft'), 'user_id' => User::identify()->id ) ),
			'entry_draft_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type('entry'), 'status' => Post::status('draft'), 'user_id' => User::identify()->id ) ),
			'unapproved_comment_count' => Comments::count_total( Comment::STATUS_UNAPPROVED, FALSE ),
			'user_entry_scheduled_count' => Posts::get( array( 'count' => 1, 'content_type' => Post::type( 'entry'), 'status' => Post::status( 'scheduled' ), 'user_id' => User::identify()->id ) ),
		);

		$this->fetch_dashboard_modules();

		// check for first run
		$u = User::identify();
		if ( ! isset( $u->info->experience_level ) ) {
			$this->theme->first_run = true;
			$u->info->experience_level = 'user';
			$u->info->commit();
		}
		else {
			$this->theme->first_run = false;
		}

		$this->display( 'dashboard' );
	}

	/**
	 * Fetches active modules for display on the dashboard
	 */
	public function fetch_dashboard_modules()
	{
		// get the active module list
		$modules = Modules::get_active();

		// append the 'Add Item' module
		$modules['nosort'] = 'Add Item';

		// register the 'Add Item' filter
		Plugins::register( array( $this, 'filter_dash_module_add_item' ), 'filter', 'dash_module_add_item');

		foreach( $modules as $id => $module_name ) {
			$slug = Utils::slugify( (string) $module_name, '_' );
			$module = array(
				'name' => $module_name,
				'title' => $module_name,
				'content' => '',
				'options' => ''
				);

			$module = Plugins::filter( 'dash_module_' .$slug, $module, $id, $this->theme );

			$modules[$id] = $module;
		}

		$this->theme->modules = $modules;
	}

	/**
	 * Handles post requests from the publish page.
	 */
	public function post_publish()
	{
		extract( $this->handler_vars );

		$form = $this->form_publish( new Post(), false );

		// check to see if we are updating or creating a new post
		if ( $form->id->value != '' ) {
			$post = Post::get( array( 'id' => $form->id->value, 'status' => Post::status( 'any' ) ) );
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

	function get_publish( $template = 'publish')
	{
		extract( $this->handler_vars );
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

	function form_publish($post, $newpost = true)
	{
		$form = new FormUI('create-content');
		$form->set_option( 'form_action', URL::get('admin', 'page=publish' ) );
		$form->class[] = 'create';

		if( isset( $this->handler_vars['id'] ) ) {
			$post_links = $form->append('wrapper', 'post_links');
			$post_links->append('static', 'post_permalink', '<a href="'.$post->permalink.'" class="viewpost">'._t('View Post').'</a>');
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

		$form->append('hidden', 'slug', 'null:null');
		$form->slug->value = $post->slug;

		// Let plugins alter this form
		Plugins::act('form_publish', $form, $post);

		// Put the form into the theme
		$this->theme->form = $form->get();
		return $form;
	}

	/**
	 * Deletes a post from the database.
	 */
	function post_delete_post()
	{
		extract( $this->handler_vars );
		$okay = TRUE;
		if ( empty( $id ) || empty( $nonce ) || empty( $timestamp ) || empty( $PasswordDigest ) ) {
			$okay = FALSE;
		}
		$wsse = Utils::WSSE( $nonce, $timestamp );
		if ( $digest != $wsse['digest'] ) {
			$okay = FALSE;
		}
		if ( !$okay )	{
			Utils::redirect( URL::get( 'admin', 'page=posts&type='. Post::status( 'any' ) ) );
		}
		$post = Post::get( array( 'id' => $id, 'status' => Post::status( 'any' ) ) );
		$post->delete();
		Session::notice( sprintf( _t( 'Deleted the %1$s titled "%2$s".' ), Post::type_name( $post->content_type ), $post->title ) );
		Utils::redirect( URL::get( 'admin', 'page=posts&type=' . Post::status( 'any' ) ) );
	}

	public function get_user()
	{
		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t('nobody');
		foreach ( $author_list as $author ) {
			$authors[ $author->id ]= $author->displayname;
		}
		$this->theme->authors = $authors;

		$this->theme->currentuser = User::identify();

		$this->theme->display('user');

	}

	/**
	 * Handles post requests from the user profile page.
	 */
	function post_user()
	{

		// Keep track of whether we actually need to update any fields
		$update = FALSE;
		$results = array( 'page' => 'user' );
		$currentuser = User::identify();
		extract( $this->handler_vars );
		$fields = array( 'user_id' => 'id', 'delete' => NULL, 'username' => 'username', 'displayname' => 'displayname', 'email' => 'email', 'imageurl' => 'imageurl', 'pass1' => NULL, 'locale_tz' => 'locale_tz', 'locale_date_format' => 'locale_date_format', 'locale_time_format' => 'locale_time_format' );
		$fields = Plugins::filter( 'adminhandler_post_user_fields', $fields );
		$posted_fields = array_intersect_key( $this->handler_vars, $fields );

		// Editing someone else's profile? If so, load that user's profile
		if ( isset($user_id) && ($currentuser->id != $user_id) ) {
			$user = User::get_by_id( $user_id );
			$results['user']= $user->username;
		}
		else {
			$user = $currentuser;
		}

		foreach ( $posted_fields as $posted_field => $posted_value ) {
			switch ( $posted_field ) {
				case 'delete': // Deleting a user
						if ( isset( $user_id ) && ( $currentuser->id != intval( $user_id ) ) ) {
							$username = $user->username;
							$posts = Posts::get( array( 'user_id' => $user_id, 'nolimit' => 1 ) );
							if ( isset( $reassign ) && ( 1 === intval( $reassign ) ) ) {
								// we're going to re-assign all of this user's posts
								$newauthor = isset( $author ) ? intval( $author ) : 1;
								Posts::reassign( $newauthor, $posts );
							}
							else {
								// delete posts
								foreach ( $posts as $post ) {
									$post->delete();
								}
							}
							$user->delete();
							Session::notice( sprintf( _t( '%s has been deleted' ), $username ) );
						}
					// redirect to main user list
					$results = array( 'page' => 'users' );
					Utils::redirect( URL::get( 'admin', $results ) );
					break;
				case 'username': // Changing username
					if ( isset( $username ) && ( $user->username != $username ) ) {
						// make sure the name isn't already used
						if ( $test = User::get_by_name( $username ) ) {
							Session::error( _t( 'That username is already in use!' ) );
							break;
						}
						$old_name = $user->username;
						$user->username = $username;
						Session::notice( sprintf( _t( '%1$s has been renamed to %2$s.' ), $old_name, $username ) );
						$results['user']= $username;
						$update = TRUE;
					}
					break;
				case 'email': // Changing e-mail address
					if ( isset( $email ) && ( $user->email != $email ) ) {
						$user->email = $email;
						Session::notice( sprintf( _t( '%1$s email has been changed to %2$s' ), $user->username, $email ) );
						$update = TRUE;
					}
					break;
				case 'pass1': // Changing password
					if ( isset( $pass1 ) && ( !empty( $pass1 ) ) ) {
						if ( isset( $pass2 ) && ( $pass1 == $pass2 ) ) {
							$user->password = Utils::crypt( $pass1 );
							if ( $user == $currentuser ) {
								$user->remember();
							}
							Session::notice( _t( 'Password changed successfully.' ) );
							$update = TRUE;
						}
						else {
							Session::error( _t( 'The passwords did not match, and were not changed.' ) );
						}
					}
					break;
				default:
					if ( isset( ${$fields[$posted_field]} ) && ( $user->info->$fields[$posted_field] != ${$fields[$posted_field]} ) ) {
						$user->info->$fields[$posted_field]= ${$fields[$posted_field]};
						Session::notice( _t( 'Userinfo updated!' ) );
						$update = TRUE;
					}
					break;
			}
		}

		if ( $update == TRUE ) {
			$user->update();
		}
		else {
			Session::notice( 'Nothing changed.' );
		}

		Utils::redirect( URL::get( 'admin', $results ) );

	}

	/**
	 * handles AJAX from /users
	 * used to delete users and fetch new ones
	 */
	public function ajax_update_users($handler_vars)
	{
		echo json_encode( $this->update_users( $handler_vars ) );
	}

	public function update_users($handler_vars)
	{
		if( isset($handler_vars['delete']) ) {

			$currentuser = User::identify();

			$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
			if ( isset($handler_vars['digest']) && $handler_vars['digest'] != $wsse['digest'] ) {
				Session::error( _t('WSSE authentication failed.') );
				return Session::messages_get( true, 'array' );
			}

			foreach ( $_POST as $id => $delete ) {

				// skip POST elements which are not log ids
				if ( preg_match( '/^p\d+/', $id ) && $delete ) {
					$id = substr($id, 1);

					$ids[] = array( 'id' => $id );

				}

			}

			if ( isset( $handler_vars['checkbox_ids'] ) ) {
				foreach ( $handler_vars['checkbox_ids'] as $id => $delete ) {
					if ( $delete ) {
						$ids[] = array( 'id' => $id );
					}
				}
			}

			$count = 0;

			if( ! isset($ids) ) {
				Session::notice( _t('No users deleted.') );
				return Session::messages_get( true, 'array' );
			}

			foreach ( $ids as $id ) {
				$id = $id['id'];
				$user = User::get_by_id( $id );

				if ( $currentuser != $user ) {
					if ( $handler_vars['reassign'] != 0 ) {
						$assign = intval($handler_vars['reassign']);

						if ( $user->id == $assign ) {
							return;
						}

						$posts = Posts::get( array( 'user_id' => $user->id, 'nolimit' => 1) );

						if ( isset($posts[0]) ) {
							Posts::reassign( $assign, $posts );
						}
					}

					$user->delete();
				} else {
					$msg_status = _t('You cannot delete yourself.');
				}

				$count++;
			}

			if ( !isset($msg_status) ) {
				$msg_status = sprintf( _t('Deleted %d users.'), $count );
			}

			Session::notice( $msg_status );
		}
	}

	/**
	 * Assign values needed to display the users listing
	 *
	 */
	private function fetch_users($params = NULL)
	{
		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();

		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t('nobody');
		foreach ( $author_list as $author ) {
			$authors[ $author->id ]= $author->displayname;
		}
		$this->theme->authors = $authors;
	}

	public function get_users()
	{
		return $this->post_users();
	}

	/**
	 * Handles post requests from the Users listing (ie: creating a new user)
	 */
	public function post_users()
	{
		$this->fetch_users();

		extract( $this->handler_vars );
				
		if(isset($newuser)) {
			$action = 'newuser';
		} elseif(isset($delete)) {
			$action = 'delete';
		}
		
		$error = '';
		if ( isset( $action ) && ( 'newuser' == $action ) ) {
			if ( !isset( $new_pass1 ) || !isset( $new_pass2 ) || empty( $new_pass1 ) || empty( $new_pass2 ) ) {
				Session::error( _t( 'Password is required.' ), 'adduser' );
			}
			else if ( $new_pass1 !== $new_pass2 ) {
				Session::error( _t( 'Password mis-match.'), 'adduser' );
			}
			if ( !isset( $new_email ) || empty( $new_email ) || ( !strstr( $new_email, '@' ) ) ) {
				Session::error( _t( 'Please supply a valid email address.' ), 'adduser' );
			}
			if ( !isset( $new_username ) || empty( $new_username ) ) {
				Session::error( _t( 'Please supply a user name.' ), 'adduser' );
			}
			// safety check to make sure no such username exists
			$user = User::get_by_name( $new_username );
			if ( isset( $user->id ) ) {
				Session::error( _t( 'That username is already assigned.' ), 'adduser' );
			}
			if ( !Session::has_errors( 'adduser' ) ) {
				$user = new User( array( 'username' => $new_username, 'email' => $new_email, 'password' => Utils::crypt( $new_pass1 ) ) );
				if ( $user->insert() ) {
					Session::notice( sprintf( _t( "Added user '%s'" ), $new_username ) );
				}
				else {
					$dberror = DB::get_last_error();
					Session::error( $dberror[2], 'adduser' );
				}
			}
			else {
				$settings = array();
				if ( isset($username) ) {
					$settings['new_username'] = $new_username;
				}
				if ( isset( $new_email ) ) {
					$settings['new_email'] = $new_email;
				}
				$this->theme->assign( 'settings', $settings );
			}
		} else if ( isset( $action ) && ( 'delete' == $action ) ) {
						
			$this->update_users($this->handler_vars);
			
		}

		$this->theme->display('users');
	}

	/**
	 * Handles plugin activation or deactivation.
	 */
	function get_plugin_toggle()
	{
		extract( $this->handler_vars );
		$plugins = Plugins::list_all();
		foreach($plugins as $file) {
			if(Plugins::id_from_file($file) == $plugin_id) {
				switch ( strtolower($action) ) {
					case 'activate':
						if ( Plugins::activate_plugin($file) ) {
							$plugins = Plugins::get_active();
							Session::notice(
								_t( "Activated plugin '%s'", array($plugins[Plugins::id_from_file( $file )]->info->name) ),
								$plugins[Plugins::id_from_file($file)]->plugin_id
							);
						}
					break;
					case 'deactivate':
						if ( Plugins::deactivate_plugin($file) ) {
							$plugins = Plugins::get_active();
							Session::notice(
								_t( "Deactivated plugin '%s'", array($plugins[Plugins::id_from_file( $file )]->info->name) ),
								$plugins[Plugins::id_from_file($file)]->plugin_id
							);
						}
					break;
					default:
						Plugins::act(
							'adminhandler_get_plugin_toggle_action',
							$action,
							$file,
							$plugin_id,
							$plugins
						);
					break;
				}
			}
		}
		Utils::redirect( URL::get( 'admin', 'page=plugins' ) );
	}

	/**
	 * A POST handler for the admin themes page that simply passes those options through.
	 */
	public function post_themes()
	{
		return $this->get_themes();
	}

	/**
	 * Handles GET requests for the theme listing
	 */
	function get_themes()
	{
		$all_themes = Themes::get_all_data();
		foreach($all_themes as $name => $theme) {
			if(isset($all_themes[$name]['info']->update) && $all_themes[$name]['info']->update != '' && isset($all_themes[$name]['info']->version) && $all_themes[$name]['info']->version != '') {
				Update::add($name, $all_themes[$name]['info']->update, $all_themes[$name]['info']->version);
			}
		}
		$updates = Update::check();
		foreach($all_themes as $name => $theme) {
			if(isset($all_themes[$name]['info']->update) && isset($updates[$all_themes[$name]['info']->update])) {
				$all_themes[$name]['info']->update = $updates[$all_themes[$name]['info']->update]['latest_version'];
			}
			else {
				$all_themes[$name]['info']->update = '';
			}
		}
		$this->theme->all_themes = $all_themes;

		$this->theme->active_theme = Themes::get_active_data();
		$this->theme->active_theme_dir = $this->theme->active_theme['path'];

		// instantiate the active theme to see if it's configurable
		$active_theme = Themes::create();
		$this->theme->active_theme_name = $this->theme->active_theme['info']->name;
		$this->theme->configurable = Plugins::filter( 'theme_config', false, $active_theme);

		$this->theme->display( 'themes' );
	}

	/**
	 * Activates a theme.
	 */
	function get_activate_theme()
	{
		extract( $this->handler_vars );
		if ( isset($theme_name)  && isset($theme_dir) ) {
			Themes::activate_theme( $theme_name,  $theme_dir );
		}
		Session::notice( sprintf( _t( "Activated theme '%s'" ), $theme_name ) );
		Utils::redirect( URL::get( 'admin', 'page=themes' ) );
	}

	/**
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 */
	function post_import()
	{
		if ( !isset( $_REQUEST['importer'] ) ) {
			Utils::redirect( URL::get( 'admin', 'page=import' ) );
			exit;
		}

		$this->display( 'import' );
	}

	function form_comment($comment, $actions) {		
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
			if($status == Comment::status_name($comment->status)):
				$buttons_1->append('static', $id, '<div id="' . $id . '" class="status ' . $action . '">' . Comment::status_name($comment->status) . '</div>');
			else:
				$buttons_1->append('submit', $id, _t(ucfirst($action)));
				$buttons_1->$id->class = 'button ' . $action;		
			endif;
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
			if($status == Comment::status_name($comment->status)):
				$buttons_2->append('static', $id, '<div id="' . $id . '" class="status ' . $action . '">' . Comment::status_name($comment->status) . '</div>');
			else:
				$buttons_2->append('submit', $id, _t(ucfirst($action)));
				$buttons_2->$id->class = 'button ' . $action;		
			endif;
		}
		
		// Allow plugins to alter form
		Plugins::act('form_comment_edit', $form, $comment);
		
		return $form;
	}
	
	function get_comment($update = FALSE) {				
		if ( isset( $this->handler_vars['id'] ) && $comment = Comment::get( $this->handler_vars['id'] ) ) {			
			$this->theme->comment = $comment;		
			
			// Convenience array to output actions twice
			$actions = array(
				'Deleted' => 'delete',
				'Spam' => 'spam',
				'Unapproved' => 'unapprove',
				'Approved' => 'approve',
				'Saved' => 'save'
				);
			
			$form = $this->form_comment( $comment, $actions );

			if ( $update ) {
				foreach ( $actions as $key => $action ) {
					$id_one = $action . '_1';
					$id_two = $action . '_2';
					if ( $form->$id_one->value != NULL || $form->$id_two->value != NULL ) {
						if ( $action == 'delete' ) {
							$comment->delete();
							Utils::redirect(URL::get('admin', 'page=comments'));
							exit();
						}
						if ( $action != 'save' ) {
							foreach ( Comment::list_comment_statuses() as $status ) {
								if ( $status == $key ) {
									$comment->status = Comment::status_name( $status );
									$set_status = true;
								}
							}
						}
					}
				}

				$comment->content = $form->content;
				$comment->name = $form->author_name;
				$comment->url = $form->author_url;
				$comment->email = $form->author_email;
				$comment->ip = ip2long( $form->author_ip );

				$comment->date = HabariDateTime::date_create( $form->comment_date );
				$comment->post_id = $form->comment_post;

				if ( ! isset($set_status) ) {
					$comment->status = $form->comment_status->value;
				}

				$comment->update();

				Plugins::act('comment_edit', $comment, $form);
				
				Utils::redirect();
			}
			
			$comment->content = $form;
			$this->theme->form = $form;
			
			$this->display('comment');
		} else {
			Utils::redirect(URL::get('admin', 'page=comments'));
		}
	}
	
	function post_comment() {		
		$this->get_comment(true);
	}

	function get_comments()
	{
		$this->post_comments();
	}

	/**
	 * Handles the submission of the comment moderation form.
	 * @todo Separate delete from "delete until purge"
	 */
	function post_comments()
	{
		// Get special search statuses
		$statuses = Comment::list_comment_statuses();
		$statuses = array_combine(
			$statuses,
			array_map(
				create_function('$a', 'return "status:{$a}";'),
				$statuses
			)
		);

		// Get special search types
		$types = Comment::list_comment_types();
		$types = array_combine(
			$types,
			array_map(
				create_function('$a', 'return "type:{$a}";'),
				$types
			)
		);

		$this->theme->special_searches = array_merge($statuses, $types);

		$this->fetch_comments();
		$this->display( 'comments' );
	}

	function fetch_comments( $params = array() )
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals = array(
			'do_delete' => false,
			'do_spam' => false,
			'do_approve' => false,
			'do_unapprove' => false,
			'comment_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'mass_spam_delete' => null,
			'mass_delete' => null,
			'type' => 'All',
			'limit' => 20,
			'offset' => 0,
			'search' => '',
			'status' => 'All',
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : (isset($params[$varname]) ? $params[$varname] : $default);
			$this->theme->{$varname}= $$varname;
		}

		// Setting these mass_delete options prevents any other processing.  Desired?
		if ( isset( $mass_spam_delete ) && $status == Comment::STATUS_SPAM ) {
			// Delete all comments that have the spam status.
			Comments::delete_by_status( Comment::STATUS_SPAM );
			// let's optimize the table
			$result = DB::query('OPTIMIZE TABLE {comments}');
			Session::notice( _t( 'Deleted all spam comments' ) );
			Utils::redirect();
			die();
		}
		elseif ( isset( $mass_delete ) && $status == Comment::STATUS_UNAPPROVED ) {
			// Delete all comments that are unapproved.
			Comments::delete_by_status( Comment::STATUS_UNAPPROVED );
			Session::notice( _t( 'Deleted all unapproved comments' ) );
			Utils::redirect();
			die();
		}
		// if we're updating posts, let's do so:
		elseif ( ( $do_delete || $do_spam || $do_approve || $do_unapprove ) && isset( $comment_ids )) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				if ( $do_delete ) {
					$action = 'delete';
				}
				elseif ( $do_spam ) {
					$action = 'spam';
				}
				elseif ( $do_approve ) {
					$action = 'approve';
				}
				elseif ( $do_unapprove ) {
					$action = 'unapprove';
				}
				$ids = array();
				foreach ( $comment_ids as $id => $id_value ) {
					if ( ! isset( ${'$comment_ids['.$id.']'} ) ) { // Skip unmoderated submitted comment_ids
						$ids[]= $id;
					}
				}
				$to_update = Comments::get( array( 'id' => $ids ) );
				$modstatus = array( 'Deleted %d comments' => 0, 'Marked %d comments as spam' => 0, 'Approved %d comments' => 0, 'Unapproved %d comments' => 0, 'Edited %d comments' => 0 );
				Plugins::act( 'admin_moderate_comments', $action, $to_update, $this );

				switch ( $action ) {
					case 'delete':
						// This comment was marked for deletion
					Comments::delete_these( $to_update );
					$modstatus['Deleted %d comments'] = count( $to_update );
						break;
					case 'spam':
							// This comment was marked as spam
					Comments::moderate_these( $to_update, Comment::STATUS_SPAM );
					$modstatus['Marked %d comments as spam'] = count( $to_update );
						break;
					case 'approve':
					// Comments marked for approval
					Comments::moderate_these( $to_update, Comment::STATUS_APPROVED );
					$modstatus['Approved %d comments'] = count( $to_update );
					foreach( $to_update as $comment ) {
								$modstatus['Approved comments on these posts: %s']= (isset($modstatus['Approved comments on these posts: %s'])? $modstatus['Approved comments on these posts: %s'] . ' &middot; ' : '') . '<a href="' . $comment->post->permalink . '">' . $comment->post->title . '</a> ';
					}
						break;
					case 'unapprove':
						// This comment was marked for unapproval
					Comments::moderate_these( $to_update, Comment::STATUS_UNAPPROVED );
					$modstatus['Unapproved %d comments'] = count ( $to_update );
						break;
					case 'edit':
					foreach ( $to_update as $comment ) {
						// This comment was edited
						if( $_POST['name_' . $comment->id] != NULL ) {
							$comment->name = $_POST['name_' . $comment->id];
						}
						if( $_POST['email_' . $comment->id] != NULL ) {
							$comment->email = $_POST['email_' . $comment->id];
						}
						if( $_POST['url_' . $comment->id] != NULL ) {
							$comment->url = $_POST['url_' . $comment->id];
						}
						if( $_POST['content_' . $comment->id] != NULL ) {
							$comment->content = $_POST['content_' . $comment->id];
						}
					}
					$modstatus['Edited %d comments'] = count( $to_update );
						break;
					}
				foreach ( $modstatus as $key => $value ) {
					if ( $value ) {
						Session::notice( sprintf( _t( $key ), $value ) );
					}
				}
			}
			Utils::redirect();
			die();
		}

		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse = Utils::WSSE();

		$arguments = array(
			'type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => $offset,
		);

		// there is no explicit 'all' type/status for comments, so we need to unset these arguments
		// if that's what we want. At the same time we can set up the search field
		$this->theme->search_args = '';
		if ( $type == 'All') {
			unset( $arguments['type'] );
		}
		else {
			$this->theme->search_args = 'type:' . Comment::type_name( $type ) . ' ';
		}
		if ( $status == 'All') {
			unset ( $arguments['status'] );
		}
		else {
			$this->theme->search_args.= 'status:' . Comment::status_name( $status );
		}

		if ( '' != $search ) {
			$arguments = array_merge( $arguments, Comments::search_to_get( $search ) );
		}

		$this->theme->comments = Comments::get( $arguments );
		$monthcts = Comments::get( array_merge( $arguments, array( 'month_cts' => 1 ) ) );
		$years = array();
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
	}
			else {
				$years[$month->year]= array( $month );
			}
		}
		$this->theme->years = $years;
	}

	/**
	 * A POST handler for the admin plugins page that simply passes those options through.
	 */
	public function post_plugins()
	{
		return $this->get_plugins();
	}

	public function get_plugins()
	{
		$all_plugins = Plugins::list_all();
		$active_plugins = Plugins::get_active();

		$sort_active_plugins = array();
		$sort_inactive_plugins = array();

		foreach ( $all_plugins as $file ) {
			$plugin = array();
			$plugin_id = Plugins::id_from_file( $file );
			$plugin['plugin_id']= $plugin_id;
			$plugin['file']= $file;

			$error = '';
			if ( Utils::php_check_file_syntax( $file, $error ) ) {
				$plugin['debug']= false;
				if ( array_key_exists( $plugin_id, $active_plugins ) ) {
					$plugin['verb']= _t( 'Deactivate' );
					$pluginobj = $active_plugins[$plugin_id];
					$plugin['active']= true;
					$plugin_actions = array();
					$plugin['actions']= Plugins::filter( 'plugin_config', $plugin_actions, $plugin_id );
				}
				else {
					// instantiate this plugin
					// in order to get its info()
					include_once( $file );
					Plugins::get_plugin_classes();
					$pluginobj = Plugins::load( $file, false );
					$plugin['active']= false;
					$plugin['verb']= _t( 'Activate' );
					$plugin['actions']= array();
				}
				$plugin['info']= $pluginobj->info;
			}
			else {
				$plugin['debug']= true;
				$plugin['error']= $error;
				$plugin['active']= false;
			}
			if ($plugin['active']) {
				$sort_active_plugins[$plugin_id]= $plugin;
			}
			else {
				$sort_inactive_plugins[$plugin_id]= $plugin;
			}
		}

		//$this->theme->plugins= array_merge($sort_active_plugins, $sort_inactive_plugins);
		$this->theme->active_plugins = $sort_active_plugins;
		$this->theme->inactive_plugins = $sort_inactive_plugins;

		$this->display( 'plugins' );
	}

	/**
	 * Assign values needed to display the entries page to the theme based on handlervars and parameters
	 *
	 */
	private function fetch_posts( $params = array() )
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals = array(
			'do_update' => false,
			'post_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'change' => '',
			'user_id' => 0,
			'type' => Post::type( 'entry' ),
			'status' => Post::status( 'any' ),
			'limit' => 20,
			'offset' => 0,
			'search' => '',
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : (isset($params[$varname]) ? $params[varname] : $default);
			$this->theme->{$varname}= $$varname;
		}

		// numbers submitted by HTTP forms are seen as strings
		// but we want the integer value for use in Posts::get,
		// so cast these two values to (int)
		if ( isset( $this->handler_vars['type'] ) ) {
			$type = (int) $this->handler_vars['type'];
		}
		if ( isset( $this->handler_vars['status'] ) ) {
			$status = (int) $this->handler_vars['status'];
		}

		// if we're updating posts, let's do so:
		if ( $do_update && isset( $post_ids ) ) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				foreach ( $post_ids as $id ) {
					$ids[]= array( 'id' => $id );
				}
				$to_update = Posts::get( array( 'where' => $ids, 'nolimit' => 1 ) );
				foreach ( $to_update as $post ) {
					switch( $change ) {
					case 'delete':
						$post->delete();
						break;
					case 'publish':
						$post->publish();
						break;
					case 'unpublish':
						$post->status = Post::status( 'draft' );
						$post->update();
						break;
					}
				}
				unset( $this->handler_vars['change'] );
			}
		}

		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse = Utils::WSSE();

		$arguments = array(
			'content_type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => $offset,
			'user_id' => $user_id,
		);

		if ( '' != $search ) {
			$arguments = array_merge( $arguments, Posts::search_to_get( $search ) );
		}
		$this->theme->posts = Posts::get( $arguments );

		// setup keyword in search field if a status or type was passed in POST
		$this->theme->search_args = '';
		if ( $status != Post::status( 'any' ) ) {
			$this->theme->search_args = 'status:' . Post::status_name( $status ) . ' ';
		}
		if ( $type != Post::type( 'any' ) ) {
			$this->theme->search_args.= 'type:' . Post::type_name( $type ) . ' ';
		}
		if ( $user_id != 0 ) {
			$this->theme->search_args.= 'author:' . User::get_by_id( $user_id )->username;
		}

		$monthcts = Posts::get( array_merge( $arguments, array( 'month_cts' => 1 ) ) );
		$years = array();
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
	}
			else {
				$years[$month->year]= array( $month );
			}
		}
		if(isset($years)) {
			$this->theme->years = $years;
		}
		
	}

	/**
	 * Handles GET requests to /admin/entries
	 *
	 */
	public function get_posts()
	{
		$this->post_posts();
	}

	/**
	 * handles POST values from /manage/entries
	 * used to control what content to show / manage
	**/
	public function post_posts()
	{
		$this->fetch_posts();
		// Get special search statuses
		$statuses = array_keys(Post::list_post_statuses());
		array_shift($statuses);
		$statuses = array_combine(
			$statuses,
			array_map(
				create_function('$a', 'return "status:{$a}";'),
				$statuses
			)
		);

		// Get special search types
		$types = array_keys(Post::list_active_post_types());
		array_shift($types);
		$types = array_combine(
			$types,
			array_map(
				create_function('$a', 'return "type:{$a}";'),
				$types
			)
		);
		$this->theme->admin_page = _t('Manage Posts');
		$this->theme->admin_title = _t('Manage Posts');
		$this->theme->special_searches = array_merge($statuses, $types);
		$this->display( 'posts' );
	}

	/**
	 * Handles ajax requests from the dashboard
	 */
	public function ajax_dashboard( $handler_vars )
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		switch ( $handler_vars['action'] ) {
		case 'updateModules':
			$modules = array();
			foreach($_POST as $key => $module ) {
				// skip POST elements which are not module names
				if ( preg_match( '/^module\d+$/', $key ) ) {
					list( $module_id, $module_name ) = split( ':', $module, 2 );
					// remove non-sortable modules from the list
					if ( $module_id != 'nosort' ) {
						$modules[$module_id] = $module_name;
					}
				}
			}

			Modules::set_active( $modules );
			echo json_encode( true );
			break;
		case 'addModule':
			$id = Modules::add( $handler_vars['module_name'] );
			$this->fetch_dashboard_modules();
			$result = array(
				'message' => "Added module {$handler_vars['module_name']}.",
				'modules' => $this->theme->fetch( 'dashboard_modules' ),
			);
			echo json_encode( $result );
			break;
		case 'removeModule':
			Modules::remove( $handler_vars['moduleid'] );
			$this->fetch_dashboard_modules();
			$result = array(
				'message' => 'Removed module',
				'modules' => $this->theme->fetch( 'dashboard_modules' ),
			);
			echo json_encode( $result );
			break;
		}
	}

	/**
	 * Handles ajax requests from the manage posts page
	 */
	public function ajax_posts()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params = $_POST;

		$this->fetch_posts( $params );
		$items = $this->theme->fetch( 'posts_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );
		
		$item_ids = array();
		
		foreach($this->theme->posts as $post) {
			$item_ids['p' . $post->id]= 1;
		}
		
		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}

	/**
	 * Handles ajax requests from the manage comments page
	 */
	public function ajax_comments()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
		$this->theme->theme = $this->theme;

		$params = $_POST;

		$this->fetch_comments( $params );
		$items = $this->theme->fetch( 'comments_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();
		
		foreach($this->theme->comments as $comment) {
			$item_ids['p' . $comment->id]= 1;
		}
		
		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}

	/**
	 * Handles ajax requests from the manage users page
	 */
	public function ajax_users()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$this->theme->currentuser = User::identify();
		$items = $this->theme->fetch( 'users_items' );

		$output = array(
			'items' => $items,
		);
		echo json_encode($output);
	}

	/**
	 * handles AJAX from /comments
	 * used to edit comments inline
	 */
	public function ajax_in_edit($handler_vars)
	{

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$comment = Comment::get($handler_vars['id']);

		if(isset($handler_vars['author']) && $handler_vars['author'] != '') {
			$comment->name = $handler_vars['author'];
		}
		if(isset($handler_vars['url']) && $handler_vars['url'] != '') {
			$comment->url = $handler_vars['url'];
		}
		if(isset($handler_vars['email']) && $handler_vars['email'] != '') {
			$comment->email = $handler_vars['email'];
		}
		if(isset($handler_vars['content']) && $handler_vars['content'] != '') {
			$comment->content = $handler_vars['content'];
		}
		if(isset($handler_vars['time']) && $handler_vars['time'] != '' && isset($handler_vars['date']) && $handler_vars['date'] != '') {
			$seconds = date('s', strtotime($comment->date));
			$date = date('Y-m-d H:i:s', strtotime($handler_vars['date'] . ' ' . $handler_vars['time'] . ':' . $seconds));
			$comment->date = $date;
		}

		$comment->update();

		Session::notice( _t('Updated 1 comment.') );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

	/**
	 * handles AJAX from /manage/entries
	 * used to delete entries
	 */
	public function ajax_delete_entries($handler_vars)
	{
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$ids = array();
		foreach($_POST as $id => $delete) {
			// skip POST elements which are not post ids
			if ( preg_match( '/^p\d+/', $id )  && $delete ) {
				$ids[] = substr($id, 1);
			}
		}
		$posts = Posts::get( array( 'id' => $ids, 'nolimit' => true ) );
		foreach ( $posts as $post ) {
			$post->delete();
		}

		Session::notice( sprintf( _t('Deleted %d entries.'), count($posts) ) );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

	/**
	 * handles AJAX from /logs
	 * used to delete logs
	 */
	public function ajax_delete_logs($handler_vars)
	{
		$count = 0;

		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		foreach($_POST as $id => $delete) {
			// skip POST elements which are not log ids
			if ( preg_match( '/^p\d+/', $id ) && $delete ) {
				$id = substr($id, 1);

				$ids[]= array( 'id' => $id );

			}
		}

		$to_delete = EventLog::get( array( 'date' => 'any', 'where' => $ids, 'nolimit' => 1 ) );

		$logstatus = array( 'Deleted %d logs' => 0 );
		foreach ( $to_delete as $log ) {
			$log->delete();
			$count++;
	}
		foreach ( $logstatus as $key => $value ) {
			if ( $value ) {
				Session::notice( sprintf( _t( $key ), $value ) );
			}
		}

		Session::notice( sprintf( _t('Deleted %d logs.'), $count ) );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

	public function ajax_update_comment( $handler_vars )
	{
		// check WSSE authentication
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$ids = array();

		foreach($_POST as $id => $update) {
			// skip POST elements which are not comment ids
			if ( preg_match( '/^p\d+/', $id )  && $update ) {
				$ids[] = substr($id, 1);
			}
		}

		$comments = Comments::get( array( 'id' => $ids, 'nolimit' => true ) );
		if ( $comments === FALSE ) {
			Session::notice( _t('No comments selected.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		Plugins::act( 'admin_moderate_comments', $handler_vars['action'], $comments, $this );
		$status_msg = _t('Unknown action');

		switch ( $handler_vars['action'] ) {
		case 'delete':
			// Comments marked for deletion
			Comments::delete_these( $comments );
			$status_msg = sprintf( _n('Deleted %d comment', 'Deleted %d comments', count( $ids ) ), count( $ids ) );
			break;
		case 'spam':
			// Comments marked as spam
			Comments::moderate_these( $comments, Comment::STATUS_SPAM );
			$status_msg = sprintf( _n('Marked %d comment as spam', 'Marked %d comments as spam', count( $ids ) ), count( $ids ) );
			break;
		case 'approve':
			// Comments marked for approval
			Comments::moderate_these( $comments, Comment::STATUS_APPROVED );
			$status_msg = sprintf( _n('Approved %d comment', 'Approved %d comments', count( $ids ) ), count( $ids ) );
			break;
		case 'unapprove':
			// Comments marked for unapproval
			Comments::moderate_these( $comments, Comment::STATUS_UNAPPROVED );
			$status_msg = sprintf( _n('Unapproved %d comment', 'Unapproved %d comments', count( $ids ) ), count( $ids ) );
			break;
		default:
			// Specific plugin-supplied action
			$status_msg = Plugins::filter( 'admin_comments_action', $status_msg, $handler_vars['action'], $comments );
			break;
		}

		Session::notice( $status_msg );
		echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
	}

	/**
	 * Handle GET requests for /admin/logs to display the logs
	 */
	public function get_logs()
	{
		$this->post_logs();
	}

	/**
	 * Handle POST requests for /admin/logs to display the logs
	 */
	public function post_logs()
	{
		$this->fetch_logs();
		$this->display( 'logs' );
	}

	/**
	 * Assign values needed to display the logs page to the theme based on handlervars and parameters
	 *
	 */
	private function fetch_logs($params = NULL)
	{
		$locals = array(
			'do_delete' => false,
			'log_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'change' => '',
			'limit' => 20,
			'user' => 0,
			'date' => 'any',
			'module' => '0',
			'type' => '0',
			'severity' => 'any',
			'address' => '0',
			'search' => '',
			'do_search' => false,
			'index' => 1,
		);
		foreach ( $locals as $varname => $default ) {
			$$varname = isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : $default;
			$this->theme->{$varname}= $$varname;
		}
		if ( $do_delete && isset( $log_ids ) ) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				foreach ( $log_ids as $id ) {
					$ids[] = array( 'id' => $id );
				}
				$to_delete = EventLog::get( array( 'nolimit' => 1 ) );
				$logstatus = array( 'Deleted %d logs' => 0 );
				foreach ( $to_delete as $log ) {
					$log->delete();
					$logstatus['Deleted %d logs']+= 1;
				}
				foreach ( $logstatus as $key => $value ) {
					if ( $value ) {
						Session::notice( sprintf( _t( $key ), $value ) );
					}
				}
			}
			Utils::redirect();
			die();
		}
		$this->theme->severities = LogEntry::list_severities();
		$any = array( '0' => 'Any' );

		$modulelist = LogEntry::list_logentry_types();
		$modules = array();
		$types = array();
		$addresses = $any;
		$ips = DB::get_column( 'SELECT DISTINCT(ip) FROM ' . DB::table( 'log' ) );
		foreach ( $ips as $ip ) {
			$addresses[$ip] = long2ip( $ip );
		}
		$this->theme->addresses = $addresses;
		foreach ( $modulelist as $modulename => $typearray ) {
			$modules['0,'.implode( ',', $typearray )] = $modulename;
			foreach ( $typearray as $typename => $typevalue ) {
				if ( !isset( $types[$typename] ) ) {
					$types[$typename] = '0';
				}
				$types[$typename] .= ',' . $typevalue;
			}
		}
		$types = array_flip( $types );
		$this->theme->types = array_merge( $any, $types );
		$this->theme->modules = array_merge( $any, $modules );

		// set up the users
		$users_temp = DB::get_results( 'SELECT DISTINCT username, user_id FROM {users} JOIN {log} ON {users}.id = {log}.user_id ORDER BY username ASC' );
		array_unshift( $users_temp, new QueryRecord( array( 'username' => 'All', 'user_id' => 0 ) ) );
		foreach ( $users_temp as $user_temp ) {
			$users[$user_temp->user_id] = $user_temp->username;
		}
		$this->theme->users = $users;

		// set up dates.
		$dates = DB::get_column( 'SELECT timestamp FROM {log} ORDER BY timestamp DESC' );
		$dates = array_map( create_function( '$date', 'return HabariDateTime::date_create( $date )->get(\'Y-m\');' ), $dates );
		array_unshift( $dates, 'Any' );
		$dates = array_combine( $dates, $dates );
		$this->theme->dates = $dates;

		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();

		$arguments = array(
			'severity' => LogEntry::severity( $severity ),
			'limit' => $limit,
			'offset' => ( $index - 1) * $limit,
		);

		// deduce type_id from module and type
		$r_type = explode( ',', substr( $type, 2 ) );
		$r_module = explode( ',', substr( $module, 2 ) );
		if( $type != '0' && $module != '0' ) {
			$arguments['type_id'] = array_intersect( $r_type, $r_module );
		}
		elseif( $type == '0' ) {
			$arguments['type_id'] = $r_module;
		}
		elseif( $module == '0' ) {
			$arguments['type_id'] = $r_type;
		}

		if ( '0' != $address ) {
			$arguments['ip'] = $address;
		}

		if ( 'any' != strtolower( $date ) ) {
			list( $arguments['year'], $arguments['month'] ) = explode( '-', $date );
		}
		if ( '' != $search ) {
			$arguments['criteria'] = $search;
		}
		if ( '0' != $user ) {
			$arguments['user_id'] = $user;
		}

		if(is_array($params)) {
			$arguments = array_merge($arguments, $params);
		}

		$this->theme->logs = EventLog::get( $arguments );

		$monthcts = EventLog::get( array_merge( $arguments, array( 'month_cts' => true ) ) );
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][] = $month;
		}
			else {
				$years[$month->year] = array( $month );
			}
		}
		if ( isset($years) ) {
			$this->theme->years = $years;
		}
	}

	/**
	 * Handles ajax requests from the logs page
	 */
	public function ajax_logs()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params = $_POST;

		$this->fetch_logs( $params );
		$items = $this->theme->fetch( 'logs_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();
		
		foreach($this->theme->logs as $log) {
			$item_ids['p' . $log->id]= 1;
		}
		
		$output = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}

	public function get_groups()
	{
		$this->post_groups();
	}

	public function post_groups()
	{
		$this->theme->groups = UserGroups::get_all();
		if ( isset( $this->handler_vars['add_group'] ) ) {
			$name = $this->handler_vars['add_group'];
			if ( UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s already exists'), $name ) );
			}
			else {
				$groupdata = array(
					'name' => $name
				);
				$group = UserGroup::create($groupdata);
				Session::notice( sprintf(_t( 'Added group %s'), $name ) );
				// reload the groups
				$this->theme->groups = UserGroups::get_all();
			}
		}

		if ( isset( $this->handler_vars['delete_group'] ) ) {
			$name = $this->handler_vars['group'];
			if ( !UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$group = UserGroup::get($name);
				$group->delete();
				Session::notice( sprintf( _t( 'Removed group %s' ), $name ) );
				// reload the groups
				$this->theme->groups = UserGroups::get_all();
			}
		}

		if ( isset( $this->handler_vars['edit_group'] ) ) {
			$name = $this->handler_vars['group'];
			if ( !UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$group = UserGroup::get($name);
				$this->theme->group_edit = $group;
				$this->theme->members = $group->members;
				$this->theme->users = Users::get_all();
				$this->theme->permissions = ACL::all_permissions( 'description' );
				$this->theme->permissions_granted = $group->permissions;
			}
		}

		if ( isset( $this->handler_vars['users'] ) ) {
			$name = $this->handler_vars['group'];
			if ( ! UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$group = UserGroup::get($name);
				$add_users = array();
				$remove_users = array();
				$form_users = array();
				if ( isset( $this->handler_vars['user_id'] ) ) {
					$form_users = $this->handler_vars['user_id'];
				}
				foreach ( Users::get_all() as $user ) {
					if ( in_array( $user->id, $form_users ) ) {
						$add_users[]= (int) $user->id;
					}
					else {
						$remove_users[]= (int) $user->id;
					}
				}
				if ( ! empty( $add_users ) ) {
					$group->add( $add_users );
				}
				if ( ! empty( $remove_users ) ) {
					$group->remove( $remove_users );
				}
				$group->update();
				Session::notice( sprintf(_t( 'Modified membership of group %s'), $name ) );
				// reload the groups
				$this->theme->groups = UserGroups::get_all();
			}
		}

		if ( isset( $this->handler_vars['permissions'] ) ) {
			$group_name = $this->handler_vars['group'];
			if ( !UserGroup::exists( $group_name ) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$grant = array();
				$revoke = array();
				$group = UserGroup::get( $group_name );

				foreach( ACL::all_permissions() as $permission ) {
					// grab the type of access for each permission
					if ( isset( $this->handler_vars['perm' + $permission->id] ) ) {
						$grant[$permission->id] = $this->handler_vars['perm' + $permission->id];
					}
					// if it isn't set, then revoke it
					else {
						$revoke[] = (int) $permission->id;
					}
				}
				if ( ! empty( $grant ) ) {
					/* the following call does not yet work as used
					 * need to re-write UserGroup::grant() */
					$group->grant( $grant );
				}
				if ( ! empty( $revoke ) ) {
					$group->revoke( $revoke );
				}
				$group->update();
				Session::notice( sprintf(_t( 'Granted the permission to group %s'), $group_name ) );
				// reload the groups
				$this->theme->groups = UserGroups::get_all();
			}
		}

		$this->display( 'groups' );
	}

	/**
	 * Handle GET requests for /admin/tags to display the tags
	 */
	public function get_tags()
	{
		$this->theme->wsse = Utils::WSSE(); /* @TODO: What the heck is this doing here? */
		$this->display( 'tags' );
	}

	/**
	 * handles AJAX from /admin/tags
	 * used to delete and rename tags
	 */
	public function ajax_tags( $handler_vars)
	{
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			Session::error( _t('WSSE authentication failed.') );
			echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
			return;
		}

		$tag_names = array();
		$action = $this->handler_vars['action'];
		switch ( $action ) {
			case 'delete':
				foreach($_POST as $id => $delete) {
					// skip POST elements which are not tag ids
					if ( preg_match( '/^tag_\d+/', $id ) && $delete ) {
						$id = substr($id, 4);
						$tag = Tags::get_by_id($id);
						$tag_names[]= $tag->tag;
						Tags::delete($tag);
					}
				}
				$msg_status = sprintf(
					_n('Tag %s has been deleted.',
							'Tags %s have been deleted.',
							count($tag_names)
					), implode($tag_names, ', ')
				);
				Session::notice( $msg_status );
				echo Session::messages_get( true, array( 'Format', 'json_messages' ) );
				break;
			case 'rename':
				if ( isset($this->handler_vars['master']) ) {
					$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
					$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
					$master = $this->handler_vars['master'];
					$tag_names = array();
					foreach($_POST as $id => $rename) {
						// skip POST elements which are not tag ids
						if ( preg_match( '/^tag_\d+/', $id ) && $rename ) {
							$id = substr($id, 4);
							$tag = Tags::get_by_id($id);
							$tag_names[]= $tag->tag;
						}
					}
					Tags::rename($master, $tag_names);
					$msg_status = sprintf(
						_n('Tag %s has been renamed to %s.',
							 'Tags %s have been renamed to %s.',
							 count($tag_names)
						), implode($tag_names, ', '), $master
					);
					Session::notice( $msg_status );
					echo json_encode( array(
						'msg' => Session::messages_get( true, 'array' ),
						'tags' => $this->theme->fetch( 'tag_collection' ),
						) );
				}
				break;
		}
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
		foreach( Post::list_active_post_types() as $type => $typeint ) {
			if ( $typeint == 0 ) {
				continue;
			}
			$createmenu['create_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=publish&content_type=' . $type ), 'title' => sprintf( _t( 'Create a new %s' ), ucwords( $type ) ), 'text' => sprintf( _t( 'Create %s' ), ucwords( $type ) ) );
			$managemenu['manage_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=posts&type=' . $typeint ), 'title' => sprintf( _t( 'Manage %s' ), ucwords( $type ) ), 'text' => sprintf( _t( 'Manage %s' ), ucwords( $type ) ) );
			switch($type) {
				case 'entry':
					$createmenu['create_' . $typeint]['hotkey']= '1';
					$managemenu['manage_' . $typeint]['hotkey']= '3';
					break;
				case 'page':
					$createmenu['create_' . $typeint]['hotkey']= '2';
					$managemenu['manage_' . $typeint]['hotkey']= '4';
					break;
				default:
					$createmenu['create_' . $typeint]['hotkey']= '';
					$managemenu['manage_' . $typeint]['hotkey']= '';
					break;
			}

			if( $page == 'publish' && isset($this->handler_vars['content_type']) && $this->handler_vars['content_type'] == $type ) {
				$createmenu['create_' . $typeint]['selected'] = TRUE;
		}
			if( $page == 'posts' && isset($this->handler_vars['type']) && $this->handler_vars['type'] == $typeint ) {
				$managemenu['manage_' . $typeint]['selected'] = TRUE;
			}
		}

		$adminmenu = array(
//		'create' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t('Content'), 'text' => _t('Comments'), 'submenu' => array($createmenu) ),
			'comments' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t( 'Manage blog comments' ), 'text' => _t( 'Comments' ), 'hotkey' => '5' ),
			'tags' => array( 'url' => URL::get( 'admin', 'page=tags' ), 'title' => _t( 'Manage blog tags' ), 'text' => _t( 'Tags' ), 'hotkey' => '6' ),
			'dashboard' => array( 'url' => URL::get( 'admin', 'page=' ), 'title' => _t( 'View your user dashboard' ), 'text' => _t( 'Dashboard' ), 'hotkey' => 'D' ),
			'options' => array( 'url' => URL::get( 'admin', 'page=options' ), 'title' => _t( 'View and configure blog options' ), 'text' => _t( 'Options' ), 'hotkey' => 'O' ),
			'themes' => array( 'url' => URL::get( 'admin', 'page=themes' ), 'title' => _t( 'Preview and activate themes' ), 'text' => _t( 'Themes' ), 'hotkey' => 'T' ),
			'plugins' => array( 'url' => URL::get( 'admin', 'page=plugins' ), 'title' => _t( 'Activate, deactivate, and configure plugins' ), 'text' => _t( 'Plugins' ), 'hotkey' => 'P' ),
			'import' => array( 'url' => URL::get( 'admin', 'page=import' ), 'title' => _t( 'Import content from another blog' ), 'text' => _t( 'Import' ), 'hotkey' => 'I' ),
			'users' => array( 'url' => URL::get( 'admin', 'page=users' ), 'title' => _t( 'View and manage users' ), 'text' => _t( 'Users' ), 'hotkey' => 'U' ),
			'logs' => array( 'url' => URL::get( 'admin', 'page=logs'), 'title' => _t( 'View system log messages' ), 'text' => _t( 'Logs' ), 'hotkey' => 'L') ,
			'logout' => array( 'url' => URL::get( 'user', 'page=logout' ), 'title' => _t( 'Log out of the administration interface' ), 'text' => _t( 'Logout' ), 'hotkey' => 'X' ),
		);

		$mainmenus = array_merge( $createmenu, $managemenu, $adminmenu );

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

	/**
	 * Assigns the main menu to $mainmenu into the theme.
		*/
	protected function set_admin_template_vars( $theme )
	{
		$this->get_main_menu( $theme );
	}

	/**
	 * Helper function to assign all handler_vars into the theme and displays a theme template.
	 * @param template_name Name of template to display (note: not the filename)
	 */
	protected function display( $template_name )
	{
		$this->theme->display( $template_name );
	}

	public function ajax_media( $handler_vars )
	{
		$path = $handler_vars['path'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo
		$assets = Media::dir( $path );
		$output = array(
			'ok' => 1,
			'dirs' => array(),
			'files' => array(),
			'path' => $path,
		);
		foreach ( $assets as $asset ) {
			if ( $asset->is_dir ) {
				$output['dirs'][$asset->basename]= $asset->get_props();
			}
			else {
				$output['files'][$asset->basename]= $asset->get_props();
			}
		}
		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, '' );
		$output['controls']= '<li>' . implode( '</li><li>', $controls ) . '</li>';

		echo json_encode( $output );
	}

	public function ajax_media_panel( $handler_vars )
	{
		$path = $handler_vars['path'];
		$panelname = $handler_vars['panel'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel = '';
		$panel = Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );
		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls = '<li>' . implode( '</li><li>', $controls ) . '</li>';
		$output = array(
			'controls' => $controls,
			'panel' => $panel,
		);

		header( 'content-type:text/javascript' );
		echo json_encode( $output );
	}

	/**
	 * Function used to set theme variables to the add module dashboard widget
	 * TODO make this form use an AJAX call instead of reloading the page
	 */
	public function filter_dash_module_add_item( $module, $id, $theme )
	{
		$modules = Modules::get_all();
		if ( $modules ) {
			$modules = array_combine( array_values( $modules ), array_values( $modules ) );
}

		$form = new FormUI( 'dash_additem' );
		$form->append( 'select', 'module', 'null:unused' );
		$form->module->options = $modules;
		$form->append( 'submit', 'submit', _t('+') );
		//$form->on_success( array( $this, 'dash_additem' ) );
		$form->properties['onsubmit'] = "dashboard.add(); return false;";
		$theme->additem_form = $form->get();

		$module['content'] = $theme->fetch( 'dash_additem' );
		return $module;
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
