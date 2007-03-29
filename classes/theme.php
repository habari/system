<?php

/**
 * Habari Theme Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
 * The Theme class is the behind-the-scenes representation of 
 * of a set of UI files that compose the visual theme of the blog
 *   
 */
class Theme
{
	private $name= null;
	private $version= null;
	public $template_engine= null;
	public $theme_dir= null;
	public $config_vars= array();
	
	/**
	 * Constructor for theme
	 * 
	 * If no parameter is supplied, then the constructor 
	 * Loads the active theme from the database.
	 * 
	 * If no theme option is set, a fatal error is thrown
	 * 
	 * @param name            ( optional ) override the default theme lookup
	 * @param template_engine ( optional ) specify a template engine
	 * @param theme_dir       ( optional ) specify a theme directory
	 */	 	 	 	 	
	public function __construct($themedata)
	{
		$this->name= $themedata->name;
		$this->version= $themedata->version;
		$this->theme_dir= $themedata->theme_dir;
		// Set up the corresponding engine to handle the templating
		$this->template_engine= new $themedata->template_engine();
		$this->template_engine->set_template_dir( $themedata->theme_dir );
	}

	/**
	 * Loads a theme's metadata from an INI file in theme's
	 * directory.
	 * 
	 * @param theme Name of theme to retrieve metadata about
	 * @note  This may change to an XML file format
	 */
	public function info( $theme )
	{
		$info_file= HABARI_PATH . '/user/themes/' . $theme . '.info';
		if ( file_exists( $info_file ) ) {
			$theme_data= parse_ini_file( $info_file ); // Use NO sections INI
		}
		if ( ! empty( $theme_data ) ) {
			// Parse out the good stuff
			$named_member_vars= array( 'name', 'version', 'template_engine', 'theme_dir' );
			foreach ( $theme_data as $key=>$value ) {
				$key= strtolower( $key );
				if ( in_array( $key, $named_member_vars ) ) { 
					$this->$key= $value;
				}
				else { 
					$this->config_vars[$key]= $value;
				}
			}
		} 
	} 

	/**
	 * Grabs post data and inserts that data into the internal
	 * handler_vars array, which eventually gets extracted into 
	 * the theme's ( and thereby the template_engine's ) local
	 * symbol table for use in the theme's templates
	 * 
	 * This is the default, generic function to grab posts.  To 
	 * "filter" the posts retrieved, simply pass any filters to 
	 * the handler_vars variables associated with the post retrieval.
	 * For instance, to filter by tag, ensure that handler_vars['tag']
	 * contains the tag to filter by.  Simple as that.
	 */
	public function act_display_posts()
	{
		/* 
		 * We build the Post filters by analyzing the handler_var
		 * data which is assigned to the handler ( by the Controller and 
		 * also, optionally, by the Theme )
		 */
		$valid_filters= array( 
			  'content_type'
			, 'slug'
			, 'status'
			, 'page' // pagination
			, 'tag'
			, 'month'
			, 'year'
			, 'day'
		);
		$where_filters= array();
		$where_filters = array_intersect_key( Controller::get_handler()->handler_vars, array_flip( $valid_filters ) );
		$where_filters['status'] = Post::status('published');

		$posts= Posts::get( $where_filters );
		/**
		 * @todo XXX
		 * the first part of the condition differentiates between single post and multiple posts,
		 * but there must be a better way.
		 * */
		if ( isset( Controller::get_handler()->handler_vars['slug'] ) && count( $posts ) == 1 && count( $where_filters ) > 0 ) {
			Controller::get_handler()->handler_vars['post']= $posts[0];
			/**
			 * @todo TODO XXX
			 * - Don't hardcode the content type to template mapping
			 * - Let this be handled by the theme?
			 * */
			if ( $posts[0]->content_type == Post::type('page') && file_exists( Site::get_path('theme') . 'page.php') ) {
				$template= 'page';
			} else {
				$template= 'post';
			}
		}
		else {
			// Automatically assigned to theme at display time.
			Controller::get_handler()->handler_vars['posts']= $posts;
			$template= 'posts';
		}
		$this->display( $template );
		
		return true;
	}
	
	public function act_search()
	{
		if ( ! isset( Controller::get_handler()->handler_vars['page'] ) )
		{
			Controller::get_handler()->handler_vars['page']= 1;
		}
		$posts= Posts::search( Controller::get_handler()->handler_vars['criteria'], Controller::get_handler()->handler_vars['page'] );
		Controller::get_handler()->handler_vars['posts']= $posts;
		$this->display( 'search' );
	}

	/**
	 * Helper passthru function to avoid having to always
	 * call $theme->template_engine->display( 'template_name' );
	 */
	public function display( $template_name )
	{
		if( isset( Controller::get_handler()->handler_vars ) ) {
			foreach ( Controller::get_handler()->handler_vars as $key => $value ) {
				$this->assign( $key, $value );
			}
		}
		$this->template_engine->display( $template_name );
	}

	/**
	 * Helper passthru function to avoid having to always
	 * call $theme->template_engine->assign( 'key', 'value' );
	 */
	public function assign( $key, $value )
	{
		$this->template_engine->assign( $key, $value );
	}
}

?>
