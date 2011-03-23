<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php

/**
 * K2 is a custom Theme class for the K2 theme.
 *
 * @package Habari
 */

/**
 * A custom theme for K2 output
 */
class K2 extends Theme
{
	/**
	 * Add the K2 menu block to the nav area upon theme activation if there's nothing already there
	 */
	public function action_theme_activated()
	{
		$blocks = $this->get_blocks('nav', '', $this);
		if(count($blocks) == 0) {
			$block = new Block(array(
				'title' => _t('K2 Menu'),
				'type' => 'k2_menu',
			));

			$block->add_to_area('nav');
			Session::notice(_t('Added K2 Menu block to Nav area.'));
		}
	}

	/**
	 * Execute on theme init to apply these filters to output
	 */
	public function action_init_theme()
	{
		// Apply Format::autop() to comment content...
		Format::apply( 'autop', 'comment_content_out' );
		// Apply Format::tag_and_list() to post tags...
		Format::apply( 'tag_and_list', 'post_tags_out' );
		
		// Remove the comment on the following line to limit post length on the home page to 1 paragraph or 100 characters
		//Format::apply_with_hook_params( 'more', 'post_content_out', _t('more'), 100, 1 );
	}

	/**
	 * Add additional template variables to the template output.
	 *
	 *  You can assign additional output values in the template here, instead of
	 *  having the PHP execute directly in the template.  The advantage is that
	 *  you would easily be able to switch between template types (RawPHP/Smarty)
	 *  without having to port code from one to the other.
	 *
	 *  You could use this area to provide "recent comments" data to the template,
	 *  for instance.
	 *
	 *  Note that the variables added here should possibly *always* be added,
	 *  especially 'user'.
	 *
	 *  Also, this function gets executed *after* regular data is assigned to the
	 *  template.  So the values here, unless checked, will overwrite any existing
	 *  values.
	 */
	public function add_template_vars ( ) {
		
		parent::add_template_vars();
		
		$this->home_tab = 'Blog';
		$this->show_author = false;
		
		$this->add_template( 'k2_text', dirname( __FILE__ ) . '/formcontrol_text.php' );
		
		if ( !isset( $this->pages ) ) {
			$this->pages = Posts::get( array( 'content_type' => 'page', 'status' => 'published', 'nolimit' => true ) );
		}
		
		if ( User::identify()->loggedin ) {
			Stack::add( 'template_header_javascript', Site::get_url('scripts') . '/jquery.js', 'jquery' );
		}
		
		if ( ( $this->request->display_entry || $this->request->display_page ) && isset( $this->post ) && $this->post->title != '' ) {
			$this->page_title = $this->post->title . ' - ' . Options::get('title');
		}
		else {
			$this->page_title = Options::get('title');
		}
		
	}

	public function k2_comment_class( $comment, $post )
	{
		$class = 'class="comment';
		if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
			$class.= '-unapproved';
		}
		// check to see if the comment is by a registered user
		if ( $u = User::get( $comment->email ) ) {
			$class.= ' byuser comment-author-' . Utils::slugify( $u->displayname );
		}
		if ( $comment->email == $post->author->email ) {
			$class.= ' bypostauthor';
		}

		$class.= '"';
		return $class;
	}

/**
 * If comments are enabled, or there are comments on the post already, output a link to the comments.
 *
 */
	public function comments_link( $post )
	{
		if ( !$post->info->comments_disabled || $post->comments->approved->count > 0 ) {
			$comment_count = $post->comments->approved->count;
			echo "<span class=\"commentslink\"><a href=\"{$post->permalink}#comments\" title=\"" . _t('Comments on this post') . "\">{$comment_count} " . _n( 'Comment', 'Comments', $comment_count ) . "</a></span>";
		}

	}

	/**
	 * Customize comment form layout. Needs thorough commenting.
	 */
	public function action_form_comment( $form ) { 
		$form->cf_commenter->caption = '<small><strong>' . _t('Name') . '</strong></small><span class="required">' . ( Options::get('comments_require_id') == 1 ? ' *' . _t('Required') : '' ) . '</span>';
		$form->cf_commenter->template = 'k2_text';
		$form->cf_email->caption = '<small><strong>' . _t('Mail') . '</strong> ' . _t( '(will not be published)' ) .'</small><span class="required">' . ( Options::get('comments_require_id') == 1 ? ' *' . _t('Required') : '' ) . '</span>';
		$form->cf_email->template = 'k2_text';
		$form->cf_url->caption = '<small><strong>' . _t('Website') . '</strong></small>';
		$form->cf_url->template = 'k2_text';
	        $form->cf_content->caption = '';
		$form->cf_submit->caption = _t( 'Submit' );
	}

	/**
	 * Add a k2_menu block to the list of available blocks
	 */
	public function filter_block_list($block_list)
	{
		$block_list['k2_menu'] = _t('K2 Menu');
		return $block_list;
	}
	
	/**
	 * Produce a menu for the K2 menu block from all of the available pages
	 */
	public function action_block_content_k2_menu($block, $theme)
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
		if ( User::identify()->loggedin ) {
			$menus['admin'] = array('link' => Site::get_url( 'admin' ), 'title' => _t('Admin area'), 'caption' => _t('Admin'), 'cssclass' => 'admintab');
		}
		$block->menus = $menus;
	}
}

?>
