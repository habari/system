<?php
/**
 * Menus
 * 
 * @property Vocabulary $vocabulary The Vocabulary object used to hold the menu
 *
 * @todo allow renaming/editing of menu items
 * @todo style everything so it looks good
 * @todo show description with name on post publish checkboxes
 * @todo PHPDoc
 * @todo ACL, CSRF, etc.
 */

class Menus extends Plugin
{
	/**
	 * Return properties of this object
	 * @param $name
	 * @return Vocabulary
	 */
	public function  __get($name)
	{
		switch ( $name ) {
			case 'vocabulary':
				if ( !isset($this->_vocabulary) ) {
					$this->_vocabulary = Vocabulary::get(self::$vocabulary);
				}
				return $this->_vocabulary;
		}
	}

	/**
	 * Create an admin token for editing menus
	 */
	public function action_plugin_activation($file)
	{
		// create default access token
		ACL::create_token( 'manage_menus', _t( 'Manage menus' ), 'Administration', false );
		$group = UserGroup::get_by_name( 'admin' );
		$group->grant( 'manage_menus' );

		// register menu types
		Vocabulary::add_object_type( 'menu_link' );
		Vocabulary::add_object_type( 'menu_spacer' );
	}

	/**
	 * Register the templates - one for the admin page, the other for the block.
	 */
	public function action_init()
	{
		$this->add_template( 'menus_admin', dirname( __FILE__ ) . '/menus_admin.php' );
		$this->add_template( 'menu_iframe', dirname( __FILE__ ) . '/menu_iframe.php' );
		$this->add_template( 'block.menu', dirname( __FILE__ ) . '/block.menu.php' );

		// formcontrol for tokens
		$this->add_template( 'text_tokens', dirname( __FILE__ ) . '/formcontrol_tokens.php' );
		$this->add_template( 'transparent_text', dirname( __FILE__ ) . '/admincontrol_text_transparent.php' );
	}

	/**
	 * Remove the admin token
	 */
	public function action_plugin_deactivation( $file )
	{
		// delete default access token
		ACL::destroy_token( 'manage_menus' );

		// delete menu vocabularies that were created
		$vocabs = DB::get_results( 'SELECT * FROM {vocabularies} WHERE name LIKE "menu_%"', array(), 'Vocabulary' );
		foreach( $vocabs as $vocab ) {
			// This should only delete the ones that are menu vocabularies, unless others have been named 'menu_xxxxx'
			$vocab->delete();
		}

		// delete blocks that were created
		$blocks = DB::get_results( 'SELECT * FROM {blocks} WHERE type = "menu"', array(), 'Block') ;
		foreach( $blocks as $block ) {
			$block->delete();
		}
	}

	/**
	 * Add to the list of possible block types.
	 */
	public function filter_block_list($block_list)
	{
		$block_list['menu'] = _t( 'Menu' );
		return $block_list;
	}

	/**
	 * Produce the form to configure a menu
	 */
	public function action_block_form_menu( $form, $block )
	{
		$form->append('select', 'menu_taxonomy', $block, _t( 'Menu Taxonomy' ), $this->get_menus( true ) );
		$form->append('checkbox', 'div_wrap', $block, _t( 'Wrap each menu link in a div' ) );
		$form->append('text', 'list_class', $block, _t( 'Custom class for the tree\'s ordered list element' ) );
	}

	/**
	 * Populate the block with some content
	 */
	public function action_block_content_menu( $block, $theme ) {
		$vocab = Vocabulary::get_by_id($block->menu_taxonomy);
		$block->vocabulary = $vocab;
		if ($block->div_wrap) {
			$block->wrapper = '<div>%s</div>';
		}
		else {
			$block->wrapper = '%s';
		}

		// preprocess some things
		$block->tree = $vocab->get_tree();
		$block->render_menu_item = array($this, 'render_menu_item');
	}

	/**
	 * Add menus to the publish form
	 */
	public function action_form_publish ( $form, $post )
	{
		$menus = $this->get_menus();

		$menulist = array();
		foreach($menus as $menu) {
			$menulist[$menu->id] = $menu->name;
		}

		$settings = $form->publish_controls->append( 'fieldset', 'menu_set', _t( 'Menus' ) );
		$settings->append( 'checkboxes', 'menus', 'null:null', _t( 'Menus' ), $menulist );

		// If this is an existing post, see if it has categories already
		if ( 0 != $post->id ) {
			// Get the terms associated to this post
			$object_terms = Vocabulary::get_all_object_terms( 'post', $post->id );
			$menu_ids = array_keys( $menulist );
			$value = array();
			// if the term is in a menu vocab, enable that checkbox
			foreach( $object_terms as $term ) {
				if ( in_array( $term->vocabulary_id, $menu_ids ) ) {
					$value[] = $term->vocabulary_id;
				}
			}

			$form->menus->value = $value;
		}
	}

	/**
	 * Process menus when the publish form is received
	 *
	 */
	public function action_publish_post( $post, $form )
	{
		// might not hurt to turn this into a function to be more DRY
		$term_title = $post->title;
		$selected_menus = $form->menus->value;
		foreach( $this->get_menus() as $menu ) {
			$terms = $menu->get_object_terms( 'post', $post->id );
			if ( in_array( $menu->id, $selected_menus ) ) {
				if ( count( $terms ) == 0 ) {
					$term = new Term(array(
						'term_display' => $post->title,
						'term' => $post->slug,
					));
					$term->info->menu = $menu->id;
					$menu->add_term( $term );
					$menu->set_object_terms( 'post',
						$post->id,
						array( $term->term ) );
				}
			}
			else {
				foreach( $terms as $term ) {
					$term->delete();
				}
			}
		}
	}

	/**
	 * Add creation and management links to the main menu
	 *
	 */
	public function filter_adminhandler_post_loadplugins_main_menu( $menu ) {
		$menus_array = array( 'create_menu' => array(
			'title' => "Create a new Menu",
			'text' => "New Menu",
			'hotkey' => '1',
			'url' => URL::get( 'admin', array( 'page' => 'menus', 'action' => 'create' ) ),
			'class' => 'over-spacer',
			'access' => array( 'manage_menus' => true ),
		));

		$items = 0;
		foreach ( $this->get_menus() as $item  ) {
			$menus_array[ ++$items ] = array(
				'title' => "{$item->name}: {$item->description}",
				'text' => $item->name,
				'hotkey' => $items+1,
				'url' => URL::get( 'admin', array( 'page' => 'menus', 'action' => 'edit', 'menu' => $item->id ) ),
				'access' => array( 'manage_menus' => true ),
			);
		}
		if ( count( $menus_array ) > 1 ) {
			$menus_array[1]['class'] = 'under-spacer';
		}

		// add to main menu
		$item_menu = array( 'menus' =>
			array(
				'url' => URL::get( 'admin', 'page=menus' ),
				'title' => _t( 'Menus' ),
				'text' => _t( 'Menus' ),
				'hotkey' => 'E',
				'selected' => false,
				'submenu' => $menus_array,
			)
		);

		$slice_point = array_search( 'themes', array_keys( $menu ) ); // Element will be inserted before "themes"
		$pre_slice = array_slice( $menu, 0, $slice_point);
		$post_slice = array_slice( $menu, $slice_point);

		$menu = array_merge( $pre_slice, $item_menu, $post_slice );

		return $menu;
	}

	/**
	 * Handle GET and POST requests
	 *
	 */
	public function alias()
	{
		return array(
			'action_admin_theme_get_menus' => 'action_admin_theme_post_menus',
			'action_admin_theme_get_menu_iframe' => 'action_admin_theme_post_menu_iframe',
		);
	}

	/**
	 * Restrict access to the admin page
	 *
	 */
	public function filter_admin_access_tokens( array $require_any, $page )
	{
		switch ( $page ) {
			case 'menu_iframe':
			case 'menus':
				$require_any = array( 'manage_menus' => true );
				break;
		}
		return $require_any;
	}

	/**
	 * Convenience function for obtaining menu type data and caching it so that it's not called repeatedly
	 *
	 * The return value should include specific paramters, which are used to feed the menu creation routines.
	 * The array should return a structure like the following:
	 * <code>
	 * $menus = array(
	 * 	'typename' => array(
	 * 		'form' => function(FormUI $form, Term|null $term){ },
	 *
	 * 	)
	 * );
	 * </code>
	 * @return array
	 */
	public function get_menu_type_data()
	{
		static $menu_type_data = null;
		if ( empty($menu_type_data) ) {
			$menu_type_data = Plugins::filter('menu_type_data', array());
		}
		return $menu_type_data;
	}

	/**
	 * Implementation of menu_type_data filter, created by this plugin
	 * @param array $menu_type_data Existing menu type data
	 * @return array Updated menu type data
	 */
	public function filter_menu_type_data($menu_type_data)
	{
		$menu_type_data['menu_link'] = array(
			'label' => _t( 'Link' ),
			'form' => function($form, $term) {
				$link_name = new FormControlText( 'link_name', 'null:null', _t( 'Link Title' ) );
				$link_name->add_validator( 'validate_required', _t( 'A name is required.' ) );
				$link_url = new FormControlText( 'link_url', 'null:null', _t( 'Link URL' ) );
				$link_url->add_validator( 'validate_required' )
					->add_validator( 'validate_url', _t( 'You must supply a valid URL.' ) );
				if ( $term ) {
					$link_name->value = $term->term_display;
					$link_url->value = $term->info->url;
					$form->append( 'hidden', 'term' )->value = $term->id;
				}
				$form->append( $link_name );
				$form->append( $link_url );
			},
			'save' => function($menu, $form) {
				if ( ! isset( $form->term->value ) ) {
					$term = new Term(array(
						'term_display' => $form->link_name->value,
						'term' => Utils::slugify($form->link_name->value),
					));
					$term->info->type = "link";
					$term->info->url = $form->link_url->value;
					$term->info->menu = $menu->id;
					$menu->add_term($term);
					$term->associate('menu_link', 0);

					Session::notice( _t( 'Link added.' ) );
				} else 	{
					$term = Term::get( intval( $form->term->value ) );
					$updated = false;
					if ( $term->info->url !== $form->link_url->value ) {
						$term->info->url = $form->link_url->value;
						$updated = true;
					}
					if ( $form->link_name->value !== $term->term_display ) {
						$term->term_display = $form->link_name->value;
						$term->term = Utils::slugify( $form->link_name->value );
						$updated = true;
					}

					$term->info->url = $form->link_url->value;

					if ( $updated ) {
						$term->update();
						Session::notice( _t( 'Link updated.' ) );
					}
				}
			},
			'render' => function($term, $object_id, $config) {
				$result = array(
					'link' => $term->info->url,
				);
				return $result;
			}
		);
		$menu_type_data['menu_spacer'] = array(
			'label' => _t( 'Spacer' ),
			'form' => function($form, $term) {
				$spacer = new FormControlText( 'spacer_text', 'null:null', _t( 'Item text' ), 'optionscontrol_text' );
				$spacer->helptext = _t( 'Leave blank for blank space' );
				if ( $term ) {
					$spacer->value = $term->term_display;
					$form->append( 'hidden', 'term' )->value = $term->id;
				}

				$form->append( $spacer );
			},
			'save' => function($menu, $form) {
				if ( ! isset( $form->term->value ) ) {
					$term = new Term(array(
						'term_display' => ($form->spacer_text->value !== '' ? $form->spacer_text->value : '&nbsp;'), // totally blank values collapse the term display in the formcontrol
						'term' => 'menu_spacer',
					));
					$term->info->type = "spacer";
					$term->info->menu = $menu->id;
					$menu->add_term($term);
					$term->associate('menu_spacer', 0);

					Session::notice( _t( 'Spacer added.' ) );
				} else {
					$term = Term::get( intval( $form->term->value ) );
					if ($form->spacer_text->value !== $term->term_display ) {
						$term->term_display = $form->spacer_text->value;
						$term->update();
						Session::notice( _t( 'Spacer updated.' ) );
					}
				}
			}
		);
		$menu_type_data['post'] = array(
			'label' => _t( 'Links to Posts' ),
			'form' => function( $form, $term ) {
				if ( $term ) {
					$object_types = $term->object_types();
					$term_object = reset( $object_types );

					$post_display = $form->append( 'text', 'term_display', 'null:null', _t( 'Title to display' ) );
					$post_display->value = $term->term_display;
					$post = Post::get( $term_object->object_id );
					$post_term = $form->append( 'static', 'post_link', _t( "Links to <a target='_blank' href='{$post->permalink}'>{$post->title}</a>" ) );
					$form->append( 'hidden', 'term' )->value = $term->id;
				}
				else {
					$post_ids = $form->append( 'text', 'post_ids', 'null:null', _t( 'Posts' ) );
					$post_ids->template = 'text_tokens';
					$post_ids->ready_function = "$('#{$post_ids->field}').tokenInput( habari.url.ajaxPostTokens )";
				}
			},
			'save' => function($menu, $form) {
				if ( ! isset( $form->term->value ) )  {
					$post_ids = explode( ',', $form->post_ids->value );
					foreach( $post_ids as $post_id ) {
						$post = Post::get( array( 'id' => $post_id ) );
						$term_title = $post->title;

						$terms = $menu->get_object_terms( 'post', $post->id );
						if ( count( $terms ) == 0 ) {
							$term = new Term( array( 'term_display' => $post->title, 'term' => $post->slug ) );
							$term->info->menu = $menu->id;
							$menu->add_term( $term );
							$menu->set_object_terms( 'post', $post->id, array( $term->term ) );
						}
					}
					Session::notice(_t( 'Link(s) added.' ));
				}
				else {
					$term = Term::get( intval( $form->term->value ) );
					if ($form->term_display->value !== $term->term_display ) {
						$term->term_display = $form->term_display->value;
						$term->update();
						Session::notice( _t( 'Link updated.' ) );
					}
				}
			},
			'render' => function($term, $object_id, $config) {
				$result = array();
				if ($post = Post::get($object_id)) {
					$rule = Controller::get_matched_rule();
					if(isset($rule->named_arg_values['slug']) && $rule->named_arg_values['slug'] == $post->slug) {
						$result['active'] = true;
					}
					$result['link'] = $post->permalink;
				}
				return $result;
			}
		);
		return $menu_type_data;
	}

	/**
	 * @return array The data array
	 */
	public function get_menu_type_ids()
	{
		static $menu_item_ids = null;
		if ( empty($menu_item_ids) ) {
			$menu_item_types = $this->get_menu_type_data();
			$menu_item_types = Utils::array_map_field($menu_item_types, 'type_id');
			$menu_item_types = array_flip($menu_item_ids);
		}
		return $menu_item_ids;
	}

	/**
	 * Minimal modal forms
	 *
	 */
	public function action_admin_theme_get_menu_iframe( AdminHandler $handler, Theme $theme )
	{
		$action = isset($_GET[ 'action' ]) ? $_GET[ 'action' ] : 'create';
		$term = null;
		if ( isset( $handler->handler_vars[ 'term' ] ) ) {
			$term = Term::get( intval( $handler->handler_vars[ 'term' ] ) );
			$object_types = $term->object_types();
			$action = $object_types[0]->type; // the 'menu_whatever' we seek should be the only element in the array.
			$form_action = URL::get( 'admin', array( 'page' => 'menu_iframe', 'menu' => $handler->handler_vars[ 'menu' ], 'term' => $handler->handler_vars[ 'term' ], 'action' => "$action" ) );
		} else {
			$form_action = URL::get( 'admin', array( 'page' => 'menu_iframe', 'menu' => $handler->handler_vars[ 'menu' ], 'action' => "$action" ) );
		}
		$form = new FormUI( 'menu_item_edit', $action );
		$form->class[] = 'tm_db_action';
		$form->set_option( 'form_action', $form_action );
		$form->append( 'hidden', 'menu' )->value = $handler->handler_vars[ 'menu' ];
		$form->on_success( array( $this, 'term_form_save' ) );

		$menu_types = $this->get_menu_type_data();

		if ( isset($menu_types[$action]) ) {
			$menu_types[$action]['form']($form, $term);
			$form->append( 'hidden', 'menu_type' )->value = $action;
			if($term) {
				$label = _t( 'Update %s', array( $menu_types[$action]['label'] ));
			}
			else {
				$label = _t( 'Add %s', array( $menu_types[$action]['label'] ));
			}
			$form->append( 'submit', 'submit', $label );
		}

		$form->properties['onsubmit'] = "return habari.menu_admin.submit_menu_item_edit(this)";

		$theme->page_content = $form->get();

		if ( isset($form->has_result) ) {
			switch ( $form->has_result ) {
				case 'added':
					$treeurl = URL::get( 'admin', array('page' => 'menus', 'menu' => $handler->handler_vars[ 'menu' ], 'action' => 'edit') ) . ' #edit_menu>*';
					$msg = _t( 'Menu item added.' ); // @todo: update this to reflect if more than one item has been added, or reword entirely.
					$theme->page_content .= <<< JAVSCRIPT_RESPONSE
<script type="text/javascript">
human_msg.display_msg('{$msg}');
$('#edit_menu').load('{$treeurl}', habari.menu_admin.init_form);
</script>
JAVSCRIPT_RESPONSE;
					break;
				case 'updated':
					$treeurl = URL::get( 'admin', array('page' => 'menus', 'menu' => $handler->handler_vars[ 'menu' ], 'action' => 'edit') ) . ' #edit_menu>*';
					$msg = _t( 'Menu item updated.' ); // @todo: update this to reflect if more than one item has been added, or reword entirely.
					$theme->page_content .= <<< JAVSCRIPT_RESPONSE
<script type="text/javascript">
human_msg.display_msg('{$msg}');
$('#menu_popup').dialog('close');
$('#edit_menu').load('{$treeurl}', habari.menu_admin.init_form);
</script>
JAVSCRIPT_RESPONSE;
					break;
			}
		}
		$theme->display( 'menu_iframe' );
		exit;
	}

	/**
	 * Prepare and display admin page
	 *
	 */
	public function action_admin_theme_get_menus( AdminHandler $handler, Theme $theme )
	{
		$theme->page_content = '';
		$action = isset($_GET[ 'action' ]) ? $_GET[ 'action' ] : 'create';
		switch ( $action ) {
			case 'edit':
				$vocabulary = Vocabulary::get_by_id( intval( $handler->handler_vars[ 'menu' ] ) );
				if ( $vocabulary == false ) {
					$theme->page_content = '<h2>' . _t( 'Invalid Menu.' );
					// that's it, we're done. Maybe we show the list of menus instead?
					break;
				}

				$form = new FormUI( 'edit_menu' );

				$form->append( new FormControlText( 'menuname', 'null:null', _t( 'Name' ), 'transparent_text' ) )
					->add_validator( 'validate_required', _t( 'You must supply a valid menu name' ) )
					->add_validator( array( $this, 'validate_newvocab' ) )
					->value = $vocabulary->name;
				$form->append( new FormControlHidden( 'oldname', 'null:null' ) )->value = $vocabulary->name;

				$form->append( new FormControlText( 'description', 'null:null', _t( 'Description' ), 'transparent_text' ) )
					->value = $vocabulary->description;

				$edit_items_array = $this->get_menu_type_data();

				$edit_items = '';
				foreach( $edit_items_array as $action => $menu_type ) {
					$edit_items .= '<a class="modal_popup_form menu_button_dark" href="' . URL::get('admin', array(
						'page' => 'menu_iframe',
						'action' => $action,
						'menu' => $vocabulary->id,
					) ) . "\">" . _t( 'Add %s', array($menu_type['label'] ) ) .  "</a>";
				}

				if ( !$vocabulary->is_empty() ) {
					$form->append( 'tree', 'tree', $vocabulary->get_tree(), _t( 'Menu' ) );
					$form->tree->options = $vocabulary->get_tree();
					$form->tree->config = array( 'itemcallback' => array( $this, 'tree_item_callback' ) );
//						$form->tree->value = $vocabulary->get_root_terms();
					// append other needed controls, if there are any.

					$form->append( 'static', 'buttons', '<div id="menu_item_button_container">' . $edit_items . '</div>' );
					$form->append( 'submit', 'save', _t( 'Apply Changes' ) );
				}
				else {
					$form->append( 'static', 'buttons', '<div id="menu_item_button_container">' . $edit_items . '</div>' );
				}
				$delete_link = URL::get( 'admin', Utils::WSSE( array( 'page' => 'menus', 'action' => 'delete_menu', 'menu' => $handler->handler_vars[ 'menu' ] ) ) );
				//$delete_link = URL::get( 'admin', array( 'page' => 'menus', 'action' => 'delete_menu', 'menu' => $handler->handler_vars[ 'menu' ] ) );
				$form->append( 'static', 'deletebutton', '<a class="a_button" href="' . $delete_link . '">' . _t( 'Delete Menu' ) . '</a>' );
				$form->append( new FormControlHidden( 'menu', 'null:null' ) )->value = $handler->handler_vars[ 'menu' ];
				$form->on_success( array( $this, 'rename_menu_form_save' ) );
				$form->properties['onsubmit'] = "return habari.menu_admin.submit_menu_update();";
				$theme->page_content .= $form->get();
				break;

			case 'create':
				$form = new FormUI('create_menu');
				$form->append( 'text', 'menuname', 'null:null', _t( 'Menu Name' ), 'transparent_text' )
					->add_validator( 'validate_required', _t( 'You must supply a valid menu name' ) )
					->add_validator( array($this, 'validate_newvocab' ) );
				$form->append( 'text', 'description', 'null:null', _t( 'Description' ), 'transparent_text' );
				$form->append( 'submit', 'submit', _t( 'Create Menu' ) );
				$form->on_success( array( $this, 'add_menu_form_save' ) );
				$theme->page_content = $form->get();

				break;

			case 'delete_menu':
				if(Utils::verify_wsse($_GET, true)) {
					$menu_vocab = Vocabulary::get_by_id( intval( $handler->handler_vars[ 'menu' ] ) );
					$menu_vocab->delete();
					// log that it has been deleted?
					Session::notice( _t( 'Menu deleted.' ) );
					// redirect to a blank menu creation form
					Utils::redirect( URL::get( 'admin', array( 'page' => 'menus', 'action' => 'create' ) ) );
				}
				else {
					Session::notice( _t( 'Menu deletion failed - please try again.' ) );
					Utils::redirect(URL::get('admin', array('page' => 'menus', 'action' => 'edit', 'menu' => $handler->handler_vars[ 'menu' ])));
				}
				break;

			case 'delete_term':
				$term = Term::get( intval( $handler->handler_vars[ 'term' ] ) );
				$menu_vocab = $term->vocabulary_id;
				if(Utils::verify_wsse($_GET, true)) {
					$term->delete();
					// log that it has been deleted?
					Session::notice( _t( 'Item deleted.' ) );
					Utils::redirect( URL::get( 'admin', array( 'page' => 'menus', 'action' => 'edit', 'menu' => $menu_vocab ) ) );
				}
				else {
					Session::notice( _t( 'Item deletion failed - please try again.' ) );
					Utils::redirect(URL::get('admin', array('page' => 'menus', 'action' => 'edit', 'menu' => $menu_vocab)));
				}
				break;
			default:
				Utils::debug( $_GET, $action ); die();
		}

		$theme->display( 'menus_admin' );
		// End everything
		exit;
	}

	public function add_menu_form_save( $form )
	{
		$params = array(
			'name' => $form->menuname->value,
			'description' => $form->description->value,
			'features' => array(
				'term_menu', // a special feature that marks the vocabulary as a menu, but has no functional purpose
				'unique', // a special feature that applies a one-to-one relationship between term and object, enforced by the Vocabulary class
			),
		);
		$vocab = Vocabulary::create( $params );

		Utils::redirect( URL::get( 'admin', array( 'page' => 'menus', 'action' => 'edit', 'menu' => $vocab->id ) ) );
	}

	public function rename_menu_form_save( $form )
	{
		// The name of this should probably change, since it is the on_success for the whole menu edit, no longer just for renaming.
		// It only renames/modifies the description currently, as item adding/rearranging is done by the NestedSortable tree.

		// get the menu from the form, grab the values, modify the vocabulary.
		$menu_vocab = intval( $form->menu->value );
		// create a term for the link, store the URL
		$menu = Vocabulary::get_by_id( $menu_vocab );
		if ( $menu->name != $form->menuname->value ) {
			$menu->name = $form->menuname->value; // could use Vocabulary::rename for this
		}
		$menu->description = $form->description->value; // no Vocabulary function for this
		$menu->update();

		$form->save();

		Session::notice( _t( 'Updated menu "%s".', array( $form->menuname->value ) ) );
		Utils::redirect( URL::get( 'admin', array(
			'page' => 'menus',
			'action' => 'edit',
			'menu' => $menu->id,
		) ) );
	}

	public function term_form_save( $form )
	{
		$menu_vocab = intval( $form->menu->value );
		$menu = Vocabulary::get_by_id( $menu_vocab );
		$menu_type_data = $this->get_menu_type_data();

		if ( isset( $form->term ) ) {
			$term = Term::get( intval( (string) $form->term->value ) );
			// maybe we should check if term exists? Or put that in the conditional above?
			$object_types = $term->object_types();
			$type = $object_types[0]->type; // that's twice we've grabbed the $term->object_types()[0]. Maybe this is a job for a function?

			if ( isset($menu_type_data[$type]['save']) ) {
				$menu_type_data[$type]['save']($menu, $form);
			}

			$form->has_result = 'updated';

		}
		else { // if no term is set, create a new item.
			// create a term for the link, store the URL

			$type = $form->menu_type->value;
			if ( isset($menu_type_data[$type]['save']) ) {
				$menu_type_data[$type]['save']($menu, $form);
			}

			$form->has_result = 'added';
		}
	}

	public function validate_newvocab( $value, $control, $form )
	{
		if ( isset( $form->oldname ) && ( $form->oldname->value ) && ( $value == $form->oldname->value ) ) {
			return array();
		}
		if ( Vocabulary::get( $value ) instanceof Vocabulary ) {
			return array( _t( 'Please choose a vocabulary name that does not already exist.' ) );
		}
		return array();
	}

	public function get_menus($as_array = false)
	{
		$vocabularies = Vocabulary::get_all();
		$outarray = array();
		foreach ( $vocabularies as $index => $menu ) {
			if ( !$menu->term_menu ) { // check for the term_menu feature we added.
				unset( $vocabularies[ $index ] );
			}
			else {
				if ( $as_array ) {
					$outarray[ $menu->id ] = $menu->name;
				}
			}
		}
		if ( $as_array ) {
			return $outarray;
		}
		else {
			return $vocabularies;
		}
	}

	/**
	 *
	 * Callback for Format::term_tree to use with $config['linkcallback']
	 *
	 * @param Term $term
	 * @param array $config
	 * @return array $config modified with the new wrapper div
	 */
	public function tree_item_callback( Term $term, $config )
	{
		// coming into this, default $config['wrapper'] is "<div>%s</div>"

		// make the links
		$edit_link = URL::get( 'admin', array(
			'page' => 'menu_iframe',
			'action' => $term->info->type,
			'term' => $term->id,
			'menu' => $term->info->menu,
		) );
		$delete_link = URL::get( 'admin', Utils::WSSE( array(
			'page' => 'menus',
			'action' => 'delete_term',
			'term' => $term->id,
			'menu' => $term->info->menu,
		) ) );

		$delete_link = str_replace('%', '%%', $delete_link); // This is so it doesn't break the sprintf in Format::term_tree()

		// insert them into the wrapper
		$edit_title = _t('Edit this');
		$edit_label = _t('edit');
		$delete_title = _t('Delete this');
		$delete_label = _t('delete');
		
		$links = <<< LINKS
<ul class="dropbutton">
	<li><a title="{$edit_title}" class="modal_popup_form" href="{$edit_link}">{$edit_label}</a></li>
	<li><a title="{$delete_title}" href="{$delete_link}">{$delete_label}</a></li>
</ul>
LINKS;

		// Put the dropbutton links for each item at the end of the item's div
		$config[ 'wrapper' ] = "<div>%s {$links}</div>";

		return $config;
	}

	/**
	 * Callback function for block output of menu list item
	 */
	public function render_menu_item( Term $term, $config )
	{
		$title = $term->term_display;

		$active = false;

		$menu_type_data = $this->get_menu_type_data();

		$spacer = false;
		$active = false;
		$link = null;
		if ( !isset($term->object_id) ) {
			$objects = $term->object_types();
			$term->type = reset($objects);
			$term->object_id = key($objects);
		}
		if ( isset($menu_type_data[$term->type]['render']) ) {
			$result = $menu_type_data[$term->type]['render']($term, $term->object_id, $config);
			$result = array_intersect_key(
				$result,
				array(
					'link' => 1,
					'title' => 1,
					'active' => 1,
					'spacer' => 1,
					'config' => 1,
				)
			);
			extract($result);
		}

		if ( empty( $link ) ) {
			$config[ 'wrapper' ] = sprintf($config[ 'linkwrapper' ], $title);
		}
		else {
			$config[ 'wrapper' ] = sprintf( $config[ 'linkwrapper' ], "<a href=\"{$link}\">{$title}</a>" );
		}
		if ( $active ) {
			$config[ 'itemattr' ][ 'class' ] = 'active';
		}
		else {
			$config[ 'itemattr' ][ 'class' ] = 'inactive';
		}
		if ( $spacer ) {
			$config[ 'itemattr' ][ 'class' ] .= ' spacer';
		}
		return $config;
	}
	/**
	 * Add required Javascript and, for now, CSS.
	 */
	public function action_admin_header( $theme )
	{
		if ( $theme->page == 'menus' ) {
			// Ideally the plugin would reuse reusable portions of the existing admin CSS. Until then, let's only add the CSS needed on the menus page.
			Stack::add( 'admin_stylesheet', array( $this->get_url( '/admin.css' ), 'screen' ), 'menus-admin-css', 'admin-css' );

			// Load the plugin and its css
			Stack::add( 'admin_header_javascript', Site::get_url( 'vendor' ) . "/jquery.tokeninput.js", 'jquery-tokeninput', 'jquery.ui' );
			Stack::add( 'admin_stylesheet', array( Site::get_url( 'admin_theme' ) . '/css/token-input.css', 'screen' ), 'admin_tokeninput', 'jquery.ui-css' );

			// Add the callback URL.
			$url = "habari.url.ajaxPostTokens = '" . URL::get( 'auth_ajax', array( 'context' => 'post_tokens' ) ) . "';";
			Stack::add( 'admin_header_javascript', $url, 'post_tokens_url', 'post_tokens' );

			// Add the menu administration javascript
			Stack::add( 'admin_header_javascript', $this->get_url('/menus_admin.js'), 'menus_admin', 'admin-js');
		}
	}

	/**
	 * Respond to Javascript callbacks for autocomplete when creating items linking to posts
	 */
	public function action_auth_ajax_post_tokens( $handler )
	{
		// Get the data that was sent
		$response = $handler->handler_vars[ 'q' ];

		$final_response = array();

		$new_response = Posts::get( array( "title_search" => $response, "status" => Post::status( 'published' ) ) );
		foreach ( $new_response as $post ) {

			$final_response[] = array(
				'id' => $post->id,
				'name' => $post->title,
			);
		}

		// Send the response
		echo json_encode( $final_response );
	}
}
?>
