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
		return '<a href="' . $post->permalink . '" title="' . _t( 'Read Comments' ) . '">' . $theme->comments_count($post, $zero, $one, $many) . '</a>';
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
		$count= $post->comments->approved->count;
		if ($count >= 1) {
			$text= _n( $one, $many, $count );
		}
		else {
			$text= _t( $zero );
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
}

?>