<?php
/**
 * @package Habari
 *
 */

/**
	* Habari AtomHandler class
	* Produces Atom feeds and accepts Atom Publishing Protocol input
	*
	* @todo Apply system error handling
	*/
class AtomHandler extends ActionHandler
{

	public $user = NULL; // Cache the username

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
	 * @todo This entire function should be put into the User class somehow.
	 * @todo X-WSSE
	 * @param bool $force Force authorization? If so, basic HTTP_AUTH is displayed if not authed
	 * @return User The logged-in user
	 */
	function is_auth( $force = FALSE )
	{
		if ( ( $this->user == NULL ) || ( $force != FALSE ) ) {
			if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
				User::authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
			}

			$this->user = User::identify();
			if ( ( $force != FALSE ) && ( !$this->user->loggedin ) ) {
				header( 'HTTP/1.1 401 Unauthorized' );
				header( 'Status: 401 Unauthorized' );
				header( 'WWW-Authenticate: Basic realm="Habari"' );
				die();
			}
		}
		return $this->user->loggedin;
	}

	/**
	 * Creates a basic Atom-format XML structure
	 *
	 * @param string $alternate the IRI of an alternate version.
	 * @param string $self The preferred URI for retrieving Atom Feed Documents representing this Atom feed.
	 * @param string $id a permanent, universally unique identifier for an the feed.
	 *
	 * @return SimpleXMLElement The requested Atom document
	 */
	public function create_atom_wrapper( $alternate, $self, $id )
	{
		// Store handler vars since we'll be using them a lot.
		$handler_vars = Controller::get_handler_vars();

		// Retrieve the current matched rule and store its name and argument values.
		$rr = URL::get_matched_rule();
		$rr_name = $rr->name;
		$rr_args = $rr->named_arg_values;

		// Build the namespaces, plugins can alter it to override or insert their own.
		$namespaces = array( 'default' => 'http://www.w3.org/2005/Atom' );
		$namespaces = Plugins::filter( 'atom_get_collection_namespaces', $namespaces );
		$namespaces = array_map( create_function( '$value,$key', 'return ( ( $key == "default" ) ? "xmlns" : "xmlns:" . $key ) . "=\"" . $value ."\"";' ), $namespaces, array_keys($namespaces) );
		$namespaces = implode( ' ', $namespaces );

		$xml = new SimpleXMLElement( '<feed ' . $namespaces . '></feed>' );

		$feed_generator = $xml->addChild( 'generator', 'Habari' );
		$feed_generator->addAttribute( 'uri', 'http://www.habariproject.org/' );
		$feed_generator->addAttribute( 'version', Version::get_habariversion() );

		$feed_id = $xml->addChild( 'id', 'tag:' . Site::get_url('hostname') . ',' . date("Y-m-d") . ':' . $id . '/' . Options::get( 'GUID' ) );

		$feed_title = $xml->addChild( 'title', htmlspecialchars( Options::get( 'title' ) ) );

		if ( $tagline = Options::get( 'tagline' ) ) {
			$feed_subtitle = $xml->addChild( 'subtitle', htmlspecialchars( $tagline ) );
		}

		// Todo Should be the latest updated of any of the posts #657
		$feed_updated = $xml->addChild( 'updated', date( 'c', time() ) );

		$feed_link = $xml->addChild( 'link' );
		$feed_link->addAttribute( 'rel', 'alternate' );
		$feed_link->addAttribute( 'href', $alternate );

		$feed_link = $xml->addChild( 'link' );
		$feed_link->addAttribute( 'rel', 'self' );
		$feed_link->addAttribute( 'href', $self );

		Plugins::act( 'atom_create_wrapper', $xml );
		return $xml;
	}

	/**
	 * Adds pagination link rels to feeds
	 *
	 * @param SimpleXMLElement $xml The Atom feed.
	 * @param int $count The total number of items of the type in the feed.
	 * @return The altered XML feed.
	 */
	public function add_pagination_links( $xml, $count )
	{
		// Retrieve the current matched rule and store its name and argument values.
		$rr = URL::get_matched_rule();
		$rr_name = $rr->name;
		$rr_args = $rr->named_arg_values;

		$page = ( isset( $rr_args['page'] ) ) ? $rr_args['page'] : 1;
		$firstpage = 1;
		$lastpage = ceil( $count / Options::get( 'pagination' ) );

		if ( $lastpage > 1 ) {
			$nextpage = intval( $page ) + 1;
			$prevpage = intval( $page ) - 1;

			$rr_args['page'] = $firstpage;
			$feed_link = $xml->addChild( 'link' );
			$feed_link->addAttribute( 'rel', 'first' );
			$feed_link->addAttribute( 'href', URL::get( $rr_name, $rr_args ) );
			$feed_link->addAttribute( 'type', 'application/atom+xml' );
			$feed_link->addAttribute( 'title', _t('First Page') );

			if ( $prevpage > $firstpage ) {
				$rr_args['page']= $prevpage;
				$feed_link = $xml->addChild( 'link' );
				$feed_link->addAttribute( 'rel', 'previous' );
				$feed_link->addAttribute( 'href', URL::get( $rr_name, $rr_args ) );
				$feed_link->addAttribute( 'type', 'application/atom+xml' );
				$feed_link->addAttribute( 'title', _t('Previous Page') );
			}

			if ( $nextpage <= $lastpage ) {
				$rr_args['page'] = $nextpage;
				$feed_link = $xml->addChild( 'link' );
				$feed_link->addAttribute( 'rel', 'next' );
				$feed_link->addAttribute( 'href', URL::get( $rr_name, $rr_args ) );
				$feed_link->addAttribute( 'type', 'application/atom+xml' );
				$feed_link->addAttribute( 'title', _t('Next Page') );
			}

			$rr_args['page'] = $lastpage;
			$feed_link = $xml->addChild( 'link' );
			$feed_link->addAttribute( 'rel', 'last' );
			$feed_link->addAttribute( 'href', URL::get( $rr_name, $rr_args ) );
			$feed_link->addAttribute( 'type', 'application/atom+xml' );
			$feed_link->addAttribute( 'title', _t('Last Page') );
		}

		return $xml;
	}

	/**
	 * Add posts as items in the provided xml structure
	 * @param SimpleXMLElement $xml The document to add to
	 * @param array $posts An array of Posts to add to the XML
	 * @return SimpleXMLElement The resultant XML with added posts
	 */
	public function add_posts($xml, $posts)
	{
		foreach ( $posts as $post ) {
			$user = User::get_by_id( $post->user_id );
			$title = ( $this->is_auth() ) ? htmlspecialchars( $post->title ) : htmlspecialchars( $post->title_atom );
			$content = ( $this->is_auth() ) ? htmlspecialchars( $post->content ) : htmlspecialchars( $post->content_atom );

			$feed_entry = $xml->addChild( 'entry' );
			$entry_title = $feed_entry->addChild( 'title', $title );

			$entry_link = $feed_entry->addChild( 'link' );
			$entry_link->addAttribute( 'rel', 'alternate' );
			$entry_link->addAttribute( 'href', $post->permalink_atom );

			$entry_link = $feed_entry->addChild( 'link' );
			$entry_link->addAttribute( 'rel', 'edit' );
			$entry_link->addAttribute( 'href', URL::get( 'atom_entry', "slug={$post->slug}" ) );

			$entry_author = $feed_entry->addChild( 'author' );
			$author_name = $entry_author->addChild( 'name', $user->displayname );
			$author_uri = $entry_author->addChild( 'uri', Site::get_url('habari') );

			$entry_id = $feed_entry->addChild( 'id', $post->guid );

			$entry_updated = $feed_entry->addChild( 'updated', $post->updated->get('c') );
			$entry_edited = $feed_entry->addChild( 'app:edited', $post->updated->get('c'), 'http://www.w3.org/2007/app' );

				foreach ( $post->tags as $tag ) {
					$entry_category = $feed_entry->addChild( 'category' );
					$entry_category->addAttribute( 'term', $tag );
				}

			$entry_content = $feed_entry->addChild( 'content', $content );
			$entry_content->addAttribute( 'type', 'html' );
			Plugins::act( 'atom_add_post', $feed_entry, $post );
		}
		return $xml;
	}

	/**
	 * Add comments as items in the provided xml structure
	 * @param SimpleXMLElement $xml The document to add to
	 * @param array $comments An array of Comments to add to the XML
	 * @return SimpleXMLElement The resultant XML with added comments
	 */
	public function add_comments($xml, $comments)
	{
		foreach ( $comments as $comment ) {
			$content = ( $this->is_auth() ) ? htmlspecialchars( $comment->content ) : htmlspecialchars( $comment->content_atom );

			$item = $xml->addChild( 'entry' );
			$title = $item->addChild( 'title', htmlspecialchars( sprintf( _t( '%1$s on "%2$s"' ), $comment->name, $comment->post->title ) ) );

			$link = $item->addChild( 'link' );
			$link->addAttribute( 'rel', 'alternate' );
			$link->addAttribute( 'href', $comment->post->permalink . '#comment-' . $comment->id );

			$author = $item->addChild( 'author' );
			$author_name = $author->addChild( 'name', htmlspecialchars( $comment->name ) );
			$author_uri = $author->addChild( 'uri', htmlspecialchars( $comment->url ) );

			$id = $item->addChild( 'id', $comment->post->guid . '/' . $comment->id );

			$updated = $item->addChild( 'updated', $comment->date->get('c') );

			$content = $item->addChild( 'content', $content );
			$content->addAttribute( 'type', 'html' );
			Plugins::act( 'atom_add_comment', $item, $comment );
		}
		return $xml;
	}

	/**
		* Handle incoming requests for Atom entry collections
		*/
	public function act_collection()
	{
		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'GET':
			case 'HEAD':
				$this->get_collection();
				break;
			case 'POST':
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
		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'GET':
			case 'HEAD':
				$this->get_entry( $this->handler_vars['slug'] );
				break;
			case 'PUT':
				$this->put_entry( $this->handler_vars['slug'] );
				break;
			case 'DELETE':
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
		/**
			* List of APIs supported by the RSD
			* Refer to namespace for required elements/attributes.
			*/
		$apis_list = array(
			'Atom' => array(
				'preferred' => 'true',
				'apiLink' => URL::get( 'atom_feed', 'index=1' ), // This should be the XML-RPC url
				'blogID' => '1',
			),
		);

		$apis_list = Plugins::filter('rsd_api_list', $apis_list);

		$cache_xml = null;

		if ( Cache::has( 'atom:rsd:apis' ) ) {
			$cache_apis = Cache::get( 'atom:rsd:apis' );
			if ( ( $cache_apis === $apis_list ) && Cache::has( 'atom:rsd:xml' ) ) {
				$cache_xml = Cache::get( 'atom:rsd:xml' );
				$cache_xml = simplexml_load_string( $cache_xml );
			}
		}
		else {
			Cache::set( 'atom:rsd:apis', $apis_list );
		}

		if ( $cache_xml instanceOf SimpleXMLElement ) {
			$xml = $cache_xml;
		}
		else {
			$xml = new SimpleXMLElement( '<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd"></rsd>' );

			$rsd_service = $xml->addChild( 'service' );
			$service_engineName = $rsd_service->addChild( 'engineName', 'Habari' );
			$service_engineLink = $rsd_service->addChild( 'engineLink', 'http://www.habariproject.org/' );
			$service_homePageLink = $rsd_service->addChild( 'homePageLink', Site::get_url('habari') );
			$service_apis = $rsd_service->addChild( 'apis' );

			if ( !isset( $apis_list ) || ( count( $apis_list ) < 1 ) ) {
				return false;
			}

			foreach ( $apis_list as $apiName => $atts ) {
				if ( !isset( $atts['preferred'], $atts['apiLink'], $atts['blogID'] ) ) {
					continue;
				}

				$apis_api = $service_apis->addChild( 'api' );
				$apis_api->addAttribute( 'name', $apiName );
				$apis_api->addAttribute( 'preferred', $atts['preferred'] );
				$apis_api->addAttribute( 'apiLink', $atts['apiLink'] );
				$apis_api->addAttribute( 'blogID', $atts['blogID'] == '' ? '1' : $atts['blogID'] );

				if ( !isset( $atts['settings'] ) || ( count( $atts['settings'] ) < 1 ) ) {
					continue;
				}

				$api_settings = $apis_api->addChild( 'settings' );

				foreach ( $atts['settings'] as $settingName => $settingValue ) {
					switch ( $settingName ) {
						case 'docs':
						case 'notes':
							$settings_setting = $api_settings->addChild( $settingName, $settingValue );
							break;
						case 'setting':
							foreach ( $settingValue as $settingArray ) {
								$settings_setting = $api_settings->addChild( 'setting', $settingArray['value'] );
								$settings_setting->addAttribute( 'name', $settingArray['name'] );
							}
							break;
					}
				}
			}

			Cache::set( 'atom:rsd:xml', $xml->asXML() );
		}

		Plugins::act( 'rsd', $xml, $this->handler_vars );
		$xml = $xml->asXML();

		ob_clean();
		header( 'Content-Type: application/rsd+xml' );
		print $xml;
	}

	/**
		* Handle incoming requests for the introspection document
		*/
	public function act_introspection()
	{
		$cache_xml = null;

		if ( Cache::has( 'atom:introspection:xml' ) ) {
			$cache_xml = Cache::get( 'atom:introspection:xml' );
			$cache_xml = simplexml_load_string( $cache_xml );
		}

		if ( $cache_xml instanceOf SimpleXMLElement ) {
			$xml = $cache_xml;
		}
		else {
		$xml = new SimpleXMLElement( '<service xmlns="http://www.w3.org/2007/app" xmlns:atom="http://www.w3.org/2005/Atom"></service>' );

		$service_workspace = $xml->addChild( 'workspace' );

			$workspace_title = $service_workspace->addChild( 'atom:title', htmlspecialchars( Options::get( 'title' ) ), 'http://www.w3.org/2005/Atom' );

		$workspace_collection = $service_workspace->addChild( 'collection' );
		$workspace_collection->addAttribute( 'href', URL::get( 'atom_feed', 'index=1' ) );

		$collection_title = $workspace_collection->addChild( 'atom:title', 'Blog Entries', 'http://www.w3.org/2005/Atom' );
		$collection_accept = $workspace_collection->addChild( 'accept', 'application/atom+xml;type=entry' );

			Cache::set( 'atom:introspection:xml', $xml->asXML() );
		}

		Plugins::act( 'atom_introspection', $xml, $this->handler_vars );
		$xml = $xml->asXML();

		ob_clean();
		header( 'Content-Type: application/atomsvc+xml' );
		print $xml;
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
	function act_comments( $params = array() )
	{
		$this->get_comments( $params );
	}

	/**
		* Handle incoming requests for the Atom entry collection for comments on an entry
		*/
	function act_entry_comments()
	{
		if ( isset( $this->handler_vars['slug'] ) ) {
			$this->act_comments( array( 'slug' => $this->handler_vars['slug'] ) );
	}
		else {
			$this->act_comments( array( 'id' => $this->handler_vars['id'] ) );
		}
	}

	/**
		* Output an Atom collection of comments based on the supplied parameters.
		*
		* @param array $params An array of parameters passed to Comments::get() to retrieve comments
		*/
	function get_comments( $params = array() )
	{
		$comments = null;
		$comments_count = null;

		// Assign alternate link.
		$alternate = URL::get( 'atom_feed_comments' );

		// Assign self link.
		$self = '';
		if ( isset( $params['slug'] ) || isset( $params['id'] ) ) {
			if ( isset( $params['slug'] ) ) {
				$post = Post::get( array( 'slug' => $params['slug'] ) );
				$comments = $post->comments->approved;
			}
			elseif ( isset( $params['id'] ) ) {
				$post = Post::get( array( 'id' => $params['id'] ) );
				$comments = $post->comments->approved;
			}
			$comments_count = count( $comments );
			$content_type = Post::type_name( $post->content_type );
			$self = URL::get( "atom_feed_{$content_type}_comments", $post, false );
		}
		else {
			$self = URL::get( 'atom_feed_comments' );
			$params['status'] = Comment::STATUS_APPROVED;
			$comments = Comments::get( $params );
			$comments_count = Comments::count_total( Comment::status('approved') );
		}

		$id = isset( $params['slug'] ) ? $params['slug'] : 'atom_comments';

		$xml = $this->create_atom_wrapper( $alternate, $self, $id );

		$xml = $this->add_pagination_links( $xml, $comments_count );

		$xml = $this->add_comments( $xml, $comments );

		Plugins::act( 'atom_get_comments', $xml, $params, $this->handler_vars );
		$xml = $xml->asXML();

		ob_clean();
		header( 'Content-Type: application/atom+xml' );
		print $xml;
	}

	/**
		* Output the Atom entry for a specific slug
		*
		* @param string $slug The slug to get the entry for
		*/
	public function get_entry( $slug )
	{
		$params['slug']= $slug;
		$params['status'] = Post::status('published');

		if ( $post = Post::get($params) ) {
			// Assign alternate link.
			$alternate = URL::get( 'atom_entry' );
			$self = URL::get( 'atom_entry' );
			$id = isset( $params['slug'] ) ? $params['slug'] : 'atom_entry';

			$user = User::get_by_id( $post->user_id );
			$title = ( $this->is_auth() ) ? htmlspecialchars( $post->title ) : htmlspecialchars( $post->title_atom );
			$content = ( $this->is_auth() ) ? htmlspecialchars( $post->content ) : htmlspecialchars( $post->content_atom );

			$xml = $this->create_atom_wrapper( $alternate, $self, $id );

			$entry = $xml->addChild('entry');
			$entry->addAttribute( 'xmlns', 'http://www.w3.org/2005/Atom' );
			$entry_title = $entry->addChild( 'title', $title );

			$entry_author = $entry->addChild( 'author' );
			$author_name = $entry_author->addChild( 'name', $user->displayname );

			$entry_link = $xml->addChild( 'link' );
			$entry_link->addAttribute( 'rel', 'alternate' );
			$entry_link->addAttribute( 'href', $post->permalink );

			$entry_link = $entry->addChild( 'link' );
			$entry_link->addAttribute( 'rel', 'edit' );
			$entry_link->addAttribute( 'href', URL::get( 'atom_entry', "slug={$post->slug}" ) );

			$entry_id = $entry->addChild( 'id', $post->guid );
			$entry_updated = $xml->addChild( 'updated', $post->updated->get('c') );
			$entry_published = $xml->addChild( 'published', $post->pubdate->get('c') );

				foreach ( $post->tags as $tag ) {
					$entry_category = $entry->addChild( 'category' );
					$entry_category->addAttribute( 'term', $tag );
				}

			$entry_content = $entry->addChild( 'content', $content );
			$entry_content->addAttribute( 'type', 'html' );

			Plugins::act( 'atom_get_entry', $xml, $post, $this->handler_vars );
			$xml = $xml->asXML();

			ob_clean();
			header( 'Content-Type: application/atom+xml' );
			print $xml;
		}
	}

	/**
		* Updates (editing) a post entry that is sent via APP.
		*
		* @param string $slug The slug of the entry to save
		*/
	public function put_entry( $slug )
	{
		$params = array();

		$this->is_auth( TRUE );
		$bxml = file_get_contents( 'php://input' );

		$params['slug']= $slug;
		$params['status'] = Post::status('published');
		if ( $post = Post::get($params) ) {
			$xml = new SimpleXMLElement( $bxml );

			Plugins::act( 'atom_put_entry', $xml, $post, $this->handler_vars );

			if ( (string) $xml->title != '' ) {
				$post->title = $xml->title;
			}

			if ( (string) $xml->id != '' ) {
				$post->guid = $xml->id;
			}

			if ( (string) $xml->content != '' ) {
				$post->content = (string) $xml->content;
			}

			if ( isset( $_SERVER['HTTP_SLUG'] ) ) {
				$post->slug = $_SERVER['HTTP_SLUG'];
			}

			$post->status = Post::status('published');
			$post->user_id = $this->user->id;
			$post->update();
		}
	}

	/**
		* Delete a post based on the HTTP DELETE request via Atom
		*
		* @param string $slug The post slug to delete
		*/
	public function delete_entry( $slug )
	{
		$params = array();

		$this->is_auth(TRUE);

		$params['slug']= $slug;
		$params['status'] = Post::status('published');
		if ( $post = Post::get($params) ) {
			$post->delete();
		}
	}

	/**
		*	Output a post collection based on the provided parameters.
		*
		* @param array $params An array of parameters as passed to Posts::get() to retrieve posts.
		*/
	public function get_collection( $params = array() )
	{
		// Store handler vars since we'll be using them a lot.
		$handler_vars = Controller::get_handler_vars();

		// Retrieve the current matched rule and store its name and argument values.
		$rr = URL::get_matched_rule();
		$rr_name = $rr->name;
		$rr_args = $rr->named_arg_values;

		// Assign alternate links based on the matched rule.
		$alternate_rules = array(
			'atom_feed_tag' => 'display_entries_by_tag',
			'atom_feed' => 'display_home',
			'atom_entry' => 'display_entry',
			'atom_feed_entry_comments' => 'display_entry',
			'atom_feed_page_comments' => 'display_entry',
			'atom_feed_comments' => 'display_home',
			);
		$alternate_rules = Plugins::filter( 'atom_get_collection_alternate_rules', $alternate_rules );
		$alternate = URL::get( $alternate_rules[$rr_name], $handler_vars, false );

		// Assign self link based on the matched rule.
		$self = URL::get( $rr_name, $rr_args, false );

		$id = isset( $rr_args_values['tag'] ) ? $rr_args_values['tag'] : 'atom';

		$xml = $this->create_atom_wrapper( $alternate, $self, $id );

		$xml = $this->add_pagination_links( $xml, Posts::count_total( Post::status('published') ) );

		// Get posts to put in the feed
		$page = ( isset( $rr_args['page'] ) ) ? $rr_args['page'] : 1;
		if( $page > 1 ) {
			$params['page'] = $page;
		}

		if(!isset($params['content_type'])) {
			$params['content_type'] = Post::type('entry');
		}
		$params['content_type'] = Plugins::filter( 'atom_get_collection_content_type', $params['content_type'] );

		$params['status'] = Post::status('published');
		$params['orderby'] = 'updated DESC';
		$params['limit'] = Options::get( 'atom_entries' );

		$params = array_merge( $params, $rr_args );

		if ( array_key_exists( 'tag', $params ) ) {
			$params['tag_slug']=  Utils::slugify($params['tag']);
			unset( $params['tag'] );
		}

		$posts = Posts::get( $params );
		$xml = $this->add_posts($xml, $posts );

		Plugins::act( 'atom_get_collection', $xml, $params, $handler_vars );
		$xml = $xml->asXML();

		ob_clean();
		header( 'Content-Type: application/atom+xml' );
		print $xml;
	}

	/**
		* Accepts an Atom entry for insertion as a new post.
		*/
	public function post_collection()
	{
		if ( $this->is_auth( TRUE ) ) {
			$bxml = file_get_contents( 'php://input' );
		}

		$xml = new SimpleXMLElement( $bxml );

		$post = new Post();
		Plugins::act( 'atom_post_collection', $xml, $post, $this->handler_vars );

		if ( (string) $xml->title != '' ) {
			$post->title = $xml->title;
		}

		if ( (string) $xml->id != '' ) {
			$post->guid = $xml->id;
		}

		if ( (string) $xml->content != '' ) {
			$post->content = (string) $xml->content;
		}

		if ( (string) $xml->pubdate != '' ) {
			$post->pubdate = (string) $xml->pubdate;
		}

		$atom_ns = $xml->children('http://www.w3.org/2005/Atom');
		$categories = $atom_ns->category;
		if ( !empty($categories) ) {
			$terms = array();
			foreach ($categories as $category) {
				$category_attrs = $category->attributes();
				$terms[] = (string) $category_attrs['term'];
			}
			$post->tags = $terms;
		}

		if ( isset( $_SERVER['HTTP_SLUG'] ) ) {
			$post->slug = $_SERVER['HTTP_SLUG'];
		}

		// Check if it's a draft
		if ( (string) $xml->control != '' && (string) $xml->control->draft == 'yes'  ) {
			$post->status = Post::status('draft');
		}
		else {
			$post->status = Post::status('published');
		}

		$post->user_id = $this->user->id;
		$post->insert();

		header('HTTP/1.1 201 Created');
		header('Status: 201 Created');
		header('Location: ' . URL::get( 'atom_entry', array( 'slug' => $post->slug ) ) );

		$this->get_entry( $post->slug );
	}

}
?>
