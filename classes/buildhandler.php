<?php
/**
 * @package Habari
 *
 */

/**
 * Handles Build requests, building and caching the file
 *
 */
class BuildHandler extends ActionHandler
{
	
	public function act_build_file() {
		$hash = $this->handler_vars['hash'];
		$request = unserialize(Utils::decode($hash));
		$storage = Site::get_dir('user') . '/build/';
		
		$path = $request['path'];
		$info = pathinfo($path);
				
		// $fullpath = HABARI_PATH . '/' . $path;
		
		// $theme_dir = Plugins::filter( 'build_dir', dirname($fullpath) . '/', $path );
		// $theme = Themes::create( 'admin', 'HiEngine', $theme_dir );
		$theme = Themes::create();
		
		header('Content-type: ' . Utils::mimetype(basename($path))); 
		
		$theme->theme= $theme;
		$render= $theme->fetch($path);
						
		echo $render;
		
		if( is_writeable($storage) ) {
			// file_put_contents($storage . $hash . '.' . $info['extension'], $render);
		}
	}
	
}

?>