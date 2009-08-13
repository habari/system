<?php

/**
 * MyTheme is a custom Theme class for the K2 theme.
 *
 * @package Habari
 */

// We must tell Habari to use MyTheme as the custom theme class:
define( 'THEME_CLASS', 'MyTheme' );

/**
 * A custom theme for K2 output
 */
class MyTheme extends Theme
{
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
	public function add_template_vars()
	{
		//Theme Options
		$this->assign('home_tab','Blog'); //Set to whatever you want your first tab text to be.
		$this->assign( 'show_author' , false ); //Display author in posts

		//Add formcontrol template with input before label
		$this->add_template( 'k2_text', dirname(__FILE__) . '/formcontrol_text.php' );


		if( !$this->template_engine->assigned( 'pages' ) ) {
			$this->assign('pages', Posts::get( array( 'content_type' => 'page', 'status' => Post::status('published'), 'nolimit' => 1 ) ) );
		}
		if( !$this->template_engine->assigned( 'page' ) ) {
			$page = Controller::get_var( 'page' );
			$this->assign('page', isset( $page ) ? $page : 1 );
		}
		parent::add_template_vars();

		if ( User::identify()->loggedin ) {
			Stack::add( 'template_header_javascript', Site::get_url('scripts') . '/jquery.js', 'jquery' );
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
		if( $comment->email == $post->author->email ) {
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
	
	public function theme_menu_empty($theme, $menu)
	{
		// Should pass menu name on to Posts::get(array('preset'=>$menu))
		if($menu == 'mainmenu') {
			$pages = Posts::get(array('content_type' => 'page', 'status' => Post::status('published')));
			$out = '';
			foreach( $pages as $page ) {
				$out .= '<li><a href="' . $page->permalink . '" title="' . $page->title . '">' . $page->title . '</a></li>' . "\n";
			}
			return $out;
		}
	}


	/**
	 * Customize comment form layout. Needs thorough commenting.
	 */
	public function action_form_comment( $form ) { 
		$form->commenter->caption = '<small><strong>' . _t('Name') . '</strong></small><span class="required">' . ( Options::get('comments_require_id') == 1 ? ' *' . _t('Required') : '' ) . '</span></label>';
		$form->commenter->template = 'k2_text';
		$form->commenter->value = $this->commenter_name;
		$form->email->caption = '<small><strong>' . _t('Mail') . '</strong> ' . _t( '(will not be published)' ) .'</small><span class="required">' . ( Options::get('comments_require_id') == 1 ? ' *' . _t('Required') : '' ) . '</span></label>';
		$form->email->template = 'k2_text';
		$form->email->value = $this->commenter_email;
		$form->url->caption = '<small><strong>' . _t('Website') . '</small></strong>';
		$form->url->template = 'k2_text';
		$form->url->value = $this->commenter_url;
	        $form->content->caption = '';
		$form->content->value = $this->commenter_content;
		$form->submit->caption = _t( 'Submit' );
	}

}

?>
