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
		$storage = Site::get_dir('builds', true);
		
		$path = $request['path'];
		$info = pathinfo($path);
				
		$theme = $this->get_theme($path);
				
		header('Content-type: ' . Utils::mimetype(basename($path))); 
		
		$theme['theme']->theme= $theme['theme'];
		$render= $theme['theme']->fetch($theme['template']);
					
		echo $render;
		
		if( is_writeable($storage) ) {
			file_put_contents($storage . $hash . '.' . $info['extension'], $render);
		}
	}
	
	/**
	 * Get the appropriate theme & template for a given path
	 *
	 * @param string Path
	 * @return array Array of $theme and $template
	 **/
	public function get_theme($path)
	{
		$scripts = str_replace(Site::get_url('scripts', TRUE), '', $path);
		$thirdparty = str_replace(Site::get_url('3rdparty', TRUE), '', $path);
		$admin = str_replace(Site::get_url('admin_theme', TRUE), '', $path);
		$user_theme = str_replace(Site::get_url('theme', TRUE), '', $path);
		
		if($scripts != $path) {
			$theme_dir = Plugins::filter( 'scripts_dir', HABARI_PATH . '/scripts/' );
			$theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir);
			$template = $scripts;
		}
		elseif($thirdparty != $path) {
			$theme_dir = Plugins::filter( '3rdparty_dir', HABARI_PATH . '/3rdparty/' );
			$theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir);
			$template = $thirdparty;
		}
		elseif($admin != $path) {
			$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
			$theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir);
			$template = $admin;
		}
		elseif($user_theme != $path) {
			$theme = Themes::create();
			$template = $user_theme;
		}
		else {
			$theme = Themes::create();
			$template = basename($path);
		}
		
		return array('theme' => $theme, 'template' => $template);
	}	
	
}

?>