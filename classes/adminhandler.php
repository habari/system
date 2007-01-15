<?php

/**
 * Habari AdminHandler Class
 *
 * @package Habari
 */

define('ADMIN_DIR', HABARI_PATH . '/system/admin/');

class AdminHandler extends ActionHandler
{

	/**
	* constructor __construct
	* verify that the page is being accessed by an admin
	* @param string The action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function __construct($action, $settings)
	{
		// check that the user is logged in, and redirect
		// to the login page, if not
		if (! User::identify() )
		{
			$settings['redirect'] = URL::get($action, $settings);
			Utils::redirect( URL::get( 'login', $settings ) );
			exit;
		}
		
		parent::__construct($action, $settings);
	}

	/**
	* function header
	* display the admin header
	*/
	public function header()
	{
		include ADMIN_DIR . 'header.php';
	}

	/**
	* function footer
	* display the amdin footer
	*/
	public function footer()
	{
		include ADMIN_DIR . 'footer.php';
	}

	/**
	* function admin
	* Figures out what admin page to show, and displays it to the user.
	* Calls post_{page}() function for post requests to the specific page.	
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function admin( $settings = null)
	{
		switch( $_SERVER['REQUEST_METHOD'] ) {
		case 'POST':
			// Handle POSTs to the admin pages
			$page = ( isset( $settings['page'] ) ) ? $page = $settings['page'] : $page = 'dashboard';
			$page = ( $page == '' ) ? 'dashboard' : $page;
			$fn = 'post_' . $page;
			if ( method_exists( $this, $fn ) ) { 
				$this->$fn( $settings );
				//call_user_func( array(&$this, $fn), $settings);
			}
			else {
				$classname = get_class($this);
				echo sprintf( __( "\n%s->%s() does not exist.\n" ), $classname, $fn );
				exit;
			}
			break;
		default:
			// Handle GETs of the admin pages
			$files = glob(ADMIN_DIR . '*.php');
			$filekeys = array_map(
			  create_function(
			    '$a',
			    'return basename($a, ".php");'
			  ),
			  $files
			);
			$map = array_combine($filekeys, $files);
			// Allow plugins to modify or add to $map here,
			// since plugins will not be installed to /system/admin
			if ( ! isset( $settings['page'] ) )
			{
				$settings['page'] = 'dashboard';
			}
			if ( isset( $map[$settings['page']] ) ) {
				$this->header();
				include $map[$settings['page']];
				$this->footer();
			}
			else
			{
			  // The requested console page doesn't exist
				$this->header();
				echo "Whooops!";
				$this->footer();
			}
		}
	}

	/**
	 * function post_options
	 * Handles post requests from the options admin page.
	 * Sets all of the set options.
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array 
	 **/	 	 	 	
	public function post_options($settings)
	{
		foreach ($_POST as $option => $value)
		{
			if ( Options::get($option) != $value )
			{
				Options::set($option, $value);
			}
		}
		Utils::redirect( URL::get('admin', 'page=options&result=success') );
	}
	
	/**
	* function post_dashboard
	* Handles post requests from the dashboard.
	* @param array An associative array of content found in the url, $_POST array, and $_GET array 
	*/
	public function post_dashboard($settings)
	{
		// do something intelligent here
		_e('Nothing sends POST requests to the dashboard. Yet.');
	}

	/**
	* function post_publish
	* Handles post requests from the publish page.
	* Adds a post to the site, if the post content is not NULL.
	* @param array An associative array of content found in the url, $_POST array, and $_GET array 
	*/
	public function post_publish($settings)
	{
		if( $_POST['content'] != '' )
		{
			if( isset( $_POST['slug'] ) ) {
				$post = Post::get( array( 'slug' => $_POST['slug'], 'status' => Post::STATUS_ANY ) );
				$post->title = $_POST['title'];
				$post->tags = $_POST['tags'];
				$post->content = $_POST['content'];
				$post->status = $_POST['status'];
				$post->update();
			}
			else {
				$postdata = array(
									'title'		=>	$_POST['title'],
									'tags'		=>	$_POST['tags'],
									'content'	=>	$_POST['content'],
									'user_id'	=>	User::identify()->id,
									'pubdate'	=>	date( 'Y-m-d H:i:s' ),
									'status'	=>	$_POST['status']
								 );
				$post = Post::create( $postdata );
			}
			Utils::redirect( Utils::de_amp( URL::get( 'admin', 'page=publish&result=success&slug=' . $post->slug, true, false ) ) );
		} 
		else 
		{
			// do something intelligent here
			_e('Danger Wil Robinson!  Danger!');
		}
	}

	/**
	 * function post_user
	 * Handles post requests from the user profile page
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array
	*/
	function post_user ( $settings )
	{
		// keep track of whether we actually need to update any fields
		$update = 0;
		$results = '';
		$currentuser = User::identify();
		$user = $currentuser;
		if ( $currentuser->id != $settings['user_id'] )
		{
			// user is editing someone else's profile
			// load that user account
			$user = User::get( $settings['user_id'] );
			$results = '/' . $user->username;
		}
		// are we deleting a user?
		if ( isset( $settings['delete'] ) && ( 'user' == $settings['delete'] ) )
		{
			// extra safety check here
			if ( isset( $settings['user_id'] ) && ( $currentuser->id != $settings['user_id'] ) )
			{
				$username = $user->username;
				$user->delete();
				$results = 'deleted';
			}
		}
		if ( isset( $settings['username'] ) && ( $user->username != $settings['username'] ) )
		{
			$user->username = $settings['username'];
			$update = 1;
			$results = '/' . $settings['username'];
		}
		if ( isset( $settings['email'] ) && ( $user->email != $settings['email'] ) )
		{
			$user->email = $settings['email'];
			$update = 1;
		}
		// see if a password change is being attempted
		if ( isset( $settings['pass1'] ) && ( '' != $settings['pass1'] ) )
		{
			if ( isset( $settings['pass2'] ) && ( $settings['pass1'] == $settings['pass2'] ) )
			{
				$user->password = sha1($settings['pass1']);
				if ( $user == $currentuser )
				{
					// update the cookie for the current user
					$user->remember();
				}
				$update = 1;
			}
			else
			{
				$results = '&error=pass';
			}
		}
		if ( $update )
		{
			$user->update();
			$results .= "&results=success";
		}
		Utils::redirect( URL::get( 'admin', "page=user$results" ) );
	}

	/**
	 * public function post_users
	 * Handles post requests from the Users listing (ie: creating a new user)
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array
	**/
	public function post_users( $settings )
	{
		$user = User::identify();
		if ( ! $user )
		{
			die ('Naughty naughty!');
		}
		$error = '';
		if ( isset( $settings['action'] ) && ( 'newuser' == $settings['action'] ) )
		{
			// basic safety checks
			if ( ! isset( $settings['username'] ) || '' == $settings['username'] )
			{
				$error .= 'Please supply a user name!<br />';
			}
			if ( ! isset( $settings['email'] ) || 
				( '' == $settings['username'] ) ||
				( ! strstr($settings['email'], '@') ) )
			{
				$error .= 'Please supply a valid email address!<br />';
			}
			if ( ( ! isset( $settings['pass1'] ) ) ||
				( ! isset( $settings['pass2'] ) ) ||
				( '' == $settings['pass1'] ) ||
				( '' == $settings['pass2'] ) )
			{
				$error .= 'Password mis-match!<br />';
			}
			if ( ! $error )
			{
				$user = new User ( array(
					'username' => $settings['username'],
					'email' => $settings['email'],
					'password' => sha1($settings['pass1']),
					) );
				if ( $user->insert() )
				{
					$settings['message'] = 'User ' . $settings['username'] . ' created!<br />';
					// clear out the other variables
					$settings['username'] = '';
					$settings['email'] = '';
					$settings['pass1'] = '';
					$settings['pass2'] = '';
				}
				else
				{
					$dberror = DB::get_last_error();
					$error .= $dberror[2];
				}
			}
		}
	}	

	/**
	 * function post_import
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 * @param array An array of settings information
	 **/
	function post_import( $settings )
	{
		/**
		 * This function needs to validate the import form fields,
		 * and then forward the import information on to an import function
		 * rather than doing the import right here.
		 **/		  
	
		$db_connection = array(
		'connection_string' => $settings['connection'],  // MySQL Connection string
		'username' => $settings['username'],  // MySQL username
		'password' => $settings['password'],  // MySQL password
		'prefix'	=>	$settings['prefix'], // Prefix for your WP tables
		);
		
		// Connect to the database or fail informatively
		try {
			$wpdb = new DB( $db_connection['connection_string'], $db_connection['username'], $db_connection['password'], $db_connection['prefix'] );
		}
		catch( Exception $e) {
			die( 'Could not connect to database using the supplied credentials.  Please check config.php for the correct values. Further information follows: ' .  $e->getMessage() );		
		}
		
		echo '<h1>Import your content into ' . Options::get('title') . '</h1>';
		
		$posts = $wpdb->get_results("
			SELECT
				post_content as content,
				ID as id,
				post_title as title,
				post_name as slug,
				post_author as user_id,
				guid as guid,
				post_date as pubdate,
				post_modified as updated,
				(post_status = 'publish') as status,
				(post_type = 'page') as content_type
			FROM {$db_connection['prefix']}posts 
			", array(), 'Post');
		
		foreach( $posts as $post ) {
		
			$tags = $wpdb->get_column( 
				"SELECT category_nicename
				FROM {$db_connection['prefix']}post2cat
				INNER JOIN {$db_connection['prefix']}categories 
				ON ({$db_connection['prefix']}categories.cat_ID = {$db_connection['prefix']}post2cat.category_id)
				WHERE post_id = {$post->id}" 
			);
		
			$p = new Post( $post->to_array() );
			$p->guid = $p->guid; // Looks fishy, but actually causes the guid to be set.
			$p->tags = $tags;
			$p->insert();
		
		}
		
		$comments = $wpdb->get_results("SELECT 
										comment_content as content,
										comment_author as name,
										comment_author_email as email,
										comment_author_url as url,
										comment_author_IP as ip,
									 	comment_approved as status,
										comment_date as date,
										comment_type as type,
										post_name as post_slug 
										FROM {$db_connection['prefix']}comments
										INNER JOIN
										{$db_connection['prefix']}posts on ({$db_connection['prefix']}posts.ID = {$db_connection['prefix']}comments.comment_post_ID)
										", 
										array(), 'Comment');
		
		foreach( $comments as $comment ) {
			switch( $comment->type ) {
				case 'pingback': $comment->type = Comment::PINGBACK; break;
				case 'trackback': $comment->type = Comment::TRACKBACK; break;
				default: $comment->type = Comment::COMMENT;
			}
				
			$c = new Comment( $comment->to_array() );
			//Utils::debug( $c );
			$c->insert();
		}
		echo '<p>All done, your content has been imported.</p>';
		
		// Redirect back to a URL with a notice?
	}
	
	/**
	 * function post_moderate
	 * Handles the submission of the comment moderation form.
	 * @param array An array of information found in the post array
	 **/
	function post_moderate( $settings )
	{
		if( isset( $_POST['mass_delete'] ) ) {
			Comment::mass_delete();
		}
		elseif( is_array( $_POST['delete'] ) ) {
			foreach( $_POST['delete'] as $destroy ) {
				Comment::delete( $destroy );
			}
		} elseif( is_array( isset( $_POST['approve'] ) ) ) {
			foreach( $_POST['approve'] as $promote ) {
				Comment::publish( $promote );
			}
		}
			Utils::redirect( URL::get( 'admin', 'page=moderate&result=success' ) );
		}
		
		/**
		 * function post_spam
		 * Handles the submission of the spam moderation form.
		 * @param array An array of information found in the post array
		 **/
		function post_spam( $settings )
		{
			if( isset( $_POST['mass_spam_delete'] ) ) {
				Comment::mass_delete( STATUS_SPAM );
			}
			elseif( is_array( $_POST['spam_delete'] ) ) {
				foreach( $_POST['spam_delete'] as $destroy ) {
					Comment::delete( $destroy );
				}
			} elseif( is_array( isset( $_POST['spam_approve'] ) ) ) {
				foreach( $_POST['spam_approve'] as $promote ) {
					Comment::publish( $promote );
				}
			}
				Utils::redirect( URL::get( 'admin', 'page=spam&result=success' ) );
			}		
	}
?>
