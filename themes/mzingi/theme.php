<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }

/**
 * Mzingi is a custom Theme class for the mzingi theme.
 *
 * @package Habari
 */

class MzingiHi extends Theme
{

	public function action_init_theme()
	{
		// Apply Format::autop() to comment content...
		Format::apply( 'autop', 'comment_content_out' );
		// Apply Format::tag_and_list() to post tags...
		Format::apply( 'tag_and_list', 'post_tags_out' );
		// Only uses the <!--more--> tag, with the 'more' as the link to full post
		Format::apply_with_hook_params( 'more', 'post_content_out', 'more' );
		// Creates an excerpt option. echo $post->content_excerpt;
		Format::apply( 'autop', 'post_content_excerpt' );
		Format::apply_with_hook_params( 'more', 'post_content_excerpt', 'more',60, 1 );
		// Format the calendar like date for home, entry.single and entry.multiple templates
		Format::apply( 'format_date', 'post_pubdate_out','<span class="calyear">{Y}</span><br><span class="calday">{j}</span><br><span  class="calmonth">{F}</span>' );
		Format::apply( 'nice_date', 'post_pubdate_nice', 'F j, Y' );
		Format::apply( 'nice_date', 'comment_date_out', 'M j, Y h:ia' );
	}

	/**
	 * Add additional template variables to the template output.
	 *
	 *  You can assign additional output values in the template here, instead of
	 *  having the PHP execute directly in the template.  The advantage is that
	 *  you would easily be able to switch between template types (RawPHP/HiEngine)
	 *  without having to port code from one to the other.
	 *
	 *  You could use this area to provide "recent comments" data to the template,
	 *  for instance.
	 *
	 *  Also, this function gets executed *after* regular data is assigned to the
	 *  template.  So the values here, unless checked, will overwrite any existing
	 *  values.
	 */
	public function action_add_template_vars($theme)
	{
		if ( !isset( $this->pages ) ) {
			$this->pages = Posts::get( 'page_list' );
		}
		//For Asides loop in sidebar.php
		$this->asides = Posts::get( array( 'vocabulary' => array( 'tags:term' => 'asides') ) );

		if ( is_object($this->request) && $this->request->display_entries_by_tag ) {
			if ( count( $this->include_tag ) && count( $this->exclude_tag ) == 0 ) {
				$this->tags_msg = _t( 'Posts tagged with %s', array( Format::tag_and_list( $this->include_tag ) ) );
			}
			else if ( count( $this->exclude_tag ) && count( $this->include_tag ) == 0 ) {
				$this->tags_msg = _t( 'Posts not tagged with %s', array( Format::tag_and_list( $this->exclude_tag ) ) );
			}
			else {
				$this->tags_msg = _t( 'Posts tagged with %s and not with %s', array( Format::tag_and_list( $this->include_tag ), Format::tag_and_list( $this->exclude_tag ) ) );
			}
		}

		// Add the stylesheet to the stack for output
		$this->add_style( array( $this->get_url( 'style.css' ), 'screen' ), 'header', 'style' );

		// Add the extra login form styling on the login page
		if( is_object( $this->request ) && $this->request->auth ) {
			Stack::add( 'template_header_javascript', 'jquery' );
			$login_style = <<< LOGIN_STYLE
.off_reset {}

.on_reset, input[type=submit].on_reset {
	display: none;
}
.do_reset .on_reset, .do_reset input[type=submit].on_reset {
	display: block;
}
.do_reset .off_reset {
	display: none;
}
LOGIN_STYLE;
			$this->add_style( array( $login_style, 'screen' ), 'header', 'login_style', 'style' );

			$login_js = <<< LOGIN_JS
$(document).ready( function() {
	$('.reset_link').click(function(){\$(this).closest('form').toggleClass('do_reset'); return false;});
});
LOGIN_JS;
			// Habari always registers jquery as a loadable stack item, so listing
			// will automatically load it.
			$this->add_script( $login_js, 'footer', 'login_js', 'jquery' );
		}
	}

	public function act_display_home( $user_filters = array() )
	{
		//To exclude posts with the aside tag from the main content loop
		parent::act_display_home( array( 'vocabulary' => array( 'tags:not:term' => 'aside' ) ) );
	}

	public function theme_next_post_link( $theme )
	{
		$next_link = '';
		if( isset( $theme->post ) ) {
			$next_post = $theme->post->ascend();
			if( ( $next_post instanceOf Post ) ) {
				$next_link = '<a href="' . $next_post->permalink. '" title="' . $next_post->title .'" >' . '&laquo; ' .$next_post->title . '</a>';
			}
		}

		return $next_link;
	}

	public function theme_prev_post_link( $theme )
	{
		$prev_link = '';

		if( isset( $theme->post ) ) {
		$prev_post = $theme->post->descend();
		if( ( $prev_post instanceOf Post) ) {
			$prev_link= '<a href="' . $prev_post->permalink. '" title="' . $prev_post->title .'" >' . $prev_post->title . ' &raquo;' . '</a>';
		}
		}
		return $prev_link;
	}

	public function theme_feed_site( $theme )
	{
		return URL::get( 'atom_feed', array( 'index' => '1' ) );
	}

	public function theme_prevpage_link( $theme )
	{
		return parent::theme_prev_page_link( $theme, '&laquo; ' . _t('Newer Posts') );
	}

	public function theme_nextpage_link( $theme )
	{
		return parent::theme_next_page_link( $theme, '&raquo; ' . _t('Older Posts') );
	}

	public function theme_prevpage_results( $theme )
	{
		return parent::theme_prev_page_link( $theme, '&laquo; ' . _t('Newer Reults') );
	}

	public function theme_nextpage_results( $theme )
	{
		return parent::theme_next_page_link( $theme, '&raquo; ' . _t('Older Reults') );
	}

	public static function theme_pageselector( $theme )
	{
		return parent::theme_page_selector( $theme, null, array( 'leftSide' => 2, 'rightSide' => 2 ) );
	}

	public function theme_comment_form( $theme )
	{
		return $theme->post->comment_form();
	}

	public function theme_search_form( $theme )
	{
		$form = new FormUI( 'searchform');
		$form->set_properties( array( 'action' => Url::get( 'display_search' ), 'method' => 'GET' ) );
		$form->append( FormControlText::create( 'criteria' )-> set_properties(
			array( 'type'  => 'search', 'id' => 's', 'placeholder' => _t( "Search" )
			 ))->set_value(
				isset( $theme->criteria ) ? htmlentities($theme->criteria, ENT_COMPAT, 'UTF-8') : '' )
		);
		$form->append( FormControlSubmit::create( 'searchsubmit' )->set_caption( _t( 'Go!' ) ) );
		return $form;
	}

	/**
	 * Customize the comment form layout with fieldsets.
	 */
	public function action_form_comment( FormUI $form ) {
		//Create a fieldset for Name, Email and URL before Name
		/** @var FormControlFieldset $cf_commenterinfo  */
		$cf_commenterinfo = $form->insert($form->label_for_cf_commenter, FormControlFieldset::create('cf_commenterinfo')->set_caption( _t( 'About You' ) ) );
		//move the Name ( cf_commenter) into the fieldset
		$form->move_into($form->label_for_cf_commenter, $cf_commenterinfo );

		//$form->label_for_cf_commenter->set_label( _t( 'Name:' ) . '<span class="required">' . ( Options::get( 'comments_require_id' ) == 1 ? ' *' . _t( 'Required' ) : '' ) . '</span>' );
		//move the Email ( cf_email) into the Fieldset
		$form->move_into( $form->label_for_cf_email, $cf_commenterinfo );

//		$label_for_cf_email = $form->label_for_cf_email;
//		$label_for_cf_email->set_label(_t( 'Email Address:' ) . '<span class="required">' . ( Options::get( 'comments_require_id' ) == 1 ? ' *' . _t( 'Required' ) : '' ) . '</span>');
		//move the URL into the fieldset
		$form->move_into( $form->label_for_cf_url, $cf_commenterinfo );
		$form->label_for_cf_url->set_label( _t( 'Web Address:' ) );
		//add a disclaimer/message
		$cf_commenterinfo->append(FormControlStatic::create('cf_disclaimer')->set_static( _t( '<p><em><small>Email address is not published</small></em></p>' ) ) );
		//create a second fieldset for the comment textarea
		$cf_contentbox = $form->append(FormControlFieldset::create('cf_contentbox')->set_caption( _t( 'Add to the Discussion' ) ) );
		//move the fieldset before the textarea
		$form->move_before( $form->cf_contentbox, $form->label_for_cf_content );
		//move the textarea into the second fieldset
		$form->move_into($form->label_for_cf_content, $cf_contentbox );
		$form->label_for_cf_content->set_label( _t( 'Message: (Required)' ) );

		$form->cf_submit->set_caption( _t( 'Submit' ) );
	}

	public function action_form_login( $form )
	{
		$form->set_wrap_each( '<div>%s</div>' );
		$form->habari_username->set_properties( array( 'placeholder' => _t( 'User name' ) ) );
		$form->habari_password->set_properties( array( 'placeholder' => _t( 'Password' ) ) );
	}
}

?>
