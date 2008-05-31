<?php
/**
 * Habari AdminHandler Class
	* Backbone of the admin area, handles requests and functionality.
 *
 * @package Habari
 */

class AdminHandler extends ActionHandler
{
	private $theme= NULL;

	/**
	 * Verifies user credentials before creating the theme and displaying the request.
	 */
	public function __construct()
	{
		$user= User::identify();
		if ( !$user ) {
			Session::error( _t('Your session expired.'), 'expired_session' );
			Session::add_to_set( 'login', $_SERVER['REQUEST_URI'], 'original' );
			if(URL::get_matched_rule()->name == 'admin_ajax') {
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
		if ( !$user->can( 'admin' ) ) {
			die( _t( 'Permission denied.' ) );
		}
		$last_form_data= Session::get_set( 'last_form_data' ); // This was saved in the "if ( !$user )" above, UserHandler transferred it properly.
		/* At this point, Controller has not created handler_vars, so we have to modify $_POST/$_GET. */
		if ( isset( $last_form_data['post'] ) ) {
			$_POST= array_merge( $_POST, $last_form_data['post'] );
			$_SERVER['REQUEST_METHOD']= 'POST'; // This will trigger the proper act_admin switches.
			Session::remove_error( 'expired_form_submission' );
		}
		if ( isset( $last_form_data['get'] ) ) {
			$_GET= array_merge( $_GET, $last_form_data['get'] );
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
		$page= ( isset( $this->handler_vars['page'] ) && !empty( $this->handler_vars['page'] ) ) ? $this->handler_vars['page'] : 'dashboard';
		$theme_dir= Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme= Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		// Add some default stylesheets
		Stack::add('admin_stylesheet', array(Site::get_url('habari') . '/3rdparty/blueprint/screen.css', 'screen'), 'blueprint');
		Stack::add('admin_stylesheet', array(Site::get_url('habari') . '/3rdparty/blueprint/print.css', 'print'), 'blueprint_print');
		Stack::add('admin_stylesheet', array(Site::get_url('admin_theme') . '/css/admin.css', 'screen'), 'admin');

		// Add some default scripts


		$this->set_admin_template_vars( $this->theme );
		$this->theme->admin_page= $page;
		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				// Let plugins try to handle the page
				Plugins::act('admin_theme_post_' . $page, $this, $this->theme);
				// Handle POSTs to the admin pages
				$fn= 'post_' . $page;
				if ( method_exists( $this, $fn ) ) {
					$this->$fn();
				}
				else {
					$classname= get_class( $this );
					echo sprintf( _t( "\n%1$s->%2$s() does not exist.\n" ), $classname, $fn );
					exit;
				}
				break;
			default:
				// Let plugins try to handle the page
				Plugins::act('admin_theme_get_' . $page, $this, $this->theme);
				// Handle GETs of the admin pages
				$fn= 'get_' . $page;
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
		$context= $this->handler_vars['context'];
		if ( method_exists( $this, 'ajax_' . $context ) ) {
			call_user_func( array( $this, 'ajax_' . $context ), $this->handler_vars );
		}
		else {
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}
	}

	/**
	 * Handles post requests from the options admin page.
	 */
	public function post_options()
	{
		extract( $this->handler_vars );
		$fields= array( 'title' => 'title', 'tagline' => 'tagline', 'pagination' => 'pagination', 'pingback_send' => 'pingback_send', 'comments_require_id' => 'comments_require_id', 'locale' => 'locale' );
		$checkboxes= array( 'pingback_send', 'comments_require_id' );
		foreach ( $checkboxes as $checkbox ) {
			if ( !isset( ${$checkbox} ) ) {
				${$checkbox}= 0;
			}
		}
		foreach ( $fields as $input => $field ) {
			if ( Options::get( $field ) != ${$input} ) {
				Options::set( $field, ${$input} );
			}
		}
		Session::notice( _t( 'Successfully updated options' ) );
		Utils::redirect( URL::get( 'admin', 'page=options' ) );
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
		$firstpostdate= strtotime(DB::get_value('SELECT min(pubdate) FROM {posts} WHERE status = ?', array(Post::status('published'))));
		$firstpostdate= time() - $firstpostdate;
		$this->theme->active_time= array(
			'years' => floor($firstpostdate / 31556736),
			'months' => floor(($firstpostdate % 31556736) / 2629728),
			'days' => round(($firstpostdate % 2629728) / 86400),
		);

		// check for updates to core and any hooked plugins
		$this->theme->updates= Update::check();

		$this->theme->stats= array(
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

		$this->theme->recent_posts= Posts::get( array( 'status' => 'published', 'limit' => 8, 'type' => Post::type('entry') ) );

		$modules= array(
			'latestentries' => 'dash_latestentries',
			'latestcomments' => 'dash_latestcomments',
			'logs' => 'dash_logs',
		);
		$modules= Plugins::filter( 'admin_modules_theme', $modules, $this->theme );
		foreach( $modules as $modulename => $moduletemplate ) {
			$themeinit = 'fetch_dash_module_' . $modulename;
			if( method_exists( $this, $themeinit ) ) {
				$this->$themeinit($this->theme);
			}
			$modules[$modulename] = $this->theme->fetch($moduletemplate);
		}
		//$modules= array_map(array($this->theme, 'fetch'), $modules);
		$this->theme->modules= Plugins::filter( 'admin_modules', $modules, $this->theme );

		$this->display( 'dashboard' );
	}

	/**
	 * Handles post requests from the publish page.
	 */
	public function post_publish()
	{
		extract( $this->handler_vars );

		if( $pubdate > date('Y-m-d H:i:s') && $status == Post::status('published') ) {
			$status= Post::status('scheduled');
		}

		if ( isset( $slug ) ) {
			$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
			$post->title= $title;
			if ( ( $newslug != '' ) && ( $newslug != $slug ) ) {
				$post->slug= $newslug;
			}
			if (! empty( $tags ) )
				$post->tags= $tags;
			$post->content= $content;
			$post->content_type= $content_type;
			if ( ( $post->status != Post::status( 'published' ) ) && ( $status == Post::status( 'published' ) ) ) {
				$post->pubdate= date( 'Y-m-d H:i:s' );
			}
			else {
				$post->pubdate= $pubdate;
			}

			$post->status= $status;
			if ( !isset( $comments_enabled ) ) {
				$post->info->comments_disabled= TRUE;
			}
			elseif ( $post->info->comments_disabled == TRUE ) {
				unset( $post->info->comments_disabled );
			}
			$post->update();
		}
		else {
			$postdata= array(
				'slug' => $newslug,
				'title' => $title,
				'tags' => $tags,
				'content' => $content,
				'user_id' => User::identify()->id,
				'pubdate' => ( $pubdate == '' ) ? date( 'Y-m-d H:i:s' ) : $pubdate,
				'status' => $status,
				'content_type' => $content_type,
			);


			$post= Post::create( $postdata );

			if ( !isset( $comments_enabled ) ) {
				$post->info->comments_disabled= TRUE;
				$post->update();
			}
		}

		Session::notice( sprintf( _t( 'The post ' ) . '<a href="%1$s">\'%2$s\'</a>' . _t( ' has been saved as %3$s.' ), $post->permalink, $title, Post::status_name( $status ) ) );
		Utils::redirect( URL::get( 'admin', 'page=publish&slug=' . $post->slug ) );
	}

	function get_publish( $template= 'publish')
	{
		extract( $this->handler_vars );

		if ( isset( $slug ) ) {
			$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
			$this->theme->post= $post;
			$this->theme->tags= htmlspecialchars( Utils::implode_quoted( ', ', $post->tags ) );
			$this->theme->content_type= Post::type( $post->content_type );
			$this->theme->newpost= false;
		}
		else {
			$post= new Post();
			$this->theme->post= $post;
			$this->theme->tags= '';
			$this->theme->content_type= Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );
			$this->theme->newpost= true;
		}

		// Theme assigns all handler vars as theme vars, thus clobbering what we
		// set above in some cases (i.e. when ?content_type=entry is in the query string)
		$this->handler_vars['content_type']= $this->theme->content_type;

		$this->theme->silos= Media::dir();

		// pass "false" to list_post_statuses() so that we don't
		// include internal post statuses
		$statuses= Post::list_post_statuses( false );
		unset( $statuses[array_search( 'any', $statuses )] );
		$statuses= Plugins::filter( 'admin_publish_list_post_statuses', $statuses );
		$this->theme->statuses= $statuses;
		$this->theme->wsse= Utils::WSSE();

		$controls= array(
			_t('Settings') => $this->theme->fetch( 'publish_settings' ),
			_t('Tags') => $this->theme->fetch( 'publish_tags' ),
		);
		$this->theme->controls= Plugins::filter( 'publish_controls', $controls, $post );

		$this->display( $template );
	}

	/**
	 * Deletes a post from the database.
	 */
	function post_delete_post()
	{
		extract( $this->handler_vars );
		$okay= TRUE;
		if ( empty( $slug ) || empty( $nonce ) || empty( $timestamp ) || empty( $PasswordDigest ) ) {
			$okay= FALSE;
		}
		$wsse= Utils::WSSE( $nonce, $timestamp );
		if ( $digest != $wsse['digest'] ) {
			$okay= FALSE;
		}
		if ( !$okay )	{
			Utils::redirect( URL::get( 'admin', 'page=entries&type='. Post::status( 'any' ) ) );
		}
		$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
		$post->delete();
		Session::notice( sprintf( _t( 'Deleted the %1$s titled "%2$s".' ), Post::type_name( $post->content_type ), $post->title ) );
		Utils::redirect( URL::get( 'admin', 'page=entries&type=' . Post::status( 'any' ) ) );
	}

	/**
	 * Handles post requests from the user profile page.
	 */
	function post_user()
	{
		// Keep track of whether we actually need to update any fields
		$update= FALSE;
		$results= array( 'page' => 'user' );
		$currentuser= User::identify();
		extract( $this->handler_vars );
		$fields= array( 'user_id' => 'id', 'delete' => NULL, 'username' => 'username', 'displayname' => 'displayname', 'email' => 'email', 'imageurl' => 'imageurl', 'pass1' => NULL );
		$fields= Plugins::filter( 'adminhandler_post_user_fields', $fields );
		$posted_fields= array_intersect_key( $this->handler_vars, $fields );

		// Editing someone else's profile? If so, load that user's profile
		if ( isset($user_id) && ($currentuser->id != $user_id) ) {
			$user= User::get_by_id( $user_id );
			$results['user']= $user->username;
		}
		else {
			$user= $currentuser;
		}

		foreach ( $posted_fields as $posted_field => $posted_value ) {
			switch ( $posted_field ) {
				case 'delete': // Deleting a user
					if ( isset( $delete ) && ( 'user' == $delete ) ) {
						// Extra safety check here
						if ( isset( $user_id ) && ( $currentuser->id != intval( $user_id ) ) ) {
							$username= $user->username;
							$posts= Posts::get( array( 'user_id' => $user_id, 'nolimit' => 1 ) );
							if ( isset( $reassign ) && ( 1 === intval( $reassign ) ) ) {
								// we're going to re-assign all of this user's posts
								$newauthor= isset( $author ) ? intval( $author ) : 1;
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
					}
					// redirect to main user list
					$results= array( 'page' => 'users' );
					break;
				case 'username': // Changing username
					if ( isset( $username ) && ( $user->username != $username ) ) {
						// make sure the name isn't already used
						if ( $test= User::get_by_name( $username ) ) {
							Session::error( _t( 'That username is already in use!' ) );
							break;
						}
						$old_name= $user->username;
						$user->username= $username;
						Session::notice( sprintf( _t( '%1$s has been renamed to %2$s.' ), $old_name, $username ) );
						$results['user']= $username;
						$update= TRUE;
					}
					break;
				case 'email': // Changing e-mail address
					if ( isset( $email ) && ( $user->email != $email ) ) {
						$user->email= $email;
						Session::notice( sprintf( _t( '%1$s email has been changed to %2$s', $user->username, $email ) ) );
						$update= TRUE;
					}
					break;
				case 'pass1': // Changing password
					if ( isset( $pass1 ) && ( !empty( $pass1 ) ) ) {
						if ( isset( $pass2 ) && ( $pass1 == $pass2 ) ) {
							$user->password= Utils::crypt( $pass1 );
							if ( $user == $currentuser ) {
								$user->remember();
							}
							Session::notice( _t( 'Password changed successfully.' ) );
							$update= TRUE;
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
						$update= TRUE;
					}
					break;
			}
		}

		if ( $update == TRUE ) {
			$user->update();
		}

		Utils::redirect( URL::get( 'admin', $results ) );
	}

	/**
	 * Handles post requests from the Users listing (ie: creating a new user)
	 */
	public function post_users()
	{
		extract( $this->handler_vars );
		$error= '';
		if ( isset( $action ) && ( 'newuser' == $action ) ) {
			if ( !isset( $pass1 ) || !isset( $pass2 ) || empty( $pass1 ) || empty( $pass2 ) ) {
				Session::error( _t( 'Password is required.' ), 'adduser' );
			}
			else if ( $pass1 !== $pass2 ) {
				Session::error( _t( 'Password mis-match.'), 'adduser' );
			}
			if ( !isset( $email ) || empty( $email ) || ( !strstr( $email, '@' ) ) ) {
				Session::error( _t( 'Please supply a valid email address.' ), 'adduser' );
			}
			if ( !isset( $username ) || empty( $username ) ) {
				Session::error( _t( 'Please supply a user name.' ), 'adduser' );
			}
			// safety check to make sure no such username exists
			$user= User::get_by_name( $username );
			if ( isset( $user->id ) ) {
				Session::error( _t( 'That username is already assigned.' ), 'adduser' );
			}
			if ( !Session::has_errors( 'adduser' ) ) {
				$user= new User( array( 'username' => $username, 'email' => $email, 'password' => Utils::crypt( $pass1 ) ) );
				if ( $user->insert() ) {
					Session::notice( sprintf( _t( "Added user '%s'" ), $username ) );
				}
				else {
					$dberror= DB::get_last_error();
					Session::error( $dberror[2], 'adduser' );
				}
			}
			else {
				$settings= array();
				if ( isset($username) ) {
					$settings['username']= $username;
				}
				if ( isset( $email ) ) {
					$settings['email']= $email;
				}
				$this->theme->assign( 'settings', $settings );
			}
			$this->theme->display( 'users' );
		}
	}

	/**
	 * Handles plugin activation or deactivation.
	 */
	function post_plugin_toggle()
	{
		extract( $this->handler_vars );
		if ( 'activate' == strtolower( $action ) ) {
			Plugins::activate_plugin( $plugin );
			$plugins= Plugins::get_active();
			Session::notice( sprintf( _t( "Activated plugin '%s'" ), $plugins[Plugins::id_from_file( $plugin )]->info->name ) );
		}
		else {
			$plugins= Plugins::get_active();
			Session::notice( sprintf( _t( "Deactivated plugin '%s'" ), $plugins[Plugins::id_from_file( $plugin )]->info->name ) );
			Plugins::deactivate_plugin( $plugin );
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
		$all_themes= Themes::get_all_data();
		foreach($all_themes as $name => $theme) {
			if(isset($all_themes[$name]['info']->update) && $all_themes[$name]['info']->update != '' && isset($all_themes[$name]['info']->version) && $all_themes[$name]['info']->version != '') {
				Update::add($name, $all_themes[$name]['info']->update, $all_themes[$name]['info']->version);
			}
		}
		$updates= Update::check();
		foreach($all_themes as $name => $theme) {
			if(isset($all_themes[$name]['info']->update) && isset($updates[$all_themes[$name]['info']->update])) {
				$all_themes[$name]['info']->update= $updates[$all_themes[$name]['info']->update]['latest_version'];
			}
			else {
				$all_themes[$name]['info']->update= '';
			}
		}
		$this->theme->all_themes= $all_themes;

		$active_theme_dir= Options::get( 'theme_dir' );
		$this->theme->active_theme_dir= $active_theme_dir;
		$this->theme->active_theme= $all_themes[$active_theme_dir];

		// instantiate the active theme to see if it's configurable
		$active_theme= Themes::create();
		$this->theme->active_theme_name= $all_themes[$active_theme_dir]['info']->name;
		$this->theme->configurable= Plugins::filter( 'theme_config', false, $active_theme);
		$this->theme->active_theme= $all_themes[$active_theme_dir];

		$this->theme->display( 'themes' );
	}

	/**
	 * Activates a theme.
	 */
	function post_activate_theme()
	{
		extract( $this->handler_vars );
		if ( 'activate' == strtolower( $submit ) ) {
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

	function fetch_comments( $params= array() )
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals= array(
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
			$$varname= isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : (isset($params[$varname]) ? $params[$varname] : $default);
			$this->theme->{$varname}= $$varname;
		}

		// Setting these mass_delete options prevents any other processing.  Desired?
		if ( isset( $mass_spam_delete ) && $status == Comment::STATUS_SPAM ) {
			// Delete all comments that have the spam status.
			Comments::delete_by_status( Comment::STATUS_SPAM );
			// let's optimize the table
			$result= DB::query('OPTIMIZE TABLE {comments}');
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
			$okay= true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay= false;
			}
			$wsse= Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay= false;
			}
			if ( $okay ) {
				if ( $do_delete ) {
					$action= 'delete';
				}
				elseif ( $do_spam ) {
					$action= 'spam';
				}
				elseif ( $do_approve ) {
					$action= 'approve';
				}
				elseif ( $do_unapprove ) {
					$action= 'unapprove';
				}
				$ids= array();
				foreach ( $comment_ids as $id => $id_value ) {
					if ( ! isset( ${'$comment_ids['.$id.']'} ) ) { // Skip unmoderated submitted comment_ids
						$ids[]= $id;
					}
				}
				$to_update= Comments::get( array( 'id' => $ids ) );
				$modstatus= array( 'Deleted %d comments' => 0, 'Marked %d comments as spam' => 0, 'Approved %d comments' => 0, 'Unapproved %d comments' => 0, 'Edited %d comments' => 0 );
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
		$this->theme->wsse= Utils::WSSE();

		$arguments= array(
			'type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => $offset,
		);

		// there is no explicit 'all' type/status for comments, so we need to unset these arguments
		// if that's what we want. At the same time we can set up the search field
		$this->theme->search_args= '';
		if ( $type == 'All') {
			unset( $arguments['type'] );
		}
		else {
			$this->theme->search_args= 'type:' . Comment::type_name( $type ) . ' ';
		}
		if ( $status == 'All') {
			unset ( $arguments['status'] );
		}
		else {
			$this->theme->search_args.= 'status:' . Comment::status_name( $status );
		}

		if ( '' != $search ) {
			$arguments= array_merge( $arguments, Comments::search_to_get( $search ) );
		}

		$this->theme->comments= Comments::get( $arguments );
		$monthcts= Comments::get( array_merge( $arguments, array( 'month_cts' => 1 ) ) );
		$years = array();
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
			}
			else {
				$years[$month->year]= array( $month );
			}
		}
		$this->theme->years= $years;
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
		$all_plugins= Plugins::list_all();
		$active_plugins= Plugins::get_active();

		$sort_active_plugins= array();
		$sort_inactive_plugins= array();

		foreach ( $all_plugins as $file ) {
			$plugin= array();
			$plugin_id= Plugins::id_from_file( $file );
			$plugin['plugin_id']= $plugin_id;
			$plugin['file']= $file;

			$error= '';
			if ( Utils::php_check_file_syntax( $file, $error ) ) {
				$plugin['debug']= false;
				if ( array_key_exists( $plugin_id, $active_plugins ) ) {
					$plugin['verb']= _t( 'Deactivate' );
					$pluginobj= $active_plugins[$plugin_id];
					$plugin['active']= true;
					$plugin_actions= array();
					$plugin['actions']= Plugins::filter( 'plugin_config', $plugin_actions, $plugin_id );
				}
				else {
					// instantiate this plugin
					// in order to get its info()
					include_once( $file );
					$pluginobj= Plugins::load( $file, false );
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
		$this->theme->active_plugins= $sort_active_plugins;
		$this->theme->inactive_plugins= $sort_inactive_plugins;

		$this->display( 'plugins' );
	}

	/**
	 * Assign values needed to display the entries page to the theme based on handlervars and parameters
	 *
	 */
	private function fetch_entries( $params= array() )
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals= array(
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
			$$varname= isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : (isset($params[$varname]) ? $params[varname] : $default);
			$this->theme->{$varname}= $$varname;
		}

		// numbers submitted by HTTP forms are seen as strings
		// but we want the integer value for use in Posts::get,
		// so cast these two values to (int)
		if ( isset( $this->handler_vars['type'] ) ) {
			$type= (int) $this->handler_vars['type'];
		}
		if ( isset( $this->handler_vars['status'] ) ) {
			$status= (int) $this->handler_vars['status'];
		}

		// if we're updating posts, let's do so:
		if ( $do_update && isset( $post_ids ) ) {
			$okay= true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay= false;
			}
			$wsse= Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay= false;
			}
			if ( $okay ) {
				foreach ( $post_ids as $id ) {
					$ids[]= array( 'id' => $id );
				}
				$to_update= Posts::get( array( 'where' => $ids, 'nolimit' => 1 ) );
				foreach ( $to_update as $post ) {
					switch( $change ) {
					case 'delete':
						$post->delete();
						break;
					case 'publish':
						$post->publish();
						break;
					case 'unpublish':
						$post->status= Post::status( 'draft' );
						$post->update();
						break;
					}
				}
				unset( $this->handler_vars['change'] );
			}
		}

		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse= Utils::WSSE();

		$arguments= array(
			'content_type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => $offset,
			'user_id' => $user_id,
		);

		if ( '' != $search ) {
			$arguments= array_merge( $arguments, Posts::search_to_get( $search ) );
		}
		$this->theme->posts= Posts::get( $arguments );

		// setup keyword in search field if a status or type was passed in POST
		$this->theme->search_args= '';
		if ( $status != Post::status( 'any' ) ) {
			$this->theme->search_args= 'status:' . Post::status_name( $status ) . ' ';
		}
		if ( $type != Post::type( 'any' ) ) {
			$this->theme->search_args.= 'type:' . Post::type_name( $type );
		}

		$monthcts= Posts::get( array_merge( $arguments, array( 'month_cts' => 1 ) ) );
		$years = array();
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
			}
			else {
				$years[$month->year]= array( $month );
			}
		}
		$this->theme->years= $years;
	}

	/**
	 * Handles GET requests to /admin/entries
	 *
	 */
	public function get_entries()
	{
		$this->post_entries();
	}

	/**
	 * handles POST values from /manage/entries
	 * used to control what content to show / manage
	**/
	public function post_entries()
	{
		$this->fetch_entries();
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

		$this->theme->special_searches = array_merge($statuses, $types);
		$this->display( 'entries' );
	}

	/**
	 * Handles ajax requests from the manage posts page
	 */
	public function ajax_entries()
	{
		$theme_dir= Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme= Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params= $_POST;

		$this->fetch_entries( $params );
		$items= $this->theme->fetch( 'entries_items' );
		$timeline= $this->theme->fetch( 'timeline_items' );

		$output= array(
			'items' => $items,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}

	/**
	 * Handles ajax requests from the manage comments page
	 */
	public function ajax_comments()
	{
		$theme_dir= Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme= Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params= $_POST;

		$this->fetch_comments( $params );
		$items= $this->theme->fetch( 'comments_items' );
		$timeline= $this->theme->fetch( 'timeline_items' );

		$output= array(
			'items' => $items,
			'timeline' => $timeline,
		);
		echo json_encode($output);
	}

	/**
	 * handles AJAX from /manage/entries
	 * used to delete entries
	 */
	public function ajax_delete_entries($handler_vars)
	{
		$count= 0;

		$wsse= Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			echo json_encode( 'WSSE authentication failed.' );
			return;
		}

		foreach($_POST as $id => $delete) {
			// skip POST elements which are not post ids
			if ( preg_match( '/^p\d+/', $id )  && $delete ) {
				$id= substr($id, 1);
				$post= Posts::get(array('id' => $id));
				$post= $post[0];
				$post->delete();
				$count++;
			}
		}

		$msg_status= sprintf( _t('Deleted %d entries.'), $count );

		echo json_encode($msg_status);
	}

	public function ajax_update_comment( $handler_vars)
	{
		$comment= Comments::get( array( 'id' => $handler_vars['id'] ) );
		$comment= $comment[0];
		$status_msg= 'No change.';

		// check WSSE authentication
		$wsse= Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			echo json_encode( 'WSSE authentication failed.' );
			return;
		}
		
		Plugins::act( 'admin_moderate_comments', $handler_vars['action'], array( $comment ), $this );

		switch ( $handler_vars['action'] ) {
		case 'delete':
			$status_msg= 'Deleted comment '. $comment->id . '.';
			$comment->delete();
			break;
		case 'spam':
			if ( $comment->status != Comment::STATUS_SPAM ) {
				// This comment was marked as spam
				$status_msg= 'Marked comment '. $comment->id . ' as spam.';
				$comment->status= Comment::STATUS_SPAM;
				$comment->update();
			}
			break;
		case 'approve':
			if ( $comment->status != Comment::STATUS_APPROVED) {
				// This comment was marked for approval
				$status_msg= 'Approved comment '. $comment->id . '.';
				$comment->status= Comment::STATUS_APPROVED;
				$comment->update();
			}
			break;
		case 'unapprove':
			if ( $comment->status != Comment::STATUS_UNAPPROVED ) {
				// This comment was marked for unapproval
				$status_msg= 'Unapproved comment '. $comment->id . '.';
				$comment->status= Comment::STATUS_UNAPPROVED;
				$comment->update();
			}
			break;
		}

		echo json_encode($status_msg);
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
	private function fetch_logs()
	{
		$locals= array(
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
			$$varname= isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : $default;
			$this->theme->{$varname}= $$varname;
		}
		if ( $do_delete && isset( $log_ids ) ) {
			$okay= true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay= false;
			}
			$wsse= Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay= false;
			}
			if ( $okay ) {
				foreach ( $log_ids as $id ) {
					$ids[]= array( 'id' => $id );
				}
				$to_delete= EventLog::get( array( 'where' => $ids, 'nolimit' => 1 ) );
				$logstatus= array( 'Deleted %d logs' => 0 );
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
		$this->theme->severities= LogEntry::list_severities();
		$any= array( '0' => 'Any' );

		$modulelist= LogEntry::list_logentry_types();
		$modules= array();
		$types= array();
		$addresses= $any;
		$ips= DB::get_column( 'SELECT DISTINCT(ip) FROM ' . DB::table( 'log' ) );
		foreach ( $ips as $ip ) {
			$addresses[$ip]= long2ip( $ip );
		}
		$this->theme->addresses= $addresses;
		foreach ( $modulelist as $modulename => $typearray ) {
			$modules['0,'.implode( ',', $typearray )]= $modulename;
			foreach ( $typearray as $typename => $typevalue ) {
				if ( !isset( $types[$typename] ) ) {
					$types[$typename]= '0';
				}
				$types[$typename].= ',' . $typevalue;
			}
		}
		$types= array_flip( $types );
		$this->theme->types= array_merge( $any, $types );
		$this->theme->modules= array_merge( $any, $modules );

		// set up the users
		$users_temp= DB::get_results( 'SELECT DISTINCT username, user_id FROM {users} JOIN {log} ON {users}.id = {log}.user_id ORDER BY username ASC' );
		array_unshift( $users_temp, new QueryRecord( array( 'username' => 'All', 'user_id' => 0 ) ) );
		foreach ( $users_temp as $user_temp ) {
			$users[$user_temp->user_id]= $user_temp->username;
		}
		$this->theme->users= $users;

		// set up dates.
		$dates= DB::get_column( "SELECT timestamp FROM " . DB::table( 'log' ) . ' ORDER BY timestamp DESC' );
		$dates= array_map( create_function( '$date', 'return strftime( "%Y-%m", strtotime( $date ) );' ), $dates );
		array_unshift( $dates, 'Any' );
		$dates= array_combine( $dates, $dates );
		$this->theme->dates= $dates;

		// prepare the WSSE tokens
		$this->theme->wsse= Utils::WSSE();

		$arguments= array(
			'severity' => LogEntry::severity( $severity ),
			'limit' => $limit,
			'offset' => ( $index - 1) * $limit,
		);

		// deduce type_id from module and type
		$r_type= explode( ',', substr( $type, 2 ) );
		$r_module= explode( ',', substr( $module, 2 ) );
		if( $type != '0' && $module != '0' ) {
			$arguments['type_id']= array_intersect( $r_type, $r_module );
		}
		elseif( $type == '0' ) {
			$arguments['type_id']= $r_module;
		}
		elseif( $module == '0' ) {
			$arguments['type_id']= $r_type;
		}

		if ( '0' != $address ) {
			$arguments['ip']= $address;
		}

		if ( 'any' != strtolower( $date ) ) {
			list( $arguments['year'], $arguments['month'] )= explode( '-', $date );
		}
		if ( '' != $search ) {
			$arguments['criteria']= $search;
		}
		if ( '0' != $user ) {
			$arguments['user_id']= $user;
		}
		$this->theme->logs= EventLog::get( $arguments );
		$monthcts= EventLog::get( array_merge( $arguments, array( 'month_cts' => true ) ) );
		foreach( $monthcts as $month ) {
			if ( isset($years[$month->year]) ) {
				$years[$month->year][]= $month;
			}
			else {
				$years[$month->year]= array( $month );
			}
		}
		$this->theme->years= $years;
	}

	/**
	 * Handles ajax requests from the logs page
	 */
	public function ajax_logs()
	{
		$theme_dir= Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme= Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$params= $_POST;

		$this->fetch_logs( $params );
		$items= $this->theme->fetch( 'logs_items' );
		$timeline= $this->theme->fetch( 'timeline_items' );

		$output= array(
			'items' => $items,
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
		$this->theme->groups= UserGroups::get_all();
		if ( isset( $this->handler_vars['add_group'] ) ) {
			$name= $this->handler_vars['add_group'];
			if ( UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s already exists'), $name ) );
			}
			else {
				$groupdata= array(
					'name' => $name
				);
				$group= UserGroup::create($groupdata);
				Session::notice( sprintf(_t( 'Added group %s'), $name ) );
				// reload the groups
				$this->theme->groups= UserGroups::get_all();
			}
		}

		if ( isset( $this->handler_vars['delete_group'] ) ) {
			$name= $this->handler_vars['group'];
			if ( !UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$group= UserGroup::get($name);
				$group->delete();
				Session::notice( sprintf( _t( 'Removed group %s' ), $name ) );
				// reload the groups
				$this->theme->groups= UserGroups::get_all();
			}
		}

		if ( isset( $this->handler_vars['edit_group'] ) ) {
			$name= $this->handler_vars['group'];
			if ( !UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$group= UserGroup::get($name);
				$this->theme->group_edit= $group;
				$this->theme->members= $group->members;
				$this->theme->users= Users::get_all();
				$this->theme->permissions= ACL::all_permissions( 'description' );
				$this->theme->permissions_granted= $group->granted;
				$this->theme->permissions_denied= $group->denied;
			}
		}

		if ( isset( $this->handler_vars['users'] ) ) {
			$name= $this->handler_vars['group'];
			if ( ! UserGroup::exists($name) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$group= UserGroup::get($name);
				$add_users= array();
				$remove_users= array();
				$form_users= array();
				if ( isset( $this->handler_vars['user_id'] ) ) {
					$form_users= $this->handler_vars['user_id'];
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
				$this->theme->groups= UserGroups::get_all();
			}
		}

		if ( isset( $this->handler_vars['permissions'] ) ) {
			$group_name= $this->handler_vars['group'];
			if ( !UserGroup::exists( $group_name ) ) {
				Session::notice( sprintf(_t( 'The group %s does not exist'), $name ) );
			}
			else {
				$grant= array();
				$deny= array();
				$revoke= array();
				if ( isset( $this->handler_vars['grant'] ) ) {
					$form_grant= $this->handler_vars['grant'];
				}
				else {
					$form_grant= array();
				}
				if ( isset( $this->handler_vars['deny'] ) ) {
					$form_deny= $this->handler_vars['deny'];
				}
				else {
					$form_deny= array();
				}
				$group= UserGroup::get( $group_name );
				foreach( ACL::all_permissions() as $permission ) {
					if ( in_array( $permission->id, $form_grant ) ) {
						$grant[]= (int) $permission->id;
					}
					elseif ( in_array( $permission->id, $form_deny ) ) {
						$deny[]= (int) $permission->id;
					}
					else {
						$revoke[]= (int) $permission->id;
					}
				}
				if ( ! empty( $grant ) ){
					$group->grant( $grant );
				}
				if ( ! empty( $deny ) ) {
					$group->deny( $deny );
				}
				if ( ! empty( $revoke ) ) {
					$group->revoke( $revoke );
				}
				$group->update();
				Session::notice( sprintf(_t( 'Granted the permission to group %s'), $group_name ) );
				// reload the groups
				$this->theme->groups= UserGroups::get_all();
			}
		}

		$this->display( 'groups' );
	}

	/**
	 * Handle GET requests for /admin/tags to display the tags
	 */
	public function get_tags()
	{
		$this->theme->wsse= Utils::WSSE(); /* @TODO: What the heck is this doing here? */
		$this->display( 'tags' );
	}

	/**
	 * handles AJAX from /admin/tags
	 * used to delete and rename tags
	 */
	public function ajax_tags( $handler_vars)
	{
		$wsse= Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( $handler_vars['digest'] != $wsse['digest'] ) {
			echo json_encode( 'WSSE authentication failed.' );
			return;
		}

		$tag_names= array();
		$action= $this->handler_vars['action'];
		switch ( $action ) {
			case 'delete':
				foreach($_POST as $id => $delete) {
					// skip POST elements which are not tag ids
					if ( preg_match( '/^tag_\d+/', $id ) && $delete ) {
						$id= substr($id, 4);
						$tag= Tags::get_by_id($id);
						$tag_names[]= $tag->tag;
						Tags::delete($tag);
					}
				}
				$msg_status= sprintf(
					_n('Tag %s has been deleted.',
							'Tags %s have been deleted.',
							count($tag_names)
					), implode($tag_names, ', ')
				);
				echo json_encode( sprintf( $msg_status ) );
				break;
			case 'rename':
				if ( isset($this->handler_vars['master']) ) {
					$theme_dir= Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
					$this->theme= Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
					$master= $this->handler_vars['master'];
					$tag_names= array();
					foreach($_POST as $id => $rename) {
						// skip POST elements which are not tag ids
						if ( preg_match( '/^tag_\d+/', $id ) && $rename ) {
							$id= substr($id, 4);
							$tag= Tags::get_by_id($id);
							$tag_names[]= $tag->tag;
						}
					}
					Tags::rename($master, $tag_names);
					$msg_status= sprintf(
						_n('Tag %s has been renamed to %s.',
							 'Tags %s have been renamed to %s.',
							 count($tag_names)
						), implode($tag_names, ', '), $master
					);
					echo json_encode( array( 'msg' => $msg_status, 'tags' => $this->theme->fetch( 'tag_collection' ) ) );
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
		// These need to be replaced with submenus, but access to them is provided temporarily
		$createmenu= array();
		$managemenu= array();
		foreach( Post::list_active_post_types() as $type => $typeint ) {
			if ( $typeint == 0 ) {
				continue;
			}
			$createmenu['create_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=publish&content_type=' . $type ), 'title' => sprintf(_t('Content: Create a %s'), ucwords($type)), 'text' => sprintf(_t('Create %s'), ucwords($type)) );
			$managemenu['manage_' . $typeint]= array( 'url' => URL::get( 'admin', 'page=entries&type=' . $typeint ), 'title' => sprintf(_t('Content: Manage %s'), ucwords($type)), 'text' => sprintf(_t('Manage %s'), ucwords($type)) );
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
		}

		$adminmenu= array(
//		'create' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t('Content'), 'text' => _t('Comments'), 'submenu' => array($createmenu) ),
			'comments' => array( 'url' => URL::get( 'admin', 'page=comments' ), 'title' => _t('Content: Manage Blog Comments'), 'text' => _t('Comments'), 'hotkey' => '5' ),
			'tags' => array( 'url' => URL::get( 'admin', 'page=tags' ), 'title' => _t('Content: Manage Tags'), 'text' => _t('Tags'), 'hotkey' => '6' ),
			'dashboard' => array( 'url' => URL::get( 'admin', 'page=' ), 'title' => _t('Admin: Your User Dashboard'), 'text' => _t('Dashboard'), 'hotkey' => 'D' ),
			'options' => array( 'url' => URL::get( 'admin', 'page=options' ), 'title' => _t('Options'), 'text' => _t('Options'), 'hotkey' => 'O' ),
			'themes' => array( 'url' => URL::get( 'admin', 'page=themes' ), 'title' => _t('Themes'), 'text' => _t('Themes'), 'hotkey' => 'T' ),
			'plugins' => array( 'url' => URL::get( 'admin', 'page=plugins' ), 'title' => _t('Plugins'), 'text' => _t('Plugins'), 'hotkey' => 'P' ),
			'import' => array( 'url' => URL::get( 'admin', 'page=import' ), 'title' => _t('Import'), 'text' => _t('Import'), 'hotkey' => 'I' ),
			'users' => array( 'url' => URL::get( 'admin', 'page=users' ), 'title' => _t('Users'), 'text' => _t('Users'), 'hotkey' => 'U' ),
			'logs' => array( 'url' => URL::get( 'admin', 'page=logs'), 'title' => _t('View system log messages'), 'text' => _t('Logs'), 'hotkey' => 'L') ,
			'logout' => array( 'url' => URL::get( 'user', 'page=logout' ), 'title' => _t('Log out of the Administration Interface'), 'text' => _t('Logout'), 'hotkey' => 'X' ),
		);
		$mainmenus= array_merge($createmenu, $managemenu, $adminmenu);

		foreach($mainmenus as $menu_id => $menu) {
			// Change this to set the correct menu as the active menu
			$mainmenus[$menu_id]['selected']= false;
		}

		$mainmenus= Plugins::filter( 'adminhandler_post_loadplugins_main_menu', $mainmenus );

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
		$path= $handler_vars['path'];
		$rpath= $path;
		$silo= Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo
		$assets= Media::dir( $path );
		$output= array(
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
		$controls= array();
		$controls= Plugins::filter( 'media_controls', $controls, $silo, $rpath, '' );
		$output['controls']= '<li>' . implode( '</li><li>', $controls ) . '</li>';

		echo json_encode( $output );
	}

	public function ajax_media_panel( $handler_vars )
	{
		$path= $handler_vars['path'];
		$panelname= $handler_vars['panel'];
		$rpath= $path;
		$silo= Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel= '';
		$panel= Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );
		$controls= array();
		$controls= Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls= '<li>' . implode( '</li><li>', $controls ) . '</li>';
		$output= array(
			'controls' => $controls,
			'panel' => $panel,
		);

		header( 'content-type:text/javascript' );
		echo json_encode( $output );
	}

	/** Function used to set theme variables to the latest comments dashboard widget
	 */
	public function fetch_dash_module_latestcomments()
	{
		$num_posts = User::identify()->info->dash_latestcomments_number;
		if ( ! isset( $num_posts ) || ! is_numeric( $num_posts ) ) {
			$num_posts = 5;
		}
		$post_ids = DB::get_results( 'SELECT DISTINCT post_id FROM ( SELECT date, post_id FROM {comments} WHERE status = ? AND type = ? ORDER BY date DESC, post_id ) AS post_ids LIMIT ' . $num_posts, array( Comment::STATUS_APPROVED, Comment::COMMENT ), 'Post' );
		$posts = array();
		$latestcomments = array();

		foreach( $post_ids as $comment_post ) {
			$post = DB::get_row( 'select * from {posts} where id = ?', array( $comment_post->post_id ) , 'Post' );
			$comments = DB::get_results( 'SELECT * FROM {comments} WHERE post_id = ? AND status = ? AND type = ? ORDER BY date DESC LIMIT 5;', array( $comment_post->post_id, Comment::STATUS_APPROVED, Comment::COMMENT ), 'Comment' );
			$posts[] = $post;
			$latestcomments[$post->id] = $comments;
		}

		$this->theme->latestcomments_posts = $posts;
		$this->theme->latestcomments = $latestcomments;
		
		// register the formUI filter
		Plugins::register( array( $this, 'filter_control_theme_dir' ), 'filter', 'control_theme_dir' );
		// Create options form
		$form = new FormUI( 'dash_latestcomments' );
		$form_select = $form->add( 'select', 'user:number', _t('# of Entries'), '5' );
		$form_select->options = array(
			'5' => '5', '10' => '10',
			);
		$form->add( 'submit', 'user:submit', _t('Submit') );
		$form->set_option( 'save_button', false );
		$form->on_success( array( $this, 'dash_module_success' ) );
		$this->theme->latestcomments_form = $form->get();
	}
	
	/** filter_control_theme_dir
	 * Sets the FormUI theme dir to 'dash_module_formcontrols' for dash widgets
	 */
	public function filter_control_theme_dir ( $dir, $control )
	{
		if ( strpos( $control->container->name, 'dash_' ) === 0 ) {
			$dir = Site::get_dir( 'admin_theme', TRUE ) . 'dash_module_formcontrols/';
			return $dir;
		}
		else return $dir;
	}
	
	/** dash_module_success
	 * Dummy function needed to get FormUI to save values to user table
	 */
	public function dash_module_success ()
	{
		return true;
	}

}

?>
