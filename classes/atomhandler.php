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
			Format::apply('autop', 'post_content_atomsummary');
		}
	}

	/**
	 * function entry
	 * Responds to Atom requests for a single entry (post)
	 * @param array Settings array from the URL
	 **/
	public function act_entry()
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_entry($this->handler_vars['slug']);
			break;
		case 'put':
			$this->put_entry($this->handler_vars['slug']);
			break;
		case 'delete':
			$this->delete_entry($this->handler_vars['slug']);
			break;
		}
	}

	/**
	 * function collection
	 * Responds to Atom requests for a post entry collection
	 * @param array Settings array from the URL
	 **/
	public function act_collection()
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
	public function tag_collection()
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_tag_collection( $this->handler_vars['tag'] );
			break;
		}
	}

	/**
	 * function comments
	 * Responds to Atom requests for a post's comment collection
	 * @param array Settings array from the URL
	 **/
	public function comments()
	{
		switch(strtolower($_SERVER['REQUEST_METHOD']))
		{
		case 'get':
			$this->get_comments($this->handler_vars['slug']);
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
		ob_clean(); // The xml header must be the very first thing in the output
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
			$content = html_entity_decode($post->content, ENT_NOQUOTES, 'UTF-8'); // @todo The character encoding needs to be applied by a filter that is enabled by default

			$xmltext = $this->xml_header();
			$xmltext .= <<< entrysnippet
<entry xmlns="http://www.w3.org/2005/Atom">
	<title>{$title}</title>
	<link rel="alternate" type="text/html" href="{$permalink}" />
	<link rel="edit" type="application/atom+xml" href="{$entryurl}" />
	<id>{$post->guid}</id>
	<updated>{$updated}</updated>
	<content type="text/xhtml" xml:base="{$permalink}">{$content}</content>
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
		$collectionurl = URL::get( 'collection', 'index=' . $this->handler_vars['index'] );
		$commentscollectionurl = URL::get( 'comments' );
		$feedupdated = Utils::atomtime(time()); // TODO: This value should be cached
		$copyright = date('Y'); // TODO: This value should be corrected
		$title = Options::get('title');
		$tagline = Options::get('tagline');
		$home = URL::get('index_page');
		$version = Options::get('version');

		$totalposts = Posts::count_last();
		$relprevious = '';
		$relnext = '';
		if(intval($this->handler_vars['index']) * Options::get('paginate') < $totalposts) {
			$relnext = '<link rel="next" type="application/atom+xml" href="' . URL::get( 'collection', 'index=' . (intval($this->handler_vars['index']) + 1) ) . '" title="Next Page" />';
		}
		if(intval($this->handler_vars['index']) > 1) {
			$relprevious = '<link rel="previous" type="application/atom+xml" href="' . URL::get( 'collection', 'index=' . (intval($this->handler_vars['index']) - 1) ) . '" title="Previous Page" />';
		}
		$relfirst = '<link rel="first" type="application/atom+xml" href="' . URL::get( 'collection', 'index=1' ) . '" title="First Page" />';
		$rellast = '<link rel="last" type="application/atom+xml" href="' . URL::get( 'collection', 'index=' . ceil($totalposts / Options::get('paginate') ) ) . '" title="Last Page" />';

		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>{$title}</title>
	<subtitle>{$tagline}</subtitle>
	<link rel="alternate" type="text/html" href="{$home}" />
	<link rel="service.post" type="application/atom+xml" href="{$collectionurl}" title="{$title}" />
	<link rel="self" type="application/atom+xml" href="{$collectionurl}" />
	<link rel="comments" type="application/atom+xml" href="{$commentscollectionurl}" />
	{$relfirst}{$relprevious}{$relnext}{$rellast}
	<updated>{$feedupdated}</updated>
	<rights>{$copyright}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$version}">Habari</generator>
	<id>{$home}</id>

feedpreamble;

		$params = array();
		if ( $this->is_auth() ) {
			// Get all posts, don't set status to anything.
		}
		else {
			$params['status'] = Post::STATUS_PUBLISHED;
		}

		foreach(Posts::get( $params ) as $post) {
			$entryurl = URL::get( 'entry', "slug={$post->slug}" );
			$entryupdated = Utils::atomtime( $post->updated );
			$user = User::get_by_id( $post->user_id );
			$title = htmlspecialchars($post->title);
			$content = html_entity_decode($post->content_atom, ENT_NOQUOTES, 'UTF-8'); // @todo The character encoding needs to be applied by a filter that is enabled by default
			$summary = html_entity_decode($post->content_atomsummary, ENT_NOQUOTES, 'UTF-8'); // @todo The character encoding needs to be applied by a filter that is enabled by default
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
		<content type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$content}</div></content>
		<summary type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$summary}</div></summary>
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
		$collectionurl = URL::get_url( 'tag_collection', 'index=1' );
		$feedupdated = Utils::atomtime(time()); // TODO: This value should be cached
		$copyright = date('Y'); // TODO: This value should be corrected
		$tagurl = URL::get( 'tag', 'tag=' . $tag, false );
		$title = Options::get('title');
		$tagline = Options::get('tagline');
		$version = Options::get('version');
		$home = URL::get('index_page');

		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>{$title}</title>
	<subtitle>{$tagline}</subtitle>
	<link rel="alternate" type="text/html" href="{$tagurl}" />
	<link rel="self" type="application/atom+xml" href="{$collectionurl}" />
	<updated>{$local['feedupdated']}</updated>
	<rights>{$local['copyright']}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$version}">Habari</generator>
	<id>{$home}</id>

feedpreamble;

		$params = array('tag'=>$tag);
		if ( $this->is_auth() ) {
			// Get all posts, don't set status to anything.
		}
		else {
			$params['status'] = Post::STATUS_PUBLISHED;
		}

		foreach(Posts::get( $params ) as $post) {
			$entryurl = URL::get( 'entry', "slug={$post->slug}" );
			$entryupdated = Utils::atomtime( $post->updated );
			$user = User::get_by_id( $post->user_id );
			$title = htmlspecialchars($post->title);
			$content = html_entity_decode($post->content, ENT_NOQUOTES, 'UTF-8'); // @todo The character encoding needs to be applied by a filter that is enabled by default
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
		<content type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$content}</div></content>
		<summary type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$content}</div></summary>
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
		$post = Post::get( array( 'slug' => $slug ) );

		$collectionurl = URL::get( 'comments' );
		$feedupdated = Utils::atomtime(time()); // TODO: This value should be cached
		$copyright = date('Y'); // TODO: This value should be corrected
		$tagline = Options::get('tagline');
		$version = Options::get('version');
		$home = URL::get('index_page');

		$xmltext = $this->xml_header();
		$xmltext .= <<< feedpreamble
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>Comments for {$post->title}</title>
	<link rel="alternate" type="text/html" href="{$post->permalink}" />
	<link rel="service.post" type="application/atom+xml" href="{collectionurl}" title="{$title}" />
	<link rel="self" type="application/atom+xml" href="{$collectionurl}" />
	<updated>{$feedupdated}</updated>
	<rights>{$copyright}</rights>
	<generator uri="http://code.google.com/p/habari/" version="{$version}">Habari</generator>
	<id>{$home}</id>

feedpreamble;

		foreach($post->comments->comments->approved as $comment) {
			$entryurl = URL::get( 'entry', "slug={$post->slug}" ) . "#comment-{$comment->id}";
			$entryupdated = Utils::atomtime( $comment->date );
			$content = html_entity_decode($comment->content, ENT_NOQUOTES, 'UTF-8'); // @todo The character encoding needs to be applied by a filter that is enabled by default
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
		<content type="xhtml" xml:base="{$post->permalink}"><div xmlns="http://www.w3.org/1999/xhtml">{$content}</div></content>
	</entry>

postentry;
		}
		$xmltext .= '</feed>';

		//header('Content-Type: application/atom+xml');
		echo $xmltext;
	}

	function act_rsd()
	{
		$local['homepage'] = URL::get( 'home' );
		$local['collectionurl'] = URL::get( 'collection', 'index=1' );

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

	function act_introspection()
	{
		$xmltext = $this->xml_header();
		$xmltext .= '
		<service xmlns="http://purl.org/atom/app#">
			<workspace title="' . Options::get('title') . '">
			  <collection title="Blog Entries" rel="entries" href="' . URL::get( 'collection', 'index=1' ) . '" />
			</workspace>
		</service>
		';

		header('Content-Type: application/atomserv+xml');
		echo $xmltext;
	}

}
?>
