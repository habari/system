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
	 * @param array Settings array from the URLParser
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
		$post = Post::get_post($slug);
		$xml = new SimpleXMLElement($this->xml_header() . '<entry xmlns="http://www.w3.org/2005/Atom"></entry>');
		$xml->addChild('title', $post->title);
		$xml->addChild('link', $post->permalink);
		$xml->addChild('id', $post->guid);
		$xml->addChild('updated', $post->updated);
		$xml->addChild('content', $post->content);
		
		header('Content-Type: application/atom+xml');
		echo $xml->asXML();
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
			$post = Post::get_post($slug);
			
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
				echo $post->permalink;
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
			if ( ( $post = Post::get_post( $slug ) ) && $post->delete() ) {
				header('HTTP/1.1 200 OK');
				header('Status: 200 OK');
				echo $post->permalink;
			}
			else {
				// This is probably not the right error code for this, but you get the idea.
				header('HTTP/1.1 404 Not Found');
				header('Status: 404 Not Found');
			}
		}
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
		global $options, $urlparser;
		
		$xml = new SimpleXMLElement($this->xml_header() . '<feed xmlns="http://www.w3.org/2005/Atom"></feed>');
	
		$xml->addChild( 'title', $options->blog_title );
		$xml->addChild( 'subtitle', $options->tag_line );
		$link = $xml->addChild( 'link' );
		$link->addAttribute( 'rel', 'alternate' ); 
		$link->addAttribute( 'type', 'text/html' ); 
		$link->addAttribute( 'href', $options->base_url );
		$link = $xml->addChild( 'link' );
		$link->addAttribute( 'rel', 'self' ); 
		$link->addAttribute( 'href', $urlparser->get_url( 'collection' ) );
		$link = $xml->addChild( 'link' );
		$link->addAttribute( 'rel', 'service.post' );
		$link->addAttribute( 'type', 'application/x.atom+xml' ); 
		$link->addAttribute( 'href', $urlparser->get_url( 'collection' ) );
		$link->addAttribute( 'title', $options->blog_title );
		$xml->addChild( 'updated', Utils::atomtime(time()) ); // TODO: This value should be cached
		$xml->addChild( 'rights', 'Copyright ' . date('Y') ); // TODO: This value should be corrected
		$generator = $xml->addChild( 'generator', 'Habari' );
		$generator->addAttribute( 'uri', 'http://code.google.com/p/habari/' );
		$generator->addAttribute( 'version', '0.1' );
		$xml->addChild( 'id', $options->base_url );
	
		foreach(Post::get_posts() as $post) {
			$entry = $xml->addChild( 'entry' );
			$entry->addChild( 'title', $post->title );
			$link = $entry->addChild( 'link' );
			$link->addAttribute( 'rel', 'alternate' );
			$link->addAttribute( 'type', 'text/html' );
			$link->addAttribute( 'href', $post->permalink );
			$link = $entry->addChild( 'link' );
			$link->addAttribute( 'rel', 'edit' );
			$link->addAttribute( 'type', 'application/x.atom+xml' );
			$link->addAttribute( 'href', $urlparser->get_url('entry', "slug={$post->slug}") );
			$author = $entry->addChild( 'author' );
			$author->addChild( 'name', 'owen' );  // TODO: Link posts to User table
			$entry->addChild( 'id', $post->guid );
			$entry->addChild( 'updated', Utils::atomtime( $post->updated ) );
			$content = $entry->addChild( 'content', $post->content );
			$content->addAttribute( 'type', 'XHTML' );
			$content->addAttribute( 'mode', 'escaped' );
			$content->addAttribute( 'base', $post->permalink, 'xml' );
			$summary = $entry->addChild( 'summary', $post->content );
		}
		
		header('Content-Type: application/x.atom+xml');
		echo $xml->asXML();		
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
				$xml = new SimpleXMLElement($bxml);
				
				$post = new Post();
				if( (string) $xml->title != '') $post->title = (string) $xml->title;
				if( (string) $xml->content != '') $post->content = (string) $xml->content;
				if( (string) $xml->pubdate != '') $post->pubdate = (string) $xml->pubdate;
				if( (string) $xml->pubdate != '') $post->pubdate = (string) $xml->pubdate;
				$post->status = 'publish';  // TODO: Use a namespaced element to set this.
				$post->insert();
			}
			catch ( Exception $e ) {
				echo $e->message;
			}
			echo $post->permalink;
		}
	}

	function introspection($settings)
	{
		global $options, $urlparser;
		
		$xml = new SimpleXMLElement($this->xml_header() . '
		<service xmlns="http://purl.org/atom/app#">
			<workspace title="' . $options->blog_title . '">
			  <collection title="Blog Entries" href="' . $urlparser->get_url( 'collection' ) . '" />
			</workspace>
		</service>
		');
		echo $xml->asXML();		
	}

}
?>
