<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari AdminPostsHandler Class
 * Handles posts-related actions in the admin
 *
 */
class AdminPostsHandler extends AdminHandler
{
	// Make certain handler_vars local with defaults
	// Do not provide defaults for the vars included in the Posts::get(), those will get defaults from the preset
	private $locals = array(
		'do_update' => false,
		'post_ids' => null,
		'nonce' => '',
		'timestamp' => '',
		'password_digest' => '',
		'change' => '',
		'author' => null,
		'type' => null,
		'status' => null,
		'limit' => null,
		'after' => null,
		'before' => null,
		'tag' => null,
		'text' => '',
		'page' => 1,
	);

	/**
	 * Handles GET requests of the publish page.
	 */
	public function get_publish( $template = 'publish' )
	{
		$extract = $this->handler_vars->filter_keys( 'id', 'content_type_name' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}
		$content_type = Post::type($content_type_name);

		// 0 is what's assigned to new posts
		if ( isset( $id ) && ( $id != 0 ) ) {
			$post = Post::get( array( 'id' => $id, 'status' => Post::status( 'any' ) ) );
			Plugins::act('admin_publish_post', $post);
			if ( !$post ) {
				Session::error( _t( "You don't have permission to edit that post" ) );
				$this->get_blank();
			}
			if ( ! ACL::access_check( $post->get_access(), 'edit' ) ) {
				Session::error( _t( "You don't have permission to edit that post" ) );
				$this->get_blank();
			}
			$this->theme->post = $post;
		}
		else {
			$post = new Post();
			Plugins::act('admin_publish_post', $post);
			$this->theme->post = $post;
			$post->content_type = Post::type( ( isset( $content_type ) ) ? $content_type : 'entry' );

			// check the user can create new posts of the set type.
			$user = User::identify();
			$type = 'post_' . Post::type_name( $post->content_type );
			if ( ACL::user_cannot( $user, $type ) || ( ! ACL::user_can( $user, 'post_any', 'create' ) && ! ACL::user_can( $user, $type, 'create' ) ) ) {
				Session::error( _t( 'Access to create posts of type %s is denied', array( Post::type_name( $post->content_type ) ) ) );
				$this->get_blank();
			}
		}

		$this->theme->admin_page = _t( 'Publish %s', array( Plugins::filter( 'post_type_display', Post::type_name( $post->content_type ), 'singular' ) ) );
		$this->theme->admin_title = _t( 'Publish %s', array( Plugins::filter( 'post_type_display', Post::type_name( $post->content_type ), 'singular' ) ) );

		$statuses = Post::list_post_statuses( false );
		$this->theme->statuses = $statuses;

		$form = $post->get_form( 'admin' );

		$this->theme->form = $form;

		$this->theme->wsse = Utils::WSSE();
		$this->display( $template );
	}

	/**
	 * Handles POST requests from the publish page.
	 */
	public function post_publish()
	{
		$this->get_publish();
	}

	/**
	 * Assign values needed to display the posts page to the theme based on handlervars and parameters
	 *
	 */
	private function fetch_posts( $params = array() )
	{
		foreach ( $this->locals as $varname => $default ) {
			$$varname = isset( $params[$varname] ) ? $params[$varname] : $default;
		}

		// if we're updating posts, let's do so:
		if ( $do_update && isset( $post_ids ) ) {
			$okay = true;
			if ( empty( $nonce ) || empty( $timestamp ) ||  empty( $password_digest ) ) {
				$okay = false;
			}
			$wsse = Utils::WSSE( $nonce, $timestamp );
			if ( $password_digest != $wsse['digest'] ) {
				$okay = false;
			}
			if ( $okay ) {
				foreach ( $post_ids as $id ) {
					$ids[] = array( 'id' => $id );
				}
				$to_update = Posts::get( array( 'where' => $ids, 'nolimit' => 1 ) );
				foreach ( $to_update as $post ) {
					switch ( $change ) {
						case 'delete':
							if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
								$post->delete();
							}
							break;
						case 'publish':
							if ( ACL::access_check( $post->get_access(), 'edit' ) ) {
								$post->publish();
							}
							break;
						case 'unpublish':
							if ( ACL::access_check( $post->get_access(), 'edit' ) ) {
								$post->status = Post::status( 'draft' );
								$post->update();
							}
							break;
					}
				}
				unset( $this->handler_vars['change'] );
			}
		}


		// we load the WSSE tokens
		// for use in the delete button
		$this->theme->wsse = Utils::WSSE();
	
		// Only pass set values to Posts::get(), otherwise they will override the defaults in the preset
		$user_filters = array();
		if ( isset( $type ) ) {
			$user_filters['content_type'] = $type;
		}
		if ( isset( $status ) ) {
			$user_filters['status'] = $status;
		}
		if ( isset( $limit ) ) {
			$user_filters['limit'] = $limit;
		}
		if ( isset( $author ) ) {
			$user_filters['user_id'] = User::get($author)->id;
		}
		if ( isset( $before ) ) {
			$user_filters['before'] = $before;
		}
		if ( isset( $after ) ) {
			$user_filters['after'] = $after;
		}
		if ( isset( $text ) ) {
			$user_filters['criteria'] = $text;
		}
		if ( isset( $tag ) ) {
			if ( !is_array( $tag ) ) {
				$tag = explode(',', $tag); // also makes $tag an array in every case
			}
			$user_filters['vocabulary'][Tags::vocabulary()->name . ':term_display'] = $tag;
		}
		if ( isset( $page ) ) {
			$user_filters['page'] = $page;
			$this->theme->pagenr = $page;
		}
		else {
			$this->theme->pagenr = 1;
		}

		$this->theme->posts = Posts::get( array_merge( array( 'preset' => 'admin' ), $user_filters ) );
	}

	/**
	 * Handles GET requests to /admin/posts.
	 *
	 */
	public function get_posts()
	{
		$this->post_posts();
	}

	/**
	 * Handles POST values from /manage/posts.
	 * Used to control what content to show / manage.
	 */
	public function post_posts()
	{
		// Simply pass $_GET to the function, it's save as only values we understand will be read
		$this->fetch_posts($_GET);
	
		// Check which values have been passed and translate them for the faceted seach
		$search_values = array();
		foreach ( $this->locals as $varname => $default ) {
			if(isset($_GET[$varname])) {
				switch($varname) {
					case 'type':
						$search_values[] = 'type: ' . Post::type_name($_GET['type']);
						break;
					case 'status':
						$search_values[] = 'status: ' . Post::status_name($_GET['status']);
						break;
					case 'tag':
						$tags = explode(',', $_GET['tag']);
						foreach($tags as $tag) {
							$search_values[] = 'tag: ' . $tag;
						}
						break;
					case 'author':
						$search_values[] = 'author: ' . User::get($_GET['author'])->username;
						break;
					default:
						$search_values[] = $varname . ': ' . $_GET[$varname];
				}
			}
		}
		if(count($search_values) > 0) {
			$search_value = implode(' ', $search_values);
		}
		else {
			$search_value = '';
		}

		// Create search controls and global buttons for the manage page
		$search = FormControlFacet::create('search');
		$search->set_value($search_value)
			->set_property('data-facet-config', array(
				'onsearch' => '$(".posts").manager("update", self.data("visualsearch").searchQuery.facets());',
				'facetsURL' => URL::get('admin_ajax_facets', array('context' => 'facets', 'page' => 'manage', 'component' => 'facets')),
				'valuesURL' => URL::get('admin_ajax_facets', array('context' => 'facets', 'page' => 'manage', 'component' => 'values')),
			));

		$navigation = FormControlStatic::create('navigation')
			->set_static('<a href="" id="nav_prev" class="navigation">' . _t('Previous page') . '</a>'
			. '<a href="" id="nav_next" class="navigation">' . _t('Next page') . '</a>');

		$aggregate = FormControlAggregate::create('selected_items')->set_selector('.post_item')->label('None Selected');

		$page_actions = FormControlDropbutton::create('page_actions');
		$page_actions->append(
			FormControlSubmit::create('delete')
				->set_caption(_t('Delete Selected'))
				->set_properties(array(
					'onclick' => 'itemManage.update(\'delete\');return false;',
					'title' => _t('Delete Selected'),
				))
		);
		Plugins::act('posts_manage_actions', $page_actions);
		
		$form = new FormUI('manage');
		$form->append($search);
		$form->append($navigation);
		$form->append($aggregate);
		$form->append($page_actions);
		$this->theme->form = $form;

		$this->theme->admin_page = _t( 'Manage Posts' );
		$this->theme->admin_title = _t( 'Manage Posts' );

		Stack::add('admin_header_javascript', 'visualsearch' );
		Stack::add('admin_header_javascript', 'manage-js' );
		Stack::add('admin_stylesheet', 'visualsearch-css');
		Stack::add('admin_stylesheet', 'visualsearch-datauri-css');

		$this->display( 'posts' );
	}

	/**
	 * Handles AJAX requests from media silos.
	 */
	public function ajax_media( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$path = $_POST['path'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo
		$assets = Media::dir( $path );
		$output = array(
			'ok' => 1,
			'dirs' => array(),
			'files' => array(),
			'path' => $path,
		);
		foreach ( $assets as $asset ) {
			if ( $asset->is_dir ) {
				$output['dirs'][$asset->basename] = $asset->get_props();
			}
			else {
				$output['files'][$asset->basename] = $asset->get_props();
			}
		}
		$rootpath = MultiByte::strpos( $path, '/' ) !== false ? MultiByte::substr( $path, 0, MultiByte::strpos( $path, '/' ) ) : $path;
		$controls = array( 'root' => '<a href="#" onclick="habari.media.fullReload();habari.media.showdir(\''. $rootpath . '\');return false;">' . _t( 'Root' ) . '</a>' );
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, '' );
		$controls_out = '';
		foreach ( $controls as $k => $v ) {
			if ( is_numeric( $k ) ) {
				$controls_out .= "<li>{$v}</li>";
			}
			else {
				$controls_out .= "<li class=\"{$k}\">{$v}</li>";
			}
		}
		$output['controls'] = $controls_out;

		$ar = new AjaxResponse();
		$ar->data = $output;
		$ar->out();
	}

	/**
	 * Handles AJAX requests from media panels.
	 */
	public function ajax_media_panel( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$path = $_POST['path'];
		$panelname = $_POST['panel'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel = '';
		$panel = Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );

		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls_out = '';
		foreach ( $controls as $k => $v ) {
			if ( is_numeric( $k ) ) {
				$controls_out .= "<li>{$v}</li>";
			}
			else {
				$controls_out .= "<li class=\"{$k}\">{$v}</li>";
			}
		}
		$output = array(
			'controls' => $controls_out,
			'panel' => $panel,
		);

		$ar = new AjaxResponse();
		$ar->data = $output;
		$ar->out();
	}
		
	/**
	 * Handles AJAX upload requests from media panels.
	 */
	public function ajax_media_upload( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		$path = $_POST['path'];
		$panelname = $_POST['panel'];
		$rpath = $path;
		$silo = Media::get_silo( $rpath, true );  // get_silo sets $rpath by reference to the path inside the silo

		$panel = '';
		$panel = Plugins::filter( 'media_panels', $panel, $silo, $rpath, $panelname );

		$controls = array();
		$controls = Plugins::filter( 'media_controls', $controls, $silo, $rpath, $panelname );
		$controls_out = '';
		foreach ( $controls as $k => $v ) {
			if ( is_numeric( $k ) ) {
				$controls_out .= "<li>{$v}</li>";
			}
			else {
				$controls_out .= "<li class=\"{$k}\">{$v}</li>";
			}
		}
		$output = array(
			'controls' => $controls_out,
			'panel' => $panel,
		);

		$ar = new AjaxResponse();
		$ar->data = $output;
		$ar->out( true ); // See discussion at https://github.com/habari/habari/issues/204
	}


	/**
	 * Handles AJAX requests from the manage posts page.
	 */
	public function ajax_posts()
	{
		Utils::check_request_method( array( 'POST', 'HEAD' ) );

		$this->create_theme();

		$params = $_POST['query'];

		$fetch_params = array();

		if( isset($params) && !empty($params) ) {
			foreach($params as $param) {
				$key = key($param);
				$value = current($param);
				if(isset($fetch_params[$key])) {
					$fetch_params[$key] = Utils::single_array($fetch_params[$key]);
					$fetch_params[$key][] = $value;
				}
				else {
					$fetch_params[$key] = $value;
				}
			}
		}

		$this->fetch_posts( $fetch_params );
		$items = $this->theme->fetch( 'posts_items' );
		$timeline = $this->theme->fetch( 'timeline_items' );

		$item_ids = array();

		foreach ( $this->theme->posts as $post ) {
			if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
				$item_ids['p' . $post->id] = 1;
			}
		}

		$ar = new AjaxResponse();
		$ar->html('.posts', $items);
		$ar->data = array(
			'items' => $items,
			'item_ids' => $item_ids,
			'timeline' => $timeline,
		);
		$ar->out();
	}

	/**
	 * Handles AJAX from /manage/posts.
	 * Used to delete posts.
	 */
	public function ajax_update_posts( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );
		$response = new AjaxResponse();

		$wsse = Utils::WSSE( $_POST['nonce'], $_POST['timestamp'] );
		if ( $_POST['digest'] != $wsse['digest'] ) {
			$response->message = _t( 'WSSE authentication failed.' );
			$response->out();
			return;
		}
		
		$ids = $_POST['selected'];
		
		if ( count( $ids ) == 0 ) {
			$posts = new Posts();
		}
		else {
			$posts = Posts::get( array( 'id' => $ids, 'nolimit' => true ) );
		}

		Plugins::act( 'admin_update_posts', $_POST['action'], $posts, $this );
		$status_msg = _t( 'Unknown action "%s"', array( $_POST['action'] ) );
		switch ( $_POST['action'] ) {
			case 'delete':
				$deleted = 0;
				foreach ( $posts as $post ) {
					if ( ACL::access_check( $post->get_access(), 'delete' ) ) {
						$post->delete();
						$deleted++;
					}
				}
				if ( $deleted != count( $posts ) ) {
					$response->message = _t( 'You did not have permission to delete some posts.' );
				}
				else {
					$response->message = sprintf( _n( 'Deleted %d post', 'Deleted %d posts', count( $ids ) ), count( $ids ) );
				}
				break;
			default:
				// Specific plugin-supplied action
				Plugins::act( 'admin_posts_action', $response, $_POST['action'], $posts );
				break;
		}

		$response->out();
		exit;
	}

	/**
	 * Plugin hook filter for the facet list
	 * @param array $facets An array of facets for the current faceted search
	 * @return array The array of facets
	 */
	public static function filter_facets($facets) {
		return array_merge($facets, array(
			'type',
			'status',
			'author',
			'after',
			'before',
			'tag',
			'page',
			'limit',
		));
	}

	/**
	 * Plugin hook filter for the values of a faceted search
	 * @param array $other_values The incoming array of values for this facet
	 * @param string $facet The selected facet
	 * @param string $q A string filter for facet values
	 * @return array The returned list of possible values
	 */
	public static function filter_facetvalues($other_values, $facet, $q) {
		switch($facet) {
			case 'type':
				$values = array_keys(Post::list_active_post_types());
				break;
			case 'status':
				$values = array_keys(Post::list_post_statuses());
				break;
			case 'tag':
				$tags = Tags::search($q);
				$values = array();
				foreach($tags as $tag) {
					$values[] = $tag->term_display;
				}
				break;
			case 'author':
				$values = array();
				$users = Users::get(array('criteria' => $q));
				foreach($users as $user) {
					$values[] = $user->username;
				}
				break;
			case 'before':
			case 'after':
				$values = array($q);
				break;
		}
		return array_merge($other_values, $values);
	}


}
