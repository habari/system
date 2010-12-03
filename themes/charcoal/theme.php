<?php

define( 'THEME_CLASS', 'charcoal' );

class charcoal extends Theme
{	
	//Set to true to show the title image, false to display the title text.
	const SHOW_TITLE_IMAGE = false;

	//Set to whatever you want your first tab text to be.
	const HOME_LABEL = 'Blog';

	//Set to true to show the paperclip graphic in posts, false to hide it.
	const SHOW_ENTRY_PAPERCLIP = true;

	//Set to true to show the paperclip graphic in pages, false to hide it.
	const SHOW_PAGE_PAPERCLIP = false;

	//Set to true to show the "powered by Habari" graphic in the sidebar, false to hide it.
	const SHOW_POWERED = true;
	
	//Set to true to show the Login/Logout link in the navigation bar, false to hide it.
	const DISPLAY_LOGIN = true;
	
	//Set to true to show the post tags in the multiple posts pages (search, tags, archives), false to hide them.
	const TAGS_IN_MULTIPLE = false;
	
	//Set to true to show single post navigation links, false to hide them.
	const SHOW_POST_NAV = true;
	
	/**
	 * Execute on theme init to apply these filters to output
	 */
	public function action_init_theme()
	{
		// Apply Format::autop() to post content...
		Format::apply( 'autop', 'post_content_out' );
		// Apply Format::autop() to comment content...
		Format::apply( 'autop', 'comment_content_out' );
		// Truncate content excerpt at "more" or 56 characters...
		Format::apply_with_hook_params( 'more', 'post_content_excerpt', '',56, 1 );
	}
	
	/**
	 * Add some variables to the template output
	 */
	public function add_template_vars()
	{
		// Use theme options to set values that can be used directly in the templates
		// Don't check for constant values in the template code itself
		$this->assign('show_title_image', self::SHOW_TITLE_IMAGE);
		$this->assign('home_label', self::HOME_LABEL);
		$this->assign('show_powered', self::SHOW_POWERED);
		$this->assign('display_login', self::DISPLAY_LOGIN);
		$this->assign('tags_in_multiple', self::TAGS_IN_MULTIPLE);
		$this->assign('post_class', 'post' . ( ! self::SHOW_ENTRY_PAPERCLIP ? ' alt' : '' ) );
		$this->assign('page_class', 'post' . ( ! self::SHOW_PAGE_PAPERCLIP ? ' alt' : '' ) );
		$this->assign('show_post_nav', self::SHOW_POST_NAV);
		
		$locale =Options::get( 'locale' );
		if ( file_exists( Site::get_dir( 'theme', true ). $locale . '.css' ) ){
			$this->assign( 'localized_css',  $locale . '.css' );
		}
		else {
			$this->assign( 'localized_css', false );
		}
		
		if( !$this->template_engine->assigned( 'pages' ) ) {
			$this->assign('pages', Posts::get( array( 'content_type' => 'page', 'status' => Post::status('published'), 'nolimit' => 1 ) ) );
		}
		$this->assign( 'post_id', ( isset($this->post) && $this->post->content_type == Post::type('page') ) ? $this->post->id : 0 );
		parent::add_template_vars();
	}
		
	/**
	 * Convert a post's tags array into a usable list of links
	 *
	 * @param array $array The tags array from a Post object
	 * @return string The HTML of the linked tags
	 */
	public function filter_post_tags_out($array)
	{
		if ( ! is_array( $array ) ) {
			$array = array ( $array );
		}
		$fn = create_function('$a,$b', 'return "<a href=\\"" . URL::get("display_entries_by_tag", array( "tag" => $b) ) . "\\" rel=\\"tag\\">" . $a . "</a>";');
		$array = array_map($fn, $array, array_keys($array));
		$out = implode(' ', $array);
		return $out;
 	}
	
	public function theme_post_comments_link($theme, $post, $zero, $one, $more)
	{
		$c = $post->comments->approved->count;
		switch ($c) {
			case '0':
				return $zero;
				break;
			case '1':
				return str_replace( '%s', '1', $one );
				break;
			default :
				return str_replace( '%s', $c, $more);
		}
	}
		
	public function filter_post_content_excerpt($return)
	{	
 		return strip_tags($return);
 	}

	public function theme_search_prompt( $theme, $criteria, $has_results )
	{
		$out =array();
		$keywords =explode(' ',trim($criteria));
		foreach ($keywords as $keyword) {
			$out[]= '<a href="' . Site::get_url( 'habari', true ) .'search?criteria=' . $keyword . '" title="' . _t( 'Search for ' ) . $keyword . '">' . $keyword . '</a>';
		}
		
		if ( sizeof( $keywords ) > 1 ) {
			if ( $has_results ) {
				return sprintf( _t( 'Search results for \'%s\'' ), implode(' ',$out) );
				exit;
			}
			return sprintf( _t('No results found for your search \'%1$s\'') . '<br>'. _t('You can try searching for \'%2$s\''), $criteria, implode('\' or \'',$out) );
		}
		else {
			return sprintf( _t( 'Search results for \'%s\'' ), $criteria );
			exit;
		}
		return sprintf( _t( 'No results found for your search \'%s\'' ), $criteria );

	}
	
	public function theme_search_form( $theme )
	{
		return $theme->fetch('searchform');
	}
	
	/**
	 * Returns an unordered list of all used Tags
	 */
	public function theme_show_tags ( $theme )
	{
		$sql ="
			SELECT t.tag_slug AS slug, t.tag_text AS text, count(tp.post_id) as ttl
			FROM {tags} t
			INNER JOIN {tag2post} tp
			ON t.id=tp.tag_id
			INNER JOIN {posts} p
			ON p.id=tp.post_id AND p.status = ?
			GROUP BY t.tag_slug
			ORDER BY t.tag_text
		";
		$tags = DB::get_results( $sql, array(Post::status('published')) );

		foreach ($tags as $index => $tag) {
			$tags[$index]->url = URL::get( 'display_entries_by_tag', array( 'tag' => $tag->slug ) );
		}
		$theme->taglist = $tags;
		
		return $theme->fetch( 'taglist' );
	}
}
?>
