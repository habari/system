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
		$post = Post::get($slug);
		
		$updated = Utils::atomtime( $post->updated );
		$permalink = 'http://' . $_SERVER["HTTP_HOST"] . $post->permalink;
		
		$xmltext = $this->xml_header();
		$xmltext .= <<< entrysnippet
<entry xmlns="http://www.w3.org/2005/Atom">
	<title>{$post->title}</title>
	<link rel="alternate" type="text/html" href="{$permalink}" />
	<id>{$post->guid}</id>
	<updated>{$updated}</updated>
	<content type="text/xhtml" xml:base="{$permalink}">{$post->content}</content>
</entry>

entrysnippet;
		header('Content-Type: application/atom+xml');
		echo $xmltext;
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
		global $url;
		
		$options = Options::o();
		$local['collectionurl'] = 'http://' . $_SERVER["HTTP_HOST"] . $url->get_url( 'collection', 'index=1' );
		$local['feedupdated'] = Utils::atomtime(time()); // TODO: This value should be cached
		$local['copyright'] = date('Y'); // TODO: This value should be corrected
		
		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>{$options->blog_title}</title>
	<subtitle>{$options->tag_line}</subtitle>
	<link rel="alternate" type="text/html" href="http://{$_SERVER["HTTP_HOST"]}{$options->base_url}" />
	<link rel="service.post" type="application/atom+xml" href="{$local['collectionurl']}" title="{$options->blog_title}" />
	<link rel="self" type="application/atom+xml" href="{$local['collectionurl']}" />
	<updated>{$local['feedupdated']}</updated>
	<rights>{$local['copyright']}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$options->version}">Habari</generator>
	<id>http://{$_SERVER["HTTP_HOST"]}{$options->base_url}</id>

feedpreamble;

		foreach(Posts::get() as $post) {
			$entryurl = $url->get_url( 'entry', "slug={$post->slug}" );
			$entryupdated = Utils::atomtime( $post->updated );
			$xmltext .= <<< postentry
	<entry>
		<title>{$post->title}</title>
		<link rel="alternate" type="text/html" href="http://{$_SERVER["HTTP_HOST"]}{$post->permalink}" />
		<link rel="edit" type="application/atom+xml" href="http://{$_SERVER["HTTP_HOST"]}{$entryurl}" />
		<author>
			<name>Owen</name><!-- TODO: Link posts to User table with id -->
		</author>
		<id>{$post->guid}</id>
		<updated>{$entryupdated}</updated>
		<content type="text/xhtml" xml:base="http://{$_SERVER["HTTP_HOST"]}{$post->permalink}">{$post->content}</content>
		<summary>{$post->content}</summary>
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
				$post->status = 'publish';  // TODO: Use a namespaced element to set this.
				$post->insert();
			}
			catch ( Exception $e ) {
				echo $e->message;
				exit;
			}
			header('HTTP/1.1 201 Created');
			header('Status: 201 Created');

			$this->get_entry($post->slug);
		}
	}

	function introspection($settings)
	{
		global $url;
		
		$options = Options::o();
		
		$xmltext = $this->xml_header();
		$xmltext .= '
		<service xmlns="http://purl.org/atom/app#">
			<workspace title="' . Options::get('title') . '">
			  <collection title="Blog Entries" rel="entries" href="http://' . $_SERVER['HTTP_HOST'] . $url->get_url( 'collection', 'index=1' ) . '" />
			</workspace>
		</service>
		';
		
		header('Content-Type: application/atom+xml');
		echo $xmltext;		
	}

}
?>
