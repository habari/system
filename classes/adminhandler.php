<?php

/**
 * Habari AdminHandler Class
 *
 * @package Habari
 */

define('ADMIN_DIR', HABARI_PATH . '/system/admin/');

class AdminHandler extends ActionHandler
{
	private $theme= NULL;

	/**
	 * Verify that the page is being accessed by an admin, then create
	 * a theme to handle admin display.
	 */
	public function __construct()
	{
		// check that the user is logged in, and redirect to the login page, if not
		$user= User::identify();
		if ($user === FALSE) {
			Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
			exit;
		}
	}

	/**
	* function admin
	* Figures out what admin page to show, and displays it to the user.
	* Calls post_{page}() function for post requests to the specific page.	
	* @param array An associative array of this->handler_vars found in the URL by the URL
	*/
	public function act_admin()
	{
		$page= ( isset( $this->handler_vars['page']) && ! empty($this->handler_vars['page']) ) 
          ? $this->handler_vars['page'] 
          : 'dashboard';
		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				// Handle POSTs to the admin pages
				$fn= 'post_' . $page;
				if ( method_exists( $this, $fn ) ) { 
					$this->$fn();
					//call_user_func( array(&$this, $fn), $this->handler_vars);
				}
				else {
					$classname= get_class($this);
					echo sprintf( _t( "\n%s->%s() does not exist.\n" ), $classname, $fn );
					exit;
				}
				break;
			default:
				/* Create the Theme and template engine */
				$this->theme= Themes::create('admin', 'RawPHPEngine', ADMIN_DIR);
				// Handle GETs of the admin pages
				$files= glob(ADMIN_DIR . '*.php');
				$filekeys= array_map(
					create_function(
				    	'$a',
						'return basename( $a, \'.php\' );'
					),
					$files
				);
				$map= array_combine($filekeys, $files);
				// Allow plugins to modify or add to $map here,
				// since plugins will not be installed to /system/admin
				if ( empty( $page ) ) {
					$this->handler_vars['page']= 'dashboard';
				}
				if ( isset( $map[$page] ) ) {
					$this->display( $page );
				}
				else {
					// The requested console page doesn't exist
					$this->header();
					_e('Whooops!');
					$this->footer();
				}
				break;
		}
	}

	/**
	 * function post_options
	 * Handles post requests from the options admin page.
	 * Sets all of the set options.
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array 
	 **/	 	 	 	
	public function post_options()
	{
		foreach ( $_POST as $option => $value ) {
			if ( Options::get($option) != $value ) {
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
	public function post_dashboard()
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
	public function post_publish()
	{
		if ( $_POST['content'] != '' ) {
			if ( isset( $_POST['slug'] ) ) {
				$post= Post::get( array( 'slug' => $_POST['slug'], 'status' => Post::STATUS_ANY ) );
				$post->title= $_POST['title'];
				$post->tags= $_POST['tags'];
				$post->content= $_POST['content'];
				$post->status= $_POST['status'];
				$post->update();
			}
			else {
				$postdata= array(
					'title'		=>	$_POST['title'],
					'tags'		=>	$_POST['tags'],
					'content'	=>	$_POST['content'],
					'user_id'	=>	User::identify()->id,
					'pubdate'	=>	date( 'Y-m-d H:i:s' ),
					'status'	=>	$_POST['status'],
				);
				$post= Post::create( $postdata );
			}
			Utils::redirect( Utils::de_amp( URL::get( 'admin', 'page=publish&result=success&slug=' . $post->slug ) ) );
		} 
		else {
			// do something intelligent here
			_e('Danger, Will Robinson!  Danger!');
		}
	}

	/**
	 * function post_user
	 * Handles post requests from the user profile page
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array
	*/
	function post_user()
	{
		// keep track of whether we actually need to update any fields
		$update= 0;
		$results= array( 'page' => 'user', );;
		$currentuser= User::identify();
		$user= $currentuser;
		
		if ( $currentuser->id != $this->handler_vars['user_id'] ) {
			// user is editing someone else's profile
			// load that user account
			$user= User::get( $this->handler_vars['user_id'] );
			$results['user']= $user->username;
		}
		// are we deleting a user?
		if ( isset( $this->handler_vars['delete'] ) && ( 'user' == $this->handler_vars['delete'] ) ) {
			// extra safety check here
			if ( isset( $this->handler_vars['user_id'] ) && ( $currentuser->id != $this->handler_vars['user_id'] ) ) {
				$username= $user->username;
				$user->delete();
				$results['result']= 'deleted';
			}
		}
		// changing username
		if ( isset( $this->handler_vars['username'] ) && ( $user->username != $this->handler_vars['username'] ) ) {
			$user->username= $this->handler_vars['username'];
			$update= 1;
			$results['user']= $this->handler_vars['username'];
		}
		// change e-mail address
		if ( isset( $this->handler_vars['email'] ) && ( $user->email != $this->handler_vars['email'] ) ) {
			$user->email= $this->handler_vars['email'];
			$update= 1;
		}
		// see if a password change is being attempted
		if ( isset( $this->handler_vars['pass1'] ) && ( '' != $this->handler_vars['pass1'] ) ) {
			if ( isset( $this->handler_vars['pass2'] ) && ( $this->handler_vars['pass1'] == $this->handler_vars['pass2'] ) ) {
				$user->password= Utils::crypt( $this->handler_vars['pass1'] );
				if ( $user == $currentuser ) {
					// update the cookie for the current user
					$user->remember();
				}
				$update= 1;
			}
			else {
				$results['error']= 'pass';
			}
		}
		if ( $update )
		{
			$user->update();
			$results['result']= 'success';
		}
		Utils::redirect( URL::get( 'admin', $results ) );
	}

	/**
	 * public function post_users
	 * Handles post requests from the Users listing (ie: creating a new user)
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array
	**/
	public function post_users()
	{
		$user= User::identify();
		if ( ! $user )
		{
			die ('Naughty naughty!');
		}
		$error= '';
		if ( isset( $this->handler_vars['action'] ) && ( 'newuser' == $this->handler_vars['action'] ) )
		{
			// basic safety checks
			if ( ! isset( $this->handler_vars['username'] ) || '' == $this->handler_vars['username'] )
			{
				$error.= 'Please supply a user name!<br />';
			}
			if ( ! isset( $this->handler_vars['email'] ) || 
				( '' == $this->handler_vars['username'] ) ||
				( ! strstr($this->handler_vars['email'], '@') ) )
			{
				$error.= 'Please supply a valid email address!<br />';
			}
			if ( ( ! isset( $this->handler_vars['pass1'] ) ) ||
				( ! isset( $this->handler_vars['pass2'] ) ) ||
				( '' == $this->handler_vars['pass1'] ) ||
				( '' == $this->handler_vars['pass2'] ) )
			{
				$error.= 'Password mis-match!<br />';
			}
			if ( ! $error )
			{
				$user= new User ( array(
					'username' => $this->handler_vars['username'],
					'email' => $this->handler_vars['email'],
					'password' => Utils::crypt($this->handler_vars['pass1']),
					) );
				if ( $user->insert() )
				{
					$this->handler_vars['message']= 'User ' . $this->handler_vars['username'] . ' created!<br />';
					// clear out the other variables
					$this->handler_vars['username']= '';
					$this->handler_vars['email']= '';
					$this->handler_vars['pass1']= '';
					$this->handler_vars['pass2']= '';
				}
				else
				{
					$dberror= DB::get_last_error();
					$error.= $dberror[2];
				}
			}
		}
	}	

	/**
	 * function post_import
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 * @param array An array of this->handler_vars information
	 **/
	function post_import()
	{
		/**
		 * This function needs to validate the import form fields,
		 * and then forward the import information on to an import function
		 * rather than doing the import right here.
		 **/		  
	
		$db_connection= array(
		'connection_string' => $this->handler_vars['connection'],  // MySQL Connection string
		'username' => $this->handler_vars['username'],  // MySQL username
		'password' => $this->handler_vars['password'],  // MySQL password
		'prefix'	=>	$this->handler_vars['prefix'], // Prefix for your WP tables
		);
		
		// Connect to the database or fail informatively
		try {
			$wpdb= new DatabaseConnection();
			$wpdb->connect( $db_connection['connection_string'], $db_connection['username'], $db_connection['password'], $db_connection['prefix'] );
		}
		catch( Exception $e) {
			die( 'Could not connect to database using the supplied credentials.  Please check config.php for the correct values. Further information follows: ' .  $e->getMessage() );		
		}
		
		echo '<h1>Import your content into ' . Options::get('title') . '</h1>';
		
		$posts= $wpdb->get_results("
			SELECT
				post_content as content,
				ID as id,
				post_title as title,
				post_name as slug,
				post_author as user_id,
				guid as guid,
				post_date as pubdate,
				post_modified as updated,
				(post_status= 'publish') as status,
				(post_type= 'page') as content_type
			FROM {$db_connection['prefix']}posts 
			", array(), 'Post');
		
		$post_map= array();
		foreach( $posts as $post ) {
		
			$tags= $wpdb->get_column( 
				"SELECT category_nicename
				FROM {$db_connection['prefix']}post2cat
				INNER JOIN {$db_connection['prefix']}categories 
				ON ({$db_connection['prefix']}categories.cat_ID= {$db_connection['prefix']}post2cat.category_id)
				WHERE post_id= {$post->id}" 
			);
		
			$p= new Post( $post->to_array() );
			$p->slug= $post->slug;
			$p->guid= $p->guid; // Looks fishy, but actually causes the guid to be set.
			$p->tags= $tags;
			$p->insert();
			$post_map[$p->slug]= $p->id;
		}
		
		$comments= $wpdb->get_results("SELECT 
										comment_content as content,
										comment_author as name,
										comment_author_email as email,
										comment_author_url as url,
										INET_ATON(comment_author_IP) as ip,
									 	comment_approved as status,
										comment_date as date,
										comment_type as type,
										post_name as post_slug 
										FROM {$db_connection['prefix']}comments
										INNER JOIN
										{$db_connection['prefix']}posts on ({$db_connection['prefix']}posts.ID= {$db_connection['prefix']}comments.comment_post_ID)
										", 
										array(), 'Comment');
		
		foreach( $comments as $comment ) {
			switch( $comment->type ) {
				case 'pingback': $comment->type= Comment::PINGBACK; break;
				case 'trackback': $comment->type= Comment::TRACKBACK; break;
				default: $comment->type= Comment::COMMENT;
			}
			
			$carray= $comment->to_array();
			if ($carray['ip'] == '') {
				$carray['ip']= 0;
			}
			switch( $carray['status'] ) {
			case '0':
				$carray['status']= Comment::STATUS_UNAPPROVED;
				break;
			case '1':
				$carray['status']= Comment::STATUS_APPROVED;
				break;
			case 'spam':
				$carray['status']= Comment::STATUS_SPAM; 
				break;
			} 
			if ( !isset($post_map[$carray['post_slug']]) ) {
				Utils::debug($carray);
			}
			else {
			$carray['post_id']= $post_map[$carray['post_slug']];
			unset($carray['post_slug']);
				
			$c= new Comment( $carray );
			//Utils::debug( $c );
			$c->insert();
		}
		}
		echo '<p>All done, your content has been imported.</p>';
		
		// Redirect back to a URL with a notice?
	}
	
	/**
	 * function post_delete
	 * Handles the submission of the comment moderation form.
	 * @param array An array of information found in the post array
	 **/
	function post_moderate()
	{
		if ( isset( $_POST['mass_delete'] ) ) {
			Comment::mass_delete();
		}
		// XXX I don't think this is right, should be else { if {} if {} }?
		elseif ( is_array( $_POST['delete'] ) ) {
			foreach( $_POST['delete'] as $destroy ) {
				Comment::delete( $destroy );
			}
		}
		elseif ( is_array( isset( $_POST['approve'] ) ) ) {
			foreach( $_POST['approve'] as $promote ) {
				Comment::publish( $promote );
			}
		}
		Utils::redirect( URL::get( 'admin', array( 'page' => 'moderate', 'result' => 'success' ) ) );
	}

	/**
	 * Helper function which automatically assigns all handler_vars
	 * into the theme and displays a theme template
	 * 
	 * @param template_name Name of template to display (note: not the filename)
	 */
	protected function display( $template_name )
	{
		/* 
		 * Assign internal variables into the theme (and therefore into the theme's template
		 * engine.  See Theme::assign().
		 */
		foreach ( $this->handler_vars as $key => $value ) {
			$this->theme->assign( $key, $value );
		}
		
		$this->theme->display( $template_name );
	}
}

?>