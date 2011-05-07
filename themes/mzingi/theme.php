<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } 

/**
 * Mzingi is a custom Theme class for the mzingi theme.
 *
 * @package Habari
 */

/**
 * @todo This stuff needs to move into the custom theme class:
 */


/**
 * A custom theme for mzingi output
 */
class Mzingi extends Theme
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
		if ( !$this->template_engine->assigned( 'pages' ) ) {
			$this->assign('pages', Posts::get( array( 'content_type' => 'page', 'status' => Post::status('published') ) ) );
		}
		//For Asides loop in sidebar.php
		$this->assign( 'asides', Posts::get( array( 'vocabulary' => array( 'tags:term' => 'aside' ), 'limit' => 5 ) ) );	

		if ( $this->request->display_entries_by_tag ) {
			if ( count($this->include_tag) && count($this->exclude_tag) == 0 ) {
				$this->tags_msg = _t('Posts tagged with %s', array(Format::tag_and_list($this->include_tag)));
			}
			else if ( count($this->exclude_tag) && count($this->include_tag) == 0 ) {
				$this->tags_msg = _t('Posts not tagged with %s', array(Format::tag_and_list($this->exclude_tag)));
			}
			else {
				$this->tags_msg = _t('Posts tagged with %s and not with %s', array(Format::tag_and_list($this->include_tag), Format::tag_and_list($this->exclude_tag)));
			}
		}

		parent::add_template_vars();
		
	}

	public function act_display_home( $user_filters = array() )
	{
		//To exclude aside tag from main content loop
		parent::act_display_home( array( 'vocabulary' => array( 'tags:not:term' => 'aside' ) ) );
	}

	/**
	 * Customize comment form layout with fieldsets. Needs thorough commenting.
	 */
	public function action_form_comment( $form ) { 
		$form->append( 'fieldset', 'cf_commenterinfo', _t( 'About You' ) );
		$form->move_before( $form->cf_commenterinfo, $form->cf_commenter );

		$form->cf_commenter->move_into($form->cf_commenterinfo);
		$form->cf_commenter->caption = _t('Name:') . '<span class="required">' . ( Options::get('comments_require_id') == 1 ? ' *' . _t('Required') : '' ) . '</span></label>';

		$form->cf_email->move_into( $form->cf_commenterinfo );
		$form->cf_email->caption = _t( 'Email Address:' ) . '<span class="required">' . ( Options::get('comments_require_id') == 1 ? ' *' . _t('Required') : '' ) . '</span></label>'; 

		$form->cf_url->move_into( $form->cf_commenterinfo );
		$form->cf_url->caption = _t( 'Web Address:' );

		$form->append('static','cf_disclaimer', _t( '<p><em><small>Email address is not published</small></em></p>') );
		$form->cf_disclaimer->move_into( $form->cf_commenterinfo );

		$form->append('fieldset', 'cf_contentbox', _t( 'Add to the Discussion' ) );
		$form->move_before($form->cf_contentbox, $form->cf_content);

		$form->cf_content->move_into($form->cf_contentbox);
	        $form->cf_content->caption = _t( 'Message: (Required)' );

		$form->cf_submit->caption = _t( 'Submit' );
	}

}

?>
