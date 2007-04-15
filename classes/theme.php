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
	* Assign the default variables that would be used in every template
	*/	
	public function add_template_vars()
	{
		$handler= Controller::get_handler();
		if( isset( $handler ) ) {
			Plugins::act('add_template_vars', $this, Controller::get_handler()->handler_vars);
		}
	}
	
	/**
	* Find the first template that matches from the list provided and display it
	* @param array $template_list The list of templates to search for
	*/
	public function display_fallback($template_list)
	{
		foreach($template_list as $template) {
			if( $this->template_engine->template_exists( $template ) ) {
				$this->display( $template );
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Display the home page
	 * @param array $userquery Additional arguments used to get the page content
	 */	 	 	
	public function act_display_home( $userquery= array() )
	{
		$query= array( 'content_type' => 'entry', 'status' => Post::status('published') );
		$query= array_merge( $query, $userquery );
		$posts= Posts::get( $query );
		$this->assign( 'posts', $posts );
		$this->assign( 'post', reset($posts) );
		return $this->display_fallback(array('home', 'multiple'));
	}
	
	/**
	 * Display a post
	 * @param array $userquery Additional arguments used to get the page content
	 */	 	 	
	public function act_display_post( $userquery= array() )
	{
		$query= array( 'slug' => Controller::get_handler()->handler_vars['slug'], 'status' => Post::status('published') );
		$query= array_merge( $query, $userquery );
		$post= Post::get( $query );
		$this->assign( 'post', $post );
		$this->assign( 'posts', new Posts(array($post)) );
		
		$types= array_flip( Post::list_post_types() );
		$type= $types[$post->content_type]; 

		return $this->display_fallback(array(
			"{$type}.{$post->id}", 
			"{$type}.single", 
			"{$type}.multiple",
			"single",
			"multiple", 
			"home",
		));
	}

	/**
	 * Display the posts for a tag
	 * @param array $userquery Additional arguments used to get the page content
	 */	 	 	
	public function act_display_tag( $userquery= array() )
	{
		$tag= Controller::get_handler()->handler_vars['tag'];
		$query= array( 'content_type' => 'entry', 'tag' => $tag, 'status' => Post::status('published') );
		if( isset(Controller::get_handler()->handler_vars['page']) ) {
			$query['page']= Controller::get_handler()->handler_vars['page'];
		}
		$query= array_merge( $query, $userquery );
		$posts= Posts::get( $query );
		$this->assign( 'posts', $posts );
		$this->assign( 'post', reset($posts) );

		return $this->display_fallback(array(
			"tag.{$tag}",
			"tag",
			"multiple", 
			"home",
		));
	}

	/**
	 * Display the post for a specific year
	 * @param array $userquery Additional arguments used to get the page content
	 */	 	 	
	public function act_display_year( $userquery= array() )
	{
		$year= Controller::get_handler()->handler_vars['year'];
		$query= array( 'content_type' => 'entry', 'year' => $year, 'status' => Post::status('published') );
		if( isset(Controller::get_handler()->handler_vars['page']) ) {
			$query['page']= Controller::get_handler()->handler_vars['page'];
		}
		$query= array_merge( $query, $userquery );
		$posts= Posts::get( $query );
		$this->assign( 'posts', $posts );
		$this->assign( 'post', reset($posts) );

		return $this->display_fallback(array(
			"year.{$year}",
			"year",
			"multiple", 
			"home",
		));
	}

	/**
	 * Display the posts for a specific month
	 * @param array $userquery Additional arguments used to get the page content
	 */	 	 	
	public function act_display_month( $userquery= array() )
	{
		$year= Controller::get_handler()->handler_vars['year'];
		$month= Controller::get_handler()->handler_vars['month'];
		$query= array( 'content_type' => 'entry', 'year' => $year, 'month' => $month, 'status' => Post::status('published') );
		if( isset(Controller::get_handler()->handler_vars['page']) ) {
			$query['page']= Controller::get_handler()->handler_vars['page'];
		}
		$query= array_merge( $query, $userquery );
		$posts= Posts::get( $query );
		$this->assign( 'posts', $posts );
		$this->assign( 'post', reset($posts) );

		return $this->display_fallback(array(
			"year.{$year}.month.{$month}",
			"month.{$month}",
			"year.{$year}",
			"month",
			"year",
			"multiple", 
			"home",
		));
	}

	/**
	 * Display the posts for a specific date
	 * @param array $userquery Additional arguments used to get the page content
	 */	 	 	
	public function act_display_date( $userquery= array() )
	{
		$year= Controller::get_handler()->handler_vars['year'];
		$month= Controller::get_handler()->handler_vars['month'];
		$day= Controller::get_handler()->handler_vars['day'];
		$query= array( 'content_type' => 'entry', 'year' => $year, 'month' => $month, 'day' => $day, 'status' => Post::status('published') );
		if( isset(Controller::get_handler()->handler_vars['page']) ) {
			$query['page']= Controller::get_handler()->handler_vars['page'];
		}
		$query= array_merge( $query, $userquery );
		$posts= Posts::get( $query );
		$this->assign( 'posts', $posts );
		$this->assign( 'post', reset($posts) );

		return $this->display_fallback(array(
			"year.{$year}.month.{$month}.day.{$day}",
			"month.{$month}.day.{$day}",
			"year.{$year}.day.{$day}",
			"day.{$day}",
			"year.month.day",
			"month.day",
			"year.day",
			"day",
			"month",
			"year",
			"multiple", 
			"home",
		));
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
	public function act_display()
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

		$this->assign( 'posts', $posts );
		$this->assign( 'post', $posts[0] );

		$types= array_flip( Post::list_post_types() );
		$type= $types[$posts[0]->content_type]; 

		$fallback = array("{$type}.{$posts[0]->id}");
		if( count( $posts ) > 1 ) {
			$fallback[]= "{$type}.multiple";
			$fallback[]= "multiple"; 
		}
		else {
			$fallback[]= "{$type}.single";
			$fallback[]= "single"; 
		}
		$fallback[]= "home";
			
		$this->display_fallback( $fallback );
		
		return true;
	}
	
	public function act_display_posts()
	{
		$this->act_display();
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
		$this->add_template_vars();

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
