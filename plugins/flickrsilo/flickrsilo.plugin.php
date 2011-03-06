<?php
class flickrAPI
{
	function __construct()
	{
		$this->key = 'cd0ae46b1332aa2bd52ba3063f0db41c';
		$this->secret = '76cf747f70be9029';
		$this->endpoint = 'http://www.flickr.com/services/rest/?';
		$this->authendpoint = 'http://www.flickr.com/services/auth/?';
		$this->uploadendpoint = 'http://api.flickr.com/services/upload/?';
		$this->conntimeout = 20;
	}

	public function sign( $args )
	{
		ksort( $args );
		unset( $args['photo'] );
		$a = '';
		foreach( $args as $key => $value ){
			$a .= $key . $value;
		}
		return md5( $this->secret . $a );
	}

	public function encode( $args )
	{
		$encoded = array();
		foreach ( $args as $key => $value ){
			$encoded[] = urlencode( $key ) . '=' . urlencode( $value );
		}
		return $encoded;
	}

	function call( $method, $args = array () )
	{
		$args = array_merge( array ( 'method' => $method,
				'api_key' => $this->key ), $args );

		ksort( $args );

		$args = array_merge( $args, array ( 'api_sig' => $this->sign( $args ) ) );
		ksort( $args );

		if ( $method == 'upload' ){
			$req = curl_init();
			$args['api_key'] = $this->key;
			$photo = $args['photo'];
			$args['photo'] = '@' . $photo;
			curl_setopt( $req, CURLOPT_URL, $this->uploadendpoint );
			curl_setopt( $req, CURLOPT_TIMEOUT, 0 );
			// curl_setopt($req, CURLOPT_INFILESIZE, filesize($photo));
			// Sign and build request parameters
			curl_setopt( $req, CURLOPT_POSTFIELDS, $args );
			curl_setopt( $req, CURLOPT_CONNECTTIMEOUT, $this->conntimeout );
			curl_setopt( $req, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $req, CURLOPT_HEADER, 0 );
			curl_setopt( $req, CURLOPT_RETURNTRANSFER, 1 );
			$this->_http_body = curl_exec( $req );

			if ( curl_errno( $req ) ){
				throw new Exception( curl_error( $req ) );
			}

			curl_close( $req );
			$xml = simplexml_load_string( $this->_http_body );
			$this->xml = $xml;
			return $xml;
		}
		else{
			$url = $this->endpoint . implode( '&', $this->encode( $args ) );

			$call = new RemoteRequest( $url );
			$call->set_timeout( 5 );
			
			try {
				$result = $call->execute();
			}
			catch ( RemoteRequest_Timeout $t ) {
				Session::error( 'Currently unable to connect to Flickr.', 'flickr API' );
				return false;
			}
			catch ( Exception $e ) {
				// at the moment we're using the same error message, though this is more catastrophic
				Session::error( 'Currently unable to connect to Flickr.', 'flickr API' );
				return false;
			}
			
			$response = $call->get_response_body();
			try{
				$xml = new SimpleXMLElement( $response );
				return $xml;
			}
			catch( Exception $e ) {
				Session::error( 'Unable to process Flickr response.', 'flickr API' );
				return false;
			}
		}
	}
}

class Flickr extends flickrAPI
{
	function __construct( $params = array() )
	{
		parent::__construct( $params );
	}
	// URL building
	function getPhotoURL( $p, $size = 'm', $ext = 'jpg' )
	{
		return "http://static.flickr.com/{$p['server']}/{$p['id']}_{$p['secret']}_{$size}.{$ext}";
	}
	// authentication and approval
	public function getFrob()
	{
		$xml = $this->call( 'flickr.auth.getFrob', array() );
		return $xml->frob;
	}

	public function authLink( $frob )
	{
		$params['api_key'] = $this->key;
		$params['frob'] = $frob;
		$params['perms'] = 'write';
		$params['api_sig'] = md5( $this->secret . 'api_key' . $params['api_key'] . 'frob' . $params['frob'] . 'permswrite' );
		$link = $this->authendpoint . implode( '&', $this->encode( $params ) );
		return $link;
	}

	function getToken( $frob )
	{
		$xml = $this->call( 'flickr.auth.getToken', array( 'frob' => $frob ) );
		return $xml;
	}
	// grab the token from our db.
	function cachedToken()
	{
		$token = Options::get( 'flickr_token_' . User::identify()->id );
		return $token;
	}
	// get publicly available photos
	function getPublicPhotos( $nsid, $extras = '', $per_page = '', $page = '' )
	{
		$params = array( 'user_id' => $nsid );
		if ( $extras ){
			$params['extras'] = $extras;
		}
		if ( $per_page ){
			$params['per_page'] = $per_page;
		}
		if ( $page ){
			$params['page'] = $page;
		}

		$xml = $this->call( 'flickr.people.getPublicPhotos' , $params );
		foreach( $xml->photos->attributes() as $key => $value ){
			$pic[$key] = (string)$value;
		}
		$i = 0;
		foreach( $xml->photos->photo as $photo ){
			foreach( $photo->attributes() as $key => $value ){
				$pic['photos'][(string)$photo['id']][$key] = (string)$value;
			}
			$i++;
		}
		return $pic;
	}
	// Photosets methods
	function photosetsGetList( $nsid = '' )
	{
		$params = array();

		if ( $nsid ){
			$params['user_id'] = $nsid;
		}

		$xml = $this->call( 'flickr.photosets.getList', $params );
		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function photosetsGetInfo( $photoset_id )
	{
		$params = array( 'photoset_id' => $photoset_id );
		$xml = $this->call( 'flickr.photosets.getInfo', $params );
		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function photosetGetPrimary( $p, $size = 'm', $ext = '.jpg' )
	{
		return 'http://static.flickr.com/' . $p['server'] . '/' . $p['primary'] . '_' . $p['secret'] . '_' . $size . $ext;
	}

	function photosetsGetPhotos( $photoset_id )
	{
		$params = array( 'photoset_id' => $photoset_id );
		$xml = $this->call( 'flickr.photosets.getPhotos', $params );
		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function photosRecentlyUpdated()
	{
		$params = array();
		if ( $this->cachedToken() ){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['secret'] = $this->secret;
		$params['min_date'] = time() - 31536000;  // Within the last year
		$params['per_page'] = 10;

		$xml = $this->call( 'flickr.photos.recentlyUpdated', $params );

		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function mediaSearch( $params = array()  )
	{
		if ( $this->cachedToken() ){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['secret'] = $this->secret;
		$params['user_id'] = 'me';
		$params['sort'] = 'date-posted-desc';
		$params['per_page'] = 20;

		$xml = $this->call( 'flickr.photos.search', $params );

		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function photosSearch( $params = array()  )
	{
		if ( $this->cachedToken() ){
			$params['auth_token'] = $this->cachedToken();
		}

		$defaults = array(
			'secret' => $this->secret,
			'user_id' => 'me',
			'sort' => 'date-posted-desc',
			'per_page' => 20,
			'media' => 'photos',
			'extras' => 'original_format',
		);

		$params = array_merge( $defaults, $params );

		$xml = $this->call( 'flickr.photos.search', $params );

		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function videoSearch( $params = array()  )
	{
		if ( $this->cachedToken() ){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['secret'] = $this->secret;
		$params['user_id'] = 'me';
		$params['sort'] = 'date-posted-desc';
		$params['per_page'] = 20;
		$params['media'] = 'videos';

		$xml = $this->call( 'flickr.photos.search', $params );

		if ( Error::is_error( $xml ) ){
			throw $xml;
		}
		return $xml;
	}

	function tagsGetListUser( $userid = null )
	{
		$params = array();
		if ( isset( $userid ) ) {
			$params['user_id'] = $userid;
		}
		$xml = $this->call( 'flickr.tags.getListUser', $params );
		return $xml;
	}

	function photosGetInfo( $photo_id )
	{
		$params = array();
		if ( $this->cachedToken() ){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['photo_id'] = $photo_id;
		$params['secret'] = $this->secret;

		$xml = $this->call( 'flickr.photos.getInfo', $params );

		if ( Error::is_error( $xml ) ){
			throw $xml;
		}

		return $xml;
	}

	function upload( $photo, $title = '', $description = '', $tags = '', $perms = '', $async = 1, &$info = null )
	{
		$store = HABARI_PATH . '/' . Site::get_path( 'user' ) . '/cache';
		if ( !is_dir( $store ) ){
			mkdir( $store, 0777 );
		}
		$params = array( 'auth_token' => $this->cachedToken() );
		$url = InputFilter::parse_url( 'file://' . $photo );
		if ( isset( $url['scheme'] ) ){
			$localphoto = fopen( HABARI_PATH . '/' . $photo, 'r' );
			$store = tempnam( $store, 'G2F' );
			file_put_contents( $store, $localphoto );
			fclose( $localphoto );
			$params['photo'] = $store;
		}
		else{
			$params['photo'] = $photo;
		}

		$info = filesize( $params['photo'] );

		if ( $title ){
			$params['title'] = $title;
		}

		if ( $description ){
			$params['description'] = $description;
		}

		if ( $tags ){
			$params['tags'] = $tags;
		}

		if ( $perms ){
			if ( isset( $perms['is_public'] ) ){
				$params['is_public'] = $perms['is_public'];
			}
			if ( isset( $perms['is_friend'] ) ){
				$params['is_friend'] = $perms['is_friend'];
			}
			if ( isset( $perms['is_family'] ) ){
				$params['is_family'] = $perms['is_family'];
			}
		}

		if ( $async ){
			$params['async'] = $async;
		}
		// call the upload method.
		$xml = $this->call( 'upload', $params );

		if ( $store ){
			unlink( $store );
		}

		if ( Error::is_error( $xml ) ){
			throw $xml;
		}

		if ( $async ){
			return( (string)$xml->ticketid );
		}
		else{
			return( (string)$xml->photoid );
		}
	}

	function photosUploadCheckTickets( $tickets )
	{
		if ( is_array( $tickets ) ){
			foreach( $tickets as $key => $value ){
				if ( $key ){
					$params['tickets'] .= ' ';
				}
				$params['tickets'] .= $value;
			}
		}
		else{
			$params['tickets'] = $tickets;
		}

		$xml = $this->call( 'flickr.photos.upload.checkTickets', $params );
		if ( Error::is_error( $xml ) ){
			throw $xml;
		}

		foreach( $xml->uploader->ticket as $ticket ){
			foreach( $ticket->attributes() as $key => $value ){
				$uptick[(string)$ticket['id']][$key] = (string)$value;
			}
		}
		return $uptick;
	}

	function reflectionGetMethods()
	{
		$params = array();
		$xml = $this->call( 'flickr.reflection.getMethods', $params );
		if ( !$xml ){
			return false;
		}
		$ret = (array)$xml->methods->method;
		return $ret;
	}
}

/**
* Flickr Silo
*/

class FlickrSilo extends Plugin implements MediaSilo
{
	const SILO_NAME = 'Flickr';

	static $cache = array();

	/**
	* Initialize some internal values when plugin initializes
	*/
	public function action_init()
	{
	}

	/**
	* Return basic information about this silo
	*     name- The name of the silo, used as the root directory for media in this silo
	*	  icon- An icon to represent the silo
	*/
	public function silo_info()
	{
		if ( $this->is_auth() ) {
			return array( 'name' => self::SILO_NAME, 'icon' => URL::get_from_filesystem(__FILE__) . '/icon.png' );
		}
		else {
			return array();
		}
	}

	/**
	* Return directory contents for the silo path
	*
	* @param string $path The path to retrieve the contents of
	* @return array An array of MediaAssets describing the contents of the directory
	*/
	public function silo_dir( $path )
	{
		$flickr = new Flickr();
		$results = array();
		$size = Options::get( 'flickrsilo__flickr_size' );

		$section = strtok( $path, '/' );
		switch ( $section ) {
			case 'attrib-sa':
				$xml = $flickr->photosSearch( array( 'user_id' => '', 'license' => '4,5', 'text'=>$_SESSION['flickrsearch'] ) );
				foreach( $xml->photos->photo as $photo ) {

					$props = array();
					foreach( $photo->attributes() as $name => $value ) {
						$props[$name] = (string)$value;
					}
					$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$photo['owner']}/{$photo['id']}", $size ) );
					$results[] = new MediaAsset(
						self::SILO_NAME . '/photos/' . $photo['id'],
						false,
						$props
					);
				}
				break;

			case 'search':
				$xml = $flickr->photosSearch( array( 'text'=>$_SESSION['flickrsearch'] ) );
				foreach( $xml->photos->photo as $photo ) {

					$props = array();
					foreach( $photo->attributes() as $name => $value ) {
						$props[$name] = (string)$value;
					}
					$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}", $size ) );
					$results[] = new MediaAsset(
						self::SILO_NAME . '/photos/' . $photo['id'],
						false,
						$props
					);
				}
				break;

			case 'photos':
				$xml = $flickr->photosSearch();
				foreach( $xml->photos->photo as $photo ) {

					$props = array();
					foreach( $photo->attributes() as $name => $value ) {
						$props[$name] = (string)$value;
					}
					$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}", $size ) );
					$results[] = new MediaAsset(
						self::SILO_NAME . '/photos/' . $photo['id'],
						false,
						$props
					);
				}
				break;
			case 'videos':
				$xml = $flickr->videoSearch();
				foreach( $xml->photos->photo as $photo ) {

					$props = array();
					foreach( $photo->attributes() as $name => $value ) {
						$props[$name] = (string)$value;
					}
					$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}", $size ) );
					$props['filetype'] = 'flickrvideo';
					$results[] = new MediaAsset(
						self::SILO_NAME . '/photos/' . $photo['id'],
						false,
						$props
					);
				}
				break;
			case 'tags':
				$selected_tag = strtok('/');
				if ( $selected_tag ) {
					$xml = $flickr->photosSearch( array( 'tags'=>$selected_tag ) );
					foreach( $xml->photos->photo as $photo ) {

						$props = array();
						foreach( $photo->attributes() as $name => $value ) {
							$props[$name] = (string)$value;
						}
						$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}", $size ) );
						$results[] = new MediaAsset(
							self::SILO_NAME . '/photos/' . $photo['id'],
							false,
							$props
						);
					}
				}
				else {
					$xml = $flickr->tagsGetListUser( $_SESSION['nsid'] );
					foreach( $xml->who->tags->tag as $tag ) {
						$results[] = new MediaAsset(
							self::SILO_NAME . '/tags/' . (string)$tag,
							true,
							array( 'title' => (string)$tag )
						);
					}
				}
				break;
			case 'sets':
				$selected_set = strtok('/');
				if ( $selected_set ) {
					$xml = $flickr->photosetsGetPhotos( $selected_set );
					foreach( $xml->photoset->photo as $photo ) {

						$props = array();
						foreach( $photo->attributes() as $name => $value ) {
							$props[$name] = (string)$value;
						}
						$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}", $size ) );
						$results[] = new MediaAsset(
							self::SILO_NAME . '/photos/' . $photo['id'],
							false,
							$props
						);
					}
				}
				else {
					$xml = $flickr->photosetsGetList( $_SESSION['nsid'] );
					foreach( $xml->photosets->photoset as $set ) {
						$results[] = new MediaAsset(
							self::SILO_NAME . '/sets/' . (string)$set['id'],
							true,
							array( 'title' => (string)$set->title )
						);
					}
				}
				break;

			case '$search':
				$path = strtok( '/' );
				$dosearch = Utils::slugify( $path );
				$_SESSION['flickrsearch'] = $path;
				$section = $path;

			case '':
				if ( isset( $_SESSION['flickrsearch'] ) ) {
					$results[] = new MediaAsset(
						self::SILO_NAME . '/search',
						true,
						array( 'title' => _t( 'Search' ) )
					);
					$results[] = new MediaAsset(
						self::SILO_NAME . '/attrib-sa',
						true,
						array( 'title' => _t( 'Search CC' ) )
					);
				}
				$results[] = new MediaAsset(
					self::SILO_NAME . '/photos',
					true,
					array('title' => _t( 'Photos' ) )
				);
				$results[] = new MediaAsset(
					self::SILO_NAME . '/videos',
					true,
					array('title' => _t( 'Videos' ) )
				);
				$results[] = new MediaAsset(
					self::SILO_NAME . '/tags',
					true,
					array('title' => _t( 'Tags' ) )
				);
				$results[] = new MediaAsset(
					self::SILO_NAME . '/sets',
					true,
					array('title' => _t( 'Sets' ) )
				);
				break;
		}
		return $results;
	}
	
	/**
	 * Function that populates the element properties for use in the silo.
	 * 
	 * This is to reduce the amount of duplicate code.
	 * 
	 * @param array $photo The photo element array
	 * @param string $url The Flickr URL to link to
	 * @param string $size The size of the image to display.
	 * @return array
	 */
	private static function element_props( $photo, $url, $size ) 
	{
		$props = array();
		$props['url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}{$size}.jpg";
		$props['thumbnail_url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_m.jpg";
		$props['flickr_url'] = $url;
		$props['filetype'] = 'flickr';
		return $props;
	}

	/**
	* Get the file from the specified path
	*
	* @param string $path The path of the file to retrieve
	* @param array $qualities Qualities that specify the version of the file to retrieve.
	* @return MediaAsset The requested asset
	*/
	public function silo_get( $path, $qualities = null )
	{
		$flickr = new Flickr();
		$results = array();
		$size = Options::get( 'flickrsilo__flickr_size' );
		list($unused, $photoid) = explode( '/', $path );
		
		$xml = $flickr->photosGetInfo($photoid);
		$photo = $xml->photo;

		$props = array();
		foreach( $photo->attributes() as $name => $value ) {
			$props[$name] = (string)$value;
		}
		$props = array_merge( $props, self::element_props( $photo, "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}", $size ) );
		$result = new MediaAsset(
			self::SILO_NAME . '/photos/' . $photo['id'],
			false,
			$props
		);

		return $result;
	}

	/**
	* Get the direct URL of the file of the specified path
	*
	* @param string $path The path of the file to retrieve
	* @param array $qualities Qualities that specify the version of the file to retrieve.
	* @return string The requested url
	*/
	public function silo_url( $path, $qualities = null )
	{
		$photo = false;
		if ( preg_match( '%^photos/(.+)$%', $path, $matches ) ) {
			$id = $matches[1];
			$photo = self::$cache[$id];
		}

		$size = '';
		if ( isset( $qualities['size'] ) && $qualities['size'] == 'thumbnail' ) {
			$size = '_m';
		}
		$url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}{$size}.jpg";
		return $url;
	}

	/**
	* Create a new asset instance for the specified path
	*
	* @param string $path The path of the new file to create
	* @return MediaAsset The requested asset
	*/
	public function silo_new( $path )
	{
	}

	/**
	* Store the specified media at the specified path
	*
	* @param string $path The path of the file to retrieve
	* @param MediaAsset $ The asset to store
	*/
	public function silo_put( $path, $filedata )
	{
	}

	/**
	* Delete the file at the specified path
	*
	* @param string $path The path of the file to retrieve
	*/
	public function silo_delete( $path )
	{
	}

	/**
	* Retrieve a set of highlights from this silo
	* This would include things like recently uploaded assets, or top downloads
	*
	* @return array An array of MediaAssets to highlihgt from this silo
	*/
	public function silo_highlights()
	{
	}

	/**
	* Retrieve the permissions for the current user to access the specified path
	*
	* @param string $path The path to retrieve permissions for
	* @return array An array of permissions constants (MediaSilo::PERM_READ, MediaSilo::PERM_WRITE)
	*/
	public function silo_permissions( $path )
	{
	}

	/**
	* Return directory contents for the silo path
	*
	* @param string $path The path to retrieve the contents of
	* @return array An array of MediaAssets describing the contents of the directory
	*/
	public function silo_contents()
	{
		$flickr = new Flickr();
		$token = Options::get( 'flickr_token_' . User::identify()->id );
		$result = $flickr->call( 'flickr.auth.checkToken',
			array( 'api_key' => $flickr->key,
				'auth_token' => $token ) );
		$photos = $flickr->GetPublicPhotos( $result->auth->user['nsid'], null, 5 );
		foreach( $photos['photos'] as $photo ){
			$url = $flickr->getPhotoURL( $photo );
			echo '<img src="' . $url . '" width="150px" alt="' . ( isset( $photo['title'] ) ? $photo['title'] : _t('This photo has no title') ) . '">';
		}
	}

	/**
	* Add actions to the plugin page for this plugin
	* The authorization should probably be done per-user.
	*
	* @param array $actions An array of actions that apply to this plugin
	* @param string $plugin_id The string id of a plugin, generated by the system
	* @return array The array of actions to attach to the specified $plugin_id
	*/
	public function filter_plugin_config( $actions, $plugin_id )
	{
		$flickr_ok = $this->is_auth();

		if ( $flickr_ok ){
			$actions['deauthorize'] = _t( 'De-Authorize' );
		}
		else{
			$actions['authorize'] = _t( 'Authorize' );
		}
		$actions['configure'] = _t( 'Configure' );

		return $actions;
	}

	/**
	 * Respond to the user selecting the authorize action on the plugin page
	 *
	 */
	public function action_plugin_ui_authorize()
	{
		if ( $this->is_auth() ){
			$deauth_url = URL::get( 'admin', array( 'page' => 'plugins', 'configure' => $this->plugin_id(), 'configaction' => 'deauthorize' ) ) . '#plugin_options';
			echo '<p>' . _t( 'You have already successfully authorized Habari to access your Flickr account.') . '</p>';
			echo '<p>' . _t( 'Do you want to ') . '<a href="">' . _t( 'revoke authorization' ) . '</a>?</p>';
		}
		else{
			$flickr = new Flickr();
			$_SESSION['flickr_frob'] = '' . $flickr->getFrob();
			$auth_url = $flickr->authLink( $_SESSION['flickr_frob'] );
			$confirm_url = URL::get( 'admin', array( 'page' => 'plugins', 'configure' => $this->plugin_id(), 'configaction' => 'confirm' ) ) . '#plugin_options';
			echo '<p>' . _t( 'To use this plugin, you must ') . "<a href=\"{$auth_url}\" target=\"_blank\">" . _t( 'authorize Habari to have access to your Flickr account' ) . '</a>.';
			echo '<p>' . _t( 'When you have completed the authorization on Flickr, return here and ') . "<a href=\"$confirm_url\">" . _t( 'confirm that the authorization was successful') . '</a>.';
		}
	}
					
	/**
	 * Respond to the user selecting the confirm action
	 *
	 */
	public function action_plugin_ui_confirm()
	{

		$flickr = new Flickr();
		if ( !isset( $_SESSION['flickr_frob'] ) ){
			$auth_url = URL::get( 'admin', array( 'page' => 'plugins', 'configure' => $this->plugin_id(), 'configaction' => 'authorize' ) ) . '#plugin_options';
			echo '<p>' . _t( 'Either you have already authorized Habari to access your flickr account, or you have not yet done so.  Please ' ). '<a href="' . $auth_url . '">' . _t( 'try again' ) . '</a></p>';
		}
		else{
			$token = $flickr->getToken( $_SESSION['flickr_frob'] );
			if ( isset( $token->auth->perms ) ){
				Options::set( 'flickr_token_' . User::identify()->id, '' . $token->auth->token );
				echo '<p>' . _t( 'Your authorization was set successfully.' ) . '</p>';
			}
			else{
				echo '<p>' . _t( 'There was a problem with your authorization:' ) . '</p>';
				echo Utils::htmlspecialchars( $token->asXML() );
			}
			unset( $_SESSION['flickr_frob'] );
		}
	}
	
	/**
	 * Respond to the user selecting the deauthorize action
	 *
	 */
	public function action_plugin_ui_deauthorize()
	{
		Options::set( 'flickr_token_' . User::identify()->id );
		$reauth_url = URL::get( 'admin', array( 'page' => 'plugins', 'configure' => $this->plugin_id(), 'configaction' => 'authorize' ) ) . '#plugin_options';
		echo '<p>' . _t( 'The Flickr Silo Plugin authorization has been deleted.' ) . '<p>';
		echo '<p>' . _t( 'Do you want to ' ) . "<a href=\"{$reauth_url}\">" . _t( 're-authorize this plugin' ) . "</a>?<p>";
	}
	
	/**
	 * Respond to the user selecting the configure action
	 *
	 */
	public function action_plugin_ui_configure()
	{
		$ui = new FormUI( strtolower( get_class( $this ) ) );
		$ui->append( 'select', 'flickr_size','option:flickrsilo__flickr_size', _t( 'Default size for images in Posts:' ) );
		$ui->flickr_size->options = array( '_s' => _t( 'Square' ) . ' (75x75)', '_t' => _t( 'Thumbnail' ) . ' (100px)', '_m' => _t( 'Small' ) . ' (240px)', '' => _t( 'Medium' ) . ' (500px)', '_b' => _t( 'Large') . ' (1024px)', '_o' => _t( 'Original Size' ) );
		$ui->append('submit', 'save', _t( 'Save' ) );
		$ui->set_option('success_message', _t( 'Options saved' ) );
		$ui->out();
	}
	
	public function action_admin_footer( $theme ) 
	{
		if ( Controller::get_var( 'page' ) == 'publish' ) {
			$size = Options::get( 'flickrsilo__flickr_size' );
			switch ( $size ) {
				case '_s':
					$vsizex = 75;
					break;
				case '_t':
					$vsizex = 100;
					break;
				case '_m':
					$vsizex = 240;
					break;
				case '':
					$vsizex = 500;
					break;
				case '_b':
					$vsizex = 1024;
					break;
				case '_o':
					$vsizex = 400;
					break;
			}
			$vsizey = intval( $vsizex/4*3 );


			echo <<< FLICKR
			<script type="text/javascript">
				habari.media.output.flickr = {
					embed_photo: function(fileindex, fileobj) {
						habari.editor.insertSelection('<a href="' + fileobj.flickr_url + '"><img alt="' + fileobj.title + '" src="' + fileobj.url + '"></a>');
					}
				}
				habari.media.output.flickrvideo = {
					embed_video: function(fileindex, fileobj) {
						habari.editor.insertSelection('<object type="application/x-shockwave-flash" width="{$vsizex}" height="{$vsizey}" data="http://www.flickr.com/apps/video/stewart.swf?v=49235" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"> <param name="flashvars" value="intl_lang=en-us&amp;photo_secret=' + fileobj.secret + '&amp;photo_id=' + fileobj.id + '&amp;show_info_box=true"></param> <param name="movie" value="http://www.flickr.com/apps/video/stewart.swf?v=49235"></param> <param name="bgcolor" value="#000000"></param> <param name="allowFullScreen" value="true"></param><embed type="application/x-shockwave-flash" src="http://www.flickr.com/apps/video/stewart.swf?v=49235" bgcolor="#000000" allowfullscreen="true" flashvars="intl_lang=en-us&amp;photo_secret=' + fileobj.secret + '&amp;photo_id=' + fileobj.id + '&amp;flickr_show_info_box=true" height="{$vsizey}" width="{$vsizex}"></embed></object>');
					},
					thumbnail: function(fileindex, fileobj) {
						habari.editor.insertSelection('<a href="' + fileobj.flickr_url + '"><img alt="' + fileobj.title + '" src="' + fileobj.url + '"></a>');
					}
				}
				habari.media.preview.flickr = function(fileindex, fileobj) {
					var stats = '';
					return '<div class="mediatitle"><a href="' + fileobj.flickr_url + '" class="medialink" onclick="$(this).attr(\'target\',\'_blank\');" title="Open in new window">media</a>' + fileobj.title + '</div><img src="' + fileobj.thumbnail_url + '"><div class="mediastats"> ' + stats + '</div>';
				}
				habari.media.preview.flickrvideo = function(fileindex, fileobj) {
					var stats = '';
					return '<div class="mediatitle"><a href="' + fileobj.flickr_url + '" class="medialink" onclick="$(this).attr(\'target\',\'_blank\');"title="Open in new window" >media</a>' + fileobj.title + '</div><img src="' + fileobj.thumbnail_url + '"><div class="mediastats"> ' + stats + '</div>';
				}
			</script>
FLICKR;
		}
	}

	private function is_auth()
	{
		static $flickr_ok = null;
		if ( isset( $flickr_ok ) ){
			return $flickr_ok;
		}

		$flickr_ok = false;
		$token = Options::get( 'flickr_token_' . User::identify()->id );
		if ( $token != '' ){
			$flickr = new Flickr();
			$result = $flickr->call( 'flickr.auth.checkToken', array( 'api_key' => $flickr->key, 'auth_token' => $token ) );
			if ( isset( $result->auth->perms ) ){
				$flickr_ok = true;
				$_SESSION['nsid'] = (string)$result->auth->user['nsid'];
			}
			else{
				Options::set( 'flickr_token_' . User::identify()->id );
				unset( $_SESSION['flickr_token'] );
			}
		}
		return $flickr_ok;
	}

	/**
	 * Provide controls for the media control bar
	 *
	 * @param array $controls Incoming controls from other plugins
	 * @param MediaSilo $silo An instance of a MediaSilo
	 * @param string $path The path to get controls for
	 * @param string $panelname The name of the requested panel, if none then emptystring
	 * @return array The altered $controls array with new (or removed) controls
	 *
	 * @todo This should really use FormUI, but FormUI needs a way to submit forms via ajax
	 */
	public function filter_media_controls( $controls, $silo, $path, $panelname )
	{
		$class = __CLASS__;
		if ( $silo instanceof $class ) {
			unset( $controls['root'] );
			$search_criteria = isset( $_SESSION['flickrsearch'] ) ? htmlentities( $_SESSION['flickrsearch'] ) : '';
			$controls['search']= '<label for="flickrsearch" class="incontent">' ._t( 'Search' ) . '</label><input type="search" id="flickrsearch" placeholder="'. _t( 'Search for photos' ) .'" value="'.$search_criteria.'">
					<script type="text/javascript">
					$(\'#flickrsearch\').keypress(function(e){
						if (e.which == 13){
							habari.media.fullReload();
							habari.media.showdir(\''.FlickrSilo::SILO_NAME.'/$search/\' + $(this).val());
							return false;
						}
					});
					</script>';
		}
		return $controls;
	}

}

?>
