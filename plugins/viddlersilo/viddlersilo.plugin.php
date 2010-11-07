<?php
/*
	#######################################################
	#  Viddler API / PHP Wrapper
	#  By: Colin Devroe | cdevroe@viddler.com
	#
	#  Docs: http://wiki.developers.viddler.com/index.php/Phpviddler
	#
	#  License(s): Licensed under:
	#  MIT (MIT-LICENSE.txt)
    #
    #  Third-party code:
    #  XML Library by Keith Devens
	#  xmlparser.php
	#
	#  Version 0.3
	########################################################
*/

class Phpviddler {

	var $apiKey = '01172c643b9743485249534a4441564953151';
	var $viddlerREST = 'http://api.viddler.com/rest/v1/'; // REST URL Version 1.0
	var $xml; // Raw XML returned by API
	var $response; // Array of results
	var $parser = false; // Use the included XML parser? Default: true.

	// Optional
	var $uploaddir = false; // Used for temporary upload directory.
/*##########  User functions ########### */
	/* viddler.users.auth
	/ accepts: $userInfo = array
	/ returns: array - sessionid (if not asking for record_token)
	/ returns: array - sessionid and recordtoken (if asking for record token)
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.users.auth
	*/
	function user_authenticate($userInfo) {
		$args = '';
		$args = $this->buildArguments($userInfo); // Arguments as string
		//var_dump( $args ); exit;
		$xml = $this->sendRequest('viddler.users.auth',$args); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				// Return record token too? Or just session id?
				if (!$args['record_token']) {
					return $response['auth']['sessionid'];
				} else {
					return $response['auth'];
				}
			}
		} else {
			return $xml;
		}
	return false;
	}

	/* viddler.users.getProfile
	/ accepts: $username = string
	/ returns: array - user's profile
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.users.getProfile
	*/
	function user_profile($username) {

		$xml = $this->sendRequest('viddler.users.getProfile','user='.$username); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['user'];
			}
		} else {
			return $xml;
		}
	return false;
	}

	/* viddler.users.setOptions
	/ accepts: $options = array
	/ returns: string - number of options updated
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.users.setOptions
	*/
	function user_setoptions($options) {
		$args = '';
		$args = $this->buildArguments($options); // Arguments as string

		$xml = $this->sendRequest('viddler.users.setOptions',$args); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['user'];
			}
		} else {
			return $xml;
		}
	return false;
	}

/*##########  Video functions ########### */

	/* viddler.videos.upload
	/ requires: POST HTTP Method
	/ accepts: $videoInfo = array
	/ returns: array - info about video uploaded
	doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.upload
	*/
	function video_upload($videoInfo) {
		$args = '';
		$args = $this->buildArguments($videoInfo,'array'); // Arguments as array

		$xml = $this->sendRequest('viddler.videos.upload',$args); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			// Deletes the uploaded file from the server
			//if ($this->uploaddir) {
			//	unlink($this->uploaddir.$_FILES['file']['name']);
			//}

			if ($response['error']) {
				return $response;
			} else {
				return $response['video'];
			}
		} else {
			return $xml;
		}
	return false;
	}

	/* viddler.videos.getRecordToken
	/ accepts: $sessionid = string
	/ returns: string - token needed for recording with webcam
	}
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.getRecordToken
	/ Instructions for use: http://wiki.developers.viddler.com/index.php/Record_With_Webcam_API
	*/
	function video_getrecordtoken($sessionid) {

		$xml = $this->sendRequest('viddler.videos.getRecordToken','sessionid='.$sessionid); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['record_token'];
			}
		} else {
			return $xml;
		}
	return false;
	}

	/* Not a method...
	/ accepts: $token = string
	/ returns: string - html for recorder embed with token included.
	}
	/ doc: None.
	*/
	function video_getrecordembed($token) {

		if (!$token) return false;

		$html = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="449" height="400" id="viddler_recorder" align="middle">
			<param name="allowScriptAccess" value="always" />
			<param name="allowNetworking" value="all" />
			<param name="movie" value="http://www.viddler.com/flash/recorder.swf" />
			<param name="quality" value="high" />
			<param name="scale" value="noScale">
			<param name="bgcolor" value="#000000" />
			<param name="flashvars" value="fake=1&recordToken='.$token.'" />
			<embed src="http://www.viddler.com/flash/recorder.swf" quality="high" scale="noScale" bgcolor="#000000" allowScriptAccess="always" allowNetworking="all" width="449" height="400" name="viddler_recorder" flashvars="fake=1&recordToken='.$token.'" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>';

		return $html;
	}


	/* viddler.videos.getStatus
	/ accepts: $videoID = string
	/ returns: array - info about video's upload status
	/ Method specific responses {
	    1: Waiting in encode queue (failed=0)
		2: Encoding (failed=0)
		3: Encoding process failed (failed=1)
		4: Ready (failed=0)
		5: Deleted (failed=0)
		6: Wrong priviledges (failed=0)
	}
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.getStatus
	*/
	function video_status($videoID) {

		$xml = $this->sendRequest('video.status','video_id='.$videoID); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['video_status'];
			}
		} else {
			return $xml;
		}
	return false;
	}

	/* viddler.videos.getDetails
	/ accepts: $sessionid and $videoID = string
	/ returns: array - info about video's upload status
	/ Method specific responses {
	    1: Waiting in encode queue (failed=0)
		2: Encoding (failed=0)
		3: Encoding process failed (failed=1)
		4: Ready (failed=0)
		5: Deleted (failed=0)
		6: Wrong priviledges (failed=0)
	}
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.getDetails
	*/
	function video_details($sessionid,$videoID) {

		$xml = $this->sendRequest('viddler.videos.getDetails','sessionid='.$sessionid.'&video_id='.$videoID); // Get XML response

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['video'];
			}
		} else {
			return $xml;
		}
	return false;
	}


	/* viddler.videos.getByUser
	/ accepts: $user = string, $page = string, $per_page = string
	/ returns: array - information about the videos matching this user
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.getByUser
	*/
	function videos_listbyuser($user,$page =1,$per_page =5) {

		 // Get XML response
		$xml = $this->sendRequest('viddler.videos.getByUser','user='.$user.'&page='.$page.'&per_page='.$per_page);

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['video_list'];
			}
		} else {
			return $xml;
		}
	}


	/* viddler.videos.getByTag
	/ accepts: $tag = string, $page = string, $per_page = string
	/ returns: array - information about videos matching this tag
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.getByTag
	*/
	function videos_listbytag($tag,$page =1,$per_page =5) {

		 // Get XML response
		$xml = $this->sendRequest('viddler.videos.getByTag','tag='.$tag.'&page='.$page.'&per_page='.$per_page);

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['video_list'];
			}
		} else {
			return $xml;
		}
	return false;
	}

	/* viddler.videos.getFeatured
	/ accepts: nothing.
	/ returns: array - information about videos that are featured
	/ doc: http://wiki.developers.viddler.com/index.php/Viddler.videos.getFeatured
	*/
	function videos_listfeatured() {

		 // Get XML response
		$xml = $this->sendRequest('viddler.videos.getFeatured','');

		// Use included parser?
		if ($this->parser) {
			// Several steps
			// 1. Convert XML into an array
			// 2. Check for errors
			//    i. Return array of error number, short, and long description
			// 3. Return multidimensional array of response.
			$response = $this->checkErrors(XML_unserialize($xml));

			if ($response['error']) {
				return $response;
			} else {
				return $response['video_list'];
			}
		} else {
			return $xml;
		}
	return false;
	}



/*##########  Misc. Functions ########### */

	// Error checking
	function checkErrors($response) {

		if ($response['error']) {

			switch ($response['error']) {

				case '1':
					$errorshort = 'An internal error has occurred.';
					break;
				case '2':
					$errorshort = 'Bad argument format.';
					break;
				case '3':
					$errorshort = 'Unknown argument specified.';
					break;
				case '4':
					$errorshort = 'Missing required argument for this method.';
					break;
				case '5':
					$errorshort = 'No method specified.';
					break;
				case '6':
					$errorshort = 'Unknown method specified.';
					break;
				case '7':
					$errorshort = 'API key missing.';
					break;
				case '8':
					$errorshort = 'Invalid or unknown API key specified.';
					break;
				case '9':
					$errorshort = 'Invalid or expired sessionid.';
					break;
				case '10':
					$errorshort = 'HTTP method used not allowed on this method.';
					break;
				case '100':
					$errorshort = 'Video could not be found.';
					break;
				case '101':
					$errorshort = 'Username not found.';
					break;
				case '102':
					$errorshort = 'This account has been suspended.';
					break;
				case '103':
					$errorshort = 'Password incorrect for this username.';
					break;
				case '104':
					$errorshort = 'Terms of Service not accepted by user.';
					break;
				case '105':
					$errorshort = 'Username already in use.';
					break;
				case '106':
					$errorshort = 'Email address already in use.';
					break;
				case '200':
					$errorshort = 'The file is too large. Please limit to 500Mb.';
					break;
				default:
					$errorshort = 'An unknown error has occured.';
					break;
			} // End switch

			$error = array('error' => array('number'=>$response['error'],'shortdesc'=>$errorshort));

			return $error;
		}

		return $response;

	}


	/**
	 * Build arguments to use for a request to the API
	 *
	 * @param array $p An associative array of parameters
	 * @param string $return_type Optional return type of parameters ['array'|'string']
	 * @return mixed The filtered arguments in the format requested
	 */
	public function buildArguments( $p, $return_type = 'string') {
		$args = array();
		foreach ($p as $key => $value) {

			// Skip method request name and submit button
			switch ($key) {
				case 'method':
				case 'submit':
				case 'MAX_FILE_SIZE':
					continue;
			}

			$args[$key] = $value;

		} // end foreach

		switch ($return_type) {
			// If array assume uploading
			case 'array':
				$uploadfile = $this->uploaddir . basename($_FILES['file']['name']);

				if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
					$args['file'] = "@{$uploadfile}";
				}
				break;

			// If String, chop off last ampersand
			case 'string':
			default:
				$args = http_build_query($args, null, '&');
				break;
		}

		return $args;
	}


	// Send REST request
	function sendRequest($method,$args) {

		// Build Request URL
		$reqURL = $this->viddlerREST.'?api_key='.$this->apiKey.'&method='.$method;
		if ($method != 'viddler.videos.upload') $reqURL .= '&'.$args;

		// Send request via CURL
		$curl_handle = curl_init();
		curl_setopt ($curl_handle, CURLOPT_URL, $reqURL); // Request URL
		curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, 1); // Return as string
		curl_setopt ($curl_handle, CURLOPT_CONNECTTIMEOUT, 1); // Fail if timeout

		// If sending a file, change to POST instead of GET
		if ($method == 'viddler.videos.upload') {
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $args);
		}

		$response = curl_exec($curl_handle); // Call!

		if (!$response)	$response = curl_error($curl_handle);

		curl_close($curl_handle); // Close connection

		// When CURL doesn't work, use this.
		//$response = file_get_contents($reqURL);


		// Return XML response as string
		if (!$response) {
			return false; // 'There is no response. CURL might not by allowed? <br />'.$reqURL.'<br /><br />';
		}

		return $response;

	} // End sentReq();

}

/**
* Viddler Silo
*/

class ViddlerSilo extends Plugin implements MediaSilo
{
	const SILO_NAME = 'Viddler';

	protected $cache = array();


	/*
	// add a rewrite rule for our auto-generated video page.
	public function filter_rewrite_rules( $rules )	{
		$rules[] = new RewriteRule(array(
			'name' => 'viddlerlist',
			'parse_regex' => '/^sillytv[\/]{0,1}$/i',
			'build_str' => 'viddlerlist',
			'handler' => 'ViddlerAdminHandler',
			'action' => 'display_viddlerlist',
			'priority' => 7,
			'is_active' => 1,
		));

		return $rules;
	}
	*/

	/**
	* Initialize some internal values when plugin initializes
	*/
	public function action_init()
	{
		// add some js to the admin header
		Stack::add( 'admin_header_javascript',  $this->get_url(true) . 'vidfuncs.js', 'viddlerjs', array('media','jquery') );
		$this->viddler = new Phpviddler();
	}

	/**
	* Return basic information about this silo
	*     name- The name of the silo, used as the root directory for media in this silo
	*/
	public function silo_info()
	{
		if ($this->is_auth()) {
			return array('name' => self::SILO_NAME, 'icon' => URL::get_from_filesystem(__FILE__) . '/icon.png');
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
		$user = User::identify()->info->viddler__username;

		if (Cache::has('viddler:videos:' . $user)) {
			$pre = Cache::get('viddler:videos:' . $user);
		}
		else {
			$pre = $this->viddler->videos_listbyuser( $user, '', 20 );
			Cache::set('viddler:videos:' . $user, $pre);
		}
		$xml = new SimpleXMLElement( $pre );
		$results = array();
		foreach( $xml->video as $video ) {

			$props = array();
			foreach($video->children() as $name => $value) {
				$props[$name] = (string)$value;
			}
			$props['filetype'] = 'viddler';

			$asset = new MediaAsset(
				self::SILO_NAME . '/' . $path . ($path == '' ? '' : '/') . $video->id,
				false,
				$props
			);
			$results[] = $asset;

			//echo '<div class="media"><img src="' . $video->thumbnail_url . '" width="150px"><div class="foroutput"><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="437" height="370" id="viddler_chrisjdavis_' . $video->id . '"><param name="movie" value="http://www.viddler.com/player/' . $video->id . '/" /><param name="allowScriptAccess" value="always" /><param name="allowFullScreen" value="true" /><embed src="http://www.viddler.com/player/' . $video->id . '/" width="437" height="370" type="application/x-shockwave-flash" allowScriptAccess="always" allowFullScreen="true" name="viddler_chrisjdavis" ></embed></object></div></div>';
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
	public function silo_url( $path, $qualities = null )
	{
		$id = basename($path);
		$video = $this->cache[$id];
		if (isset($qualities['size']) && $qualities['size']=='thumbnail') {
			$url = $video->thumbnail_url;
		}
		else {
			$url = $video->url;
		}
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

	public function controls()
	{
		echo '<ul class="silo-controls"><li id="vupload"><a href="#upload" title="Upload a video">Upload</a></li<li id="vrecord"><a href="#record" title="Record a video">Record</a></li><li id="vstream"><a class="final" href="#stream" title="View your video stream">Viddler Stream</a></li></ul>';
	}

	public function silo_upload_form()
	{
		echo '<form name="viddler_upload"></form>';
	}

	public function upload_form()
	{
	?>
		<div id="viddlerform" class="container" style="display:none;">
		<h2>Upload a Video</h2>
		<form name="viddler_upload">
		<div class="container">
		<p class="column span-5">Video</p>
		<p class="column span-14 last"><input type="file" name="viddler_file"></p>
		</div>
		<hr>
		<div class="container">
		<p class="column span-5">Video Title</p>
		<p class="column span-14 last"><input type="text" name="viddler_title"></p>
		</div>
		<hr>
		<div class="container">
		<p class="column span-5">Video Tags</p>
		<p class="column span-14 last"><input type="text" name="viddler_tags"></p>
		</div>
		<hr>
		<div class="container">
		<p class="column span-5">Video Description</p>
		<p class="column span-14 last"><textarea name="viddler_desc"></textarea></p>
		<p class="column span-5 prepend-5"><input type="submit" value="Upload Video" id="execute_viddler"></p>
		</div>
		</form>
		</div>
		<div id="viddlerrecord" class="container" style="display:none;">
			<div class="column span-14 prepend-4">
			<h2>Record a Video</h2>
			<p><?php
			$user = User::identify()->info->viddler__username;
			$pass = User::identify()->info->viddler__password;
			$auth = $this->viddler->user_authenticate( array( 'user' => $user, 'password' => $pass, 'get_record_token' => 1 ) );
			$sid = new SimpleXMLElement( $auth );
			$mt = $test->video_getrecordtoken( $sid->sessionid );
			$token = new SimpleXMLElement( $mt );
			$embed = $test->video_getrecordembed( $token );
			echo $embed;
			?></p>
		</div>
	<?php
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
	* Add actions to the plugin page for this plugin
	* The authorization should probably be done per-user.
	*
	* @param array $actions An array of actions that apply to this plugin
	* @param string $plugin_id The string id of a plugin, generated by the system
	* @return array The array of actions to attach to the specified $plugin_id
	*/
	public function filter_plugin_config( $actions, $plugin_id ) {
		if ( $plugin_id == $this->plugin_id() ) {
			$viddler_ok = $this->is_auth();
			if ( $viddler_ok ) {
				$actions[]= 'Log Out';
			} else {
				$actions[]= 'Log In';
			}
		}
		return $actions;
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui( $plugin_id, $action ) {
		switch ( $action ) {
			case 'Log In':
				$ui = new FormUI( strtolower( get_class( $this ) ) );
				$ui->append('text', 'username', 'user:viddler__username', 'Viddler Username:');
				$ui->append('password', 'password', 'user:viddler__password', 'Viddler Password:');
				$ui->append('submit', 'submit', 'Log In');
				$ui->on_success( array( $this, 'updated_config' ) );
				$ui->out();

/*
				if ( $this->is_auth() ) {
					$deauth_url= URL::get('admin', array('page' => 'plugins', 'configure' => $this->plugin_id(), 'action' => 'Log Out')) . '#plugin_options';
					echo "<p>You have successfully logged into Vidder via Habari.</p>";
					echo "<p>Do you want to <a href=\"{$deauth_url}\">log out</a>?</p>";
				}
				else {
					$username = $ui->username->value;
					$password = $ui->password->value;
					if ($username != '' && $password != '') {
						$auth= $this->viddler->user_authenticate( array( 'user' => $username, 'password' => $password ) );

						$xml= new SimpleXMLElement( $auth );
						User::identify()->info->viddler__token = (string) $xml->session_id;
					}
				}
*/
				break;
			case 'Log Out':
				unset(User::identify()->info->viddler__token);
				$reauth_url = URL::get('admin', array('page' => 'plugins', 'configure' => $this->plugin_id(), 'action' => 'Log In')) . '#plugin_options';
				echo '<p>The Viddler Silo Plugin session has been logged out.<p>';
				echo "<p>Do you want to <a href=\"{$reauth_url}\">log in again</a>?<p>";
				break;
		}
	}

	/**
   * Returns true if plugin config form values defined in action_plugin_ui should be stored in options by Habari
   * @return boolean True if options should be stored
   **/
  public function updated_config($ui)
  {
		$username = $ui->username->value;
		$password = $ui->password->value;
		if ($username != '' && $password != '') {
			$auth = $this->viddler->user_authenticate( array( 'user' => $username, 'password' => $password ) );

			$xml = new SimpleXMLElement( $auth );
			$token = (string) $xml->sessionid;
			if ($token == '') {
		  	$ui->set_option('success_message', _t('The username and password you supplied failed to log in to Viddler.'));
			}
			else {
		  	$ui->save();
				User::identify()->info->viddler__token = $token;
				User::identify()->info->commit();
		  	return  '<p>' . _t('You have logged in to Viddler.') . '</p>';
			}
		}

    return false;
  }

	private function is_auth()
	{
		$token = User::identify()->info->viddler__token;
		if ( $token != '' ) {
			return true;
		} else {
			return false;
		}
	}

	public function action_admin_header( $theme )
	{
		if (Controller::get_var('page') == 'publish') {
			$header = <<< HEADER
habari.media.output.viddler = {display: function(index, fileobj) {
	habari.editor.insertSelection( ''+
		'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="437" height="370" id="viddler_' + fileobj.basename + '">' +
		'<param name="movie" value="http://www.viddler.com/player/' + fileobj.basename + '/" />' +
		'<param name="allowScriptAccess" value="always" />' +
		'<param name="allowFullScreen" value="true" />' +
		'<embed src="http://www.viddler.com/player/' + fileobj.basename + '/" width="437" height="370" type="application/x-shockwave-flash" allowScriptAccess="always" allowFullScreen="true" ></embed>' +
		'</object>'
	);
}}
HEADER;
		Stack::add( 'admin_header_javascript',  $header, 'viddlerinline', array('viddlerjs', 'media','jquery') );

		}
	}

	/**
	 * Produce a link for the media control bar that causes a specific panel to be displayed
	 *
	 * @param string $path The path to pass
	 * @param string $path The panel to display
	 * @param string $title The text to use for the link in the control bar
	 * @return string The link to create
	 */
	public function link_panel( $path, $panel, $title )
	{
		return '<a href="#" onclick="habari.media.showpanel(\''.$path.'\', \''.$panel.'\');return false;">' . $title . '</a>';
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
		if ($silo instanceof $class) {
			if (User::identify()->can('upload_viddler')) {
				$controls[] = $this->link_panel(self::SILO_NAME . '/' . $path, 'upload', 'Upload');
				$controls[] = $this->link_panel(self::SILO_NAME . '/' . $path, 'record', 'Record');
			}
		}
		return $controls;
	}

	/**
	 * Provide requested media panels for this plugin
	 *
	 * @param string $panel The HTML content of the panel to be output in the media bar
	 * @param MediaSilo $silo The silo for which the panel was requested
	 * @param string $path The path within the silo (silo root omitted) for which the panel was requested
	 * @param string $panelname The name of the requested panel
	 * @return string The modified $panel to contain the HTML output for the requested panel
	 *
	 * @todo Move the uploaded file from the temporary location to the location indicated by the path field.
	 */
	public function filter_media_panels( $panel, $silo, $path, $panelname)
	{
		$class = __CLASS__;
		if ($silo instanceof $class) {
			switch ($panelname) {
				case 'record':
					$user = User::identify()->info->viddler__username;
					$pass = User::identify()->info->viddler__password;
					$auth = $this->viddler->user_authenticate( array( 'user' => $user, 'password' => $pass, 'get_record_token' => 1, 'record_token' => 1 ) );
					$sid = new SimpleXMLElement( $auth );
					$rt = $sid->record_token;
					$panel .= "<div class=\"span-18\" style=\"padding-top:30px;color: #e0e0e0;margin: 0px auto;\">";
					$panel .= $this->viddler->video_getrecordembed( $rt );
					$panel .= '</div>';
					break;
				case 'upload':
					if (isset($_FILES['file'])) {
						$size = Utils::human_size($_FILES['file']['size']);
						$panel .= "<div class=\"span-18\" style=\"padding-top:30px;color: #e0e0e0;margin: 0px auto;\"><p>File Uploaded: {$_FILES['file']['name']} ($size)</p>";

						$path = self::SILO_NAME . '/' . preg_replace('%\.{2,}%', '.', $path). '/' . $_FILES['file']['name'];
						$asset = new MediaAsset($path, false);
						$asset->upload($_FILES['file']);

						if ($asset->put()) {
							$panel .= '<p>File added successfully.</p>';
						}
						else {
							$panel .= '<p>File could not be added to the silo.</p>';
						}

						$panel .= '<p><a href="#" onclick="habari.media.forceReload();habari.media.showdir(\'' . dirname($path) . '\');">Browse the current silo path.</a></p></div>';
					}
					else {

						$fullpath = self::SILO_NAME . '/' . $path;
						$form_action = URL::get('admin_ajax', array('context' => 'media_panel'));
						$panel .= <<< UPLOAD_FORM
<form enctype="multipart/form-data" method="post" id="simple_upload" target="simple_upload_frame" action="{$form_action}" class="span-10" style="margin:0px auto;text-align: center">
	<p style="padding-top:30px;">Upload to: <b style="font-weight:normal;color: #e0e0e0;font-size: 1.2em;">/{$path}</b></p>
	<p><input type="file" name="file"><input type="submit" name="upload" value="Upload">
	<input type="hidden" name="path" value="{$fullpath}">
	<input type="hidden" name="panel" value="{$panelname}">
	</p>
</form>
<iframe id="simple_upload_frame" name="simple_upload_frame" style="width:1px;height:1px;" onload="simple_uploaded();"></iframe>
<script type="text/javascript">
var responsedata;
function simple_uploaded() {
	if (!$('#simple_upload_frame')[0].contentWindow) return;
	var response = $($('#simple_upload_frame')[0].contentWindow.document.body).text();
	if (response) {
		eval('responsedata = ' + response);
		window.setTimeout(simple_uploaded_complete, 500);
	}
}
function simple_uploaded_complete() {
	habari.media.jsonpanel(responsedata);
}
</script>
UPLOAD_FORM;

				}
			}
		}
		return $panel;
	}

}

/*
// This is an auto-generated video page.  There is a rewrite rules array in the plugin
// above that needs to be uncommented as well.  Not sure if we actually want this in
// the plugin, which is why it is commented out.

class ViddlerAdminHandler extends ActionHandler
{
	private $vids;
	private $theme= null;

	public function __construct() {
		$this->theme= Themes::create();
	}

	public function act_display_viddlerlist() {
		$this->load_vids();
		$this->theme->assign( 'vids', $this->vid );
		$this->theme->display( 'vids' );
	}

	public function load_vids() {
		$user= Options::get( 'viddlersilo:username_' . User::identify()->id );
		$viddler= new Phpviddler();
		$pre= $viddler->videos_listbyuser( $user, '', 20, 'view_count' );
		$xml= new SimpleXMLElement( $pre );
			foreach( $xml->video as $video ) {
				$this->vid[]= $video;
			}
		}
}
*/
?>
