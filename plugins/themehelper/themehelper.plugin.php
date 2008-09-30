<?php

/**
 * ThemeHelper - Provides additional functions that may be useful to some themes.
 */

class ThemeHelper extends Plugin
{
	/**
	 * function info
	 * Returns information about this plugin
	 * @return array Plugin info array
	 **/
	function info()
	{
		return array (
			'name' => 'ThemeHelper',
			'url' => 'http://habariproject.org/',
			'author' => 'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'version' => '1.0',
			'description' => 'Provides additional functions that may be useful to some themes.',
			'license' => 'Apache License 2.0',
		);
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
	 * @return string Linked string to display for comment count
	 * @see ThemeHelper::theme_call_comments_count()
	 */
	public function theme_call_comments_link( $theme, $post, $zero = '%d Comments', $one = '1 Comment', $many = '%d Comments')
	{
		return '<a href="' . $post->permalink . '#comments" title="' . _t( 'Read Comments' ) . '">' . end( $theme->comments_count_return( $post, $zero, $one, $many ) ) . '</a>';
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
	public function theme_comments_count( $theme, $post, $zero = '%d Comments', $one = '1 Comment', $many = '%d Comments')
	{
		$count = $post->comments->approved->count;
		if ($count >= 1) {
			$text = _n( $one, $many, $count );
		}
		else {
			$text = _t( $zero );
		}
		return sprintf( $text, $count );
	}

	/**
	 * Returns the count of queries executed
	 *
	 * @return integer The query count
	 */
	public function theme_query_count()
	{
		return count(DB::get_profiles());
	}

	/**
	 * Returns total query execution time in seconds
	 *
	 * @return float Query execution time in seconds, with fractions.
	 */
	public function theme_query_time( )
	{
		return array_sum(array_map(create_function('$a', 'return $a->total_time;'), DB::get_profiles()));
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
		if($url != '') {
			$parsed_url = InputFilter::parse_url($url);
			if($parsed_url['host'] == '') {
				$url = '';
			}
			else {
				$url = InputFilter::glue_url($parsed_url);
			}
		}
		if($url != '') {
			return '<a href="'.$url.'">' . $comment->name . '</a>';
		}
		else {
			return $comment->name;
		}
	}

	/**
	 * Returns a comment form for themer's to use.
	 * Theme code: <?php $theme->comment_form( ); ?>
	 *
	 * @param Theme $theme The current theme
	 * @return Nothing. Outputs the comment form at
	 */
	public function theme_comment_form( $theme )
	{
		$ui = new FormUI( 'commentform' );
		$ui->set_option( 'form_action',  URL::get( 'submit_feedback', array( 'id' => $theme->post->id ) ) );

		$name = $ui->append( 'text', 'ename', 'null:null', _t( 'Name' ) . ' (<em>' . _t( 'Required' ) . '</em>) ' );
		$name->value = $theme->commenter_name;
		$name->id = 'name';

		$email = $ui->append( 'text', 'email', 'null:null', _t('Email' ) . ' (<em>' . _t( 'Required - not published' ) . '</em>) ' );
		$email->value = $theme->commenter_email;
		$email->id = $email->name;

		$url = $ui->append( 'text', 'url', 'null:null', _t( 'Web Address' ) . ' ' );
		$url->value = $theme->commenter_url;
		$url->id = $url->name;

		$commentContent = $ui->append( 'textarea', 'commentContent', 'null:null', _t( 'Message' ) . " (<em>" . _t( 'Required' ) . "</em>)" );
		$commentContent->value = $theme->commenter_content;
		$commentContent->id = 'content';

		$submit = $ui->append( 'submit', 'submit', _t( 'Say It' ) );
		$submit->id = $submit->name;

		$ui->out();

	}

}

?>
