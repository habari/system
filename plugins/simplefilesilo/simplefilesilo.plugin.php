<?php

/**
 * Simple file access silo
 *
 * @todo Create some helper functions in a superclass to display panel controls more easily, so that you don't need to include 80 lines of code to build a simple upload form every time.
 */

class SimpleFileSilo extends Plugin implements MediaSilo
{
	protected $root= null;
	protected $url= null;

	const SILO_NAME= 'Local Files';

	const DERIV_DIR= '.deriv';

	/**
	 * Provide plugin info to the system
	 */
	public function info()
	{
		return array(
			'name' => 'Simple File Media Silo',
			'version' => '1.0',
			'url' => 'http://habariproject.org/',
			'author' =>	'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'license' => 'Apache License 2.0',
			'description' => 'Demonstrates basic silo functionality',
			'copyright' => '2008',
		);
	}

	/**
	 * Initialize some internal values when plugin initializes
	 */
	public function action_init()
	{
		$user_path= HABARI_PATH . '/' . Site::get_path('user', true);
		$this->root= $user_path . 'files'; //Options::get('simple_file_root');
		$this->url= Site::get_url('user', true) . 'files';  //Options::get('simple_file_url');
		
		if (! $this->check_files()) {
			Session::error( "Web server does not have permission to create 'files' directory for SimpleFile Media Silo." );
			Plugins::deactivate_plugin(__FILE__); //Deactivate plugin
			Utils::redirect(); //Refresh page – unfortunately, if not done so then results don't appear
		}
	}
	
	/**
	 * Checks if files directory is usable
	 */
	private function check_files() {
		$user_path= HABARI_PATH . '/' . Site::get_path('user', true);
		$this->root= $user_path . 'files'; //Options::get('simple_file_root');
		$this->url= Site::get_url('user', true) . 'files';  //Options::get('simple_file_url');
		
		if ( ! is_dir( $this->root ) ) {
			if ( is_writable( $user_path ) ) {
				mkdir( $this->root, 0766 );
			} else {
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
		if( ! isset( $this->root ) ) {
			return array();
		}

		$path= preg_replace('%\.{2,}%', '.', $path);
		$results= array();

		$dir= glob($this->root . ( $path == '' ? '' : '/' ) . $path . '/*');


		foreach( $dir as $item ) {
			if( substr( basename( $item ), 0, 1 ) == '.' ) {
				continue;
			}
			if( basename( $item ) == 'desktop.ini' ) {
				continue;
			}

			$file = basename( $item );
			$props = array(
				'title' => basename( $item ),
			);
			if( ! is_dir( $item ) ) {
				$thumbnail_suffix= SimpleFileSilo::DERIV_DIR . '/' . $file . '.thumbnail.jpg';
				$thumbnail_url= $this->url . '/' . $path . ($path == '' ? '' : '/') . $thumbnail_suffix;

				if( ! file_exists( dirname( $item ) . '/' . $thumbnail_suffix ) ) {
					if( ! $this->create_thumbnail( $item ) ) {
						// Do something if we can't create a thumbnail, like return a default image
					}
				}
				$props = array_merge(
					$props,
					array(
						'url' => $this->url . '/' . $path . ($path == '' ? '' : '/') . $file,
						'thumbnail_url' => $thumbnail_url,
						'filetype' => preg_replace('%[^a-z_0-9]%', '_', Utils::mimetype($item)),
					)
				);
			}

			$results[] = new MediaAsset(
				self::SILO_NAME . '/' . $path . ($path == '' ? '' : '/') . basename( $item ),
				is_dir( $item ),
				$props
			);
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
		if( ! isset( $this->root ) ) {
			return false;
		}

		$path= preg_replace('%\.{2,}%', '.', $path);

		$file= $this->root . '/' . $path;

		if( file_exists( $file ) ) {
			$asset= new MediaAsset( self::SILO_NAME . '/' . $path );
			$asset->set( file_get_contents( $file ) );
			return $asset;
		}
		return false;
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
		$thumbdir = dirname( $src_filename ) . '/' . SimpleFileSilo::DERIV_DIR . '';
		if( ! is_dir( $thumbdir ) ) {
			// Create the derivative driectory
			if( ! mkdir( $thumbdir, 0766 ) ){
				// Couldn't make derivative directory
				return false;
			}
		}

    // Get information about the image
    list( $src_width, $src_height, $type, $attr )= getimagesize( $src_filename );

    // Load the image based on filetype
    switch( $type ) {
    case IMAGETYPE_JPEG:
      $src_img = imagecreatefromjpeg( $src_filename );
      break;
    case IMAGETYPE_PNG:
      $src_img = imagecreatefrompng( $src_filename );
      break;
    case IMAGETYPE_GIF:
      $src_img = imagecreatefromgif( $src_filename );
      break;
    default:
      return false;
    }
    // Did the image fail to load?
    if ( !$src_img ) {
      return false;
    }

    // Calculate the output size based on the original's aspect ratio
	$y_displacement= 0;
    if ( $src_width / $src_height > $max_width / $max_height ) {
      $thumb_w= $max_width;
      $thumb_h= $src_height * $max_width / $src_width;

	  // thumbnail is not full height, position it down so that it will be padded on the
	  // top and bottom with black
	  $y_displacement= ($max_height - $thumb_h) / 2;
    }
    else {
      $thumb_w= $src_width * $max_height / $src_height;
      $thumb_h= $max_height;
    }

    // Create the output image and copy to source to it
    $dst_img= ImageCreateTrueColor( $thumb_w, $max_height );
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
		$path= preg_replace('%\.{2,}%', '.', $path);
		$file= $this->root . '/' . $path;

		return $filedata->save( $file );
	}

	/**
	 * Delete the file at the specified path
	 * @param string $path The path of the file to retrieve
	 **/
	public function silo_delete( $path )
	{
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
		if( $title == '' ) {
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
		if( $silo instanceof $class ) {
			$controls[]= $this->link_path( self::SILO_NAME . '/' . $path, 'Browse' );
			if( User::identify()->can( 'upload_media' ) ) {
				$controls[]= $this->link_panel(self::SILO_NAME . '/' . $path, 'upload', 'Upload');
			}
			if( User::identify()->can( 'create_directories' ) ) {
				$controls[]= $this->link_panel(self::SILO_NAME . '/' . $path, 'mkdir', 'Create Directory');
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
		if( $silo instanceof $class ) {
			switch( $panelname ) {
				case 'mkdir':

					$fullpath= self::SILO_NAME . '/' . $path;

					$form= new FormUI( 'simplefilesilomkdir' );
					$form->append( 'static', 'ParentDirectory', _t('Parent Directory:'). " <strong>/{$path}</strong>" );

					// add the parent directory as a hidden input for later validation
					$form->append( 'hidden', 'path', 'null:unused', '', $path );
					$dir_text_control= $form->append( 'text', 'directory', 'null:unused', _t('Enter the name of the new directory to create here') );
					$dir_text_control->add_validator( array( $this, 'mkdir_validator' ) );
					$form->append( 'submit', 'submit', _t('Submit') );
					$form->media_panel($fullpath, $panelname, 'habari.media.forceReload();');
					$form->on_success( array( $this, 'mkdir_success' ) );
					$panel= $form->get(); /* form submission magicallly happens here */

					return $panel;

					break;
				case 'upload':
					if( isset( $_FILES['file'] ) ) {
						$size= Utils::human_size($_FILES['file']['size']);
						$panel.= "<div class=\"span-18\" style=\"padding-top:30px;color: #e0e0e0;margin: 0px auto;\"><p>File Uploaded: {$_FILES['file']['name']} ($size)</p>";

						$path= self::SILO_NAME . '/' . preg_replace('%\.{2,}%', '.', $path). '/' . $_FILES['file']['name'];
						$asset= new MediaAsset($path, false);
						$asset->upload( $_FILES['file'] );

						if( $asset->put() ) {
							$panel.= '<p>File added successfully.</p>';
						}
						else {
							$panel.= '<p>File could not be added to the silo.</p>';
						}

						$panel.= '<p><a href="#" onclick="habari.media.forceReload();habari.media.showdir(\'' . dirname($path) . '\');">Browse the current silo path.</a></p></div>';
					}
					else {

						$fullpath= self::SILO_NAME . '/' . $path;
						$form_action= URL::get('admin_ajax', array('context' => 'media_panel'));
						$panel.= <<< UPLOAD_FORM
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
	if(!$('#simple_upload_frame')[0].contentWindow) return;
	var response = $($('#simple_upload_frame')[0].contentWindow.document.body).text();
	if(response) {
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

	/* this function should convert the virtual path to a real path and
	 * then call php's mkdir function */
	public function mkdir($form, $panel, $silo, $path)
	{
		/* check that the regular expression is required for this case */
		$path= preg_replace('%\.{2,}%', '.', $path);
		$dir= $this->root . '/' . $path;
		return mkdir( $dir );
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
		$dir= preg_replace( '%\.{2,}%', '.', $dir );
		$path= preg_replace( '%\.{2,}%', '.', $form->path->value );
		$dir= $this->root . ( $path == '' ? '' : '/' ) . $path . '/'. $dir;

		if ( ! is_writable( $this->root . '/' . $path ) ) {
			return array(_t("Webserver does not have permission to create directory: {$dir}."));
		}
		if ( is_dir( $dir ) ) {
			return array(_t("Directory: {$dir} already exists."));
		}

		return array();
	}
	/**
	 * This function performs the mkdir action on submission of the form. It is
	 * called by FormUI's success() method.
	 * @param FormUI $form
	 */
	public function mkdir_success ( $form )
	{
		$dir= preg_replace( '%\.{2,}%', '.', $form->directory->value );
		$path= preg_replace( '%\.{2,}%', '.', $form->path->value );

		$dir= $this->root . ( $path == '' ? '' : '/' ) . $path . '/'. $dir;
		mkdir( $dir, 0766 );

		return "<div class=\"span-18\"style=\"padding-top:30px;color: #e0e0e0;margin: 0px auto;\"><p>". _t('Directory Created:') ." {$form->directory->value}</p>";
	}

}

?>
