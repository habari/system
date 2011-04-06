<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Theme Class
 *
 * The Theme class is the behind-the-scenes representation of
 * of a set of UI files that compose the visual theme of the blog
 *
 */
class Theme extends Pluggable
{
	public $name = null;
	public $version = null;
	public $template_engine = null;
	public $theme_dir = null;
	public $config_vars = array();
	private $var_stack = array( array() );
	private $current_var_stack = 0;

	/**
	 * We build the Post filters by analyzing the handler_var
	 * data which is assigned to the handler ( by the Controller and
	 * also, optionally, by the Theme )
	 */
	public $valid_filters = array(
		'content_type',
		'not:content_type',
		'slug',
		'not:slug',
		'user_id',
		'vocabulary',
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
		'offset',
		'fetch_fn',
		'id',
		'info',
		'has:info',
		'all:info',
		'any:info',
		'not:info',
		'not:all:info',
		'not:any:info',
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
		$this->name = $themedata->name;
		$this->version = $themedata->version;
		$this->theme_dir = $themedata->theme_dir;
		// Set up the corresponding engine to handle the templating
		$this->template_engine = new $themedata->template_engine();
		$this->template_engine->set_template_dir( $themedata->theme_dir );
		$this->plugin_id = $this->plugin_id();
		$this->load();
	}

	/**
	 * Loads a theme's metadata from an XML file in theme's
	 * directory.
	 *
	 */
	public function info()
	{

		$xml_file = $this->theme_dir . '/theme.xml';
		if(!file_exists($xml_file)) {
			return new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?>
<pluggable type="theme">
	<name>Unknown Theme</name>
	<version>1.0</version>
</pluggable>
');
		}
		if ( $xml_content = file_get_contents( $xml_file ) ) {
			$theme_data = new SimpleXMLElement( $xml_content );
			return $theme_data;
		}
	}

	/**
	 * Assign the default variables that would be used in every template
	 */
	public function add_template_vars()
	{
		if ( !$this->template_engine->assigned( 'user' ) ) {
			$this->assign( 'user', User::identify() );
		}

		if ( !$this->template_engine->assigned( 'loggedin' ) ) {
			$this->assign( 'loggedin', User::identify()->loggedin );
		}

		if ( !$this->template_engine->assigned( 'page' ) ) {
			$this->assign( 'page', isset( $this->page ) ? $this->page : 1 );
		}

		$handler = Controller::get_handler();
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
		foreach ( (array)$template_list as $template ) {
			if ( $this->template_exists( $template ) ) {
				$this->assign( '_template_list', $template_list );
				$this->assign( '_template', $template );
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
	public function act_display( $paramarray = array( 'user_filters'=> array() ) )
	{
		Utils::check_request_method( array( 'GET', 'HEAD', 'POST' ) );

		// Get any full-query parameters
		$possible = array( 'user_filters', 'fallback', 'posts', 'post', 'content_type' );
		foreach ( $possible as $varname ) {
			if ( isset( $paramarray[$varname] ) ) {
				$$varname = $paramarray[$varname];
			}
		}

		$where_filters = array();
		$where_filters = Controller::get_handler()->handler_vars->filter_keys( $this->valid_filters );
		$where_filters['vocabulary'] = array();

		if ( array_key_exists( 'tag', $where_filters ) ) {
			$tags = Tags::parse_url_tags( $where_filters['tag'] );
			$not_tag = $tags['exclude_tag'];
			$all_tag = $tags['include_tag'];
			if ( count( $not_tag ) > 0 ) {
				$where_filters['vocabulary'] = array_merge( $where_filters['vocabulary'], array( Tags::vocabulary()->name . ':not:term' => $not_tag ) );
			}
			if ( count( $all_tag ) > 0 ) {
				$where_filters['vocabulary'] = array_merge( $where_filters['vocabulary'], array( Tags::vocabulary()->name . ':all:term' => $all_tag ) );
			}
			$where_filters['tag_slug'] = Utils::slugify( $where_filters['tag'] );
			unset( $where_filters['tag'] );
		}
		if ( !isset( $_GET['preview'] ) ) {
			$where_filters['status'] = Post::status( 'published' );
		}

		if ( !isset( $posts ) ) {
			$user_filters = Plugins::filter( 'template_user_filters', $user_filters );
			$user_filters = array_intersect_key( $user_filters, array_flip( $this->valid_filters ) );

			// Work around the tags parameters to Posts::get() being subsumed by the vocabulary parameter
			if( isset( $user_filters['not:tag'] ) ) {
				$user_filters['vocabulary'] = array( Tags::vocabulary()->name . ':not:term' => $user_filters['not:tag'] );
				unset( $user_filters['not:tag'] );
			}
			if( isset( $user_filters['tag'] ) ) {
				$user_filters['vocabulary'] = array( Tags::vocabulary()->name . ':term_display' => $user_filters['tag'] );
				unset( $user_filters['tag'] );
			}

			$where_filters = $where_filters->merge( $user_filters );
			$where_filters = Plugins::filter( 'template_where_filters', $where_filters );
			$posts = Posts::get( $where_filters );
		}

		$this->assign( 'posts', $posts );


		if ( $posts !== false && count( $posts ) > 0 ) {
			if ( count( $posts ) == 1 ) {
				$post = $posts instanceof Post ? $posts : reset( $posts );
				Stack::add( 'body_class', Post::type_name( $post->content_type ) . '-' . $post->id );
			}
			else {
				$post = reset( $posts );
				Stack::add( 'body_class', 'multiple' );
			}
			$this->assign( 'post', $post );
			$type = Post::type_name( $post->content_type );
		}
		elseif ( ( $posts === false ) ||
			( isset( $where_filters['page'] ) && $where_filters['page'] > 1 && count( $posts ) == 0 ) ) {
			if ( $this->template_exists( '404' ) ) {
				$fallback = array( '404' );
				// Replace template variables with the 404 rewrite rule
				$this->request->{URL::get_matched_rule()->name} = false;
				$this->request->{URL::set_404()->name} = true;
				$this->matched_rule = URL::get_matched_rule();
				// 404 status header sent in act_display_404, but we're past
				// that, so send it now.
				header( 'HTTP/1.1 404 Not Found', true, 404 );
			}
			else {
				$this->display( 'header' );
				echo '<h2>';
				_e( "Whoops! 404. The page you were trying to access is not really there. Please try again." );
				echo '</h2>';
				header( 'HTTP/1.1 404 Not Found', true, 404 );
				$this->display( 'footer' );
				die;
			}
		}

		$extract = $where_filters->filter_keys( 'page', 'type', 'id', 'slug', 'posttag', 'year', 'month', 'day', 'tag', 'tag_slug' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}

		$this->assign( 'page', isset( $page )? $page:1 );

		if ( !isset( $fallback ) ) {
			// Default fallbacks based on the number of posts
			$fallback = array( '{$type}.{$id}', '{$type}.{$slug}', '{$type}.tag.{$posttag}' );
			if ( count( $posts ) > 1 ) {
				$fallback[] = '{$type}.multiple';
				$fallback[] = 'multiple';
			}
			else {
				$fallback[] = '{$type}.single';
				$fallback[] = 'single';
			}
		}

		$searches = array( '{$id}','{$slug}','{$year}','{$month}','{$day}','{$type}','{$tag}', );
		$replacements = array(
			( isset( $post ) && $post instanceof Post ) ? $post->id : '-',
			( isset( $post ) && $post instanceof Post ) ? $post->slug : '-',
			isset( $year ) ? $year : '-',
			isset( $month ) ? $month : '-',
			isset( $day ) ? $day : '-',
			isset( $type ) ? $type : '-',
			isset( $tag_slug ) ? $tag_slug : '-',
		);
		$fallback[] = 'home';
		$fallback = Plugins::filter( 'template_fallback', $fallback, $posts, isset( $post ) ? $post : null );
		$fallback = array_values( array_unique( MultiByte::str_replace( $searches, $replacements, $fallback ) ) );
		for ( $z = 0; $z < count( $fallback ); $z++ ) {
			if ( ( MultiByte::strpos( $fallback[$z], '{$posttag}' ) !== false ) && ( isset( $post ) ) && ( $post instanceof Post ) ) {
				$replacements = array();
				if ( $alltags = $post->tags ) {
					foreach ( $alltags as $current_tag ) {
						$replacements[] = MultiByte::str_replace( '{$posttag}', $current_tag->term, $fallback[$z] );
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
	public function act_display_home( $user_filters = array() )
	{
		$paramarray['fallback'] = array(
			'home',
			'multiple',
		);

		// Makes sure home displays only entries
		$default_filters = array(
			'content_type' => Post::type( 'entry' ),
		);

		$paramarray['user_filters'] = array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Displays multiple entries
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_entries( $user_filters = array() )
	{
		$paramarray['fallback'] = array(
			'{$type}.multiple',
			'multiple',
		);

		// Makes sure home displays only entries
		$default_filters = array(
			'content_type' => Post::type( 'entry' ),
		);

		$paramarray['user_filters'] = array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display a post
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_post( $user_filters = array() )
	{
		$paramarray['fallback'] = array(
			'{$type}.{$id}',
			'{$type}.{$slug}',
			'{$type}.tag.{$posttag}',
			'{$type}.single',
			'{$type}.multiple',
			'single',
			'multiple',
		);

		// Does the same as a Post::get()
		$default_filters = array(
			'fetch_fn' => 'get_row',
			'limit' => 1,
		);

		// Remove the page from filters.
		$page_key = array_search( 'page', $this->valid_filters );
		unset( $this->valid_filters[$page_key] );

		$paramarray['user_filters'] = array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display the posts for a tag
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_tag( $user_filters = array() )
	{
		$paramarray['fallback'] = array(
			'tag.{$tag}',
			'tag',
			'multiple',
		);

		// Makes sure home displays only entries
		$default_filters = array(
			'content_type' => Post::type( 'entry' ),
		);

		$this->assign( 'tag', Controller::get_var( 'tag' ) );

		// Assign tag objects to the theme
		$tags = Tags::parse_url_tags( Controller::get_var( 'tag' ), true );
		$this->assign( 'include_tag', $tags['include_tag'] );
		$this->assign( 'exclude_tag', $tags['exclude_tag'] );
		$paramarray['user_filters'] = array_merge( $default_filters, $user_filters );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display the posts for a specific date
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_display_date( $user_filters = array() )
	{
		$handler_vars = Controller::get_handler()->handler_vars;
		$y = isset( $handler_vars['year'] );
		$m = isset( $handler_vars['month'] );
		$d = isset( $handler_vars['day'] );

		if ( $y && $m && $d ) {
			$paramarray['fallback'][] = 'year.{$year}.month.{$month}.day.{$day}';
		}
		if ( $y && $m && $d ) {
			$paramarray['fallback'][] = 'year.month.day';
		}
		if ( $m && $d ) {
			$paramarray['fallback'][] = 'month.{$month}.day.{$day}';
		}
		if ( $y && $m ) {
			$paramarray['fallback'][] = 'year.{$year}.month.{$month}';
		}
		if ( $y && $d ) {
			$paramarray['fallback'][] = 'year.{$year}.day.{$day}';
		}
		if ( $m && $d ) {
			$paramarray['fallback'][] = 'month.day';
		}
		if ( $y && $d ) {
			$paramarray['fallback'][] = 'year.day';
		}
		if ( $y && $m ) {
			$paramarray['fallback'][] = 'year.month';
		}
		if ( $m ) {
			$paramarray['fallback'][] = 'month.{$month}';
		}
		if ( $d ) {
			$paramarray['fallback'][] = 'day.{$day}';
		}
		if ( $y ) {
			$paramarray['fallback'][] = 'year.{$year}';
		}
		if ( $y ) {
			$paramarray['fallback'][] = 'year';
		}
		if ( $m ) {
			$paramarray['fallback'][] = 'month';
		}
		if ( $d ) {
			$paramarray['fallback'][] = 'day';
		}
		$paramarray['fallback'][] = 'date';
		$paramarray['fallback'][] = 'multiple';
		$paramarray['fallback'][] = 'home';

		$paramarray['user_filters'] = $user_filters;
		if ( !isset( $paramarray['user_filters']['content_type'] ) ) {
			$paramarray['user_filters']['content_type'] = Post::type( 'entry' );
		}

		$this->assign( 'year', $y ? (int)Controller::get_var( 'year' ) : null );
		$this->assign( 'month', $m ? (int)Controller::get_var( 'month' ) : null );
		$this->assign( 'day', $d ? (int)Controller::get_var( 'day' ) : null );

		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display the posts for a specific criteria
	 * @param array $user_filters Additional arguments used to get the page content
	 */
	public function act_search( $user_filters = array() )
	{
		$paramarray['fallback'] = array(
			'search',
			'multiple',
		);

		$paramarray['user_filters'] = $user_filters;

		$this->assign( 'criteria', Controller::get_var( 'criteria' ) );
		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Display a 404 template
	 *
	 * @param array $user_filters Additional arguments user to get the page content
	 */
	public function act_display_404( $user_filters = array() )
	{
		$paramarray['fallback'] = array(
			'404',
		);

		header( 'HTTP/1.1 404 Not Found' );
		$paramarray['user_filters'] = $user_filters;
		return $this->act_display( $paramarray );
	}

	/**
	 * Helper function: Avoids having to call $theme->template_engine->display( 'template_name' );
	 * @param string $template_name The name of the template to display
	 */
	public function display( $template_name )
	{
		$this->add_template_vars();

		$this->play_var_stack();

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
		$this->play_var_stack();

		$this->add_template_vars();

		$this->template_engine->assign( 'theme', $this );

		$return = $this->fetch_unassigned( $template_name );
		if ( $unstack ) {
			$this->end_buffer();
		}
		return $return;
	}

	/**
	 * Play back the full stack of template variables to assign them into the template
	 */
	protected function play_var_stack()
	{
		$this->template_engine->clear();
		for ( $z = 0; $z <= $this->current_var_stack; $z++ ) {
			foreach ( $this->var_stack[$z] as $key => $value ) {
				$this->template_engine->assign( $key, $value );
			}
		}
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
		
		// create a stack of the atom tags before the first action so they can be unset if desired
		Stack::add( 'template_atom', array( 'alternate', 'application/atom+xml', 'Atom 1.0', implode( '', $this->feed_alternate_return() ) ), 'atom' );
		Stack::add( 'template_atom', array( 'edit', 'application/atom+xml', 'Atom Publishing Protocol', URL::get( 'atompub_servicedocument' ) ), 'app' );
		Stack::add( 'template_atom', array( 'EditURI', 'application/rsd+xml', 'RSD', URL::get( 'rsd' ) ), 'rsd' );
		
		Plugins::act( 'template_header', $theme );
		
		$atom = Stack::get( 'template_atom', '<link rel="%1$s" type="%2$s" title="%3$s" href="%4$s">' );
		$styles = Stack::get( 'template_stylesheet', array( 'Stack', 'styles' ) );
		$scripts = Stack::get( 'template_header_javascript', array( 'Stack', 'scripts' ) );
		
		$output = implode( "\n", array( $atom, $styles, $scripts ) );
		
		Plugins::act( 'template_header_after', $theme );
		
		return $output;
	}

	/**
	 * Aggregates and echos the additional footer code by combining Plugins and Stack calls.
	 */
	public function theme_footer( $theme )
	{
		Plugins::act( 'template_footer', $theme );
		$output = Stack::get( 'template_footer_stylesheet', array( 'Stack', 'styles' ) );
		$output .= Stack::get( 'template_footer_javascript', array( 'Stack', 'scripts' ) );
		return $output;
	}

	/**
	 * Display an object using a template designed for the type of object it is
	 * The $object is assigned into the theme using the $content template variable
	 *
	 * @param Theme $theme The theme used to display the object
	 * @param object $object An object to display
	 * @param string $context The context in which the object will be displayed
	 * @return
	 */
	public function theme_content( $theme, $object, $context = null )
	{
		$fallback = array();
		$content_types = array();
		if ( $object instanceof IsContent ) {
			$content_types = Utils::single_array( $object->content_type() );
		}
		if ( is_object( $object ) ) {
			$content_types[] = strtolower( get_class( $object ) );
		}
		$content_types[] = 'content';
		$content_types = array_flip( $content_types );
		if ( isset( $context ) ) {
			foreach ( $content_types as $type => $type_id ) {
				$content_type = $context . $object->content_type();
				$fallback[] = strtolower( $context . '.' . $type );
			}
		}
		foreach ( $content_types as $type => $type_id ) {
			$fallback[] = strtolower( $type );
		}
		if ( isset( $context ) ) {
			$fallback[] = strtolower( $context );
		}
		$fallback = array_unique( $fallback );

		$this->content = $object;
		return $this->display_fallback( $fallback, 'fetch' );
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
		$matched_rule = URL::get_matched_rule();
		if ( is_object( $matched_rule ) ) {
			// This is not a 404
			$rulename = $matched_rule->name;
		}
		else {
			// If this is a 404 and no rewrite rule matched the request
			$rulename = '';
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
	 * @param string The RewriteRule name used to build the links.
	 * @param array Various settings used by the method and the RewriteRule.
	 * @return string Collection of paginated URLs built by the RewriteRule.
	 */
	public static function theme_page_selector( $theme, $rr_name = null, $settings = array() )
	{
		// We can't detect proper pagination if $theme->posts isn't a Posts object, 
		// so if it's not, bail.
		if(!$theme->posts instanceof Posts) {
			return '';
		}
		$current = $theme->page;
		$items_per_page = isset( $theme->posts->get_param_cache['limit'] ) ?
			$theme->posts->get_param_cache['limit'] :
			Options::get( 'pagination' );
		$total = Utils::archive_pages( $theme->posts->count_all(), $items_per_page );

		// Make sure the current page is valid
		if ( $current > $total ) {
			$current = $total;
		}
		else if ( $current < 1 ) {
			$current = 1;
		}

		// Number of pages to display on each side of the current page.
		$leftSide = isset( $settings['leftSide'] ) ? $settings['leftSide'] : 1;
		$rightSide = isset( $settings['rightSide'] ) ? $settings['rightSide'] : 1;

		// Add the page '1'.
		$pages[] = 1;

		// Add the pages to display on each side of the current page, based on $leftSide and $rightSide.
		for ( $i = max( $current - $leftSide, 2 ); $i < $total && $i <= $current + $rightSide; $i++ ) {
			$pages[] = $i;
		}

		// Add the last page if there is more than one page.
		if ( $total > 1 ) {
			$pages[] = (int) $total;
		}

		// Sort the array by natural order.
		natsort( $pages );

		// This variable is used to know the last page processed by the foreach().
		$prevpage = 0;
		// Create the output variable.
		$out = '';

		if ( 1 === count( $pages ) && isset( $settings['hideIfSinglePage'] ) &&  $settings['hideIfSinglePage'] === true ) {
			return '';
		}

		foreach ( $pages as $page ) {
			$settings['page'] = $page;

			// Add ... if the gap between the previous page is higher than 1.
			if ( ( $page - $prevpage ) > 1 ) {
				$out .= '&nbsp;<span class="sep">&hellip;</span>';
			}
			// Wrap the current page number with square brackets.
			$caption = ( $page == $current ) ?  $current  : $page;
			// Build the URL using the supplied $settings and the found RewriteRules arguments.
			$url = URL::get( $rr_name, $settings, false );
			// Build the HTML link.
			$out .= '&nbsp;<a href="' . $url . '" ' . ( ( $page == $current ) ? 'class="current-page"' : '' ) . '>' . $caption . '</a>';

			$prevpage = $page;
		}

		return $out;
	}

	/**
	 *Provides a link to the previous page
	 *
	 * @param string $text text to display for link
	 */
	public function theme_prev_page_link( $theme, $text = null )
	{
		$settings = array();

		// If there's no previous page, skip and return null
		$settings['page'] = (int) ( $theme->page - 1 );
		if ( $settings['page'] < 1 ) {
			return null;
		}

		// If no text was supplied, use default text
		if ( $text == '' ) {
			$text = '&larr; ' . _t( 'Previous' );
		}

		return '<a class="prev-page" href="' . URL::get( null, $settings, false ) . '" title="' . $text . '">' . $text . '</a>';
	}

	/**
	 *Provides a link to the next page
	 *
	 * @param string $text text to display for link
	 */
	public function theme_next_page_link( $theme, $text = null )
	{
		$settings = array();

		// If there's no next page, skip and return null
		$settings['page'] = (int) ( $theme->page + 1 );
		$items_per_page = isset( $theme->posts->get_param_cache['limit'] ) ?
			$theme->posts->get_param_cache['limit'] :
			Options::get( 'pagination' );
		$total = Utils::archive_pages( $theme->posts->count_all(), $items_per_page );
		if ( $settings['page'] > $total ) {
			return null;
		}

		// If no text was supplied, use default text
		if ( $text == '' ) {
			$text = _t( 'Next' ) . ' &rarr;';
		}

		return '<a class="next-page" href="' . URL::get( null, $settings, false ) . '" title="' . $text . '">' . $text . '</a>';
	}

	/**
	 * Returns a full qualified URL of the specified post based on the comments count, and links to the post.
 	 *
	 * Passed strings are localized prior to parsing therefore to localize "%d Comments" in french, it would be "%d Commentaires".
	 *
	 * Since we use sprintf() in the final concatenation, you must format passed strings accordingly.
	 *
	 * @param Theme $theme The current theme object
	 * @param Post $post Post object used to build the comments link
	 * @param string $zero String to return when there are no comments
	 * @param string $one String to return when there is one comment
	 * @param string $many String to return when there are more than one comment
	 * @param string $fragment Fragment (bookmark) portion of the URL to append to the link
	 * @param string $title Fragment (bookmark) portion of the URL to append to the link
	 * @return string Linked string to display for comment count
	 * @see Theme::theme_comments_count()
	 */
	public function theme_comments_link( $theme, $post, $zero = '', $one = '', $many = '', $fragment =  'comments' )
	{
		$count = $theme->comments_count_return( $post, $zero, $one, $many );
		return '<a href="' . $post->permalink . '#' . $fragment . '" title="' . _t( 'Read Comments' ) . '">' . end( $count ) . '</a>';
	}

	/**
	 * Returns a full qualified URL of the specified post based on the comments count.
 	 *
	 * Passed strings are localized prior to parsing therefore to localize "%d Comments" in french, it would be "%d Commentaires".
	 *
	 * Since we use sprintf() in the final concatenation, you must format passed strings accordingly.
	 *
	 * @param Theme $theme The current theme object
	 * @param Post $post Post object used to build the comments link
	 * @param string $zero String to return when there are no comments
	 * @param string $one String to return when there is one comment
	 * @param string $many String to return when there are more than one comment
	 * @return string String to display for comment count
	 */
	public function theme_comments_count( $theme, $post, $zero = '', $one = '', $many = '' )
	{
		$count = $post->comments->approved->count;
		if ( $count == 0 ) {
			$text = empty( $zero ) ? _t( 'No Comments' ) : $zero;
			return sprintf( $text, $count );
		}
		else {
			if ( empty( $one ) && empty( $many ) ) {
				$text = _n( '%s Comment', '%s Comments', $count );
			}
			else {
				if ( empty( $one ) ) {
					$one = $many;
				}
				if ( empty( $many ) ) {
					$many = $one;
				}
				$text = $count == 1 ? $one : $many;
			}
			return sprintf( $text, $count );
		}
	}

	/**
	 * Returns the count of queries executed
	 *
	 * @return integer The query count
	 */
	public function theme_query_count()
	{
		return count( DB::get_profiles() );
	}

	/**
	 * Returns total query execution time in seconds
	 *
	 * @return float Query execution time in seconds, with fractions.
	 */
	public function theme_query_time()
	{
		return array_sum( array_map( create_function( '$a', 'return $a->total_time;' ), DB::get_profiles() ) );
	}

	/**
	 * Returns a humane commenter's link for a comment if a URL is supplied, or just display the comment author's name
	 *
	 * @param Theme $theme The current theme
	 * @param Comment $comment The comment object
	 * @return string A link to the comment author or the comment author's name with no link
	 */
	public function theme_comment_author_link( $theme, $comment )
	{
		$url = $comment->url;
		if ( $url != '' ) {
			$parsed_url = InputFilter::parse_url( $url );
				if ( $parsed_url['host'] == '' ) {
					$url = '';
			}
			else {
				$url = InputFilter::glue_url( $parsed_url );
			}
		}
		if ( $url != '' ) {
			return '<a href="'.$url.'">' . $comment->name . '</a>';
		}
		else {
			return $comment->name;
		}
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
		unset( $this->var_stack[$this->current_var_stack][$key] );
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
		unset( $this->var_stack[$this->current_var_stack] );
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
				$user_filters = array();
			}
			$action = substr( $function, 4 );
			Plugins::act( 'theme_action', $action, $this, $user_filters );
		}
		else {
			$purposed = 'output';
			if ( preg_match( '/^(.*)_(return|end)$/', $function, $matches ) ) {
				$purposed = $matches[2];
				$function = $matches[1];
			}
			array_unshift( $params, $function, $this );
			$result = call_user_func_array( array( 'Plugins', 'theme' ), $params );
			switch ( $purposed ) {
				case 'return':
					return $result;
				case 'end':
					echo end( $result );
					return end( $result );
				default:
					$output = implode( '', ( array ) $result );
					echo $output;
					return $output;
			}
		}
	}

	/**
	 * Retrieve the block objects for the current scope and specified area
	 * Incomplete!
	 *
	 * @param string $area The area to which blocks will be output
	 * @param string $scope The scope to which blocks will be output
	 * @param Theme $theme The theme that is outputting these blocks
	 * @return array An array of Block instances to render
	 * @todo Finish this function to pull data from a block_instances table
	 */
	public function get_blocks( $area, $scope, $theme )
	{
		$blocks = DB::get_results( 'SELECT b.* FROM {blocks} b INNER JOIN {blocks_areas} ba ON ba.block_id = b.id WHERE ba.area = ? AND ba.scope_id = ? ORDER BY ba.display_order ASC', array( $area, $scope ), 'Block' );
		$blocks = Plugins::filter( 'get_blocks', $blocks, $area, $scope, $theme );
		return $blocks;
	}

	/**
	 * Matches the scope criteria against the current request
	 *
	 * @param array $criteria An array of scope criteria data in RPN, where values are arrays and operators are strings
	 * @return boolean True if the criteria matches the current request
	 */
	function check_scope_criteria( $criteria )
	{
		$stack = array();
		foreach ( $criteria as $crit ) {
			if ( is_array( $crit ) ) {
				$value = false;
				switch ( $crit[0] ) {
					case 'request':
						$value = URL::get_matched_rule()->name == $crit[1];
						break;
					case 'token':
						if ( isset( $crit[2] ) ) {
							$value = User::identify()->can( $crit[1], $crit[2] );
						}
						else {
							$value = User::identify()->can( $crit[1] );
						}
						break;
					default:

						$value = Plugins::filter( 'scope_criteria_value', $value, $crit[1], $crit[2] );
						break;
				}
				$stack[] = $value;
			}
			else {
				switch ( $crit ) {
					case 'not':
						$stack[] = ! array_pop( $stack );
						break;
					case 'or':
						$value1 = array_pop( $stack );
						$value2 = array_pop( $stack );
						$stack[] = $value1 || $value2;
						break;
					case 'and':
						$value1 = array_pop( $stack );
						$value2 = array_pop( $stack );
						$stack[] = $value1 && $value2;
						break;
					default:
						Plugins::act( 'scope_criteria_operator', $stack, $crit );
						break;
				}
			}
		}
		return array_pop( $stack );
	}

	/**
	 * Retrieve current scope data from the database based on the requested area
	 *
	 * @param string $area The area for which a scope may be applied
	 * @return array An array of scope data
	 */
	public function get_scopes( $area )
	{
		$scopes = DB::get_results( 'SELECT * FROM {scopes} s INNER JOIN {blocks_areas} ba ON ba.scope_id = s.id WHERE ba.area = ? ORDER BY s.priority DESC', array( $area ) );
		foreach ( $scopes as $key => $value ) {
			$scopes[$key]->criteria = unserialize( $value->criteria );
		}
		$scopes = Plugins::filter( 'get_scopes', $scopes );
		
		usort( $scopes, array( $this, 'sort_scopes' ) );
		return $scopes;
	}
	
	/**
	 * Sort function for ordering scope object rows by priority
	 * @param StdObject $scope1 A scope to compare 	
	 * @param StdObject $scope2 A scope to compare
	 * @return integer A sort return value, -1 to 1
	 **/	 	 
	public function sort_scopes( $scope1, $scope2 )
	{
		if ( $scope1->priority == $scope2->priority ) {
			return 0;
		}
		return $scope1->priority < $scope2->priority ? 1 : -1;
	}

	/**
	 * Displays blocks associated to the specified area and current scope.
	 *
	 * @param Theme $theme The theme with which this area will be output
	 * @param string $area The area to which blocks will be output
	 * @param string $context The area of context within the theme that could adjust the template used
	 * @param string $scope Used to force a specific scope
	 * @return string the output of all the blocks
	 */
	public function theme_area( $theme, $area, $context = null, $scope = null )
	{

		// This array would normally come from the database via:
		$scopes = $this->get_scopes( $area );

		$active_scope = 0;
		foreach ( $scopes as $scope_id => $scope_object ) {
			if ( $this->check_scope_criteria( $scope_object->criteria ) ) {
				$scope_block_count = DB::get_value( 'SELECT count( *) FROM {blocks_areas} ba WHERE ba.scope_id = ?', array( $scope_object->id ) );
				if ( $scope_block_count > 0 ) {
					$active_scope = $scope_object->id;
				}
				break;
			}
		}

		$area_blocks = $this->get_blocks( $area, $active_scope, $theme );

		$this->area = $area;

		// This is the block wrapper fallback template list
		$fallback = array(
			$context . '.' . $area . '.blockwrapper',
			$context . '.blockwrapper',
			$area . '.blockwrapper',
			'blockwrapper',
			'content',
		);

		$output = '';
		$i = 0;
		foreach ( $area_blocks as $block_instance_id => $block ) {
			// Temporarily set some values into the block
			$block->_area = $area;
			$block->_instance_id = $block_instance_id;
			$block->_area_index = $i++;

			$hook = 'block_content_' . $block->type;
			Plugins::act( $hook, $block, $this );
			Plugins::act( 'block_content', $block, $this );
			$block->_content = implode( '', $this->content_return( $block, $context ) );
			if ( trim( $block->_content ) == '' ) {
				unset( $area_blocks[$block_instance_id] );
			} 
		}
		// Potentially render each block inside of a wrapper.
		reset( $area_blocks );
		$firstkey = key( $area_blocks );
		end( $area_blocks );
		$lastkey = key( $area_blocks );
		foreach ( $area_blocks as $block_instance_id => $block ) {
			$block->_first = $block_instance_id == $firstkey;
			$block->_last = $block_instance_id == $lastkey;

			// Set up the theme for the wrapper 
			$this->block = $block;
			$this->content = $block->_content;
			// This pattern renders the block inside the wrapper template only if a matching template exists
			$newoutput = $this->display_fallback( $fallback, 'fetch' );
			if ( $newoutput === false ) {
				$output .= $block->_content;
			}
			else {
				$output .= $newoutput;
			}

			// Remove temporary values from the block so they're not saved to the database
			unset( $block->_area );
			unset( $block->_instance_id );
			unset( $block->_area_index );
			unset( $block->_first );
			unset( $block->_last );
		}

		// This is the area fallback template list
		$fallback = array(
			$context . '.area.' . $area,
			$context . '.area',
			'area.' . $area,
			'area',
		);
		$this->content = $output;
		$newoutput = $this->display_fallback( $fallback, 'fetch' );
		if ( $newoutput !== false ) {
			$output = $newoutput;
		}

		$this->area = '';

		return $output;
	}

	/**
 	 * A theme function for outputting CSS classes based on the requested content
 	 * @param Theme $theme A Theme object instance
 	 * @param mixed $args Additional classes that should be added to the ones generated
 	 * @return string The resultant classes
 	 */
	function theme_body_class( $theme, $args = array() )
	{
		$body_class = array();
		foreach ( get_object_vars( $this->request ) as $key => $value ) {
			if ( $value ) {
				$body_class[$key] = $key;
			}
		}

		$body_class = array_unique( array_merge( $body_class, Stack::get_named_stack( 'body_class' ), Utils::single_array( $args ) ) );
		$body_class = Plugins::filter( 'body_class', $body_class, $theme );
		return implode( ' ', $body_class );
	}
	
	/**
	 * Add javascript to the stack to be output in the theme.
	 * 
	 * @param string $where Where should it be output? Options are header and footer.
	 * @param string $value Either a URL or raw JS to be output inline.
	 * @param string $name A name to reference this script by. Used for removing or using in $requires by other scripts.
	 * @param string|array $requires Either a string or an array of strings of $name's for scripts this script requires.
	 * @return boolean True if added successfully, false otherwise.
	 */
	public function add_script ( $where = 'header', $value, $name = null, $requires = null )
	{
		
		$result = false;
		
		switch ( $where ) {
			
			case 'header':
				$result = Stack::add( 'template_header_javascript', $value, $name, $requires );
				break;
			
			case 'footer':
				$result = Stack::add( 'template_footer_javascript', $value, $name, $requires );
				break;
			
		}
		
		return $result;
		
	}
	
	/**
	 * Add a stylesheet to the stack to be output in the theme.
	 * 
	 * @param string $where Where should it be output? Options are header and footer.
	 * @param string $value Either a URL or raw CSS to be output inline.
	 * @param string $name A name to reference this script by. Used for removing or using in $after by other scripts.
	 * @param string|array $requires Either a string or an array of strings of $name's for scripts this script requires.
	 * @return boolean True if added successfully, false otherwise.
	 */
	public function add_style ( $where = 'header', $value, $name = null, $requires = null )
	{
		
		$result = false;
		
		switch ( $where ) {
			
			case 'header':
				$result = Stack::add( 'template_stylesheet', $value, $name, $requires );
				break;
			
			case 'footer':
				$result = Stack::add( 'template_footer_stylesheet', $value, $name, $requires );
				break;
			
		}
		
		return $result;
		
	}

	/** 
	* Provide a method to return the version number from the theme xml
	* @return string The theme version from XML
	**/
	public function get_version()
	{
		return (string)$this->info()->version;
	}

}
?>
