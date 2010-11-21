<?php
/**
 * @package Habari
 *
 */

/**
* Habari Terms Class
* Holds multiple Term object instances in an array-like structure, 
* for the purpose of acting on them en-masse, or testing against them. 
*
*/
class Terms extends ArrayObject
{

	/**
	 * See if a term or set of terms is in this set of terms
	 *
	 * @param mixed $tags. A string containing a string or a comma separated list of strings,
	 *  or an array of strings, Terms, or Tags
	 * @return boolean. Whether the tag(s) is in the current set of tags.
	 */
	public function has( $tags )
	{
		$tags = (array)new Tags( $tags );

		$diff = array_diff( $tags, (array)$this );
		foreach( $tags as $tag ) {
			if ( in_array( $tag, $diff ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Turns a comma-separated string or array of terms into an array of Term objects
	 * @param mixed $terms A comma-separated string or array of string terms
	 * @param string $term_class The class of the Term object type to create from each string
	 * @param Vocabulary $vocabulary An instance of the Vocabulary that might hold the terms.  
	 * 	 Use existing term object data if found in the specified vocabulary.   	 
	 * @return Terms An instance of Terms contianing the specified Term objects
	 **/
	public static function parse( $terms, $term_class = 'Term', $vocabulary = null )
	{
		if ( is_string( $terms ) ) {
			if ( '' === $terms ) {
				return new Terms();
			}
			// dirrty ;)
			$rez = array( '\\"'=>':__unlikely_quote__:', '\\\''=>':__unlikely_apos__:' );
			$zer = array( ':__unlikely_quote__:'=>'"', ':__unlikely_apos__:'=>"'" );
			// escape
			$tagstr = str_replace( array_keys( $rez ), $rez, $terms );
			// match-o-matic
			preg_match_all( '/((("|((?<= )|^)\')\\S([^\\3]*?)\\3((?=[\\W])|$))|[^,])+/u', $tagstr, $matches );
			// cleanup
			$terms = array_map( 'trim', $matches[0] );
			$terms = preg_replace( array_fill( 0, count( $terms ), '/^(["\'])(((?!").)+)(\\1)$/'), '$2', $terms );
			// unescape
			$terms = str_replace( array_keys( $zer ), $zer, $terms );
			// hooray
		}
		if(is_array($terms)) {
			if($vocabulary instanceof Vocabulary) {
				foreach($terms as $k => $term) {
					if($saved_term = $vocabulary->get_term($term, $term_class)) {
						$terms[$k] = $saved_term;
					}
					else {
						$terms[$k] = new $term_class($term);
					}
				}
//Utils::debug($terms);
			}
			else {
				array_walk( $terms, create_function('&$tag', '$tag = new ' . $term_class . '($tag);') );
			}
			return new Terms($terms);
		}
		return new Terms();
	}

}

?>
