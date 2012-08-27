<?php

class ThemeBase extends Pluggable
{
	public $name = null;
	public $version = null;
	public $template_engine = null;
	public $theme_dir = null;
	public $config_vars = array();
	protected $var_stack = array( array() );
	protected $current_var_stack = 0;
	public $context = array();
	protected $added_template_vars = false;

	/**
	 * Constructor for themebase
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
		$theme_dir = Utils::single_array($themedata->theme_dir);
		// Set up the corresponding engine to handle the templating
		$this->template_engine = new $themedata->template_engine();

		$this->theme_dir = $theme_dir;
		$this->template_engine->set_template_dir( $theme_dir );
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

		$xml_file = end($this->theme_dir) . '/theme.xml';
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
	 * Provide a method to return the version number from the theme xml
	 * @return string The theme version from XML
	 **/
	public function get_version()
	{
		return (string)$this->info()->version;
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
	 * Assign the default variables that would be used in every template
	 */
	public function add_template_vars()
	{
		// set the locale and character set that habari is configured to use presently
		if ( !isset( $this->locale ) ) {
			$this->locale = Options::get('locale', 'en');	// default to 'en' just in case we somehow don't have one?
		}

		if ( !isset( $this->charset ) ) {
			$this->charset = MultiByte::hab_encoding();
		}

		if ( !$this->template_engine->assigned( 'user' ) ) {
			$this->assign( 'user', User::identify() );
		}

		if ( !$this->template_engine->assigned( 'loggedin' ) ) {
			$this->assign( 'loggedin', User::identify()->loggedin );
		}

		$handler = Controller::get_handler();
		if ( isset( $handler ) ) {
			$this->assign( 'handler', $handler );
			Plugins::act( 'add_template_vars', $this, $handler->handler_vars );
		}
		$this->added_template_vars = true;
	}

	/**
	 * Helper function: Avoids having to call $theme->template_engine->display( 'template_name' );
	 * @param string $template_name The name of the template to display
	 */
	public function display( $template_name )
	{
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
		if(!$this->added_template_vars) {
			$this->add_template_vars();
		}
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
	 * Display an object using a template designed for the type of object it is
	 * The $object is assigned into the theme using the $content template variable
	 *
	 * @param ThemeBase $theme The theme object used for display
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
		if(isset($context)) {
			$this->context[] = $context;
		}
		$result = $this->display_fallback( $fallback, 'fetch' );
		if(isset($context)) {
			array_pop($this->context);
		}
		if( $result === false && DEBUG ) {
			$fallback_list = implode( ', ', $fallback );
			$result = '<p>' . _t( 'Content could not be displayed. One of the following templates - %s - has to be present in the active theme.', array( $fallback_list ) ) . '</p>';
		}
		return $result;
	}

	/**
	 * Check to see if the theme is currently rendering a specific context
	 * @param string $context The context to check for.
	 * @return bool True if the context is active.
	 */
	public function has_context($context)
	{
		if(in_array($context, $this->context)) {
			return true;
		}
		return false;
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
			if ( preg_match( '/^(.*)_(return|end|out)$/', $function, $matches ) ) {
				$purposed = $matches[2];
				$function = $matches[1];
			}
			array_unshift( $params, $function, $this );
			$result = call_user_func_array( array( 'Plugins', 'theme' ), $params );
			switch ( $purposed ) {
				case 'return':
					return $result;
				case 'end':
					return end( $result );
				case 'out':
					$output = implode( '', (array) $result );
					echo $output;
					return $output;
				default:
					$output = implode( '', (array) $result );
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
	 * @param string $area The area to which blocks will be output
	 * @param string $context The area of context within the theme that could adjust the template used
	 * @param string $scope Used to force a specific scope
	 * @return string the output of all the blocks
	 */
	public function area( $area, $context = null, $scope = null )
	{

		// This array would normally come from the database via:
		$scopes = $this->get_scopes( $area );

		$active_scope = 0;
		foreach ( $scopes as $scope_id => $scope_object ) {
			if ( ( is_null($scope) && $this->check_scope_criteria( $scope_object->criteria ) ) || $scope == $scope_object->name ) {
				$scope_block_count = DB::get_value( 'SELECT count( *) FROM {blocks_areas} ba WHERE ba.scope_id = ?', array( $scope_object->id ) );
				if ( $scope_block_count > 0 ) {
					$active_scope = $scope_object->id;
				}
				break;
			}
		}

		$area_blocks = $this->get_blocks( $area, $active_scope, $this );

		$this->area = $area;
		if(isset($context)) {
			$this->context[] = $context;
		}

		// This is the block wrapper fallback template list
		$fallback = array(
			$area . '.blockwrapper',
			'blockwrapper',
			'content',
		);
		if(!is_null($context)) {
			array_unshift($fallback, $context . '.blockwrapper');
			array_unshift($fallback, $context . '.' . $area . '.blockwrapper');
		}

		$output = '';
		$i = 0;
		foreach ( $area_blocks as $block_instance_id => $block ) {
			// Temporarily set some values into the block
			$block->_area = $area;
			$block->_instance_id = $block_instance_id;
			$block->_area_index = $i++;
			$block->_fallback = $fallback;

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
		if(isset($context)) {
			array_pop($this->context);
		}
		return $output;
	}

	/**
	 * Add a template to the list of available templates
	 * @param string $name Name of the new template
	 * @param string $file File of the template to add
	 * @param boolean $replace If true, replace any existing template with this name
	 */
	public function add_template($name, $file, $replace = false)
	{
		$this->template_engine->add_template($name, $file, $replace);
	}


	/**
	 * Load and return a list of all assets in the current theme chain's /assets/ directory
	 * @param bool $refresh If True, clear and reload all assets
	 * @return array An array of URLs of assets in the assets directories of the active theme chain
	 */
	public function load_assets($refresh = false)
	{
		static $assets = null;

		if(is_null($assets) || $refresh) {
			$themedirs = $this->theme_dir;
			$assets = array(
				'css' => array(),
				'js' => array(),
			);

			foreach($themedirs as $dir) {
				if( file_exists(Utils::end_in_slash($dir) . 'assets')) {
					$theme_assets = Utils::glob(Utils::end_in_slash($dir) . 'assets/*.*');
					foreach($theme_assets as $asset) {
						$extension = strtolower(substr($asset, strrpos($asset, '.') + 1));
						$assets[$extension][basename($asset)] = $this->dir_to_url($asset);
					}
				}
			}
		}
		return $assets;
	}

	/**
	 * Load assets and add the CSS ones to the header on the template_stylesheet action hook.
	 */
	public function action_template_header_9()
	{
		$assets = $this->load_assets();
		foreach($assets['css'] as $css) {
			Stack::add('template_stylesheet', array($css , 'screen,projection'));
		}
	}

	/**
	 * Load assets and add the javascript ones to the footer on the template_footer_javascript action hook.
	 */
	public function action_template_footer_9()
	{
		$assets = $this->load_assets();
		foreach($assets['js'] as $js) {
			Stack::add('template_footer_javascript', $js);
		}
	}

	/**
	 * Get the URL for a resource in one of the directories used by the active theme, child theme directory first
	 * @param bool|string $resource The resource name
	 * @param bool $overrideok If false, find only the parent theme resources
	 * @return string The URL of the requested resource
	 * @todo This method needs to be aware of the class that called it so that it can find the right directory to use
	 */
	public function get_url($resource = false, $overrideok = true)
	{
		$url = false;
		$theme = '';

		$themedirs = $this->theme_dir;

		if(!$overrideok) {
			$themedirs = reset($this->theme_dir);
		}

		foreach($themedirs as $dir) {
			if(file_exists(Utils::end_in_slash($dir) . trim($resource, '/'))) {
				$url = $this->dir_to_url(Utils::end_in_slash($dir) . trim($resource, '/'));
				break;
			}
		}

		$url = Plugins::filter( 'site_url_theme', $url, $theme );
		return $url;
	}

	/**
	 * Convert a theme directory or resource into a URL
	 * @param string $dir The pathname to convert
	 * @return bool|string The URL to use, or false if none was found
	 */
	public function dir_to_url($dir)
	{
		static $tomatch = false;

		if(!$tomatch) {
			$tomatch = array(
				Site::get_dir( 'config' ) . '/themes/' => Site::get_url( 'user' ) .  '/themes/',
				HABARI_PATH . '/user/themes/' => Site::get_url( 'habari' ) . '/user/themes/',
				HABARI_PATH . '/3rdparty/themes/' => Site::get_url( 'habari' ) . '/3rdparty/themes/',
				HABARI_PATH . '/system/themes/' => Site::get_url( 'habari' ) . '/system/themes/',
			);
		}

		if(preg_match('#^(' . implode('|', array_map('preg_quote', array_keys($tomatch))) . ')(.*)$#', $dir, $matches)) {
			return $tomatch[$matches[1]] . $matches[2];
		}
		return false;
	}

}