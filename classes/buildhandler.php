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
		$path = $this->handler_vars['path'];
		$fullpath = HABARI_PATH . '/' . $path;
		$parts = explode('.', basename($path));
		$extension = $parts[count($parts) - 1];
		
		// $engine = new RawPHPEngine();
		// $engine->set_template_dir($file);
		// 
		// Utils::debug($engine->template_exists('style', 'css'));
		
		$theme_dir = Plugins::filter( 'build_dir', dirname($fullpath) . '/', $path );
		$theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );
		
		header('Content-type: text/css'); 
		
		$theme->display(basename($path));
	}
	
}

?>