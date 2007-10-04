<?php

/**
 * Habari ThemeHandler Class
 *
 * @package Habari
 */
class ThemeHandler extends ActionHandler
{
	private $themedir;
	
	public function __construct( $action, $settings )
	{
		$this->themedir= HABARI_PATH . '/themes/' . Options::get( 'theme_dir' ) . '/';
		parent::__construct( $action, $settings );
	}

	/**
	 * function __call
	 * Handles all calls to this class for methods that don't exist.
	 * In this class, method names correlated with action names set in the URL rules.
	 * Throws an execption if the requested action isn't valid.
	 * @param string The action that was called.
	 * @param array Settings passed in from the URL
	 **/	 	 
	public function __call( $action, $settings )
	{
		global $url, $theme; // XXX are these used?

		// What this handler handles and how
		$handle= array( 
			'post' => 'post.php', 
			'home' => 'index.php',
			'login' => 'login.php',
			'logout' => 'login.php',
			'error' => 'error.php',
			'search' => 'search.php',
			'tag' => 'tag.php',
		);
		
		$theme= new ThemeEngine();

		if ( isset( $handle[$action] ) ) {
			$potential_template= $this->themedir . $handle[$action];
			// is this a request for a single post?
			if ( 'post' == $action ) {
				// let's see if that post actually exists
				if ( $post= Post::get() ) {
					$potential_template= $this->themedir . $post->slug . '.php';
					if ( ! file_exists( $potential_template ) ) {
						// no specific file, so use default
						$potential_template= $this->themedir . $handle[$action];
					}
				}
				else {
					// no such post -- display the error page
					$action= 'error';
					$potential_template= $this->themedir . $handle[$action];
				}
			}
			if ( file_exists( $potential_template ) ) {
				include $potential_template;
			}
		}
		else {
			throw new Exception( "ThemeHandler does not handle the action {$action}." );
		}
	}
}

?>