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
	 * @param mixed $return Incoming return value from other plugins
	 * @param Theme $theme The current theme object
	 * @param Post $post Post object used to build the comments link
	 * @param string $zero String to return when there are no comments
	 * @param string $one String to return when there is one comment
	 * @param string $many String to return when there are more than one comment
	 * @return string Linked string to display for comment count
	 * @see ThemeHelper::filter_theme_call_comments_count()
	 */
	public function filter_theme_call_comments_link( $return, $theme, $post, $zero = '%d Comments', $one = '1 Comment', $many = '%d Comments')
	{
		return '<a href="' . $post->permalink . '" title="' . _( 'Read Comments' ) . '">' . $theme->comments_count($post, $zero, $one, $many) . '</a>';
	}

	/**
	 * Returns a full qualified URL of the specified post based on the comments count.
 	 *
	 * Passed strings are localized prior to parsing therefore to localize "%d Comments" in french, it would be "%d Commentaires".
	 *
	 * Since we use sprintf() in the final concatenation, you must format passed strings accordingly.
	 *
	 * @param mixed $return Incoming return value from other plugins
	 * @param Theme $theme The current theme object
	 * @param Post $post Post object used to build the comments link
	 * @param string $zero String to return when there are no comments
	 * @param string $one String to return when there is one comment
	 * @param string $many String to return when there are more than one comment
	 * @return string String to display for comment count
	 */
	public function filter_theme_call_comments_count( $return, $theme, $post, $zero = '%d Comments', $one = '1 Comment', $many = '%d Comments')
	{
		$count= $post->comments->approved->count;
		if ($count >= 1) {
			$text= _n( $one, $many, $count );
		}
		else {
			$text= _( $zero );
		}
		return sprintf( $text, $count );
	}

	/**
	 * Returns the appropriate alternate feed based on the currently matched rewrite rule.
	 *
	 * @param mixed $return Incoming return value from other plugins
	 * @param Theme $theme The current theme object
	 * @return string Link to the appropriate alternate Atom feed
	 */
	public function filter_theme_call_feed_alternate( $return )
	{
		$matched_rule= URL::get_matched_rule();
		switch ( $matched_rule->name ) {
			case 'display_entry':
			case 'display_page':
				echo URL::get( 'atom_entry', array( 'slug' => Controller::get_var('slug') ) );
				break;
			case 'display_entries_by_tag':
				echo URL::get( 'atom_feed_tag', array( 'tag' => Controller::get_var('tag') ) );
				break;
			case 'display_home':
			default:
				echo URL::get( 'atom_feed', array( 'index' => '1' ) );
		}
		return $return;
	}

	/**
	 * Returns the count of queries executed
	 *
	 * @return integer The query count
	 */
	public function filter_theme_call_query_count()
	{
		return count(DB::get_profiles());
	}

	/**
	 * Returns total query execution time in seconds
	 *
	 * @return float Query execution time in seconds, with fractions.
	 */
	public function filter_theme_call_query_time( )
	{
		return array_sum(array_map(create_function('$a', 'return $a->total_time;'), DB::get_profiles()));
	}

	/**
	 * Returns a humane commenter's link for a comment if a URL is supplied, or just display the comment author's name
	 *
	 * @param mixed $return Incoming return value from other plugins
	 * @param Theme $theme The current theme
	 * @param Comment $comment The comment object
	 * @return string A link to the comment author or the comment author's name with no link
	 */
	public function filter_theme_call_comment_author_link( $return, $theme, $comment )
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
	 * Returns the feedback URL to which comments should be submitted for the indicated Post
	 *
	 * @param mixed $return Incoming reurn value from other plugins
	 * @param Theme $theme The current theme
	 * @param Post $post The post object to get the feedback URL for
	 * @return string The URL to the feedback entrypoint for this comment
	 */
	public function filter_theme_call_comment_form_action( $return, $theme, $post )
	{
		return URL::get( 'submit_feedback', array( 'id' => $post->id ) );
	}

}

?>