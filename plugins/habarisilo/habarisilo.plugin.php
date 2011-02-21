<?php

/**
 * Simple file access silo
 *
 * @todo Create some helper functions in a superclass to display panel controls more easily, so that you don't need to include 80 lines of code to build a simple upload form every time.
 */

class HabariSilo extends Plugin implements MediaSilo
{
	protected $root = null;
	protected $url = null;

	const SILO_NAME = 'Habari';

	const DERIV_DIR = '.deriv';

	/**
	 * Initialize some internal values when plugin initializes
	 */
	public function action_init()
	{
		$user_path = HABARI_PATH . '/' . Site::get_path('user', true);
		$this->root = $user_path . 'files'; //Options::get('simple_file_root');
		$this->url = Site::get_url('user', true) . 'files';  //Options::get('simple_file_url');
	}

	public function filter_activate_plugin( $ok, $file )
	{
		if ( Plugins::id_from_file($file) == Plugins::id_from_file(__FILE__) ) {
			if ( !$this->check_files() ) {
				EventLog::log( _t( "Habari Silo activation failed. The web server does not have permission to create the 'files' directory for the Habari Media Silo." ), 'warning', 'plugin' );
				Session::error( _t( "Habari Silo activation failed. The web server does not have permission to create the 'files' directory for the Habari Media Silo." ) );
				$ok = false;
			}
			// Don't bother loading if the gd library isn't active
			if ( !function_exists( 'imagecreatefromjpeg' ) ) {
				EventLog::log( _t( "Habari Silo activation failed. PHP has not loaded the gd imaging library." ), 'warning', 'plugin' );
				Session::error( _t( "Habari Silo activation failed. PHP has not loaded the gd imaging library." ) );
				$ok = false;
			}
		}
		return $ok;
	}

	public function action_plugin_activation( $file )
	{
		// Create required tokens
		ACL::create_token( 'create_directories', _t( 'Create media silo directories' ), 'Administration' );
		ACL::create_token( 'delete_directories', _t( 'Delete media silo directories' ), 'Administration' );
		ACL::create_token( 'upload_media', _t( 'Upload files to media silos' ), 'Administration' );
		ACL::create_token( 'delete_media', _t( 'Delete files from media silos' ), 'Administration' );
	}

	/**
	*
	* @param string $file. The name of the plugin file
	*
	* Delete the special silo permissions if they're no longer
	* being used.
	*/
	public function action_plugin_deactivation( $file ) {
		$silos = Plugins::get_by_interface( 'MediaSilo' );
		if ( count( $silos ) <= 1 ) {
			ACL::destroy_token( 'upload_media' );
			ACL::destroy_token( 'delete_media' );
			ACL::destroy_token( 'create_directories' );
			ACL::destroy_token( 'delete_directories' );
		}
	}

	/**
	 *
	 * Checks if files directory is usable
	 */
	private function check_files() {
		$user_path = HABARI_PATH . '/' . Site::get_path('user', true);
		$this->root = $user_path . 'files'; //Options::get('simple_file_root');
		$this->url = Site::get_url('user', true) . 'files';  //Options::get('simple_file_url');

		if ( !is_dir( $this->root ) ) {
			if ( is_writable( $user_path ) ) {
				mkdir( $this->root, 0755 );
			}
			else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return basic information about this silo
	 *   name- The name of the silo, used as the root directory for media in this silo
	 **/
	public function silo_info()
	{
		return array(
			'name' => self::SILO_NAME,
			'icon' => URL::get_from_filesystem(__FILE__) . '/icon.png'
		);
	}

	/**
	 * Return directory contents for the silo path
	 * @param string $path The path to retrieve the contents of
	 * @return array An array of MediaAssets describing the contents of the directory
	 **/
	public function silo_dir( $path )
	{
		if ( !isset( $this->root ) ) {
			return array();
		}

		$path = preg_replace('%\.{2,}%', '.', $path);
		$results = array();

		$dir = Utils::glob( $this->root . ( $path == '' ? '' : '/' ) . $path . '/*' );

		foreach ( $dir as $item ) {
			if ( substr( basename( $item ), 0, 1 ) == '.' ) {
				continue;
			}
			if ( basename( $item ) == 'desktop.ini' ) {
				continue;
			}

			$file = basename( $item );
			$props = array(
				'title' => basename( $item ),
			);
			if(is_dir($item)) {
				$results[] = new MediaAsset(
					self::SILO_NAME . '/' . $path . ($path == '' ? '' : '/') . basename( $item ),
					is_dir( $item ),
					$props
				);
			}
			else {
				$results[] = $this->silo_get($path . ($path == '' ? '' : '/') . basename( $item ));
			}
		}
		//print_r($results);
		return $results;
	}


	/**
	 * Get the file from the specified path
	 * @param string $path The path of the file to retrieve
	 * @param array $qualities Qualities that specify the version of the file to retrieve.
	 * @return MediaAsset The requested asset
	 **/
	public function silo_get( $path, $qualities = null )
	{
		if ( ! isset( $this->root ) ) {
			return false;
		}

		$path = preg_replace('%\.{2,}%', '.', $path);

		$file = basename( $path );
		$props = array(
			'title' => basename( $path ),
		);
		$realfile = $this->root . '/' . $path;

		$thumbnail_suffix = HabariSilo::DERIV_DIR . '/' . $file . '.thumbnail.jpg';
		$thumbnail_url = $this->url . '/' . dirname($path) . (dirname($path) == '' ? '' : '/') . $thumbnail_suffix;
		$mimetype = preg_replace('%[^a-z_0-9]%', '_', Utils::mimetype($realfile));
		$mtime = '';

		if ( !file_exists( dirname( $realfile ) . '/' . $thumbnail_suffix ) ) {
			switch (strtolower(substr($realfile, strrpos($realfile, '.') + 1))) {
				case 'jpg':
				case 'png':
				case 'gif':
					if ( !$this->create_thumbnail( $realfile ) ) {
						// there is no thumbnail so use icon based on mimetype.
						$icon_path = Plugins::filter( 'habarisilo_icon_base_path', dirname($this->get_file()) . '/icons' );
						$icon_url = Plugins::filter( 'habarisilo_icon_base_url', $this->get_url() . '/icons' );
								
						if ( ( $icons = Utils::glob($icon_path . '/*.{png,jpg,gif,svg}', GLOB_BRACE) ) && $mimetype ) {
							$icon_keys = array_map( create_function('$a', 'return pathinfo($a, PATHINFO_FILENAME);'), $icons );
							$icons = array_combine($icon_keys, $icons);
							$icon_filter = create_function('$a, $b', "\$mime = '$mimetype';".'return (((strpos($mime, $a)===0) ? (strlen($a) / strlen($mime)) : 0) >= (((strpos($mime, $b)===0)) ? (strlen($b) / strlen($mime)) : 0)) ? $a : $b;');
							$icon_key = array_reduce($icon_keys, $icon_filter);
							if ($icon_key) {
								$icon = basename($icons[$icon_key]);
								$thumbnail_url = $icon_url .'/'. $icon;
							}
							else {
								// couldn't find an icon so use default
								$thumbnail_url = $icon_url .'/default.png';
							}
						}
					}
					break;
			}
		}
				
		// If the asset is an image, obtain the image dimensions
		if ( in_array( $mimetype, array( 'image_jpeg', 'image_png', 'image_gif' ) ) ) {
			list( $props['width'], $props['height'] ) = getimagesize( $realfile );
			$mtime = '?' . filemtime( $realfile );
		}
		$props = array_merge(
			$props,
			array(
				'url' => $this->url . '/' . dirname($path) . ($path == '' ? '' : '/') . $file,
				'thumbnail_url' => $thumbnail_url . $mtime,
				'filetype' => $mimetype,
			)
		);
		
		$asset = new MediaAsset( self::SILO_NAME . '/' . $path, false, $props );
		if ( file_exists( $realfile ) && is_file( $realfile ) ) {
			$asset->content = file_get_contents( $realfile );
		}
		return $asset;
	}


	/**
	 * Create a thumbnail in the derivative directory
	 *
	 * @param string $src_filename The source filename
	 * @param integer $max_width The maximum width of the output image
	 * @param integer $max_height The maximum height of the output image
	 * @return boolean true if the thumbnail creation succeeded
	 */
	private function create_thumbnail($src_filename, $max_width = Media::THUMBNAIL_WIDTH, $max_height = Media::THUMBNAIL_HEIGHT)
	{
		// Does derivative directory not exist?
		$thumbdir = dirname( $src_filename ) . '/' . HabariSilo::DERIV_DIR . '';
		if ( !is_dir( $thumbdir ) ) {
			// Create the derivative driectory
			if ( !mkdir( $thumbdir, 0755 ) ) {
				// Couldn't make derivative directory
				return false;
			}
		}

		// Get information about the image
		list( $src_width, $src_height, $type, $attr )= getimagesize( $src_filename );

		// Load the image based on filetype
		switch ( $type ) {
		case IMAGETYPE_JPEG:
			$src_img = imagecreatefromjpeg( $src_filename );
			break;
		case IMAGETYPE_PNG:
			$src_img = imagecreatefrompng( $src_filename );
			break;
		case IMAGETYPE_GIF:
			$src_img = imagecreatefromgif ( $src_filename );
			break;
		default:
			return false;
		}
		// Did the image fail to load?
		if ( !$src_img ) {
			return false;
		}

		// Calculate the output size based on the original's aspect ratio
		$y_displacement = 0;
		if ( $src_width / $src_height > $max_width / $max_height ) {
			$thumb_w = $max_width;
			$thumb_h = $src_height * $max_width / $src_width;

		// thumbnail is not full height, position it down so that it will be padded on the
		// top and bottom with black
		$y_displacement = ($max_height - $thumb_h) / 2;
		}
		else {
			$thumb_w = $src_width * $max_height / $src_height;
			$thumb_h = $max_height;
		}

		// Create the output image and copy to source to it
		$dst_img = ImageCreateTrueColor( $thumb_w, $max_height );
		imagecopyresampled( $dst_img, $src_img, 0, $y_displacement, 0, 0, $thumb_w, $thumb_h, $src_width, $src_height );

		/* Sharpen before save?
		$sharpenMatrix= array( array(-1, -1, -1), array(-1, 16, -1), array(-1, -1, -1) );
		$divisor= 8;
		$offset= 0;
		imageconvolution( $dst_img, $sharpenMatrix, $divisor, $offset );
		//*/

		// Define the thumbnail filename
		$dst_filename = $thumbdir . '/' . basename($src_filename) . ".thumbnail.jpg";

		// Save the thumbnail as a JPEG
		imagejpeg( $dst_img, $dst_filename );

		// Clean up memory
		imagedestroy( $dst_img );
		imagedestroy( $src_img );

		return true;
	}

	/**
	 * Create a new asset instance for the specified path
	 * @param string $path The path of the new file to create
	 * @return MediaAsset The requested asset
	 **/
	public function silo_new( $path )
	{
	}

	/**
	 * Store the specified media at the specified path
	 * @param string $path The path of the file to retrieve
	 * @param MediaAsset $filedata The MediaAsset to store
	 * @return boolean True on success
	 **/
	public function silo_put( $path, $filedata )
	{
		$path = preg_replace('%\.{2,}%', '.', $path);
		$file = $this->root . '/' . $path;

		$result = $filedata->save( $file );
		if ( $result ) {
			$this->create_thumbnail( $file );
		}

		return $result;
	}

	/**
	 * Delete the file at the specified path
	 * @param string $path The path of the file to retrieve
	 **/
	public function silo_delete( $path )
	{
		$file = $this->root . '/' . $path;

		// Delete the file
		$result = unlink( $file );

		// If it's an image, remove the file in .deriv too
		$thumbdir = dirname( $file ) . '/' . HabariSilo::DERIV_DIR . '';
		$thumb = $thumbdir . '/' . basename( $file ) . ".thumbnail.jpg";

		if ( file_exists( $thumbdir ) && file_exists( $thumb ) ) {
			unlink( $thumb );
			// if this is the last thumb, delete the .deriv dir too
			if ( self::isEmptyDir( $thumbdir ) ) {
				rmdir( $thumbdir );
			}
		}
		return $result;
	}

	/**
	 * Retrieve a set of highlights from this silo
	 * This would include things like recently uploaded assets, or top downloads
	 * @return array An array of MediaAssets to highlihgt from this silo
	 **/
	public function silo_highlights()
	{
	}

	/**
	 * Retrieve the permissions for the current user to access the specified path
	 * @param string $path The path to retrieve permissions for
	 * @return array An array of permissions constants (MediaSilo::PERM_READ, MediaSilo::PERM_WRITE)
	 **/
	public function silo_permissions( $path )
	{
	}

	/**
	 * Produce a link for the media control bar that causes a specific path to be displayed
	 *
	 * @param string $path The path to display
	 * @param string $title The text to use for the link in the control bar
	 * @return string The link to create
	 */
	public function link_path( $path, $title = '' )
	{
		if ( $title == '' ) {
			$title = basename( $path );
		}
		return '<a href="#" onclick="habari.media.showdir(\''.$path.'\');return false;">' . $title . '</a>';
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
		if ( $silo instanceof $class ) {
			$controls[] = $this->link_path( self::SILO_NAME . '/' . $path, _t( 'Browse' ) );
			if ( User::identify()->can( 'upload_media' ) ) {
				$controls[] = $this->link_panel(self::SILO_NAME . '/' . $path, 'upload', _t( 'Upload' ) );
			}
			if ( User::identify()->can( 'create_directories' ) ) {
				$controls[] = $this->link_panel(self::SILO_NAME . '/' . $path, 'mkdir', _t( 'Create Directory' ) );
			}
			if ( User::identify()->can( 'delete_directories' ) && ( $path && self::isEmptyDir( $this->root . '/' . $path ) ) ) {
				$controls[] = $this->link_panel(self::SILO_NAME . '/' . $path, 'rmdir', _t( 'Delete Directory' ) );
			}
		}
		return $controls;
	}

	/**
	 * Provide requested media panels for this plugin
	 *
	 * Regarding Uploading:
	 * A panel is returned to the media bar that contains a form, an iframe, and a javascript function.
	 * The form allows the user to select a file, and is submitted back to the same URL that produced this panel in the first place.
	 * This has the result of submitting the uploaded file to here when the form is submitted.
	 * To prevent the panel form from reloading the whole publishing page, the form is submitted into the iframe.
	 * An onload event attached to the iframe calls the function.
	 * The function accesses the content of the iframe when it loads, which should contain the results of the request to obtain this panel, which are in JSON format.
	 * The JSON data is passed to the habari.media.jsonpanel() function in media.js to process the data and display the results, just like when displaying a panel normally.
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
		if ( $silo instanceof $class ) {
			switch ( $panelname ) {
				case 'mkdir':

					$fullpath = self::SILO_NAME . '/' . $path;

					$form = new FormUI( 'habarisilomkdir' );
					$form->append( 'static', 'ParentDirectory', '<div style="margin: 10px auto;">' . _t('Parent Directory:') . " <strong>/{$path}</strong></div>" );

					// add the parent directory as a hidden input for later validation
					$form->append( 'hidden', 'path', 'null:unused' )->value = $path;
					$form->append( 'hidden', 'action', 'null:unused')->value = $panelname;
					$dir_text_control = $form->append( 'text', 'directory', 'null:unused', _t('What would you like to call the new directory?') );
					$dir_text_control->add_validator( array( $this, 'mkdir_validator' ) );
					$form->append( 'submit', 'submit', _t('Submit') );
					$form->media_panel($fullpath, $panelname, 'habari.media.forceReload();');
					$form->on_success( array( $this, 'dir_success' ) );
					$panel = $form->get(); /* form submission magicallly happens here */

					return $panel;

					break;
				case 'rmdir':
					$fullpath = self::SILO_NAME . '/' . $path;

					$form = new FormUI( 'habarisilormdir' );
					$form->append( 'static', 'RmDirectory', '<div style="margin: 10px auto;">' . _t('Directory:') . " <strong>/{$path}</strong></div>" );

					// add the parent directory as a hidden input for later validation
					$form->append( 'hidden', 'path', 'null:unused' )->value = $path;
					$form->append( 'hidden', 'action', 'null:unused')->value = $panelname;
					$dir_text_control = $form->append( 'static', 'directory', _t('Are you sure you want to delete this directory?') );
					$form->append( 'submit', 'submit', _t('Delete') );
					$form->media_panel($fullpath, $panelname, 'habari.media.forceReload();');
					$form->on_success( array( $this, 'dir_success' ) );
					$panel = $form->get(); /* form submission magicallly happens here */

					return $panel;

					break;
				case 'delete':
					$fullpath = self::SILO_NAME . '/' . $path;

					$form = new FormUI( 'habarisilodelete' );
					$form->append( 'static', 'RmFile', '<div style="margin: 10px auto;">' . _t('File:') . " <strong>/{$path}</strong></div>" );

					// add the parent directory as a hidden input for later validation
					$form->append( 'hidden', 'path', 'null:unused' )->value = $path;
					$dir_text_control = $form->append( 'static', 'directory', '<p>' . _t('Are you sure you want to delete this file?') . '</p>');
					$form->append( 'submit', 'submit', _t('Delete') );
					$form->media_panel($fullpath, $panelname, 'habari.media.forceReload();');
					$form->on_success( array( $this, 'do_delete' ) );
					$panel = $form->get();

					return $panel;
					break;
				case 'upload':
					if ( isset( $_FILES['file'] ) ) {
						$size = Utils::human_size($_FILES['file']['size']);
						$panel .= "<div class=\"span-18\" style=\"padding-top:30px;color: #e0e0e0;margin: 0px auto;\"><p>" . _t( "File Uploaded: " ) . "{$_FILES['file']['name']} ($size)</p>";

						$path = self::SILO_NAME . '/' . preg_replace('%\.{2,}%', '.', $path). '/' . $_FILES['file']['name'];
						$asset = new MediaAsset($path, false);
						$asset->upload( $_FILES['file'] );

						if ( $asset->put() ) {
							$panel .= '<p>' . _t( 'File added successfully.' ) . '</p>';
						}
						else {
							$panel .= '<p>' . _t( 'File could not be added to the silo.' ) . '</p>';
						}

						$panel .= '<p><a href="#" onclick="habari.media.forceReload();habari.media.showdir(\'' . dirname($path) . '\');">' . _t( 'Browse the current silo path.' ) . '</a></p></div>';
					}
					else {

						$fullpath = self::SILO_NAME . '/' . $path;
						$form_action = URL::get('admin_ajax', array('context' => 'media_panel'));
						$panel .= <<< UPLOAD_FORM
<form enctype="multipart/form-data" method="post" id="simple_upload" target="simple_upload_frame" action="{$form_action}" class="span-10" style="margin:0px auto;text-align: center">
	<p style="padding-top:30px;">%s <b style="font-weight:normal;color: #e0e0e0;font-size: 1.2em;">/{$path}</b></p>
	<p><input type="file" name="file"><input type="submit" name="upload" value="%s">
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

					$panel = sprintf( $panel, _t( "Upload to:" ), _t( "Upload" ) );
				}
			}
		}
		return $panel;
	}

	/**
	 * A validator for the mkdir form created with FormUI. Checks to see if the
	 * webserver can write to the parent directory and that the directory does
	 * not already exist.
	 * @param $dir The input from the form
	 * @param $control The FormControl object
	 * @param $form The FormUI object
	 */
	public function mkdir_validator( $dir, $control, $form )
	{
		if ( strpos($dir, '*') !== false || preg_match('%(?:^|/)\.%', $dir) ) {
		    return array(_t("The directory name contains invalid characters: %s.", array($dir)));
		}

		$path = preg_replace( '%\.{2,}%', '.', $form->path->value );
		$dir = $this->root . ( $path == '' ? '' : '/' ) . $path . '/'. $dir;

		if ( !is_writable( $this->root . '/' . $path ) ) {
			return array(_t("Webserver does not have permission to create directory: %s.", array( $dir ) ) );
		}
		if ( is_dir( $dir ) ) {
			return array( _t( "Directory: %s already exists.", array( $dir ) ) );
		}

		return array();
	}
	/**
	 * This function performs the mkdir and rmdir actions on submission of the form.
	 * It is called by FormUI's success() method.
	 * @param FormUI $form
	 */
	public function dir_success ( $form )
	{
		$dir = preg_replace( '%\.{2,}%', '.', $form->directory->value );
		$path = preg_replace( '%\.{2,}%', '.', $form->path->value );

		switch ( $form->action->value ) {
			case 'rmdir':
				$dir = $this->root . ( $path == '' ? '' : '/' ) . $path;
				rmdir( $dir );
				$msg = 'Directory Deleted:';
				$what = $path;
				break;
			case 'mkdir':
				$dir = $this->root . ( $path == '' ? '' : '/' ) . $path . '/'. $dir;
				mkdir( $dir, 0755 );
				$msg = 'Directory Created:';
				$what = $path . '/' . $form->directory->value;
				break;
		}

		return '<div class="span-18"style="padding-top:30px;color: #e0e0e0;margin: 0px auto;"><p>' . _t( $msg ) . ' ' . $what . '</p></div>';
	}

	/**
	 * This function takes the path passed from the form and passes it to silo_delete
	 * to delete the file and it's thumbnail if it's an image.
	 *
	 * @param FormUI $form
	 */
	public function do_delete ( $form )
	{
		$path = preg_replace( '%\.{2,}%', '.', $form->path->value );
		$result = $this->silo_delete($path);
		$panel = '<div class="span-18"style="padding-top:30px;color: #e0e0e0;margin: 0px auto;">';
		if ( $result ) {
			$panel .= '<p>' . _t( 'File deleted successfully.' ) . '</p>';
		} else {
			$panel .= '<p>' . _t( 'Failed to delete file.' ) . '</p>';
		}

		$panel .= '<p><a href="#" onclick="habari.media.forceReload();habari.media.showdir(\'' . self::SILO_NAME . '/' . dirname( $path ) . '\');">' . _t( 'Browse the current silo path.' ) . '</a></p></div>';

		return $panel;
	}

	/**
	 * This function is used to check if a directory is empty.
	 *
	 * @param string $dir
	 * @return boolean
	 */
	private static function isEmptyDir( $dir )
	{
		return ( ( $files = @scandir( $dir ) ) && count( $files ) <= 2 );
	}

}

?>
