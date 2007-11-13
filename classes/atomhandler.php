<?php
/**
	* Habari AtomHandler class
	* Produces Atom feeds and accepts Atom Publishing Protocol input	
	* 
	* @package Habari
	* @todo Apply system error handling 	
	*/
	
class AtomHandler extends ActionHandler
{
	
	public $user= NULL; // Cache the username

	/**
	 * Constructor for AtomHandler class.
	 * Set some default formatting for Atom output.
	 */
	public function __construct()
	{
		Plugins::act('init_atom');
		/**
		* The following Format::apply calls should be moved into a plugin that is
		* active by default.  They apply autop formatting to the Atom content
		* that preserves line breaks in the feed output.
		*
		* These formatters should probably not be applied in the case of APP usage,
		* since you'll want to edit the actual raw data, and not an autop'ed
		* version of that data.
		* Currently, we use the user login to determine if the Atom is being used
		* for APP instead of a feed, but maybe there should be a separate
		* feed URL?
		*/
		if ( !$this->is_auth() ) {
			Format::apply('autop', 'post_content_atom');
		}
	}
	
	/**
		* Check if a user is authenticated for Atom editing
		* 
		* @todo This entire funciton should be put into the User class somehow.
		* @todo X-WSSE
		* @param bool $force Force authorization? If so, basic HTTP_AUTH is displayed if not authed
		* @return User The logged-in user
		*/
	function is_auth( $force= FALSE )
	{
		try {
			if ( ( $this->user == NULL ) || ( $force != FALSE ) ) {			
				if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
					User::authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
				}
				
				if ( ( $force != FALSE ) && ( !$this->user = User::identify() ) ) {
					header( 'HTTP/1.1 401 Unauthorized' );
					header( 'Status: 401 Unauthorized' );
					header( 'WWW-Authenticate: Basic realm="Habari"' );
					die();
				}
			}
			
			return $this->user;
		}
		catch (Exception $e) {
   		print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		* Handle incoming requests for Atom entry collections
		*/
	public function act_collection()
	{
		switch( strtolower( $_SERVER['REQUEST_METHOD'] ) ) {
			case 'get':
				$this->get_collection();
				break;
			case 'post':
				$this->post_collection();
				break;
		}
	}

	/**
		* function act_entry
		* 'index' should be 'slug'
		*/
	public function act_entry()
	{
		switch( strtolower( $_SERVER['REQUEST_METHOD'] ) ) {
			case 'get':
				$this->get_entry( $this->handler_vars['slug'] );
				break;
			case 'put':
				$this->put_entry( $this->handler_vars['slug'] );
				break;
			case 'delete':
				$this->delete_entry( $this->handler_vars['slug'] );
				break;
		}
	}
		
	/**
		* Handle incoming requests for RSD
		* 
		* @todo Move the internal list of supported feeds into options to allow dynamic editing of capabilities 				
		*/
	public function act_rsd()
	{
		try {
			/**
				* List of APIs supported by the RSD
				* Refer to namespace for required elements/attributes.
				*/
			$apis_list= array(
				'Atom' => array(
					'preferred' => 'true',
					'apiLink' => URL::get( 'collection', 'index=1' ), // This should be the XML-RPC url
					'blogID' => '',
				),
			);
			
			$apis_list= Plugins::filter('rsd_api_list', $apis_list);
					
			$xml= new SimpleXMLElement( '<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd"></rsd>' );
			
			$rsd_service= $xml->addChild( 'service' );
			$service_engineName= $rsd_service->addChild( 'engineName', 'Habari' );
			$service_engineLink= $rsd_service->addChild( 'engineLink', 'http://www.habariproject.org/' );
			$service_homePageLink= $rsd_service->addChild( 'homePageLink', Site::get_url('habari') );
			$service_apis= $rsd_service->addChild( 'apis' );
			
			if ( !isset( $apis_list ) || ( count( $apis_list ) < 1 ) ) {
				return false;
			}
			
			foreach ( $apis_list as $apiName => $atts ) {
				if ( !isset( $atts['preferred'], $atts['apiLink'], $atts['blogID'] ) ) {
					continue;
				}
				
				$apis_api= $service_apis->addChild( 'api' );
				$apis_api->addAttribute( 'name', $apiName );
				$apis_api->addAttribute( 'preferred', $atts['preferred'] );
				$apis_api->addAttribute( 'apiLink', $atts['apiLink'] );
				$apis_api->addAttribute( 'blogID', $atts['blogID'] );
							
				if ( !isset( $atts['settings'] ) || ( count( $atts['settings'] ) < 1 ) ) {
					continue;
				}
							
				$api_settings= $apis_api->addChild( 'settings' );
	
				foreach ( $atts['settings'] as $settingName => $settingValue ) {
					switch ( $settingName ) {
						case 'docs':
						case 'notes':
							$settings_setting= $api_settings->addChild( $settingName, $settingValue );
							break;
						case 'setting':
							foreach ( $settingValue as $settingArray ) {
								$settings_setting= $api_settings->addChild( 'setting', $settingArray['value'] );
								$settings_setting->addAttribute( 'name', $settingArray['name'] );
							}
							break;
					}						
				}
			} 
			
			$xml= Plugins::filter( 'rsd', $xml, $this->handler_vars );
			$xml= $xml->asXML();
			
			ob_clean();
			header( 'Content-Type: application/rsd+xml' );
			print $xml;
		}
		catch (Exception $e) {
   		print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		* Handle incoming requests for the introspection document
		*/
	public function act_introspection()
	{
		try {
			$xml= new SimpleXMLElement( '<service xmlns="http://www.w3.org/2007/app" xmlns:atom="http://www.w3.org/2005/Atom"></service>' );
			
			$service_workspace= $xml->addChild( 'workspace' );
			
			$workspace_title= $service_workspace->addChild( 'atom:title', Options::get( 'title' ), 'http://www.w3.org/2005/Atom' );
			
			$workspace_collection= $service_workspace->addChild( 'collection' );
			$workspace_collection->addAttribute( 'href', URL::get( 'collection', 'index=1' ) );
			
			$collection_title= $workspace_collection->addChild( 'atom:title', 'Blog Entries', 'http://www.w3.org/2005/Atom' );
			$collection_accept= $workspace_collection->addChild( 'accept', 'application/atom+xml;type=entry' );
			
			$xml= Plugins::filter( 'atom_introspection', $xml, $this->handler_vars );
			$xml= $xml->asXML();
			
			ob_clean();
			header( 'Content-Type: application/atomsvc+xml' );
			print $xml;
		}
		catch (Exception $e) {
   		print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		* Handle incoming requests for the Atom entry collection for a specific tag
		*/	
	public function act_tag_collection()
	{
		$this->get_collection();
	}
	
	/**
		* Handle incoming requests for the Atom entry collection for all comments
		*/
	function act_comments( $params= array() )
	{
		$this->get_comments( $params );
	}
	
	/**
		* Handle incoming requests for the Atom entry collection for comments on an entry
		*/
	function act_entry_comments()
	{
		$this->act_comments( array( 'slug' => $this->handler_vars['slug'] ) );
	}
	
	/**
		* Output an Atom collection of comments based on the supplied parameters.
		* 
		* @param array $params An array of parameters passed to Comments::get() to retrieve comments				
		*/
	function get_comments( $params= array() )
	{
		try {
			$params['status'] = Post::status('published');

			$xml= new SimpleXMLElement( '<feed xmlns="http://www.w3.org/2005/Atom"></feed>' );
			
			$feed_title= $xml->addChild( 'title', Options::get( 'title' ) );
			
			if ( $tagline= Options::get( 'tagline' ) )
			{
				$feed_subtitle= $xml->addChild( 'subtitle', $tagline );
			}
			
			$feed_updated= $xml->addChild( 'updated', date( 'c', time() ) );
			
			$feed_link= $xml->addChild( 'link' );
			$feed_link->addAttribute( 'rel', 'alternate' );
			$feed_link->addAttribute( 'href', URL::get( 'comments' ) );
			
			$feed_link= $xml->addChild( 'link' );
			$feed_link->addAttribute( 'rel', 'self' );
			
			if ( isset( $params['slug'] ) ) {
				$feed_link->addAttribute( 'href', URL::get( 'entry_comments', 'slug=' . $params['slug'] ) );
			}
			else {
				$feed_link->addAttribute( 'href', URL::get( 'comments' ) );
			}
				
			$feed_generator= $xml->addChild( 'generator', 'Habari' );
			$feed_generator->addAttribute( 'uri', 'http://www.habariproject.org/' );
			$feed_generator->addAttribute( 'version', Options::get( 'version' ) );
			
			$feed_id= $xml->addChild( 'id', 'tag:' . Site::get_url('hostname') . ',' . date("Y-m-d") . ':' . ( ( isset( $params['slug'] ) ) ? $params['slug'] : 'atom_comments' ) . '/' . Options::get( 'GUID' ) );

			foreach ( Posts::get( $params ) as $post ) {
				
				foreach ( $post->comments->approved as $comment ) {
					$user= User::get_by_id( $post->user_id );
					$title= ( $this->is_auth() ) ? htmlspecialchars( $post->title ) : htmlspecialchars( $post->title_atom );
					$content= ( $this->is_auth() ) ? htmlspecialchars( $comment->content ) : htmlspecialchars( $comment->content_atom );
				
					$feed_entry= $xml->addChild( 'entry' );
					$entry_title= $feed_entry->addChild( 'title', 'Comment on ' . $title . ' by ' . $comment->name );
					
					$entry_link= $feed_entry->addChild( 'link' );
					$entry_link->addAttribute( 'rel', 'alternate' );
					$entry_link->addAttribute( 'href', $post->permalink . '#comment-' . $comment->id );
						
					$entry_author= $feed_entry->addChild( 'author' );
					$author_name= $entry_author->addChild( 'name', $comment->name );
					
					$entry_id= $feed_entry->addChild( 'id', $post->guid . '/' . $comment->id );
					
					$entry_updated= $feed_entry->addChild( 'updated', date( 'c', strtotime( $comment->date ) ) );
					
					$entry_content= $feed_entry->addChild( 'content', $content );
					$entry_content->addAttribute( 'type', 'html' );
				}
			}
			
			$xml= Plugins::filter( 'atom_get_comments', $xml, $params, $this->handler_vars );
			$xml= $xml->asXML();
	
			ob_clean();
			header( 'Content-Type: application/atom+xml' );
			print $xml;
		}
		catch (Exception $e) {
   		print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
			
	/**
		* Output the Atom entry for a specific slug
		* 
		* @param string $slug The slug to get the entry for 				
		*/
	public function get_entry( $slug )
	{
		try
		{
			$params['slug']= $slug;
			$params['status'] = Post::status('published');
			
			if ( $post = Post::get($params) ) {
				$user= User::get_by_id( $post->user_id );
				$title= ( $this->is_auth() ) ? htmlspecialchars( $post->title ) : htmlspecialchars( $post->title_atom );
				$content= ( $this->is_auth() ) ? htmlspecialchars( $post->content ) : htmlspecialchars( $post->content_atom );
				
				$xml= new SimpleXMLElement( '<entry xmlns="http://www.w3.org/2005/Atom"></entry>' );
				$entry_title= $xml->addChild( 'title', $title );
				
				$entry_author= $xml->addChild( 'author' );
				$author_name= $entry_author->addChild( 'name', $user->username );
				
				$entry_link= $xml->addChild( 'link' );
				$entry_link->addAttribute( 'rel', 'alternate' );
				$entry_link->addAttribute( 'href', $post->permalink );
				
				$entry_link= $xml->addChild( 'link' );
				$entry_link->addAttribute( 'rel', 'edit' );
				$entry_link->addAttribute( 'href', URL::get( 'entry', "slug={$post->slug}" ) );
				
				$entry_id= $xml->addChild( 'id', $post->guid );
				$entry_updated= $xml->addChild( 'updated', date( 'c', strtotime( $post->updated ) ) );
				$entry_published= $xml->addChild( 'published', date( 'c', strtotime( $post->pubdate ) ) );
				
				$entry_content= $xml->addChild( 'content', $content );
				$entry_content->addAttribute( 'type', 'html' );
				
				$xml= Plugins::filter( 'atom_get_entry', $xml, $slug, $this->handler_vars );
				$xml= $xml->asXML();
	
				ob_clean();
				header( 'Content-Type: application/atom+xml' );
				print $xml;
			}
		}
		catch (Exception $e) {
   		print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		* Updates (editing) a post entry that is sent via APP.
		* 
		* @param string $slug The slug of the entry to save				
		*/
	public function put_entry( $slug )
	{
		try {
			$params= array();
	
			$user= $this->is_auth( TRUE );
			$bxml= file_get_contents( 'php://input' );
			
			$params['slug']= $slug;
			$params['status'] = Post::status('published');
			if ( $post = Post::get($params) ) {
				$xml = new SimpleXMLElement( $bxml );
				
				preg_match( '/<content type=[\'|"]\w*[\'|"]>(.*)<\/content>/is', $xml->content->asXML(), $content );
				$xml->content= $content[1];
				$xml= Plugins::filter( 'atom_put_entry', $xml, $slug, $this->handler_vars );
			
				if ( (string) $xml->title != '' ) {
					$post->title= $xml->title;
				}
				
				if ( (string) $xml->id != '' ) {
					$post->guid= $xml->id;
				}
				
				if ( (string) $xml->content != '' ) {
					$post->content= (string) $xml->content;
				}
				
				if ( (string) $xml->pubdate != '' ) {
					$post->pubdate= (string) $xml->pubdate;
				}
				
				if ( isset( $_SERVER['HTTP_SLUG'] ) ) {
					$post->slug= $_SERVER['HTTP_SLUG'];
				}
			
				$post->status= Post::status('published');
				$post->user_id= $user->id;
				$post->update();
			}
		}
		catch (Exception $e) {
			print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		* Delete a post based on the HTTP DELETE request via Atom
		* 
		* @param string $slug The post slug to delete				
		*/
	public function delete_entry( $slug )
	{
		try {
			$params = array();
			
			$this->is_auth();
			
			$params['slug']= $slug;
			$params['status'] = Post::status('published');
			if ( $post= Post::get($params) ) {
				$post->delete();
			}
		}
		catch (Exception $e) {
			print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		*	Output a post collection based on the provided parameters.
		*	
		* @param array $params An array of parameters as passed to Posts::get() to retrieve posts.				
		*/
	public function get_collection( $params = array() )
	{	
		try {
			// Assign alternate links based on the matched rule
			$alternate_rules= array(
				'tag_collection' => 'display_entries_by_tag',
				'collection' => 'index_page',
				'entry' => 'display_entry',
				'entry_comments' => 'display_entry',
				'comments' => 'index_page',
				);
				
			// If RewriteRule name is not supplied, use the current matched rule.
			// Else retrieve the RewriteRule matching the supplied name.
			$rr= URL::get_matched_rule();

			// Retrieve arguments name the RewriteRule can use to build a URL.
			$rr_named_args= $rr->named_args;
			$rr_args= array_merge( $rr_named_args['required'], $rr_named_args['optional']  );
			
			// Check if the handler_vars array has the arguments we need.
			$rr_args_values= array_intersect_key( Controller::get_handler_vars(), array_flip( $rr_args ) );
						
			$namespaces= array( 'default' => 'http://www.w3.org/2005/Atom' );
			$namespaces= Plugins::filter( 'atom_get_collection_namespaces', $namespaces );
			$namespaces= array_map( create_function( '$value,$key', 'return ( ( $key == "default" ) ? "xmlns" : "xmlns:" . $key ) . "=\"" . $value ."\"";' ), $namespaces, array_keys($namespaces) );
			$namespaces= implode( ' ', $namespaces );

			$xml= new SimpleXMLElement( '<feed ' . $namespaces . '></feed>' );
			
			$feed_title= $xml->addChild( 'title', Options::get( 'title' ) );
			
			if ( $tagline= Options::get( 'tagline' ) ) {
				$feed_subtitle= $xml->addChild( 'subtitle', $tagline );
			}
			
			$feed_updated= $xml->addChild( 'updated', date( 'c', time() ) );
			
			$feed_link= $xml->addChild( 'link' );
			$feed_link->addAttribute( 'rel', 'alternate' );
			$feed_link->addAttribute( 'href', URL::get( $alternate_rules[$rr->name], $rr_args_values, false ) );
			
			$feed_link= $xml->addChild( 'link' );
			$feed_link->addAttribute( 'rel', 'self' );
			$feed_link->addAttribute( 'href', URL::get( $rr->name, $rr_args_values, false ) );
			
			$page= ( isset( $rr_args_values['page'] ) ) ? $rr_args_values['page'] : 1;
			$firstpage= 1;
			$lastpage= ceil( Posts::count_total( Post::status('published') ) / Options::get( 'pagination' ) );

			if ( $lastpage > 1 ) {
				$nextpage= intval( $page ) + 1;
				$prevpage= intval( $page ) - 1;
				
				$feed_link= $xml->addChild( 'link' );
				$feed_link->addAttribute( 'rel', 'first' );
				$feed_link->addAttribute( 'href', URL::get( $rr->name, array_merge( $rr_args_values, array( 'page' => $firstpage ) ) ) );	
				$feed_link->addAttribute( 'type', 'application/atom+xml' );
				$feed_link->addAttribute( 'title', 'First Page' );
				
				if ( $prevpage > $firstpage ) {
					$feed_link= $xml->addChild( 'link' );
					$feed_link->addAttribute( 'rel', 'previous' );
					$feed_link->addAttribute( 'href', URL::get( $rr->name, array_merge( $rr_args_values, array( 'page' => $prevpage ) ) ) );	
					$feed_link->addAttribute( 'type', 'application/atom+xml' );
					$feed_link->addAttribute( 'title', 'Previous Page' );
				}
				
				if ( $nextpage <= $lastpage ) {
					$feed_link= $xml->addChild( 'link' );
					$feed_link->addAttribute( 'rel', 'next' );
					$feed_link->addAttribute( 'href', URL::get( $rr->name, array_merge( $rr_args_values, array( 'page' => $nextpage ) ) ) );	
					$feed_link->addAttribute( 'type', 'application/atom+xml' );
					$feed_link->addAttribute( 'title', 'Next Page' );
				}
				
				$feed_link= $xml->addChild( 'link' );
				$feed_link->addAttribute( 'rel', 'last' );
				$feed_link->addAttribute( 'href', URL::get( $rr->name, array_merge( $rr_args_values, array( 'page' => $lastpage ) ) ) );	
				$feed_link->addAttribute( 'type', 'application/atom+xml' );
				$feed_link->addAttribute( 'title', 'Last Page' );
			}
			
			$feed_generator= $xml->addChild( 'generator', 'Habari' );
			$feed_generator->addAttribute( 'uri', 'http://www.habariproject.org/' );
			$feed_generator->addAttribute( 'version', Options::get( 'version' ) );
			
			$feed_id= $xml->addChild( 'id', 'tag:' . Site::get_url('hostname') . ',' . date("Y-m-d") . ':' . ( ( isset( $rr_args_values['tag'] ) ) ? $rr_args_values['tag'] : 'atom' ) . '/' . Options::get( 'GUID' ) );

			if( $page > 1 ) {
				$params['page'] = $page;
			}	
			
			if(!isset($params['content_type'])) {
				$params['content_type'] = Post::type('entry');
			}
			$params['content_type'] = Plugins::filter( 'atom_get_collection_content_type', $params['content_type'] );
			
			$params['status'] = Post::status('published');
			$params['orderby'] = 'updated DESC';
					
			foreach ( Posts::get( $params ) as $post ) {
				$user= User::get_by_id( $post->user_id );
				$title= ( $this->is_auth() ) ? htmlspecialchars( $post->title ) : htmlspecialchars( $post->title_atom );
				$content= ( $this->is_auth() ) ? htmlspecialchars( $post->content ) : htmlspecialchars( $post->content_atom );

				$feed_entry= $xml->addChild( 'entry' );
				$entry_title= $feed_entry->addChild( 'title', $title );
				
				$entry_link= $feed_entry->addChild( 'link' );
				$entry_link->addAttribute( 'rel', 'alternate' );
				$entry_link->addAttribute( 'href', $post->permalink );
	
				$entry_link= $feed_entry->addChild( 'link' );
				$entry_link->addAttribute( 'rel', 'edit' );
				$entry_link->addAttribute( 'href', URL::get( 'entry', "slug={$post->slug}" ) );
				
				$entry_author= $feed_entry->addChild( 'author' );
				$author_name= $entry_author->addChild( 'name', $user->username );
				
				$entry_id= $feed_entry->addChild( 'id', $post->guid );
				
				$entry_updated= $feed_entry->addChild( 'updated', date( 'c', strtotime( $post->updated ) ) );
				$entry_edited= $feed_entry->addChild( 'app:edited', date( 'c', strtotime( $post->updated ) ), 'http://www.w3.org/2007/app' );
				
				$entry_content= $feed_entry->addChild( 'content', $content );
				$entry_content->addAttribute( 'type', 'html' );
			}
			
			$xml= Plugins::filter( 'atom_get_collection', $xml, $params, Controller::get_handler_vars() );
			$xml= $xml->asXML();
	
			ob_clean();
			header( 'Content-Type: application/atom+xml' );
			print $xml;
		}
		catch (Exception $e) {
			print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
	/**
		* Accepts an Atom entry for insertion as a new post.
		* 
		*/					
	public function post_collection()
	{
		try {
			if ( $user = $this->is_auth( TRUE ) ) {
				$bxml= file_get_contents( 'php://input' );
			}

			$xml = new SimpleXMLElement( $bxml );
	
			preg_match( '/<content type=[\'|"]\w*[\'|"]>(.*)<\/content>/i', $xml->content->asXML(), $content );
			$xml->content= $content[1];
			$xml= Plugins::filter( 'atom_post_collection', $xml, $this->handler_vars );
	
			$post = new Post();
			
			if ( (string) $xml->title != '' ) {
				$post->title= $xml->title;
			}
			
			if ( (string) $xml->id != '' ) {
				$post->guid= $xml->id;
			}
			
			if ( (string) $xml->content != '' ) {
				$post->content= (string) $xml->content;
			}
			
			if ( (string) $xml->pubdate != '' ) {
				$post->pubdate= (string) $xml->pubdate;
			}
			
			if ( isset( $_SERVER['HTTP_SLUG'] ) ) {
				$post->slug= $_SERVER['HTTP_SLUG'];
			}
			
			$post->status= Post::status('published');
			$post->user_id= $user->id;
			$post->insert();
	
			header('HTTP/1.1 201 Created');
			header('Status: 201 Created');
			header('Location: ' . URL::get( 'entry', array( 'slug' => $post->slug ) ) );
			
			$this->get_entry( $post->slug );
		}
		catch (Exception $e) {
			print 'Caught exception: ' .  $e->getMessage() . "\n";
		}
	}
	
}
?>