<?php
/**
 * @package Habari
 *
 */

/**
 * HTML Token Set (created by @see HTMLTokenizer)
 */
class HTMLTokenSet implements Iterator, ArrayAccess
{
	protected $tokens = array();

	protected $sliceOffsetBegin  = null;
	protected $sliceOffsetLength = null;

	public $escape;

	public function __construct( $escape = true )
	{
		$this->escape = $escape;
	}

	public function __tostring()
	{
		$out = '';
		foreach ( $this->tokens as $token ) {
			$out .= self::token_to_string( $token, $this->escape );
		}
		return $out;
	}

	/**
	 * Convert a token to a string
	 *
	 * @param array $token The token to convert
	 * @param bool $escape Whether to escape the string that is returned
	 * @return string The string representation of the token
	 */
	public static function token_to_string( array $token, $escape = true )
	{
		switch ( $token['type'] ) {
			case HTMLTokenizer::NODE_TYPE_TEXT:
				return $escape ? Utils::htmlspecialchars( html_entity_decode( $token['value'], ENT_QUOTES, 'UTF-8' ) ) : $token['value'];
				break;

			case HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN:
			case HTMLTokenizer::NODE_TYPE_ELEMENT_EMPTY:
				$out  = '<' . $token['name'];
				if ( isset( $token['attrs'] ) && is_array( $token['attrs'] ) ) {
					foreach ( $token['attrs'] as $attr => $attrval ) {
						$out .= " {$attr}=\"";
						if ( $escape ) {
							$out .= Utils::htmlspecialchars( html_entity_decode( $attrval, ENT_QUOTES, 'UTF-8' ) );
						}
						else {
							$out .= html_entity_decode( $attrval, ENT_QUOTES, 'UTF-8' );
						}
						$out .= '"';
					}
				}
				$out .= '>';
				break;

			case HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE:
				$out = "</{$token['name']}>";
				break;

			case HTMLTokenizer::NODE_TYPE_PI:
				$out = "<?{$token['name']}{$token['value']}>";
				break;

			case HTMLTokenizer::NODE_TYPE_COMMENT:
				$out = "<!--{$token['value']}-->";
				break;

			case HTMLTokenizer::NODE_TYPE_CDATA_SECTION:
				$out = "<![CDATA[{$token['value']}]]>";
				break;

			case HTMLTokenizer::NODE_TYPE_STATEMENT:
				$out = "<!{$token['name']}";
				if ( !empty($token['value']) ) {
					$out .= " {$token['value']}";
				}
				$out .= ">";
				break;
		}
		return $out;
	}

	/**
	 * Get the offset of the last token in this HTMLTokenSet
	 *
	 * @return integer the offset of the last token in this HTMLTokenSet
	 */
	public function get_end_offset()
	{
		return $this->sliceOffsetBegin + $this->sliceOffsetLength;
	}

	/**
	 * Fetch a section of the tokens, based on passed criteria
	 *
	 * @param mixed $names The name of an html tag, or an array of names of html tags to get the tokens of
	 * @param array $attr Any attributes for the token that you want to filter by
	 * @return array An array of the HTMLTokenSets that were found
	 */
	public function slice( $names, array $attr = null )
	{
		$names = (array)$names;
		$ret = array();
		foreach ( $names as $name ) {
			$offset = 0;
			$slices = array();
			while ( $slice = $this->find_slice( $offset, $name, $attr ) ) {
				$slices[] = $slice;
				$offset = $slice->get_end_offset();
			}
			// Need to reverse this because we need to splice the last chunks first
			// if we splice the earlier chunks first, then the offsets get all
			// messed up. Trust me.
			$ret = array_merge( $ret, array_reverse( $slices ) );
		}
		return $ret;
	}

	/**
	 * This is a support function for HTMLTokenSet::slice(). It finds a specific slice
	 * @param integer $offset The offset at which to start looking for relevant tokens
	 * @param string $name The name of the html tag being searched for
	 * @param array $attr The attributes of the html tag being searched for
	 *
	 * @return HTMLTokenSet
	 */
	protected function find_slice( $offset, $name, array $attr )
	{
		// find start:
		$foundStart = false;
		for ( ; $offset < count( $this->tokens ); $offset++ ) {
			// short circuit if possible
			if ( $this->tokens[$offset]['type'] != HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN ) {
				continue;
			}
			if ( $this->tokens[$offset]['name'] != $name ) {
				continue;
			}

			// check attributes
			if ( !count( $attr ) ) {
				$foundStart = true;
				break; // To: FOUNDSTARTBREAKPOINT
			}
			foreach ( $attr as $compareName => $compareVal ) {
				if ( isset( $this->tokens[$offset]['attrs'][$compareName] ) &&
						stripos( $this->tokens[$offset]['attrs'][$compareName], $compareVal ) !== false ) {
					$foundStart = true;
					break 2; // To: FOUNDSTARTBREAKPOINT
				}
			}
		}
		// Fake label: FOUNDSTARTBREAKPOINT

		// short circuit if possible:
		if ( !$foundStart ) {
			return false;
		}

		$startOffset = $offset;

		// find the closing tag
		// (keep a stack so we don't mistake a nested node for this closing node)
		$stackDepth = 0;
		$foundEnd = false;
		for ( ; $offset < count( $this->tokens ); $offset++ ) {
			switch ( $this->tokens[$offset]['type'] ) {
				case HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN:
					if ( $this->tokens[$offset]['name'] == $name ) {
						++$stackDepth;
					}
					break;
				case HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE:
					if ( $this->tokens[$offset]['name'] == $name ) {
						--$stackDepth;
					}
					break;
				// default: skip
			}
			if ( $stackDepth <= 0 ) {
				$foundEnd = true;
				break;
			}
		}

		// short circuit if possible:
		if ( !$foundEnd ) {
			return false;
		}

		$offsetLength = $offset - $startOffset + 1;

		// now, place the found set into a new HTMLTokenSet:
		$slice = new HTMLTokenSet($this->escape);
		$slice->sliceOffsetBegin  = $startOffset;
		$slice->sliceOffsetLength = $offsetLength;
		$slice->tokens = array_slice( $this->tokens, $slice->sliceOffsetBegin, $slice->sliceOffsetLength );
		return $slice;
	}

	/**
	 * Remove the first and last tokens from this HTMLTokenSet
	 *
	 * @return nothing
	 */
	public function trim_container()
	{
		$this->tokens = array_slice( $this->tokens, 1, -1 );
	}

	/**
	 * Replace a set of tokens in this HTMLTokenSet
	 *
	 * @param HTMLTokenSet $slice The tokens to insert in place of the old tokens
	 * @return nothing
	 */
	public function replace_slice( HTMLTokenSet $slice )
	{
		array_splice(
			$this->tokens,
			$slice->sliceOffsetBegin,
			$slice->sliceOffsetLength,
			$slice->tokens
		);
	}

	/**
	 * Replace a full set of tokens with new tokens. The tokens are replaced
	 * in place as well as being returned
	 *
	 * @param string $source The text to create the new set of tokens from
	 * @return HTMLTokenSet The new set of tokens created
	 */
	public function tokenize_replace( $source )
	{
		$ht = new HTMLTokenizer( $source, $this->escape );
		$this->tokens = $ht->parse()->tokens;
		return $this->tokens;
	}

	/**
	 * Insert an HTMLTokenset before the given position
	 *
	 * @param HTMLTokenset $set. The HTMLTokenset to insert
	 * @param integer $pos. The position to insert the HTMLTokenset before
	 * @return Nothing
	 */
	public function insert( HTMLTokenset $set, $pos = 0 )
	{
		$set->end();
		$length = $set->key() - 1;

		$pre = array_slice( $this->tokens, 0, $pos );
		$post = array_slice( $this->tokens, $pos );
		$set->rewind();
		while ( $set->valid() ) {
			$pre[] = $set->current();
			$set->next();
		}
		$this->tokens = array_merge( $pre, $post );
	}

	////////////////////////////////////////////////////

	// Iterator implemetation:

	public function rewind()
	{
		reset( $this->tokens );
	}

	public function current()
	{
		return current( $this->tokens );
	}

	public function key()
	{
		return key( $this->tokens );
	}

	public function next()
	{
		return next( $this->tokens );
	}

	public function valid()
	{
		return $this->current() !== false;
	}

	public function end()
	{
		return end( $this->tokens );
	}

	// ArrayAccess implementation

	public function offsetExists( $offset )
	{
		return isset( $this->tokens[ $offset ] );
	}

	public function offsetGet( $offset )
	{
		return $this->tokens[ $offset ];
	}

	public function offsetSet( $offset, $value )
	{
		if ( $offset === null ) {
			$this->tokens[] = $value;
		}
		else {
			$this->tokens[ $offset ] = $value;
		}
	}

	public function offsetUnset( $offset )
	{
		unset( $this->tokens[ $offset ] );
	}
}
?>
