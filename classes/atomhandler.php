<?php

/**
 * Habari AtomHandler Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */


class AtomHandler extends ActionHandler
{
	
	/**
	 * function entry
	 * Responds to Atom requests for a single entry (post)
	 * @param array Settings array from the URL
	 **/	 	 	 	
	public function entry($settings) 
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_entry($settings['slug']);
			break;
		case 'put':
			$this->put_entry($settings['slug']);
			break;
		case 'delete':
			$this->delete_entry($settings['slug']);
			break;			
		}
	}
	
	/**
	 * function collection
	 * Responds to Atom requests for a post entry collection
	 * @param array Settings array from the URL
	 **/	 	 	 	
	public function collection($settings)
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_collection();
			break;
		case 'post':
			$this->post_collection();
			break;
		}
	} 
	
	/**
	 * function tag_collection
	 * Responds to Atom requests for a tag's post entry collection
	 * @param array Settings array from the URL
	 **/	 	 	 	
	public function tag_collection($settings)
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_tag_collection( $settings['tag'] );
			break;
		}
	} 
		
	/**
	 * function comments
	 * Responds to Atom requests for a post's comment collection
	 * @param array Settings array from the URL
	 **/	 	 	 	
	public function comments($settings)
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_comments($settings['slug']);
			break;
		}
	} 
	
	/**
	 * function xml_header
	 * Produces a standard XML header
	 * @return string The header
	 **/	 	  	 	
	private function xml_header()
	{
		return '<'.'?xml version="1.0" encoding="utf-8"?'.'>';
	}
	
	/**
	 * function get_entry
	 * Responds to an Atom GET request to retrieve a single post entry
	 * @param string The post slug to look up
	 **/	 	 	 	
	private function get_entry($slug)
	{
		$params = array('slug'=>$slug);
		if ( $this->is_auth() ) {
			// Get any post, don't set status to anything.
		}
		else {
			$params['status'] = Post::STATUS_PUBLISHED;
		}
		
		if ( $post = Post::get($params) ) {
		
			$updated = Utils::atomtime( $post->updated );
			$permalink = $post->permalink;
			$title = htmlspecialchars($post->title);
			$entryurl = URL::get( 'entry', array( 'slug' => $slug) );
			
			$xmltext = $this->xml_header();
			$xmltext .= <<< entrysnippet
<entry xmlns="http://www.w3.org/2005/Atom">
	<title>{$title}</title>
	<link rel="alternate" type="text/html" href="{$permalink}" />
	<link rel="edit" type="application/atom+xml" href="{$entryurl}" />
	<id>{$post->guid}</id>
	<updated>{$updated}</updated>
	<content type="text/xhtml" xml:base="{$permalink}">{$post->content}</content>
</entry>

entrysnippet;
			header('Content-Type: application/atom+xml');
			echo $xmltext;
		}
		else {
			header('HTTP/1.1 404 Not Found');
			header('Status: 404 Not Found');
		}
	}
	
	/**
	 * function put_entry
	 * Responds to an AtomPUT request to update a single post entry
	 * @param string The post slug of the post to update
	 **/	 	 
	private function put_entry($slug)
	{
		global $db;
		
		if ( $user = $this->force_auth() ) {
			$post = Post::get( array( 'slug' => $slug, 'status' => '%') );
			
			//$bxml = file_get_contents('php://input');
		  $s = fopen("php://input", "r");
		  while($kb = fread($s, 1024)) { 
				$bxml .= $kb; 
			}
		  fclose($s);
			$xml = new SimpleXMLElement($bxml);
			
			if( (string) $xml->title != '') $post->title = (string) $xml->title;
			if( (string) $xml->content != '') $post->content = (string) $xml->content;
			if( (string) $xml->pubdate != '') $post->pubdate = (string) $xml->pubdate;

			if($post->update()) {
				header('HTTP/1.1 200 OK');
				header('Status: 200 OK');
				$this->get_entry($slug);
			}
			else {
				header('HTTP/1.1 404 Not Found');
				header('Status: 404 Not Found');
			}
		}
	}

	/**
	 * function delete_entry
	 * Responds to an Atom DELETE request to delete a single post entry
	 * @param string The post slug of the post to delete
	 **/	 	 
	private function delete_entry($slug)
	{
		global $db;

		if ( $user = $this->force_auth() ) {
			if ( $post = Post::get( array( 'slug' => $slug, 'status'=>'%') ) ) {
				if ( $post->delete() ) {
					header('HTTP/1.1 200 OK');
					header('Status: 200 OK');
					echo $post->permalink;
				}
				else {
					echo "Couldn't delete.";
				}
			}
			else {
				// This is probably not the right error code for this, but you get the idea.
				header('HTTP/1.1 404 Not Found');
				header('Status: 404 Not Found');
				Utils::debug($post, $slug);
			}
		}
	}
	
	/**
	 * function is_auth
	 * Check if a user is authenticated for Atom editing
	 * TODO: This entire funciton should be put into the User class somehow.
	 * TODO: X-WSSE	 	 
	 * @return User The logged-in user
	 **/
	function is_auth()
	{
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			User::authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ); 
		}
		
		$user = User::identify();

		return $user;
	}

	/**
	 * function force_auth
	 * Require authentication to continue.  
	 * Display basic HTTP_AUTH if not authed.
	 * TODO: This entire function should be put into the User class somehow.
	 * @return User The logged-in user	 
	 **/	   	  
	function force_auth()
	{
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			User::authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ); 
		}
		
		if ( ! $user = User::identify() ) {
			header('HTTP/1.1 401 Unauthorized');
			header('Status: 401 Unauthorized');
			header('WWW-Authenticate: Basic realm="Habari"');
			die();
		}
		return $user;
	}

	/**
	 * function get_collection
	 * Return a collection of posts in Atom format
	 **/	 	 		
	function get_collection()
	{
		$options = Options::o();
		$local['collectionurl'] = URL::get_url( 'collection', 'index=1' );
		$local['commentscollectionurl'] = URL::get_url( 'comments' );
		$local['feedupdated'] = Utils::atomtime(time()); // TODO: This value should be cached
		$local['copyright'] = date('Y'); // TODO: This value should be corrected
		
		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>{$options->title}</title>
	<subtitle>{$options->tagline}</subtitle>
	<link rel="alternate" type="text/html" href="http://{$_SERVER["HTTP_HOST"]}{$options->host_url}" />
	<link rel="service.post" type="application/atom+xml" href="{$local['collectionurl']}" title="{$options->title}" />
	<link rel="self" type="application/atom+xml" href="{$local['collectionurl']}" />
	<link rel="comments" type="application/atom+xml" href="{$local['commentscollectionurl']}" />
	<updated>{$local['feedupdated']}</updated>
	<rights>{$local['copyright']}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$options->version}">Habari</generator>
	<id>{$options->host_url}</id>

feedpreamble;

		$params = array();
		if ( $this->is_auth() ) {
			// Get all posts, don't set status to anything.
		}
		else {
			$params['status'] = Post::STATUS_PUBLISHED;
		}

		foreach(Posts::get( $params ) as $post) {
			$entryurl = URL::get_url( 'entry', "slug={$post->slug}" );
			$entryupdated = Utils::atomtime( $post->updated );
			$user = User::get( $post->user_id );
			$title = htmlspecialchars($post->title);
			$xmltext .= <<< postentry
	<entry>
		<title>{$title}</title>
		<link rel="alternate" type="text/html" href="{$post->permalink}" />
		<link rel="edit" type="application/atom+xml" href="{$entryurl}" />
		<author>
			<name>{$user->username}</name>
		</author>
		<id>{$post->guid}</id>
		<updated>{$entryupdated}</updated>
		<content type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$post->content}</div></content>
		<summary><div xmlns="http://www.w3.org/1999/xhtml">{$post->content}</div></summary>
	</entry>

postentry;
		}
		$xmltext .= '</feed>';

		header('Content-Type: application/atom+xml');
		echo $xmltext;
	}	
	
	/**
	 * function get_tag_collection
	 * Outputs a collection of post entries for a specific tag
	 **/
	function get_tag_collection( $tag )
	{
		$options = Options::o();
		$local['collectionurl'] = URL::get_url( 'tag_collection', 'index=1' );
		$local['feedupdated'] = Utils::atomtime(time()); // TODO: This value should be cached
		$local['copyright'] = date('Y'); // TODO: This value should be corrected
		$local['tagurl'] = URL::get_url( 'tag', 'tag=' . $tag, false );
		
		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>{$options->title}</title>
	<subtitle>{$options->tagline}</subtitle>
	<link rel="alternate" type="text/html" href="http://{$_SERVER["HTTP_HOST"]}{$options->host_url}{$local['tagurl']}" />
	<link rel="self" type="application/atom+xml" href="{$local['collectionurl']}" />
	<updated>{$local['feedupdated']}</updated>
	<rights>{$local['copyright']}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$options->version}">Habari</generator>
	<id>{$options->host_url}</id>

feedpreamble;

		$params = array('tag'=>$tag);
		if ( $this->is_auth() ) {
			// Get all posts, don't set status to anything.
		}
		else {
			$params['status'] = Post::STATUS_PUBLISHED;
		}

		foreach(Posts::get( $params ) as $post) {
			$entryurl = URL::get_url( 'entry', "slug={$post->slug}" );
			$entryupdated = Utils::atomtime( $post->updated );
			$user = User::get( $post->user_id );
			$title = htmlspecialchars($post->title);
			$xmltext .= <<< postentry
	<entry>
		<title>{$title}</title>
		<link rel="alternate" type="text/html" href="{$post->permalink}" />
		<link rel="edit" type="application/atom+xml" href="{$entryurl}" />
		<author>
			<name>{$user->username}</name>
		</author>
		<id>{$post->guid}</id>
		<updated>{$entryupdated}</updated>
		<content type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$post->content}</div></content>
		<summary><div xmlns="http://www.w3.org/1999/xhtml">{$post->content}</div></summary>
	</entry>

postentry;
		}
		$xmltext .= '</feed>';

		header('Content-Type: application/atom+xml');
		echo $xmltext;
	} 	 	

	/**
	 * function post_collection
	 * Responds to an Atom POST request to add a new post entry
	 **/	 
	function post_collection()
	{
		if ( $user = $this->force_auth() ) { 
		  $s = fopen("php://input", "r");
		  while($kb = fread($s, 1024)) { 
				$bxml .= $kb; 
			}
		  fclose($s);

			try {  // Exception handling!  Yay!
				$bxml = str_replace("xmlns=", "a=", $bxml);  // Rearrange namespaces
				$xml = new SimpleXMLElement($bxml);
				
				$content = $xml->xpath("//content/*[@a='http://www.w3.org/1999/xhtml']");				
				
				$post = new Post();
				if( (string) $xml->title != '') $post->title = (string) $xml->title;
				if( (string) $content[0]->asXML() != '') $post->content = (string) $content[0]->asXML();
				if( (string) $xml->pubdate != '') $post->pubdate = (string) $xml->pubdate;
				switch ( (string) $xml->draft ) {
				case 'false':				
					$post->status = 'publish';
					break;
				case 'true':
					$post->status = 'draft';
					break;
				}
				$post->insert();
			}
			catch ( Exception $e ) {
				echo $e->message;
				exit;
			}
			header('HTTP/1.1 201 Created');
			header('Status: 201 Created');
			header('Location: ' . URL::get( 'entry', array( 'slug' => $post->slug ) ) );

			$this->get_entry($post->slug);
		}
	}

	/**
	 * function get_comments
	 * Responds to an Atom GET request for post comments
	 **/
	function get_comments($slug)
	{
		$options = Options::o();
		
		$post = Post::get( array( 'slug' => $slug ) );
		
		$local['collectionurl'] = URL::get_url( 'comments' );
		$local['feedupdated'] = Utils::atomtime(time()); // TODO: This value should be cached
		$local['copyright'] = date('Y'); // TODO: This value should be corrected
		
		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>Comments for {$post->title}</title>
	<link rel="alternate" type="text/html" href="{$post->permalink}" />
	<link rel="service.post" type="application/atom+xml" href="{$local['collectionurl']}" title="{$options->title}" />
	<link rel="self" type="application/atom+xml" href="{$local['collectionurl']}" />
	<updated>{$local['feedupdated']}</updated>
	<rights>{$local['copyright']}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$options->version}">Habari</generator>
	<id>{$options->host_url}</id>

feedpreamble;

		foreach($post->comments->comments->approved as $comment) {
			$entryurl = URL::get_url( 'entry', "slug={$post->slug}" ) . "#comment-{$comment->id}";
			$entryupdated = Utils::atomtime( $comment->date );
			$xmltext .= <<< postentry
	<entry>
		<title>{$post->title}</title>
		<link rel="alternate" type="text/html" href="{$post->permalink}#comment-{$comment->id}" />
		<link rel="edit" type="application/atom+xml" href="{$entryurl}" />
		<author>
			<name>{$comment->name}</name>
		</author>
		<id>{$post->guid}:#{$comment->id}</id>
		<updated>{$entryupdated}</updated>
		<content type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$comment->content}</div></content>
	</entry>

postentry;
		}
		$xmltext .= '</feed>';

		//header('Content-Type: application/atom+xml');
		echo $xmltext;
	}
	
	function rsd($settings)
	{
		$local['homepage'] = URL::get_url( 'home' ); 
		$local['collectionurl'] = URL::get_url( 'collection', 'index=1' );

		$xmltext = <<< rsdcontent
<rsd xmlns="http://archipelago.phrasewise.com/rsd" version="1.0">
  <service xmlns="">
    <engineName>Habari</engineName>
    <engineLink>http://code.google.com/p/habari/</engineLink>
    <homePageLink>{$local['homepage']}</homePageLink>
    <apis>
      <api name="Blogger" preferred="true" apiLink="{$local['collectionurl']}" />
    </apis>
  </service>
</rsd>
rsdcontent;
		header('Content-Type: application/rsd+xml');
		echo $xmltext;
	}	 	  

	function introspection($settings)
	{
		$options = Options::o();
		
		$xmltext = $this->xml_header();
		$xmltext .= '
		<service xmlns="http://purl.org/atom/app#">
			<workspace title="' . Options::get('title') . '">
			  <collection title="Blog Entries" rel="entries" href="' . URL::get_url( 'collection', 'index=1' ) . '" />
			</workspace>
		</service>
		';
		
		header('Content-Type: application/atomserv+xml');
		echo $xmltext;		
	}

}
?>
