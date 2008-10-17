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
class Theme extends Pluggable
{
	private $name= null;
	private $version= null;
	public $template_engine= null;
	public $theme_dir= null;
	public $config_vars= array();
	private $var_stack = array(array());
	private $current_var_stack = 0;

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
		'id',
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
	public function __construct( $themedata )
	{
		$this->name= $themedata->name;
		$this->version= $themedata->version;
		$this->theme_dir= $themedata->theme_dir;
		// Set up the corresponding engine to handle the templating
		$this->template_engine= new $themedata->template_engine();
		$this->template_engine->set_template_dir( $themedata->theme_dir );
		$this->plugin_id= $this->plugin_id();
		$this->load();
	}

	/**
	 * Loads a theme's metadata from an XML file in theme's
	 * directory.
	 *
	 * @param theme Name of theme to retrieve metadata about
	 */
	public function info( $theme )
	{
		$xml_file= Site::get_path( 'user' ) . '/themes/' . $theme . '/theme.xml';
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
		if( !$this->template_engine->assigned( 'user' ) ) {
			$this->assign('user', User::identify() );
		}

		if( !$this->template_engine->assigned( 'page' ) ) {
			$this->assign('page', isset( $this->page ) ? $this->page : 1 );
		}

		$handler= Controller::get_handler();
		if ( isset( $handler ) ) {
			Plugins::act( 'add_template_vars', $this, Controller::get_handler()->handler_vars );
		}
	}

	/**
	 * Find the first template that matches from the list provided and display it
	 * @param array $template_list The list of templates to search for
	 */
	public function display_fallback( $template_list, $display_function = 'display' )
	{
		foreach ( $template_list as $template ) {
			if ( $this->template_exists( $template ) ) {
				return $this->$display_function( $template );
			}
		}
		return false;
	}

	/**
	 * Determine if a template exists in the current theme
	 *
	 * @param string $template_name The name of the template to detect
	 * @return boolean True if template exists
	 */
	public function template_exists( $template_name )
	{
		return $this->template_engine->template_exists( $template_name );
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
		//$where_filters['status']= Post::status( 'published' );
		if ( array_key_exists( 'tag', $where_filters ) ) {
			$where_filters['tag_slug']= Utils::slugify($where_filters['tag']);
			unset( $where_filters['tag'] );
		}
		if ( User::identify() ) {
			$where_filters['status']= Post::status( 'any' );
		}
		else {
			$where_filters['status']= Post::status( 'published' );
		}

		if ( !isset( $posts ) ) {
			$user_filters= Plugins::filter( 'template_user_filters', $user_filters );
			$user_filters= array_intersect_key( $user_filters, array_flip( $this->valid_filters ) );
			$where_filters= array_merge( $where_filters, $user_filters );
			$where_filters= Plugins::filter( 'template_where_filters', $where_filters );

			$posts= Posts::get( $where_filters );
		}

		$this->assign( 'posts', $posts );
		/*
		if( !isset( $this->page ) ) {
			if( isset( $page ) ) {
				$this->assign( 'page', $page );
			}
			elseif( isset( Controller::get_handler()->handler_vars['page'] ) ) {
				$this->assign( 'page', Controller::get_handler()->handler_vars['page'] );
			}
		}*/

		if ( $posts !== false && count( $posts ) > 0 ) {
			$post= ( count( $posts ) > 1 ) ? $posts[0] : $posts;
			$this->assign( 'post', $post );
			$types= array_flip( Post::list_active_post_types() );
			$type= $types[$post->content_type];
		}
		elseif ( $posts === false ) {
			if ($this->template_exists('404')) {
			$fallback= array( '404' );
			header( 'HTTP/1.0 404 Not Found' );
			// Replace template variables with the 404 rewrite rule
			$this->request->{URL::get_matched_rule()->name}= false;
			$this->request->{URL::set_404()->name}= true;	
			$this->matched_rule= URL::get_matched_rule();
			} else {
				$this->display('header');
				echo '<h2>';
				 _e( "Whoops! 404. The page you were trying to access is not really there. Please try again." );
				echo '</h2>';
				header( 'HTTP/1.0 404 Not Found' );
				$this->display('footer');
				die;
			}
		}

		extract( $where_filters );
		$this->assign( 'page', isset($page)? $page:1 );

		if ( !isset( $fallback ) ) {
			// Default fallbacks based on the number of posts
			$fallback= array( '{$type}.{$id}', '{$type}.{$slug}', '{$type}.tag.{$posttag}' );
			if ( count( $posts ) > 1 ) {
				$fallback[]= '{$type}.multiple';
				$fallback[]= 'multiple';
			}
			else {
				$fallback[]= '{$type}.single';
				$fallback[]= 'single';
			}
		}

		$searches= array( '{$id}','{$slug}','{$year}','{$month}','{$day}','{$type}','{$tag}', );
		$replacements= array(
			( isset( $post ) && $post instanceof Post ) ? $post->id : '-',
			( isset( $post ) && $post instanceof Post ) ? $post->slug : '-',
			isset( $year ) ? $year : '-',
			isset( $month ) ? $month : '-',
			isset( $day ) ? $day : '-',
			isset( $type ) ? $type : '-',
			isset( $tag_slug ) ? $tag_slug : '-',
		);
		$fallback[]= 'home';
		$fallback= Plugins::filter( 'template_fallback', $fallback );
		$fallback= array_unique( str_replace( $searches, $replacements, $fallback ) );
		for ( $z= 0; $z < count( $fallback ); $z++ ) {
			if ( ( strpos( $fallback[$z], '{$posttag}' ) !== false ) && ( isset( $post ) ) && ( $post instanceof Post ) ) {
				$replacements= array();
				if ( $alltags= $post->tags ) {
					foreach ( $alltags as $tag_slug => $tag_text ) {
						$replacements[]= str_replace( '{$posttag}', $tag_slug, $fallback[$z] );
					}
					array_splice( $fallback, $z, 1, $replacements );
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
			'content_type' => Post::type( 'entry' ),
		);

		$paramarray['user_filters']= array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Displays multiple entries
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_entries( $user_filters= array() )
	{
		$paramarray['fallback']= array(
		 	'{$type}.multiple',
			'multiple',
		);

		// Makes sure home displays only entries
		$default_filters= array(
			'content_type' => Post::type( 'entry' ),
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

		// Handle comment submissions and default commenter id values
		$cookie= 'comment_' . Options::get( 'GUID' );
		$commenter_name= '';
		$commenter_email= '';
		$commenter_url= '';
		$commenter_content= '';
		$user= User::identify();
		if ( isset( $_SESSION['comment'] ) ) {
			$details= Session::get_set( 'comment' );
			$commenter_name= $details['name'];
			$commenter_email= $details['email'];
			$commenter_url= $details['url'];
			$commenter_content= $details['content'];
		}
		elseif ( $user ) {
			$commenter_name= $user->displayname;
			$commenter_email= $user->email;
			$commenter_url= Site::get_url( 'habari' );
		}
		elseif ( isset( $_COOKIE[$cookie] ) ) {
			list( $commenter_name, $commenter_email, $commenter_url )= explode( '#', $_COOKIE[$cookie] );
		}

		$this->commenter_name= $commenter_name;
		$this->commenter_email= $commenter_email;
		$this->commenter_url= $commenter_url;
		$this->commenter_content= $commenter_content;

		$this->comments_require_id= Options::get( 'comments_require_id' );

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
			'content_type' => Post::type( 'entry' ),
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
		$y= isset( $handler_vars['year'] );
		$m= isset( $handler_vars['month'] );
		$d= isset( $handler_vars['day'] );

		if ( $y && $m && $d ) {
			$paramarray['fallback'][]= 'year.{$year}.month.{$month}.day.{$day}';
		}
		if ( $y && $m && $d) {
			$paramarray['fallback'][]= 'year.month.day';
		}
		if ( $m && $d ) {
			$paramarray['fallback'][]= 'month.{$month}.day.{$day}';
		}
		if ( $y && $m ) {
			$paramarray['fallback'][]= 'year.{$year}.month.{$month}';
		}
		if ( $y && $d ) {
			$paramarray['fallback'][]= 'year.{$year}.day.{$day}';
		}
		if ( $m && $d ) {
			$paramarray['fallback'][]= 'month.day';
		}
		if ( $y && $d ) {
			$paramarray['fallback'][]= 'year.day';
		}
		if ( $y && $m ) {
			$paramarray['fallback'][]= 'year.month';
		}
		if ( $m ) {
			$paramarray['fallback'][]= 'month.{$month}';
		}
		if ( $d ) {
			$paramarray['fallback'][]= 'day.{$day}';
		}
		if ( $y ) {
			$paramarray['fallback'][]= 'year.{$year}';
		}
		if ( $y ) {
			$paramarray['fallback'][]= 'year';
		}
		if ( $m ) {
			$paramarray['fallback'][]= 'month';
		}
		if ( $d ) {
			$paramarray['fallback'][]= 'day';
		}
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
		$this->assign( 'criteria', htmlentities( Controller::get_var('criteria'), ENT_QUOTES, 'UTF-8' ) );
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

		foreach($this->var_stack[$this->current_var_stack] as $key => $value) {
			$this->template_engine->assign( $key, $value );
		}

		$this->template_engine->assign( 'theme', $this );
		$this->template_engine->display( $template_name );
	}

	/**
	 * Helper function: Avoids having to call $theme->template_engine->fetch( 'template_name' );
	 *
	 * @param string $template_name The name of the template to display
	 * @param boolean $unstack If true, end the current template variable buffer upon returning
	 * @return string The content of the template
	 */
	public function fetch( $template_name, $unstack = false )
	{
		foreach($this->var_stack[$this->current_var_stack] as $key => $value) {
			$this->template_engine->assign( $key, $value );
		}

		$this->add_template_vars();

		$this->assign( 'theme', $this );

		$return = $this->fetch_unassigned( $template_name );
		if ( $unstack ) {
			$this->end_buffer();
		}
		return $return;
	}

	/**
	 * Calls the template engine's fetch() method without pre-assigning template variables.
	 * Assumes that the template variables have already been set.
	 *
	 * @param string $template_name The name of the template to display
	 * @return string The content of the template
	 */
	public function fetch_unassigned( $template_name )
	{
		return $this->template_engine->fetch( $template_name );
	}

	/**
	 * Helper function: Avoids having to call $theme->template_engine->key= 'value';
	 */
	public function assign( $key, $value )
	{
		$this->var_stack[$this->current_var_stack][$key] = $value;
	}

 	/**
	 * Aggregates and echos the additional header code by combining Plugins and Stack calls.
	 */
	public function theme_header( $theme )
	{
		Plugins::act( 'template_header', $theme );
		$output= Stack::get( 'template_stylesheet', '<link rel="stylesheet" type="text/css" href="%s" media="%s">'."\r\n" );
		$output.= Stack::get( 'template_header_javascript', '<script src="%s" type="text/javascript"></script>'."\r\n" );
		return $output;
	}

	/**
	 * Aggregates and echos the additional footer code by combining Plugins and Stack calls.
	 */
	public function theme_footer( $theme )
	{
		Plugins::act( 'template_footer', $theme );
		$output= Stack::get( 'template_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
		return $output;
	}

	/**
	 * Display an object using a template designed for the type of object it is
	 * The $object is assigned into the theme using the $content template variable
	 *
	 * @param Theme $theme The theme used to display the object
	 * @param object $object An object to display
	 * @return
	 */
	public function theme_content( $theme, $object )
	{
		$fallback = array(
			"content",
		);
		if( $object instanceof IsContent ) {
			$content_type = $object->content_type();
			array_unshift($fallback, $content_type);
		}
		$theme->content = $object;
		$this->display_fallback( $fallback, 'fetch_unassigned' );
	}

	/**
	 * Returns the appropriate alternate feed based on the currently matched rewrite rule.
	 *
	 * @param mixed $return Incoming return value from other plugins
	 * @param Theme $theme The current theme object
	 * @return string Link to the appropriate alternate Atom feed
	 */
	public function theme_feed_alternate( $theme )
	{
		$matched_rule= URL::get_matched_rule();
		if ( is_object( $matched_rule ) ) {
			// This is not a 404
			$rulename= $matched_rule->name;
		}
		else {
			// If this is a 404 and no rewrite rule matched the request
			$rulename= '';
		}
		switch ( $rulename ) {
			case 'display_entry':
			case 'display_page':
				return URL::get( 'atom_entry', array( 'slug' => Controller::get_var( 'slug' ) ) );
				break;
			case 'display_entries_by_tag':
				return URL::get( 'atom_feed_tag', array( 'tag' => Controller::get_var( 'tag' ) ) );
				break;
			case 'display_home':
			default:
				return URL::get( 'atom_feed', array( 'index' => '1' ) );
		}
		return '';
	}


	/**
	 * Returns the feedback URL to which comments should be submitted for the indicated Post
	 *
	 * @param Theme $theme The current theme
	 * @param Post $post The post object to get the feedback URL for
	 * @return string The URL to the feedback entrypoint for this comment
	 */
	public function theme_comment_form_action( $theme, $post )
	{
		return URL::get( 'submit_feedback', array( 'id' => $post->id ) );
	}

	/**
	 * Build a collection of paginated URLs to be used for pagination.
	 *
	 * @param integer Current page
	 * @param integer Total pages
	 * @param string The RewriteRule name used to build the links.
	 * @param array Various settings used by the method and the RewriteRule.
	 * @return string Collection of paginated URLs built by the RewriteRule.
	 **/
	public static function theme_page_selector( $theme, $rr_name= NULL, $settings= array() )
	{
		$current= $theme->page;
		$total= Utils::archive_pages( $theme->posts->count_all() );

		// Make sure the current page is valid
		if ( $current > $total ) {
			$current= $total;
		}
		else if ( $current < 1 ) {
			$current= 1;
		}

		// Number of pages to display on each side of the current page.
		$leftSide= isset( $settings['leftSide'] ) ? $settings['leftSide'] : 1;
		$rightSide= isset( $settings['rightSide'] ) ? $settings['rightSide'] : 1;

		// Add the page '1'.
		$pages[]= 1;

		// Add the pages to display on each side of the current page, based on $leftSide and $rightSide.
		for ( $i= max( $current - $leftSide, 2 ); $i < $total && $i <= $current + $rightSide; $i++ ) {
			$pages[]= $i;
		}

		// Add the last page if there is more than one page.
		if ( $total > 1 ) {
			$pages[]= (int) $total;
		}

		// Sort the array by natural order.
		natsort( $pages );

		// This variable is used to know the last page processed by the foreach().
		$prevpage= 0;
		// Create the output variable.
		$out= '';

		foreach ( $pages as $page ) {
			$settings['page']= $page;

			// Add ... if the gap between the previous page is higher than 1.
			if ( ($page - $prevpage) > 1 ) {
				$out.= '&nbsp;&hellip;';
			}
			// Wrap the current page number with square brackets.
			$caption= ( $page == $current ) ?  $current  : $page;
			// Build the URL using the supplied $settings and the found RewriteRules arguments.
			$url= URL::get( $rr_name, $settings , false );
			// Build the HTML link.
			$out.= '&nbsp;<a href="' . $url . '" ' . ( ( $page == $current ) ? 'class="current-page"' : '' ) . '>' . $caption . '</a>';

			$prevpage= $page;
		}

		return $out;
	}

	/**
	*Provides a link to the previous page
	*
	* @param string $text text to display for link
	*/
	public function theme_prev_page_link( $theme, $text= NULL )
	{
		$settings= array();

		// If there's no previous page, skip and return null
		$settings['page']= (int) ( $theme->page - 1);
		if ($settings['page'] < 1) {
			return null;
		}

		// If no text was supplied, use default text
		if ($text == '') {
			$text= '&larr; ' . _t( 'Previous' );
		}

		return '<a class="prev-page" href="' . URL::get(null, $settings, false) . '" title="' . $text . '">' . $text . '</a>';
	}

	/**
	*Provides a link to the next page
	*
	* @param string $text text to display for link
	*/
	public function theme_next_page_link( $theme, $text= NULL )
	{
		$settings= array();

		// If there's no next page, skip and return null
		$settings['page']= (int) ( $theme->page + 1);
		if ($settings['page'] > Utils::archive_pages( $theme->posts->count_all() )) {
			return null;
		}

		// If no text was supplied, use default text
		if ($text == '') {
			$text= _t( 'Next' ) . ' &rarr;';
		}

		return '<a class="next-page" href="' . URL::get(null, $settings, false) . '" title="' . $text . '">' . $text . '</a>';
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
		return isset( $this->var_stack[$this->current_var_stack][$key] );
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
		if ( isset( $this->var_stack[$this->current_var_stack][$key] ) ) {
			return $this->var_stack[$this->current_var_stack][$key];
		}
		return '';
	}

	/**
	 * Remove a template variable value
	 *
	 * @param string $key The template variable name to unset
	 */
	public function __unset( $key )
	{
		unset($this->var_stack[$this->current_var_stack][$key]);
	}

	/**
	 * Start a new template variable buffer
	 */
	public function start_buffer()
	{
		$this->current_var_stack++;
		$this->var_stack[$this->current_var_stack] = $this->var_stack[$this->current_var_stack - 1];
	}

	/**
	 * End the current template variable buffer
	 */
	public function end_buffer()
	{
		unset($this->var_stack[$this->current_var_stack]);
		$this->current_var_stack--;
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
		if ( strpos( $function, 'act_' ) === 0 ) {
			// The first parameter is an array, get it
			if ( count( $params ) > 0 ) {
				list( $user_filters )= $params;
			}
			else {
				$user_filters= array();
			}
			$action= substr( $function, 4 );
			Plugins::act( 'theme_action', $action, $this, $user_filters );
		}
		else {
			$purposed= 'output';
			if ( preg_match( '%^(.*)_(return|end)$%', $function, $matches ) ) {
				$purposed= $matches[2];
				$function= $matches[1];
			}
			array_unshift( $params, $function, $this );
			$result= call_user_func_array( array( 'Plugins', 'theme' ), $params );
			switch( $purposed ) {
				case 'return':
					return $result;
				case 'end':
					echo end( $result );
					return end( $result );
				default:
					$output= implode( '', ( array ) $result );
					echo $output;
					return $output;
			}
		}
	}
}
?>
