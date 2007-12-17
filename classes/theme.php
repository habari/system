<?php
/**
 * Habari Theme Class
 *
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
	 * We build the Post filters by analyzing the handler_var
		* data which is assigned to the handler ( by the Controller and
		* also, optionally, by the Theme )
		*/
	public $valid_filters= array(
	 'content_type',
		'slug',
		'status',
		'page',
		'tag',
		'not:tag',
		'month',
		'year',
		'day',
		'criteria',
		'limit',
		'nolimit',
		'fetch_fn',
	);

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
	 * Loads a theme's metadata from an XML file in theme's
	 * directory.
	 *
	 * @param theme Name of theme to retrieve metadata about
	 */
	public function info( $theme )
	{
		$xml_file= Site::get_path('user') . '/themes/' . $theme . '/theme.xml';
		if ( $xml_content= file_get_contents( $xml_file ) ) {
			$theme_data= new SimpleXMLElement(  $xml_file );
			// Is it a valid theme xml file?
			if ( isset( $theme_data->theme ) ) {
				$valid_named_elements= array(
					'name',
					'version',
					'template_engine',
					'theme_dir'
				);
				// Assigns based on wether or not it's a valid named element.
				foreach ( $theme_data->theme->children() as $key => $value ) {
					$key= strtolower( $key );
					if ( in_array( $key, $valid_named_elements ) ) {
						$this->$key= $value;
					}
					else {
						$this->config_vars[$key]= $value;
					}
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
		foreach( $template_list as $template ) {
			if( $this->template_engine->template_exists( $template ) ) {
				$this->display( $template );
				return true;
			}
		}
		return false;
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
	public function act_display( $paramarray= array( 'user_filters'=> array() ) )
	{
		extract( $paramarray );

		$where_filters= array();
		$where_filters= array_intersect_key( Controller::get_handler()->handler_vars, array_flip( $this->valid_filters ) );
		//$where_filters['status']= Post::status('published');
		if (array_key_exists('tag', $where_filters))
		{
			$where_filters['tag_slug']=  $where_filters['tag'];
			unset($where_filters['tag']);
		}
		if ( User::identify() )
		{
			$where_filters['status']= Post::status('any');
		}
		else
		{
			$where_filters['status']= Post::status('published');
		}

		if( !isset( $posts ) ) {
			$user_filters= Plugins::filter( 'template_user_filters', $user_filters );
			$user_filters= array_intersect_key( $user_filters, array_flip( $this->valid_filters ) );
			$where_filters= array_merge( $where_filters, $user_filters );

			$posts= Posts::get( $where_filters );
		}

		$this->assign( 'posts', $posts );
		$this->assign( 'page', isset( $page ) ? $page : 1 );

		if ( $posts !== false && count( $posts ) > 0 ) {
			$post= ( count( $posts ) > 1 ) ? $posts[0] : $posts;
			$this->assign( 'post', $post );
			$types= array_flip( Post::list_active_post_types() );
			$type= $types[$post->content_type];
		}
		elseif( $posts === false ) {
			$fallback= array('404');
			header( 'HTTP/1.0 404 Not Found' );
		}

		extract( $where_filters );

		if ( !isset( $fallback ) ) {
			// Default fallbacks based on the number of posts
			$fallback= array( '{$type}.{$id}', '{$type}.{$slug}', '{$type}.tag.{$posttag}' );
			if( count( $posts ) > 1 ) {
				$fallback[]= '{$type}.multiple';
				$fallback[]= 'multiple';
			}
			else {
				$fallback[]= '{$type}.single';
				$fallback[]= 'single';
			}
		}

		$searches= array('{$id}','{$slug}','{$year}','{$month}','{$day}','{$type}','{$tag}',);
		$replacements= array(
			(isset($post) && $post instanceof Post)?$post->id:'-',
			(isset($post) && $post instanceof Post)?$post->slug:'-',
			isset($year)?$year:'-',
			isset($month)?$month:'-',
			isset($day)?$day:'-',
			isset($type)?$type:'-',
			isset($tag)?$tag:'-',
		);
		$fallback[]= 'home';
		$fallback= Plugins::filter( 'template_fallback', $fallback );
		$fallback= array_unique( str_replace($searches, $replacements, $fallback) );
		for($z = 0; $z < count($fallback); $z++) {
			if( (strpos($fallback[$z], '{$posttag}') !== false) && (isset($post)) && ($post instanceof Post)) {
				$replacements= array();
				if( $alltags= $post->tags ) {
					foreach($alltags as $tag_slug => $tag_text ) {
						$replacements[] = str_replace('{$posttag}', $tag_slug, $fallback[$z]);
					}
					array_splice($fallback, $z, 1, $replacements);
				}
				else {
					break;
				}
			}
		}

		return $this->display_fallback( $fallback );
	}

	/**
	 * Helper function: Displays the home page
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_home( $user_filters= array() )
	{
		$paramarray['fallback']= array(
			'home',
			'multiple',
		);

		// Makes sure home displays only entries
		$default_filters= array(
		 'content_type' => Post::type('entry'),
		);

		$paramarray['user_filters']= array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display a post
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_post( $user_filters= array() )
	{
		$paramarray['fallback']= array(
		 '{$type}.{$id}',
		 '{$type}.{$slug}',
		 '{$type}.tag.{$posttag}',
		 '{$type}.single',
		 '{$type}.multiple',
		 'single',
		 'multiple',
		);

		// Does the same as a Post::get()
		$default_filters= array(
		 'fetch_fn' => 'get_row',
		 'limit' => 1,
		);

		// Remove the page from filters.
		$page_key= array_search( 'page', $this->valid_filters );
		unset( $this->valid_filters[$page_key] );

		$paramarray['user_filters']= array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display the posts for a tag
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_tag( $user_filters= array() )
	{
		$paramarray['fallback']= array(
			'tag.{$tag}',
			'tag',
			'multiple',
		);

		// Makes sure home displays only entries
		$default_filters= array(
		 'content_type' => Post::type('entry'),
		);

		$paramarray['user_filters']= array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display the posts for a specific date
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_date( $user_filters= array() )
	{
		$handler_vars= Controller::get_handler()->handler_vars;
		$y = isset( $handler_vars['year'] );
		$m = isset( $handler_vars['month'] );
		$d = isset( $handler_vars['day'] );

		if($y&&$m&&$d) $paramarray['fallback'][]= 'year.{$year}.month.{$month}.day.{$day}';
		if($y&&$m&&$d) $paramarray['fallback'][]= 'year.month.day';
		if($m&&$d) $paramarray['fallback'][]= 'month.{$month}.day.{$day}';
		if($y&&$m) $paramarray['fallback'][]= 'year.{$year}.month.{$month}';
		if($y&&$d) $paramarray['fallback'][]= 'year.{$year}.day.{$day}';
		if($m&&$d) $paramarray['fallback'][]= 'month.day';
		if($y&&$d) $paramarray['fallback'][]= 'year.day';
		if($y&&$m) $paramarray['fallback'][]= 'year.month';
		if($m) $paramarray['fallback'][]= 'month.{$month}';
		if($d) $paramarray['fallback'][]= 'day.{$day}';
		if($y) $paramarray['fallback'][]= 'year.{$year}';
		if($y) $paramarray['fallback'][]= 'year';
		if($m) $paramarray['fallback'][]= 'month';
		if($d) $paramarray['fallback'][]= 'day';
		$paramarray['fallback'][]= 'multiple';
		$paramarray['fallback'][]= 'home';

		$paramarray['user_filters']= $user_filters;
		if ( !isset( $paramarray['user_filters']['content_type'] ) ) {
			$paramarray['user_filters']['content_type']= 'entry';
		}

		return $this->act_display( $paramarray );
	}

	/**
		* Helper function: Display the posts for a specific criteria
		* @param array $user_filters Additional arguments used to get the page content
		*/
	public function act_search( $user_filters= array() )
	{
		$paramarray['fallback']= array(
			'search',
			'multiple',
		);

		$paramarray['user_filters']= $user_filters;

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display a 404 template
	 *
	 * @param array $user_filters Additional arguments user to get the page content
	 */
	public function act_display_404( $user_filters= array() )
	{
		$paramarray['fallback']= array(
			'404',
		);
		$paramarray['user_filters']= $user_filters;
		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Avoids having to call $theme->template_engine->display( 'template_name' );
	 * @param string $template_name The name of the template to display
	 */
	public function display( $template_name )
	{
		$this->add_template_vars();

		if( isset( Controller::get_handler()->handler_vars ) ) {
			foreach ( Controller::get_handler()->handler_vars as $key => $value ) {
				$this->assign( $key, $value );
			}
		}
		$this->assign( 'theme', $this );

		$this->template_engine->display( $template_name );
	}

	public function fetch( $template_name )
	{
		$this->add_template_vars();

		if( isset( Controller::get_handler()->handler_vars ) ) {
			foreach ( Controller::get_handler()->handler_vars as $key => $value ) {
				$this->assign( $key, $value );
			}
		}
		$this->assign( 'theme', $this );

		return $this->template_engine->fetch( $template_name );
	}

	/**
	 * Helper function: Avoids having to call $theme->template_engine->key= 'value';
	 */
	public function assign( $key, $value )
	{
		$this->template_engine->$key= $value;
	}

 	/**
	 * Aggregates and echos the additional header code by combining Plugins and Stack calls.
	 */
	public function header() {
		Plugins::act( 'template_header', $this );
		Stack::out( 'template_stylesheet', '<link rel="stylesheet" type="text/css" href="%s" media="%s">'."\r\n" );
		Stack::out( 'template_header_javascript', '<script src="%s" type="text/javascript"></script>'."\r\n" );
	}

	/**
	 * Aggregates and echos the additional footer code by combining Plugins and Stack calls.
	 */
	public function footer() {
		Plugins::act( 'template_footer', $this );
		Stack::out( 'template_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	}

	/**
	 * Detects if a variable is assigned to the template engine for use in
	 * constructing the template's output.
	 *
	 * @param key name of variable
	 * @returns boolean true if name is set, false if not set
	 */
	public function __isset( $key )
	{
		return isset( $this->template_engine->$key );
	}

	/**
	 * Set a template variable, a property alias for assign()
	 *
	 * @param string $key The template variable to set
	 * @param mixed $value The value of the variable
	 */
	public function __set( $key, $value )
	{
		$this->assign( $key, $value );
	}

	/**
	 * Get a template variable value
	 *
	 * @param string $key The template variable name to get
	 * @return mixed The value of the variable
	 */
	public function __get( $key )
	{
		return $this->template_engine->$key;
	}

	/**
	 * Handle methods called on this class or its descendants that are not defined by this class.
	 * Allow plugins to provide additional theme actions, like a custom act_display_*()
	 *
	 * @param string $function The method that was called.
	 * @param array $params An array of parameters passed to the method
	 **/
	public function __call( $function, $params )
	{
		if(strpos($function, 'act_') === 0) {
			// The first parameter is an array, get it
			if(count($params) > 0) {
				list($user_filters)= $params;
			}
			else {
				$user_filters= array();
			}
			$action = substr($function, 4);
			Plugins::act('theme_action', $action, $this, $user_filters);
		}
		else {
			$return = false;
			array_unshift($params, 'theme_call_' . $function, $return, $this);
			return call_user_func_array(array('Plugins', 'filter'), $params);
		}
	}

}



?>
