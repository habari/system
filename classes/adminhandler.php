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
		if (!$user->can('admin')) {
			die(_t('Permission denied.'));
		}
		$user->remember();
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
		/* Create the Theme and template engine */
		$this->theme= Themes::create('admin', 'RawPHPEngine', ADMIN_DIR);
		$this->set_admin_template_vars($this->theme);
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
					header("HTTP/1.0 404 Not Found");
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
				$post= Post::get( array( 'slug' => $_POST['slug'], 'status' => Post::status('any') ) );
				$post->title= $_POST['title'];
				$post->slug= $_POST['newslug'];
				$post->tags= $_POST['tags'];
				$post->content= $_POST['content'];
				$post->content_type= Post::type( $_POST['content_type'] );
				$post->status= $_POST['status'];
				$post->pubdate= $_POST['pubdate'];
				if ( 1 == $_POST['comments_disabled'] ) {
					$post->info->comments_disabled= 1;
				} elseif ( 1 == $post->info->comments_disabled ) {
					unset($post->info->comments_disabled);
				}
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
					'content_type' => Post::type( $_POST['content_type'] ),
				);
				$post= Post::create( $postdata );
				if ( 1 == $_POST['comments_disabled'] ) {
					$post->info->comments_disabled= 1;
					$post->update();
				}
			}
			Utils::redirect( Utils::de_amp( URL::get( 'admin', 'page=publish&result=success&slug=' . $post->slug ) ) );
		}
		else {
			// do something intelligent here
			_e('Danger, Will Robinson!  Danger!');
		}
	}
	
	/**
	 * function post_delete_post
	 * deletes a post from the database
	**/
	function post_delete_post()
	{
		$okay= true;
		$slug= '';
		$nonce= '';
		$timestamp= '';
		$digest= '';
		// first, get the POSTed values and check them for sanity
		if ( isset($_POST['slug']) ) {
			$slug= $_POST['slug'];
		}
		if ( isset( $_POST['nonce'] ) ) {
			$nonce= $_POST['nonce'];
		}
		if ( isset( $_POST['timestamp'] ) ) {
			$timestamp= $_POST['timestamp'];
		}
		if ( isset( $_POST['PasswordDigest'] ) ) {
			$digest= $_POST['PasswordDigest'];
		}

		if ( empty($slug) || empty($nonce)
			|| empty($timestamp) || empty($digest) )
		{
			$okay= false;
		}
		// ensure the request was submitted less than five minutes ago
		if ( (time() - strtotime($timestamp) ) > 300 )
		{
			$okay= false;
		}
		$user= User::identify();
		$wsse= Utils::WSSE( $nonce, $timestamp );
		if ( $digest != $wsse['digest'] )
		{
			$okay= false;
		}
		if ( ! $okay )
		{
			Utils::redirect( URL::get('admin', 'page=content') );
		}
		$post= Post::get( array( 'slug' => $slug ) );
		$post->delete();
		Utils::redirect( URL::get('admin', 'page=content') );
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
			$user= User::get_by_id( $this->handler_vars['user_id'] );
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
					Utils::redirect( Utils::de_amp( URL::get( 'admin', 'page=users&result=success&username=' . $this->handler_vars['username'] ) ) );
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
	 * function post_plugin_toggle
	 * activates or deactviates a plugin
	 * @param string the full filename of a plugin to (de)activate
	**/
	function post_plugin_toggle()
	{
		if ( 'activate' == strtolower($this->handler_vars['submit']) )
		{
			Plugins::activate_plugin( $this->handler_vars['plugin'] );
		}
		else
		{
			Plugins::deactivate_plugin( $this->handler_vars['plugin'] );
		}
		Utils::redirect( URL::get('admin', 'page=plugins') );
	}

	/**
	 * fuction post_activate_theme
	 * Activates a theme
	**/
	function post_activate_theme()
	{
		if ( 'activate' == strtolower($this->handler_vars['submit']) )
		{
			Themes::activate_theme( $this->handler_vars['theme_name'],  $this->handler_vars['theme_dir'] );
		}
		Utils::redirect( URL::get( 'admin', 'page=themes') );
	}

	/**
	 * function post_import
	 * Handles the submission of the import form, importing data from a WordPress database.
	 * This function should probably be broken into an importer class, since it is WordPress-specific.
	 * @param array An array of this->handler_vars information
	 **/
	function post_import()
	{
		// A specific importer has to be chosen before entering here.
		if( !isset( $_REQUEST['importer'] ) ) {
			Utils::redirect( Utils::de_amp( URL::get( 'admin', 'page=import' ) ) );
			exit;
		}

		$this->display( 'import' );
	}

	/**
	 * function post_moderate
	 * Handles the submission of the comment moderation form.
	 * @param array An array of information found in the post array
	 * @todo Separate delete from "delete until purge"
	 **/
	function post_moderate()
	{
		// Setting these mass_delete options prevents any other processing.  Desired?
		if ( isset($_POST['mass_spam_delete']) ) {
			$comments= Comments::by_status(Comment::STATUS_SPAM);
			// Delete all comments that have the spam status.
			foreach($comments as $comment) {
				$comment->delete();
			}
		}
		elseif ( isset($_POST['mass_delete']) ) {
			$comments= Comments::by_status(Comment::STATUS_UNAPPROVED);
			// Delete all comments that are unapproved.
			foreach($comments as $comment) {
				$comment->delete();
			}
		}
		else {
			// Process each comment according to its setting in the form.
			$deletes= array();
			if( isset( $_POST['moderate'] ) ) {
				foreach( $_POST['moderate'] as $commentid => $status ) {
					switch ( $status ){
					case 'delete':
						// This comment was marked for deletion
						$deletes[]= $commentid;
						break;
					case 'spam':
						// This comment was marked as spam
						$comment= Comment::get($commentid);
						$comment->status = Comment::STATUS_SPAM;
						$comment->update();
						break;
					case 'approve':
						// This comment was marked for approval
						$comment= Comment::get($commentid);
						$comment->status = Comment::STATUS_APPROVED;
						$comment->update();
						break;
					case 'unapprove':
						// This comment was marked for unapproval
						$comment= Comment::get($commentid);
						$comment->status = Comment::STATUS_UNAPPROVED;
						$comment->update();
						break;
					}
				}
				if ( count($deletes) > 0 ) {
					Comments::delete_these($deletes);
				}
			}
		}

		// Get the return page, making sure it's one of the valid pages.
		if(
				isset( $_POST['returnpage'] ) &&
			 	in_array( $_POST['returnpage'], array('comments', 'moderate', 'spam'))
			) {
			$returnpage= $_POST['returnpage'];
		}
		else {
			$returnpage= 'moderate';
		}
		Utils::redirect( URL::get( 'admin', array( 'page' => $returnpage, 'result' => 'success' ) ) );
	}
	
	/**
	 * A POST handler for the admin plugins page that simply passes those options through
	 **/
	public function post_plugins()
	{
		$this->display( 'plugins' );
	}
	
	/**
	 * Loads through the existing plugins to make sure that they are syntactically valid
	 **/	 	
	public function post_loadplugins()
	{
		$failed_plugins= array();
	
		$all_plugins= Plugins::list_all();
		$active_plugins= Plugins::list_active(true);
		$check_plugins= array_diff($all_plugins, $active_plugins);
		
		$plugin_pids= array_map('md5', $check_plugins);
		$check_plugins= array_combine($plugin_pids, $check_plugins);

		// Are we checking a single plugin?
		if(isset($_POST['pid'])) {
			header("HTTP/1.0 500 Internal Server Error");
			
			include_once($check_plugins[$_POST['pid']]);
			
			header("HTTP/1.0 200 OK");
			die('Loaded ' . basename($check_plugins[$_POST['pid']]) . ' successfully.');
		}
		else {
			foreach($check_plugins as $pid=>$file) {
				$request= new RemoteRequest(URL::get('admin', array('page'=>'loadplugins')), 'POST', 300);
				$request->add_header(array('Cookie'=>$_SERVER['HTTP_COOKIE']));
				$request->set_body("pid={$pid}");
				$request->execute();
				if(!$request->executed() || preg_match('%^http/1\.\d 500%i', $request->get_response_headers())) {
					$failed_plugins[]= $file;
				}
			}
			Options::set('failed_plugins', $failed_plugins);
			Plugins::set_present();
		}
		//header("HTTP/1.0 500 Internal Server Error");
	}
	
	protected function get_main_menu()
	{
		$mainmenus = array(
			'admin' => array(
				'caption' => _t('Admin'),
				'url' => URL::get('admin', 'page='),
				'title'=> _t('Overview of your site'),
				'submenu' => array(
					'options'=>array(
						'caption'=>_t('Options'),
						'url'=>URL::get('admin', 'page=options')
					),
					'plugins'=>array(
						'caption'=>_t('Plugins'),
						'url'=>URL::get('admin', 'page=plugins')
					),
					'themes'=>array(
						'caption'=>_t('Themes'),
						'url'=>URL::get('admin', 'page=themes')
					),
					'users'=>array(
						'caption'=>_t('Users'),
						'url'=>URL::get('admin', 'page=users')
					),
					'import'=>array(
						'caption'=>_t('Import'),
						'url'=>URL::get('admin', 'page=import')
					)
				)
			),
			'publish'=>array(
				'caption'=>_t('Publish'),
				'url'=>URL::get('admin', 'page=publish'),
				'title'=>_t('Edit the content of your site'),
				'submenu'=>array(
				)
			),
			'manage'=>array(
				'caption'=>_t('Manage'),
				'url'=>URL::get('admin', 'page=options'),
				'title'=>_t('Manage your site options'),
				'submenu'=>array(
					'content'=>array(
						'caption'=>_t('Content'),
						'url'=>URL::get('admin', 'page=content')
					),
					'unapproved'=>array(
						'caption'=>_t('Unapproved Comments'),
						'url'=>URL::get('admin', 'page=moderate')
					),
					'approved'=>array(
						'caption'=>_t('Approved Comments'),
						'url'=>URL::get('admin', 'page=moderate&show=approved')
					),
					'spam'=>array(
						'caption'=>_t('Spam'),
						'url'=>URL::get('admin', 'page=moderate&show=spam')
					),
				)
			)
		);
		foreach(Post::list_post_types() as $type => $typeint) {
			if( $typeint == 0 ) {
				continue;
			}
			$mainmenus['publish']['submenu'][$type] = array(
				'caption'=>_t( ucwords($type) ),
				'url'=>URL::get('admin', 'page=publish&content_type=' . $type)
			);
		}
		
		$mainmenus = Plugins::filter('main_menu', $mainmenus);
		
		$out = '';
		foreach($mainmenus as $mainmenukey => $mainmenu) {
			$out.= "<li class=\"menu-item\"><a href=\"{$mainmenu['url']}\" title=\"{$mainmenu['title']}\">{$mainmenu['caption']}</a>";
			$out.= "<ul class=\"menu-list\">";
			foreach($mainmenu['submenu'] as $menukey => $menuitem) {
				$out.= "<li><a href=\"{$menuitem['url']}\">{$menuitem['caption']}</a></li>";				
			}
			$out.= "</ul>";
		}
		return $out;
	}

	protected function set_admin_template_vars($theme)
	{
		$theme->assign( 'mainmenu', $this->get_main_menu());
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
