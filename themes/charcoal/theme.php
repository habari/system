<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } 

class Charcoal extends Theme
{
	var $defaults = array(
		'show_title_image' => false,
		'home_label' => 'Blog',
		'show_entry_paperclip' => true,
		'show_page_paperclip' => false,
		'show_powered' => true,
		'display_login' => true,
		'tags_in_multiple' => false,
		'show_post_nav' => true,
		'tags_count' => 40,
		);

	/**
	 * On theme activation, set the default options and activate a default menu
	 */
	public function action_theme_activated()
	{
		$opts = Options::get_group( __CLASS__ );
		if ( empty( $opts ) ) {
			Options::set_group( __CLASS__, $this->defaults );
		}
		$blocks = $this->get_blocks( 'nav', '', $this );
		if( count( $blocks ) == 0 ) {
			$block = new Block( array(
				'title' => _t( 'Charcoal Menu' ),
				'type' => 'charcoal_menu',
			) );

			$block->add_to_area( 'nav' );
			Session::notice( _t( 'Added Charcoal Menu block to Nav area.' ) );
		}
	}

	/**
	 * Configuration form for the Charcoal theme
	 **/
	public function action_theme_ui( $theme )
	{
		$ui = new FormUI( __CLASS__ );
		// This is a fudge as I only need to add a little bit of styling to make things look nice.
		$ui->append( 'static', 'style', '<style type="text/css">#charcoal .formcontrol { line-height: 2.2em; }</style>');
		$ui->append( 'checkbox', 'show_title_image', __CLASS__.'__show_title_image', _t( 'Show Title Image:'), 'optionscontrol_checkbox' );
			$ui->show_title_image->helptext = _t( 'Check to show the title image, uncheck to display the title text.' );
		$ui->append( 'text', 'home_label', __CLASS__.'__home_label', _t( 'Home label:' ), 'optionscontrol_text' );
			$ui->home_label->helptext = _t( 'Set to whatever you want your first tab text to be.' );
		$ui->append( 'checkbox', 'show_entry_paperclip', __CLASS__.'__show_entry_paperclip', _t( 'Show Entry Paperclip:' ), 'optionscontrol_checkbox' );
			$ui->show_entry_paperclip->helptext = _t( 'Check to show the paperclip graphic in posts, uncheck to hide it.' );
		$ui->append( 'checkbox', 'show_page_paperclip', __CLASS__.'__show_page_paperclip', _t( 'Show Page Paperclip:' ), 'optionscontrol_checkbox' );
			$ui->show_page_paperclip->helptext = _t( 'Check to show the paperclip graphic in pages, uncheck to hide it.' );
		$ui->append( 'checkbox', 'show_powered', __CLASS__.'__show_powered', _t( 'Show Powered By:' ), 'optionscontrol_checkbox' );
			$ui->show_powered->helptext = _t( 'Check to show the "powered by Habari" graphic in the sidebar, uncheck to hide it.' );
		$ui->append( 'checkbox', 'display_login', __CLASS__.'__display_login', _t( 'Display Login:' ), 'optionscontrol_checkbox' );
			$ui->display_login->helptext = _t( 'Check to show the Login/Logout link in the navigation bar, uncheck to hide it.' );
		$ui->append( 'checkbox', 'tags_in_multiple', __CLASS__.'__tags_in_multiple', _t( 'Tags in Multiple Posts Page:'), 'optionscontrol_checkbox' );
			$ui->tags_in_multiple->helptext = _t( 'Check to show the post tags in the multiple posts pages (search, tags, archives), uncheck to hide them.' );
		$ui->append( 'checkbox', 'show_post_nav', __CLASS__.'__show_post_nav', _t( 'Show Post Navigation:' ), 'optionscontrol_checkbox' );
			$ui->show_post_nav->helptext = _t( 'Set to true to show single post navigation links, false to hide them.' );
		$ui->append( 'text', 'tags_count', __CLASS__.'__tags_count', _t( 'Tag Cloud Count:' ), 'optionscontrol_text' );
			$ui->tags_count->helptext = _t( 'Set to the number of tags to display on the default "cloud".' );

		// Save
		$ui->append( 'submit', 'save', _t( 'Save' ) );
		$ui->set_option( 'success_message', _t( 'Options saved' ) );
		$ui->out();
	}
	
	/**
	 * Execute on theme init to apply these filters to output
	 */
	public function action_init_theme()
	{
		// Apply Format::autop() to comment content...
		Format::apply( 'autop', 'comment_content_out' );
		// Truncate content excerpt at "more" or 56 characters...
		Format::apply( 'autop', 'post_content_excerpt' );
		Format::apply_with_hook_params( 'more', 'post_content_excerpt', '', 56, 1 );
	}
	
	/**
	 * Add some variables to the template output
	 */
	public function action_add_template_vars( $theme, $handler_vars )
	{
		// Use theme options to set values that can be used directly in the templates
		$opts = Options::get_group( __CLASS__ );
				
		$this->assign( 'show_title_image', $opts['show_title_image'] );
		$this->assign( 'home_label', $opts['home_label'] );
		$this->assign( 'show_powered', $opts['show_powered'] );
		$this->assign( 'display_login', $opts['display_login'] );
		$this->assign( 'tags_in_multiple', $opts['tags_in_multiple'] );
		$this->assign( 'post_class', 'post' . ( ! $opts['show_entry_paperclip'] ? ' alt' : '' ) );
		$this->assign( 'page_class', 'post' . ( ! $opts['show_page_paperclip'] ? ' alt' : '' ) );
		$this->assign( 'show_post_nav', $opts['show_post_nav'] );
		
		$locale = Options::get( 'locale' );
		if ( file_exists( Site::get_dir( 'theme', true ). $locale . '.css' ) ) {
			$this->assign( 'localized_css', $locale . '.css' );
		}
		else {
			$this->assign( 'localized_css', false );
		}
		
		if ( !$this->template_engine->assigned( 'pages' ) ) {
			$this->assign( 'pages', Posts::get( array( 'content_type' => 'page', 'status' => 'published', 'nolimit' => 1 ) ) );
		}
		$this->assign( 'post_id', ( isset( $this->post ) && $this->post->content_type == Post::type( 'page' ) ) ? $this->post->id : 0 );

		if ( $this->request->display_entries_by_tag ) {
			if ( count($this->include_tag) && count($this->exclude_tag) == 0 ) {
				$this->tags_msg = _t('Displaying posts tagged: %s', array(Format::tag_and_list($this->include_tag)));
			}
			else if ( count($this->exclude_tag) && count($this->include_tag) == 0 ) {
				$this->tags_msg = _t('Displaying posts not tagged: %s', array(Format::tag_and_list($this->exclude_tag)));
			}
			else {
				$this->tags_msg = _t('Displaying posts tagged: %s and not %s', array(Format::tag_and_list($this->include_tag), Format::tag_and_list($this->exclude_tag)));
			}
		}


		// Add FormUI template placing the input before the label
		$this->add_template( 'charcoal_text', dirname( __FILE__ ) . '/formcontrol_text.php' );
	}
		
	/**
	 * Convert a post's tags array into a usable list of links
	 *
	 * @param array $array The tags array from a Post object
	 * @return string The HTML of the linked tags
	 */
	public function filter_post_tags_out( $array )
	{
		$fn = create_function( '$a', 'return "<a href=\\"" . URL::get("display_entries_by_tag", array( "tag" => $a->tag_slug) ) . "\\" rel=\\"tag\\">" . $a->tag . "</a>";' );
		$array = array_map( $fn, (array)$array );
		$out = implode( ' ', $array );
		return $out;
	}

	public function theme_post_comments_link( $theme, $post, $zero, $one, $more )
	{
		$c = $post->comments->approved->count;
		return 0 == $c ? $zero : sprintf( '%1$d %2$s', $c, _n( $one, $more, $c ) );
	}

	public function filter_post_content_excerpt( $return )
	{
		return strip_tags( $return );
	}

	public function theme_search_prompt( $theme, $criteria, $has_results )
	{
		$out =array();
		$keywords = explode( ' ', trim( $criteria ) );
		foreach ( $keywords as $keyword ) {
			$out[]= '<a href="' . Site::get_url( 'habari', true ) .'search?criteria=' . $keyword . '" title="' . _t( 'Search for ' ) . $keyword . '">' . $keyword . '</a>';
		}
		
		if ( sizeof( $keywords ) > 1 ) {
			if ( $has_results ) {
				return sprintf( _t( 'Search results for \'%s\'' ), implode( ' ', $out ) );
				exit;
			}
			return sprintf( _t( 'No results found for your search \'%1$s\'' ) . '<br>'. _t( 'You can try searching for \'%2$s\'' ), $criteria, implode( '\' or \'', $out ) );
		}
		else {
			return sprintf( _t( 'Search results for \'%s\'' ), $criteria );
			exit;
		}
		return sprintf( _t( 'No results found for your search \'%s\'' ), $criteria );

	}
	
	public function theme_search_form( $theme )
	{
		return $theme->fetch( 'searchform' );
	}
	
	/**
	 * Returns an unordered list of all used Tags
	 */
	public function theme_show_tags ( $theme )
	{
		$limit = Options::get( __CLASS__ . '__tags_count' );
		$sql ="
			SELECT t.term AS slug, t.term_display AS text, count(tp.object_id) as ttl
			FROM {terms} t
			INNER JOIN {object_terms} tp
			ON t.id=tp.term_id
			INNER JOIN {posts} p
			ON p.id=tp.object_id AND p.status = ?
			WHERE t.vocabulary_id = ? AND tp.object_type_id = ?
			GROUP BY t.term, t.term_display
			ORDER BY t.term_display
			LIMIT {$limit}
		";
		$tags = DB::get_results( $sql, array( Post::status( 'published' ), Tags::vocabulary()->id, Vocabulary::object_type_id( 'post' ) ) );
		foreach ( $tags as $index => $tag ) {
			$tags[$index]->url = URL::get( 'display_entries_by_tag', array( 'tag' => $tag->slug ) );
		}
		$theme->taglist = $tags;
		
		return $theme->fetch( 'taglist' );
	}

	/**
	 * Customize comment form layout. Needs thorough commenting.
	 */
	public function action_form_comment( $form ) { 
		$form->cf_commenter->caption = '<strong>' . _t( 'Name' ) . '</strong> <span class="required">' . ( Options::get( 'comments_require_id' ) == 1 ? _t( '(Required)' ) : '' ) . '</span></label>';
		$form->cf_commenter->template = 'charcoal_text';
		$form->cf_email->caption = '<strong>' . _t( 'Mail' ) . '</strong> ' . _t( '(will not be published' ) .' <span class="required">' . ( Options::get( 'comments_require_id' ) == 1 ? _t( '- Required)' ) : ')' ) . '</span></label>';
		$form->cf_email->template = 'charcoal_text';
		$form->cf_url->caption = '<strong>' . _t( 'Website' ) . '</strong>';
		$form->cf_url->template = 'charcoal_text';
		$form->cf_content->caption = '';
		$form->cf_submit->caption = _t( 'Submit' );
	}

	/**
	 * Add a charcoal_menu block to the list of available blocks
	 */
	public function filter_block_list($block_list)
	{
		$block_list['charcoal_menu'] = _t('Charcoal Menu');
		return $block_list;
	}
	
	/**
	 * Produce a menu for the Charcoal menu block from all of the available pages
	 */
	public function action_block_content_charcoal_menu($block, $theme)
	{
		$menus = array('home' => array(
			'link' => Site::get_url( 'habari' ), 
			'title' => Options::get( 'title' ), 
			'caption' => _t('Blog'), 
			'cssclass' => $theme->request->display_home ? 'current_page_item' : '',
		));
		$pages = Posts::get(array('content_type' => 'page', 'status' => Post::status('published')));
		foreach($pages as $page) {
			$menus[] = array(
				'link' => $page->permalink, 
				'title' => $page->title, 
				'caption' => $page->title, 
				'cssclass' => (isset($theme->post) && $theme->post->id == $page->id) ? 'current_page_item' : '',
			);
		}
		$block->menus = $menus;
	}

}
?>
