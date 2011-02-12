<?php
/**
 * @package Habari
 *
 */

/**
 * Class which describes a single Tag object
 *
 */
class Tag extends Term
{

	public function __construct( $params = array() )
	{
		if ( is_string( $params ) ) {
			$params = array( 'term_display' => $params );
		}
		$params['vocabulary_id'] = Tags::vocabulary()->id;
		parent::__construct( $params );
	}

	/**
	 * Check if a tag exists on a published post, to see if we should match this rewrite rule.
	 *
	 * @return Boolean Whether the tag exists on a published post.
	 */
	public static function rewrite_tag_exists( $rule, $slug, $parameters )
	{
		$tags = explode( ' ', $rule->named_arg_values['tag'] );
		$tags = array_map( 'trim', $tags, array_fill( 0, count( $tags ), '-' ) );
		$tags = array_map( array( 'Tags', 'get_one' ), $tags );
		$initial_tag_count = count( $tags );
		$tags = array_filter( $tags );
		// Are all of the tags we asked for actual tags on this site?
		if ( count( $tags ) != $initial_tag_count ) {
			return false;
		}
		$tag_params = array();
		foreach ( $tags as $tag ) {
			$tag_params[] = $tag->term_display;
		}
		return ( $tag instanceOf Term && Posts::count_by_tag( $tag_params, Post::status( 'published' ) ) > 0 );
	}

	/**
	 * Create a tag and save it.
	 *
	 * @param array $paramarray An associative array of tag fields
	 * @return Tag The new Tag object
	 */
	public static function create( $paramarray )
	{
		$tag = new Tag( $paramarray );
		$tag = Tags::vocabulary()->add_term( $tag );
		return $tag;
	}

	/**
	 * Handle calls to this Tag object that are implemented by plugins
	 * @param string $name The name of the function called
	 * @param array $args Arguments passed to the function call
	 * @return mixed The value returned from any plugin filters, null if no value is returned
	 */
	public function __call( $name, $args )
	{
		array_unshift( $args, 'tag_call_' . $name, null, $this );
		return call_user_func_array( array( 'Plugins', 'filter' ), $args );
	}

}

?>
