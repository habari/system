<?php
class flickrAPI
{
	function __construct()
	{
		$this->key = 'cd0ae46b1332aa2bd52ba3063f0db41c';
		$this->secret = '76cf747f70be9029';
		$this->endpoint = 'http://flickr.com/services/rest/?';
		$this->authendpoint = 'http://flickr.com/services/auth/?';
		$this->uploadendpoint = 'http://api.flickr.com/services/upload/?';
		$this->conntimeout = 20;
	}

	public function sign($args)
	{
		ksort($args);
		unset($args['photo']);
		$a = '';
		foreach($args as $key => $value){
			$a .= $key . $value;
		}
		return md5($this->secret . $a);
	}

	public function encode($args)
	{
		$encoded = array();
		foreach ($args as $key => $value){
			$encoded[] = urlencode($key) . '=' . urlencode($value);
		}
		return $encoded;
	}

	function call($method, $args = array ())
	{
		$args = array_merge(array ('method' => $method,
				'api_key' => $this->key), $args);

		ksort($args);

		$args = array_merge($args, array ('api_sig' => $this->sign($args)));
		ksort($args);

		if($method == 'upload'){
			$req = curl_init();
			$args['api_key'] = $this->key;
			$photo = $args['photo'];
			$args['photo'] = '@' . $photo;
			curl_setopt($req, CURLOPT_URL, $this->uploadendpoint);
			curl_setopt($req, CURLOPT_TIMEOUT, 0);
			// curl_setopt($req, CURLOPT_INFILESIZE, filesize($photo));
			// Sign and build request parameters
			curl_setopt($req, CURLOPT_POSTFIELDS, $args);
			curl_setopt($req, CURLOPT_CONNECTTIMEOUT, $this->conntimeout);
			curl_setopt($req, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($req, CURLOPT_HEADER, 0);
			curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
			$this->_http_body = curl_exec($req);

			if (curl_errno($req)){
				throw new Exception(curl_error($req));
			}

			curl_close($req);
			$xml = simplexml_load_string($this->_http_body);
			$this->xml = $xml;
			return $xml;
		}
		else{
			$url = $this->endpoint . implode('&', $this->encode($args));

			$call = new RemoteRequest($url);

			$call->set_timeout(5);
			$result = $call->execute();
			if (Error::is_error($result)){
				throw $result;
			}

			$response = $call->get_response_body();
			try{
				$xml = new SimpleXMLElement($response);
				return $xml;
			}
			catch(Exception $e) {
				Session::error('Currently unable to connect to Flickr.', 'flickr API');
//				Utils::debug($url, $response);
				return false;
			}
		}
	}
}

class Flickr extends flickrAPI
{
	function __construct($params = array())
	{
		parent::__construct($params);
	}
	// URL building
	function getPhotoURL($p, $size = 'm', $ext = 'jpg')
	{
		return "http://static.flickr.com/{$p['server']}/{$p['id']}_{$p['secret']}_{$size}.{$ext}";
	}
	// authentication and approval
	public function getFrob()
	{
		$xml = $this->call('flickr.auth.getFrob', array());
		return $xml->frob;
	}

	public function authLink($frob)
	{
		$params['api_key'] = $this->key;
		$params['frob'] = $frob;
		$params['perms'] = 'write';
		$params['api_sig'] = md5($this->secret . 'api_key' . $params['api_key'] . 'frob' . $params['frob'] . 'permswrite');
		$link = $this->authendpoint . implode('&', $this->encode($params));
		return $link;
	}

	function getToken($frob)
	{
		$xml = $this->call('flickr.auth.getToken', array('frob' => $frob));
		return $xml;
	}
	// grab the token from our db.
	function cachedToken()
	{
		$token = Options::get('flickr_token_' . User::identify()->id);
		return $token;
	}
	// get publicly available photos
	function getPublicPhotos($nsid, $extras = '', $per_page = '', $page = '')
	{
		$params = array('user_id' => $nsid);
		if($extras){
			$params['extras'] = $extras;
		}
		if($per_page){
			$params['per_page'] = $per_page;
		}
		if($page){
			$params['page'] = $page;
		}

		$xml = $this->call('flickr.people.getPublicPhotos' , $params);
		foreach($xml->photos->attributes() as $key => $value){
			$pic[$key] = (string)$value;
		}
		$i = 0;
		foreach($xml->photos->photo as $photo){
			foreach($photo->attributes() as $key => $value){
				$pic['photos'][(string)$photo['id']][$key] = (string)$value;
			}
			$i++;
		}
		return $pic;
	}
	// Photosets methods
	function photosetsGetList($nsid = '')
	{
		$params = array();

		if($nsid){
			$params['user_id'] = $nsid;
		}

		$xml = $this->call('flickr.photosets.getList', $params);
		if (Error::is_error($xml)){
			throw $xml;
		}
		return $xml;
	}

	function photosetsGetInfo($photoset_id)
	{
		$params = array('photoset_id' => $photoset_id);
		$xml = $this->call('flickr.photosets.getInfo', $params);
		if (Error::is_error($xml)){
			throw $xml;
		}
		return $xml;
	}

	function photosetGetPrimary($p, $size = 'm', $ext = '.jpg')
	{
		return 'http://static.flickr.com/' . $p['server'] . '/' . $p['primary'] . '_' . $p['secret'] . '_' . $size . $ext;
	}

	function photosetsGetPhotos($photoset_id)
	{
		$params = array('photoset_id' => $photoset_id);
		$xml = $this->call('flickr.photosets.getPhotos', $params);
		if (Error::is_error($xml)){
			throw $xml;
		}
		return $xml;
	}

	function photosRecentlyUpdated()
	{
		$params = array();
		if($this->cachedToken()){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['secret'] = $this->secret;
		$params['min_date'] = time() - 31536000;  // Within the last year
		$params['per_page'] = 10;

		$xml = $this->call('flickr.photos.recentlyUpdated', $params);

		if (Error::is_error($xml)){
			throw $xml;
		}
		return $xml;
	}

	function photosSearch( $params = array()  )
	{
		if($this->cachedToken()){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['secret'] = $this->secret;
		$params['user_id'] = 'me';
		$params['sort'] = 'date-posted-desc';
		$params['per_page'] = 20;

		$xml = $this->call('flickr.photos.search', $params);

		if (Error::is_error($xml)){
			throw $xml;
		}
		return $xml;
	}

	function tagsGetListUser($userid = null)
	{
		$params = array();
		if(isset($userid)) {
			$params['user_id'] = $userid;
		}
		$xml = $this->call('flickr.tags.getListUser', $params);
		return $xml;
	}

	function photosGetInfo($photo_id)
	{
		$params = array();
		if($this->cachedToken()){
			$params['auth_token'] = $this->cachedToken();
		}

		$params['photo_id'] = $photo_id;
		$params['secret'] = $this->secret;

		$xml = $this->call('flickr.photos.getInfo', $params);

		if (Error::is_error($xml)){
			throw $xml;
		}

		foreach($xml->photo->attributes() as $key => $value){
			$result[(string)$key] = (string)$value;
		}

		foreach($xml->photo->children() as $key => $value){
			foreach($value->attributes() as $kk => $vv) $result[(string)$key][(string)$kk] = (string)$vv;
			$id = -1;
			foreach($value->children() as $kk => $vv){
				$typed = false;
				if(isset($vv['id'])){
					$id = (string)$vv['id'];
				}elseif(isset($vv['type'])){
					$id = (string)$vv['type'];
					$typed = true;
				}else $id++;
				foreach($vv->attributes() as $kkk => $vvv){
					$ret[(string)$key][$id][(string)$kkk] = (string)$vvv;
				}
				if($typed){
					$ret[(string)$key][$id] = (string)$vv;
				}
				else{
					$ret[(string)$key][$id]['text'] = (string)$vv;
				}
			}
			if(!count($ret[(string)$key])) $ret[(string)$key] = (string)$value;
		}
		return $ret;
	}

	function upload($photo, $title = '', $description = '', $tags = '', $perms = '', $async = 1, &$info = null)
	{
		$store = HABARI_PATH . '/' . Site::get_path('user') . '/cache';
		if(!is_dir($store)){
			mkdir($store, 0777);
		}
		$params = array('auth_token' => $this->cachedToken());
		$url = InputFilter::parse_url('file://' . $photo);
		if(isset($url['scheme'])){
			$localphoto = fopen(HABARI_PATH . '/' . $photo, 'r');
			$store = tempnam($store, 'G2F');
			file_put_contents($store, $localphoto);
			fclose($localphoto);
			$params['photo'] = $store;
		}
		else{
			$params['photo'] = $photo;
		}

		$info = filesize($params['photo']);

		if($title){
			$params['title'] = $title;
		}

		if($description){
			$params['description'] = $description;
		}

		if($tags){
			$params['tags'] = $tags;
		}

		if($perms){
			if(isset($perms['is_public'])){
				$params['is_public'] = $perms['is_public'];
			}
			if(isset($perms['is_friend'])){
				$params['is_friend'] = $perms['is_friend'];
			}
			if(isset($perms['is_family'])){
				$params['is_family'] = $perms['is_family'];
			}
		}

		if($async){
			$params['async'] = $async;
		}
		// call the upload method.
		$xml = $this->call('upload', $params);

		if($store){
			unlink($store);
		}

		if (Error::is_error($xml)){
			throw $xml;
		}

		if($async){
			return((string)$xml->ticketid);
		}
		else{
			return((string)$xml->photoid);
		}
	}

	function photosUploadCheckTickets($tickets)
	{
		if(is_array($tickets)){
			foreach($tickets as $key => $value){
				if($key){
					$params['tickets'] .= ' ';
				}
				$params['tickets'] .= $value;
			}
		}
		else{
			$params['tickets'] = $tickets;
		}

		$xml = $this->call('flickr.photos.upload.checkTickets', $params);
		if (Error::is_error($xml)){
			throw $xml;
		}

		foreach($xml->uploader->ticket as $ticket){
			foreach($ticket->attributes() as $key => $value){
				$uptick[(string)$ticket['id']][$key] = (string)$value;
			}
		}
		return $uptick;
	}

	function reflectionGetMethods()
	{
		$params = array();
		$xml = $this->call('flickr.reflection.getMethods', $params);
		if(!$xml){
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
	* Provide plugin info to the system
	*/
	public function info()
	{
		return array('name' => 'Flickr Media Silo',
			'version' => '1.0',
			'url' => 'http://habariproject.org/',
			'author' => 'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'license' => 'Apache License 2.0',
			'description' => 'Implements basic Flickr integration',
			'copyright' => '2008',
			);
	}

	/**
	* Initialize some internal values when plugin initializes
	*/
	public function action_init()
	{
	}

	/**
	* Return basic information about this silo
	*     name- The name of the silo, used as the root directory for media in this silo
	*/
	public function silo_info()
	{
		if($this->is_auth()) {
			return array('name' => self::SILO_NAME);
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
	public function silo_dir($path)
	{
		$flickr = new Flickr();
		$results = array();
		$size = Options::get('flickrsilo:flickr_size');

		$section = strtok($path, '/');
		switch($section) {
			case 'photos':
				$xml = $flickr->photosSearch();
				foreach($xml->photos->photo as $photo) {

					$props = array();
					foreach($photo->attributes() as $name => $value) {
						$props[$name] = (string)$value;
					}
					$props['url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}$size.jpg";
					$props['thumbnail_url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_m.jpg";
					$props['flickr_url'] = "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}";
					$props['filetype'] = 'flickr';

					$results[] = new MediaAsset(
						self::SILO_NAME . '/photos/' . $photo['id'],
						false,
						$props
					);
				}
				break;
			case 'tags':
				$selected_tag = strtok('/');
				if($selected_tag) {
					$xml = $flickr->photosSearch(array('tags'=>$selected_tag));
					foreach($xml->photos->photo as $photo) {

						$props = array();
						foreach($photo->attributes() as $name => $value) {
							$props[$name] = (string)$value;
						}
						$props['url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}.jpg";
						$props['thumbnail_url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_m.jpg";
						$props['flickr_url'] = "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}";
						$props['filetype'] = 'flickr';

						$results[] = new MediaAsset(
							self::SILO_NAME . '/photos/' . $photo['id'],
							false,
							$props
						);
					}
				}
				else {
					$xml = $flickr->tagsGetListUser($_SESSION['nsid']);
					foreach($xml->who->tags->tag as $tag) {
						$results[] = new MediaAsset(
							self::SILO_NAME . '/tags/' . (string)$tag,
							true,
							array('title' => (string)$tag)
						);
					}
				}
				break;
			case 'sets':
				$selected_set = strtok('/');
				if($selected_set) {
					$xml = $flickr->photosetsGetPhotos($selected_set);
					foreach($xml->photoset->photo as $photo) {

						$props = array();
						foreach($photo->attributes() as $name => $value) {
							$props[$name] = (string)$value;
						}
						$props['url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}.jpg";
						$props['thumbnail_url'] = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_m.jpg";
						$props['flickr_url'] = "http://www.flickr.com/photos/{$_SESSION['nsid']}/{$photo['id']}";
						$props['filetype'] = 'flickr';

						$results[] = new MediaAsset(
							self::SILO_NAME . '/photos/' . $photo['id'],
							false,
							$props
						);
					}
				}
				else {
					$xml = $flickr->photosetsGetList($_SESSION['nsid']);
					foreach($xml->photosets->photoset as $set) {
						$results[] = new MediaAsset(
							self::SILO_NAME . '/sets/' . (string)$set['id'],
							true,
							array('title' => (string)$set->title)
						);
					}
				}
				break;


			case '':
				$results[] = new MediaAsset(
					self::SILO_NAME . '/photos',
					true,
					array('title' => 'Photos')
				);
				$results[] = new MediaAsset(
					self::SILO_NAME . '/tags',
					true,
					array('title' => 'Tags')
				);
				$results[] = new MediaAsset(
					self::SILO_NAME . '/sets',
					true,
					array('title' => 'Sets')
				);
				break;
		}
		return $results;
	}

	/**
	* Get the file from the specified path
	*
	* @param string $path The path of the file to retrieve
	* @param array $qualities Qualities that specify the version of the file to retrieve.
	* @return MediaAsset The requested asset
	*/
	public function silo_get($path, $qualities = null)
	{
	}

	/**
	* Get the direct URL of the file of the specified path
	*
	* @param string $path The path of the file to retrieve
	* @param array $qualities Qualities that specify the version of the file to retrieve.
	* @return string The requested url
	*/
	public function silo_url($path, $qualities = null)
	{
		$photo = false;
		if(preg_match('%^photos/(.+)$%', $path, $matches)) {
			$id = $matches[1];
			$photo = self::$cache[$id];
		}

		$size = '';
		if(isset($qualities['size']) && $qualities['size'] == 'thumbnail') {
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
	public function silo_new($path)
	{
	}

	/**
	* Store the specified media at the specified path
	*
	* @param string $path The path of the file to retrieve
	* @param MediaAsset $ The asset to store
	*/
	public function silo_put($path, $filedata)
	{
	}

	/**
	* Delete the file at the specified path
	*
	* @param string $path The path of the file to retrieve
	*/
	public function silo_delete($path)
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
	public function silo_permissions($path)
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
		$token = Options::get('flickr_token_' . User::identify()->id);
		$result = $flickr->call('flickr.auth.checkToken',
			array('api_key' => $flickr->key,
				'auth_token' => $token));
		$photos = $flickr->GetPublicPhotos($result->auth->user['nsid'], null, 5);
		foreach($photos['photos'] as $photo){
			$url = $flickr->getPhotoURL($photo);
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
	public function filter_plugin_config($actions, $plugin_id)
	{
		if ($plugin_id == $this->plugin_id()){
			$flickr_ok = $this->is_auth();

			if($flickr_ok){
				$actions[] = 'De-Authorize';
			}
			else{
				$actions[] = 'Authorize';
			}
			$actions[] = 'Configure';
		}

		return $actions;
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui($plugin_id, $action)
	{
		if ($plugin_id == $this->plugin_id()){
			switch ($action){
				case 'Authorize':
					if($this->is_auth()){
						$deauth_url = URL::get('admin', array('page' => 'plugins', 'configure' => $this->plugin_id(), 'action' => 'De-Authorize')) . '#plugin_options';
						echo "<p>You have already successfully authorized Habari to access your Flickr account.</p>";
						echo "<p>Do you want to <a href=\"\">revoke authorization</a>?</p>";
					}
					else{
						$flickr = new Flickr();
						$_SESSION['flickr_frob'] = '' . $flickr->getFrob();
						$auth_url = $flickr->authLink($_SESSION['flickr_frob']);
						$confirm_url = URL::get('admin', array('page' => 'plugins', 'configure' => $this->plugin_id(), 'action' => 'confirm')) . '#plugin_options';
						echo <<< END_AUTH

<p>To use this plugin, you must <a href="{$auth_url}" target="_blank">authorize Habari to have access to your Flickr account</a>.
<p>When you have completed the authorization on Flickr, return here and <a href="$confirm_url">confirm that the authorization was successful</a>.

END_AUTH;
					}
					break;

				case 'confirm':
					$flickr = new Flickr();
					if(!isset($_SESSION['flickr_frob'])){
						$auth_url = URL::get('admin', array('page' => 'plugins', 'configure' => $this->plugin_id(), 'action' => 'Authorize')) . '#plugin_options';
						echo '<p>Either you have already authorized Habari to access your flickr account, or you have not yet done so.  Please <a href="' . $auth_url . '">try again</a>.</p>';
					}
					else{
						$token = $flickr->getToken($_SESSION['flickr_frob']);
						if(isset($token->auth->perms)){
							Options::set('flickr_token_' . User::identify()->id, '' . $token->auth->token);
							echo '<p>Your authorization was set successfully.</p>';
						}
						else{
							echo '<p>There was a problem with your authorization:</p>';
							echo htmlspecialchars($token->asXML());
						}
						unset($_SESSION['flickr_frob']);
					}
					break;
				case 'De-Authorize':
					Options::set('flickr_token_' . User::identify()->id);
					$reauth_url = URL::get('admin', array('page' => 'plugins', 'configure' => $this->plugin_id(), 'action' => 'Authorize')) . '#plugin_options';
					echo '<p>The Flickr Silo Plugin authorization has been deleted.<p>';
					echo "<p>Do you want to <a href=\"{$reauth_url}\">re-authorize this plugin</a>?<p>";
					break;
				case 'Configure' :
					$ui= new FormUI( strtolower( get_class( $this ) ) );
					$flickr_size= $ui->add( 'select', 'flickr_size', 'Default size for images in Posts:' );
					$flickr_size->options= array( '_s' => 'Square (75x75)', '_t' => 'Thubmnail (100px)', '_m' => 'Small (240px)', '' => 'Medium (500px)', '_b' => 'Large (1024px)', '_o' => 'Original Size' );
					$ui->out();
					break;
			}
		}
	}
	public function action_admin_footer( $theme ) {
		if ($theme->admin_page == 'publish') {
			echo <<< FLICKR
			<script type="text/javascript">
				habari.media.output.flickr = {display: function(fileindex, fileobj) {
					habari.editor.insertSelection('<a href="' + fileobj.flickr_url + '"><img src="' + fileobj.url + '"></a>');
				}}
				habari.media.preview.flickr = function(fileindex, fileobj) {
					var stats = '';
					return '<div class="mediatitle">' + fileobj.title + '</div><img src="' + fileobj.thumbnail_url + '"><div class="mediastats"> ' + stats + '</div>';
				}
			</script>
FLICKR;
		}
	}

	private function is_auth()
	{
		static $flickr_ok = null;
		if(isset($flickr_ok)){
			return $flickr_ok;
		}

		$flickr_ok = false;
		$token = Options::get('flickr_token_' . User::identify()->id);
		if($token != ''){
			$flickr = new Flickr();
			$result = $flickr->call('flickr.auth.checkToken', array('api_key' => $flickr->key, 'auth_token' => $token));
			if(isset($result->auth->perms)){
				$flickr_ok = true;
				$_SESSION['nsid'] = (string)$result->auth->user['nsid'];
			}
			else{
				Options::set('flickr_token_' . User::identify()->id);
				unset($_SESSION['flickr_token']);
			}
		}
		return $flickr_ok;
	}
}

?>
