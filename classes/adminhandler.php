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
			if ( !empty( $_POST ) ) {
				Session::add_to_set( 'last_form_data', $_POST, 'post' );
				Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
			}
			if ( !empty( $_GET ) ) {
				Session::add_to_set( 'last_form_data', $_GET, 'get' );
				Session::error( _t('We saved the last form you posted. Log back in to continue its submission.'), 'expired_form_submission' );
			}
			Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
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
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme= Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
		$this->set_admin_template_vars( $this->theme );
		$this->theme->admin_page = $page;
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
					echo sprintf( _t( "\n%s->%s() does not exist.\n" ), $classname, $fn );
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
		$fields= array( 'title' => 'title', 'tagline' => 'tagline', 'about' => 'about', 'pagination' => 'pagination', 'pingback_send' => 'pingback_send', 'comments_require_id' => 'comments_require_id' );
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
		_e( 'Nothing sends POST requests to the dashboard. Yet.' );
	}

	/**
	 * Handles get requests for the dashboard
	 */
	public function get_dashboard()
	{
		// Not sure how best to determine this yet, maybe set an option on install, maybe do this:
		$firstpostdate = strtotime(DB::get_value('SELECT min(pubdate) FROM {posts} WHERE status = ?', array(Post::status('published'))));
		$firstpostdate = time() - $firstpostdate;
		$this->theme->active_time = array(
			'years' => floor($firstpostdate / 31536000),
			'months' => floor(($firstpostdate % 31536000) / 2678400),
			'days' => round(($firstpostdate % 2678400) / 86400),
		);

		$this->theme->stats = array(
			'author_count' => DB::get_value('SELECT count(*) FROM {posts} GROUP BY user_id;'),
			'page_count' => DB::get_value('SELECT count(pubdate) FROM {posts} WHERE content_type = ? and status = ?', array(Post::type('page'), Post::status('published'))),
			'entry_count' => DB::get_value('SELECT count(pubdate) FROM {posts} WHERE content_type = ? and status = ?', array(Post::type('entry'), Post::status('published'))),
			'comment_count' => DB::get_value('SELECT min(pubdate) FROM {posts} WHERE content_type = ?', array(Post::type('page'))),
			'tag_count' => DB::get_value('SELECT count(*) FROM {tags}'),
			'page_draft_count' => DB::get_value('SELECT min(pubdate) FROM {posts} WHERE content_type = ? and status = ?', array(Post::type('page'), Post::status('draft'))),
			'entry_draft_count' => DB::get_value('SELECT min(pubdate) FROM {posts} WHERE content_type = ? and status = ?', array(Post::type('entry'), Post::status('draft'))),
			'unapproved_comment_count' => Comments::count_total( Comment::STATUS_UNAPPROVED ),
		);

		$this->theme->recent_posts = Posts::get( array( 'status' => 'published', 'limit' => 8 ) );
		$this->theme->recent_comments = Comments::get( array( 'status' => 'approved', 'limit' => 5 ) );

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

		Session::notice( sprintf( _t( 'The post \'%s\' has been saved as %s.' ), $title, Post::status_name( $status ) ) );
		Utils::redirect( URL::get( 'admin', 'page=publish&slug=' . $post->slug ) );
	}

	function get_publish( $template= 'publish')
	{
		extract( $this->handler_vars );

		if ( isset( $slug ) ) {
			$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
			$this->theme->post= $post;
			$this->theme->tags= htmlspecialchars( Utils::implode_quoted( ',', $post->tags ) );
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
			'Settings' => $this->theme->fetch( 'publish_settings' ),
			'Tags' => $this->theme->fetch( 'publish_tags' ),
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
			$okey= FALSE;
		}
		// Ensure the request was submitted less than five minutes ago
		if ( ( time() - strtotime( $timestamp ) ) > 300 ) {
			$okay= FALSE;
		}
		$wsse= Utils::WSSE( $nonce, $timestamp );
		if ( $digest != $wsse['digest'] ) {
			$okay= FALSE;
		}
		if ( !$okay )	{
			Utils::redirect( URL::get( 'admin', 'page=content' ) );
		}
		$post= Post::get( array( 'slug' => $slug, 'status' => Post::status( 'any' ) ) );
		$post->delete();
		Session::notice( sprintf( _t( 'Deleted %1$s "%2$s".' ), Post::type_name( $post->content_type ), $post->title ) );
		Utils::redirect( URL::get( 'admin', 'page=content' ) );
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
		$user= $currentuser;
		extract( $this->handler_vars );
		$fields= array( 'user_id' => 'id', 'delete' => NULL, 'username' => 'username', 'displayname' => 'displayname', 'email' => 'email', 'imageurl' => 'imageurl', 'pass1' => NULL );
		$fields= Plugins::filter( 'adminhandler_post_user_fields', $fields );

		foreach ( $fields as $input => $field ) {
			switch ( $input ) {
				case 'user_id': // Editing someone else's profile? If so, load that user's profile
					if ( $currentuser->id != $user_id ) {
						$user= User::get_by_id( $user_id );
						$results['user']= $user->username;
					}
					break;
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
						$update= TRUE;
					}
					break;
				case 'email': // Changing e-mail address
					if ( isset( $email ) && ( $user->email != $email ) ) {
						$user->email= $email;
						Session::notice( $user->username . _t( ' email has been changed to ' ) . $email . '.' );
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
					if ( isset( ${$input} ) && ( $user->info->$field != ${$input} ) ) {
						$user->info->$field= ${$input};
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
			$this->theme->display('users');
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
		$this->theme->all_themes= $all_themes;

		$active_theme_dir= Options::get( 'theme_dir' );
		$this->theme->active_theme_dir= $active_theme_dir;
		$this->theme->active_theme= $all_themes[$active_theme_dir];

		// instantiate the active theme to see if it's configurable
		$active_theme= Themes::create();
		$this->theme->active_theme_name= $all_themes[$active_theme_dir]['info']->name;
		$this->theme->configurable= Plugins::filter( 'theme_config', false, $active_theme);

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

	function get_moderate()
	{
		$this->post_moderate();
	}

	/**
	 * Handles the submission of the comment moderation form.
	 * @todo Separate delete from "delete until purge"
	 */
	function post_moderate()
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals= array(
			'do_update' => false,
			'comment_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'mass_spam_delete' => null,
			'mass_delete' => null,

			'type' => Comment::type( 'comment' ),
			'status' => Comment::status( 'approved' ),
			'limit' => 30,
			'orderby' => 'date DESC',
			'default_radio' => array( 'approve'=>'', 'delete'=>'', 'spam'=>'', 'unapprove'=>'', 'edit' =>'' ),
			'show' => '0',
			'search' => '',
			'search_fields' => array( 'content' ),
			'search_status' => null,
			'search_type' => null,
			'do_search' => false,
			'index' => 1,
		);
		foreach ( $locals as $varname => $default ) {
			$$varname= isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : $default;
			$this->theme->{$varname}= $$varname;
		}

		// Setting these mass_delete options prevents any other processing.  Desired?
		if ( isset( $mass_spam_delete ) && $search_status == Comment::STATUS_SPAM ) {
			// Delete all comments that have the spam status.
			Comments::delete_by_status( Comment::STATUS_SPAM );
			// let's optimize the table
			$result= DB::query('OPTIMIZE TABLE {comments}');
			Session::notice( _t( 'Deleted all spam comments' ) );
			Utils::redirect();
			die();
		}
		elseif ( isset( $mass_delete ) && $search_status == Comment::STATUS_UNAPPROVED ) {
			// Delete all comments that are unapproved.
			Comments::delete_by_status( Comment::STATUS_UNAPPROVED );
			Session::notice( _t( 'Deleted all unapproved comments' ) );
			Utils::redirect();
			die();
		}
		// if we're updating posts, let's do so:
		elseif ( $do_update && isset( $comment_ids ) ) {
			$okay= true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $PasswordDigest ) ) {
				$okay= false;
			}
			// Ensure the request was submitted less than five minutes ago
			if ( ( time() - strtotime( $timestamp ) ) > 300 ) {
				$okay= false;
			}
			$wsse= Utils::WSSE( $nonce, $timestamp );
			if ( $PasswordDigest != $wsse['digest'] ) {
				$okay= false;
			}
			if ( $okay ) {
				foreach ( $comment_ids as $id => $id_change ) {
					if ( $id_change != $show ) { // Skip unmoderated submitted comment_ids
						$ids[]= $id;
						$ids_change[$id]= $id_change;
					}
				}
				$to_update= Comments::get( array( 'id' => $ids ) );
				$modstatus= array( 'Deleted %d comments' => 0, 'Marked %d comments as spam' => 0, 'Approved %d comments' => 0, 'Unapproved %d comments' => 0, 'Edited %d comments' => 0 );
				Plugins::act( 'admin_moderate_comments', $ids_change, $to_update, $this );
				foreach ( $to_update as $comment ) {
					switch ( $ids_change[$comment->id] ) {
					case 'delete':
						// This comment was marked for deletion
						$comment->delete();
						$modstatus['Deleted %d comments']++;
						break;
					case 'spam':
						// This comment was marked as spam
						$comment= Comment::get( $comment->id );
						$modstatus['Marked %d comments as spam']+= $comment->status != Comment::STATUS_SPAM;
						$comment->status= Comment::STATUS_SPAM;
						$comment->update();
						break;
					case 'approve':
						// This comment was marked for approval
						$comment= Comment::get( $comment->id );
						$modstatus['Approved %d comments']+= $comment->status != Comment::STATUS_APPROVED;
						$modstatus['Approved comments on these posts: %s'] = (isset($modstatus['Approved comments on these posts: %s'])? $modstatus['Approved comments on these posts: %s'] . ' &middot; ' : '') . '<a href="' . $comment->post->permalink . '">' . $comment->post->title . '</a> ';
						$comment->status= Comment::STATUS_APPROVED;
						$comment->update();
						break;
					case 'unapprove':
						// This comment was marked for unapproval
						$comment= Comment::get( $comment->id );
						$modstatus['Unapproved %d comments']+= $comment->status != Comment::STATUS_UNAPPROVED;
						$comment->status= Comment::STATUS_UNAPPROVED;
						$comment->update();
						break;
					case 'edit':
						// This comment was edited
						$comment= Comment::get( $comment->id );
						if( $_POST['name_' . $comment->id] != NULL ) {
							$comment->name= $_POST['name_' . $comment->id];
						}
						if( $_POST['email_' . $comment->id] != NULL ) {
							$comment->email= $_POST['email_' . $comment->id];
						}
						if( $_POST['url_' . $comment->id] != NULL ) {
							$comment->url= $_POST['url_' . $comment->id];
						}
						if( $_POST['content_' . $comment->id] != NULL ) {
							$comment->content= $_POST['content_' . $comment->id];
						}
						$comment->update();
						$modstatus['Edited %d comments']++;
						break;
					}
				}
				foreach ( $modstatus as $key => $value ) {
					if ( $value ) {
						Session::notice( sprintf( _t( $key ), $value ) );
					}
				}
				unset( $this->handler_vars['change'] );
			}
			Utils::redirect();
			die();
		}

		// Set up the limits select box
		$limits= array( 5, 10, 20, 50, 100 );
		$limits= array_combine( $limits, $limits );
		$this->theme->limits= $limits;

		// Set up the type select box
		$types_tmp= Comment::list_comment_types();
		$types['All']= 'All';
		foreach ( $types_tmp as $type_key => $type_val  ) {
			$types[$type_key]= $type_val;
		}
		$this->theme->types= $types;

		// Set up the status select box
		$statuses_tmp= Comment::list_comment_statuses();
		$statuses['All']= 'All';
		foreach ( $statuses_tmp as $status_key => $status_val  ) {
			$statuses[$status_key]= $status_val;
		}
		$this->theme->statuses= $statuses;

		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse= Utils::WSSE();

		$arguments= array(
			'limit' => $limit,
			'offset' => ( $index - 1 ) * $limit,
		);

		// Decide what to display
		$arguments['status']= intval( $search_status );
		switch ( $search_status ) {
			case 'All':
				$this->theme->mass_delete= '';
				unset( $arguments['status'] );
				break;
			case Comment::STATUS_SPAM:
				$this->theme->mass_delete= 'mass_spam_delete';
				$default_radio['spam']= ' checked';
				break;
			case Comment::STATUS_APPROVED:
				$this->theme->mass_delete= '';
				$default_radio['approve']= ' checked';
				break;
			case Comment::STATUS_UNAPPROVED:
				$this->theme->mass_delete= 'mass_delete';
				$default_radio['unapprove']= ' checked';
				break;
			default:
				$this->theme->mass_delete= '';
				break;
		}
		$this->theme->default_radio= $default_radio;

		if($search_type != 'All') {
			$arguments['type']= intval( $search_type );
		}

		if ( '' != $search ) {
			$arguments['criteria']= $search;
			$arguments['criteria_fields']= $search_fields;
		}
		
		if ( $search_type == 'All' ) {
			unset( $arguments['type'] );
		}
		$this->theme->comments= Comments::get( $arguments );

		// Get the page count
		$arguments['count']= 'id';
		unset( $arguments['limit'] );
		unset( $arguments['offset'] );
		$totalpages= Comments::get( $arguments );
		$pagecount= ceil( $totalpages / $limit );

		// Put page numbers into an array for the page controls to output.
		$pages= array();
		for ( $z= 1; $z <= $pagecount; $z++ ) {
			$pages[$z]= $z;
		}
		$this->theme->pagecount= $pagecount;
		$this->theme->pages= $pages;

		$this->display( 'moderate' );
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
			} else {
				$sort_inactive_plugins[$plugin_id]= $plugin;
			}
		}

		//$this->theme->plugins= array_merge($sort_active_plugins, $sort_inactive_plugins);
		$this->theme->active_plugins= $sort_active_plugins;
		$this->theme->inactive_plugins= $sort_inactive_plugins;

		$this->display( 'plugins' );
	}

	public function get_content()
	{
		$this->post_content();
	}

	/**
	 * handles POST values from /manage/content
	 * used to control what content to show / manage
	**/
	public function post_content()
	{
		// Make certain handler_vars local with defaults, and add them to the theme output
		$locals= array(
			'do_update' => false,
			'post_ids' => null,
			'nonce' => '',
			'timestamp' => '',
			'PasswordDigest' => '',
			'change' => '',

			'author' => 0,
			'type' => Post::type( 'entry' ),
			'status' => Post::status( 'published' ),
			'limit' => 20,
			'year_month' => 'Any',
			'search' => '',
			'do_search' => false,
			'index' => 1,
		);
		foreach ( $locals as $varname => $default ) {
			$$varname= isset( $this->handler_vars[$varname] ) ? $this->handler_vars[$varname] : $default;
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
			// Ensure the request was submitted less than five minutes ago
			if ( ( time() - strtotime( $timestamp ) ) > 300 ) {
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

		// Set up Authors select box
		$authors_temp= DB::get_results( 'SELECT username, user_id FROM {users} JOIN {posts} ON {users}.id={posts}.user_id GROUP BY user_id ORDER BY username ASC' );
		array_unshift( $authors_temp, new QueryRecord( array( 'username' => 'All', 'user_id' => 0 ) ) );
		$authors= array();
		foreach ( $authors_temp as $author ) {
			$authors[$author->user_id]= $author->username;
		}
		$this->theme->authors= $authors;

		// Set up the dates select box
		$dates= DB::get_column( "SELECT pubdate FROM " . DB::table( 'posts' ) . ' ORDER BY pubdate DESC' );
		$dates= array_map( create_function( '$date', 'return strftime( "%Y-%m", strtotime( $date ) );' ), $dates );
		array_unshift( $dates, 'Any' );
		$dates= array_combine( $dates, $dates );
		$this->theme->dates= $dates;

		// Set up the limits select box
		$limits= array( 5, 10, 20, 50, 100 );
		$limits= array_combine( $limits, $limits );
		$this->theme->limits= $limits;

		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse= Utils::WSSE();

		$arguments= array(
			'content_type' => $type,
			'status' => $status,
			'limit' => $limit,
			'offset' => ( $index - 1 ) * $limit,
		);
		if ( 'any' != strtolower( $year_month ) ) {
			list( $arguments['year'], $arguments['month'] )= explode( '-', $year_month );
		}
		if ( '' != $search ) {
			$arguments['criteria']= $search;
		}
		$this->theme->posts= Posts::get( $arguments );

		// Get the page count
		$arguments['count']= 'id';
		unset( $arguments['limit'] );
		unset( $arguments['offset'] );
		$totalpages= Posts::get( $arguments );
		$pagecount= ceil( $totalpages / $limit );

		// Put page numbers into an array for the page controls to output.
		$pages= array();
		for ( $z= 1; $z <= $pagecount; $z++ ) {
			$pages[$z]= $z;
		}
		$this->theme->pagecount= $pagecount;
		$this->theme->pages= $pages;

		$this->display( 'content' );
	}

	public function get_logs()
	{
		$this->post_logs();
	}

	public function post_logs()
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
			// Ensure the request was submitted less than five minutes ago
			if ( ( time() - strtotime( $timestamp ) ) > 300 ) {
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
		$users_temp= DB::get_results( 'SELECT username, user_id FROM ' . DB::table( 'users' ) . ' JOIN ' . DB::table( 'log' ) . ' ON ' . DB::table( 'users' ) . '.id=' . DB::table( 'log' ) . '.user_id GROUP BY user_id ORDER BY username ASC' );
		array_unshift( $users_temp, new QueryRecord( array( 'username' => 'All', 'user_id' => 0 ) ) );
		foreach ( $users_temp as $user ) {
			$users[$user->user_id]= $user->username;
		}
		$this->theme->users= $users;

		// set up dates.
		$dates= DB::get_column( "SELECT timestamp FROM " . DB::table( 'log' ) . ' ORDER BY timestamp DESC' );
		$dates= array_map( create_function( '$date', 'return strftime( "%Y-%m", strtotime( $date ) );' ), $dates );
		array_unshift( $dates, 'Any' );
		$dates= array_combine( $dates, $dates );
		$this->theme->dates= $dates;

		// set up the limit select box
		$limits= array( 5, 10, 20, 50, 100 );
		$limits= array_combine( $limits, $limits );
		$this->theme->limits= $limits;

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
		$this->theme->logs= EventLog::get( $arguments );

		// get the page count
		$arguments['count']= 'id';
		unset( $arguments['limit'] );
		unset( $arguments['offset'] );
		$totalpages= EventLog::get( $arguments );
		$pagecount= ceil( $totalpages / $limit );

		// put the page numbers into an array
		$pages= array();
		for ( $z= 1; $z <= $pagecount; $z++ ) {
			$pages[$z]= $z;
		}
		$this->theme->pagecount= $pagecount;
		$this->theme->pages= $pages;

		$this->display( 'logs' );
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
					} else {
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
	 * Assembles the main menu for the admin area.
		*/
	protected function get_main_menu()
	{
		$mainmenus= array(
			'admin' => array(
				'caption' => _t( 'Admin' ),
				'url' => URL::get( 'admin', 'page=' ),
				'title' => _t( 'Display the dashboard' ),
				'submenu' => array(
					'options' => array( 'caption' => _t( 'Options' ), 'url' => URL::get( 'admin', 'page=options' ) ),
					'plugins' => array( 'caption' => _t( 'Plugins' ), 'url' => URL::get( 'admin', 'page=plugins' ) ),
					'themes' => array( 'caption' => _t( 'Themes' ), 'url' => URL::get( 'admin', 'page=themes' ) ),
					'users' => array( 'caption' => _t( 'Users' ), 'url' => URL::get( 'admin', 'page=users' ) ),
					'logs' => array( 'caption' => _t( 'Logs' ), 'url' => URL::get( 'admin', 'page=logs' ) ),
					'import' => array( 'caption' => _t( 'Import' ), 'url' => URL::get( 'admin', 'page=import' ) ),
				)
			),
			'publish' => array(
				'caption' => _t( 'Create' ),
				'url' => URL::get( 'admin', 'page=publish' ),
				'title' => _t( 'Create content for your site' ),
				'submenu' => array()
			),
			'manage' => array(
				'caption' => _t( 'Manage' ),
				'url' => URL::get( 'admin', 'page=content' ),
				'title' => _t( 'Manage your site content' ),
				'submenu' => array(
					'content' => array( 'caption' => _t( 'Content' ), 'url' => URL::get( 'admin', 'page=content' ) ),
					'unapproved' => array( 'caption' => _t( 'Unapproved Comments' ), 'url' => URL::get( 'admin', 'page=moderate' ) ),
					'approved' => array( 'caption' => _t( 'Approved Comments' ), 'url' => URL::get( 'admin', 'page=moderate&search_status=1' ) ),
					'spam' => array( 'caption' => _t( 'Spam' ), 'url' => URL::get( 'admin', 'page=moderate&search_status=2' ) ),
				)
			),
		);

		foreach( Post::list_active_post_types() as $type => $typeint ) {
			if ( $typeint == 0 ) {
				continue;
			}
			$mainmenus['publish']['submenu'][$type]= array( 'caption' => _t( ucwords( $type ) ), 'url' => URL::get( 'admin', 'page=publish&content_type=' . $type ) );
		}

		$mainmenus= Plugins::filter( 'adminhandler_post_loadplugins_main_menu', $mainmenus );

		$out= '';
		foreach( $mainmenus as $mainmenukey => $mainmenu ) {
			$out.= '<li class="menu-item"><a href="' . $mainmenu['url'] . '" title="' . $mainmenu['title'] . '">' . $mainmenu['caption'] . '</a>';
			$out.= '<ul class="menu-list">';
			foreach( $mainmenu['submenu'] as $menukey => $menuitem ) {
				$out.= '<li><a href="' . $menuitem['url'] . '">' . $menuitem['caption'] . '</a></li>';
			}
			$out.= '</ul>';
			$out.= '</li>';
		}
		return $out;
	}

	/**
	 * Assigns the main menu to $mainmenu into the theme.
		*/
	protected function set_admin_template_vars( $theme )
	{
		$theme->assign( 'mainmenu', $this->get_main_menu() );
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

}

?>
