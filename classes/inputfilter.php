<?php
/**
 * @package Habari
 *
 */

/**
 * Input filtering functions.
 *
 */
class InputFilter
{
	/**
	 * Allowed elements.
	 */
	private static $whitelist_elements = array(
		// http://www.w3.org/TR/html4/struct/global.html#h-7.5.4
		'div', 'span',
		// http://www.w3.org/TR/html4/struct/links.html#h-12.2
		'a',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.2.1
		'strong', 'em', 'code', 'kbd', 'dfn', 'samp', 'var', 'cite', 'abbr', 'acronym',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.2.2
		'blockquote', 'q',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.2.3
		'sub', 'sup',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.3.1
		'p',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.3.2.1
		'br',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.3.4
		'pre',
		// http://www.w3.org/TR/html4/struct/text.html#h-9.4
		'ins', 'del',
		// http://www.w3.org/TR/html4/struct/lists.html#h-10.2
		'ol', 'ul', 'li',
		// http://www.w3.org/TR/html4/struct/lists.html#h-10.3
		'dl', 'dt', 'dd',
		// http://www.w3.org/TR/html4/present/graphics.html#h-15.2.1
		'b', 'i', 'u', 's', 'tt',
		// http://www.w3.org/TR/html4/struct/global.html#h-7.5.5
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		// http://www.w3.org/TR/html4/struct/global.html#h-7.5.6
		'address',
		// http://www.w3.org/TR/html4/struct/dirlang.html#h-8.2.4
		'bdo',
		// http://www.w3.org/TR/html4/struct/tables.html#h-11.2.1
		'table',
		// http://www.w3.org/TR/html4/struct/tables.html#h-11.2.2
		'caption',
		// http://www.w3.org/TR/html4/struct/tables.html#h-11.2.3
		'thead', 'tfoot', 'tbody',
		// http://www.w3.org/TR/html4/struct/tables.html#h-11.2.4
		'colgroup', 'col',
		// http://www.w3.org/TR/html4/struct/tables.html#h-11.2.5
		'tr',
		// http://www.w3.org/TR/html4/struct/tables.html#h-11.2.6
		'th', 'td',
		// http://www.w3.org/TR/html4/struct/objects.html#h-13.2
		'img',
		// http://www.w3.org/TR/html4/struct/objects.html#h-13.6.1
		'map', 'area',
		// http://www.w3.org/TR/html4/present/graphics.html#h-15.2.1 (the non-deprecated ones)
		'tt', 'i', 'b', 'big', 'small',
		// http://www.w3.org/TR/html4/present/graphics.html#h-15.3
		'hr',
		// http://www.w3.org/TR/html4/present/frames.html#h-16.2.1
		'frameset',
		// http://www.w3.org/TR/html4/present/frames.html#h-16.2.2
		'frame',
		// http://www.w3.org/TR/html4/present/frames.html#h-16.4.1
		'noframes',
		// http://www.w3.org/TR/html4/present/frames.html#h-16.5
		'iframe',

	);

	/**
	 * Allowed attributes and values.
	 */
	private static $whitelist_attributes = array(
		// attributes that are valid for ALL elements (a subset of coreattrs)
		// elements that only take coreattrs don't need to be listed separately
		'*' => array(
			'lang' => 'language-code',
			'xml:lang' => 'language-code', // this is our xhtml support... all of it
			'dir' => array( 'ltr', 'rtl', ),
			'title' => 'text',
		),
		// http://www.w3.org/TR/html4/struct/links.html#h-12.2
		'a' => array( 'href' => 'uri', ),
		// http://www.w3.org/TR/html4/struct/text.html#h-9.4
		'ins' => array( 'cite' => 'uri', 'datetime' => 'datetime', ),
		'del' => array( 'cite' => 'uri', 'datetime' => 'datetime', ),
		// http://www.w3.org/TR/html4/struct/text.html#h-9.2.2
		'blockquote' => array( 'cite' => 'uri', ),
		'q' => array( 'cite' => 'uri', ),
		'img' => array( 'src' => 'uri', 'alt' => 'text' ),
	);

	/**
	 * #EMPTY elements.
	 */
	private static $elements_empty = array(
		'img',
	);

	/**
	 * Protocols that are ok for use in URIs.
	 */
	private static $whitelist_protocols = array(
		'http', 'https', 'ftp', 'mailto', 'irc', 'news', 'nntp', 'callto', 'rtsp', 'mms', 'svn',
	);

	/**
	 * List of all defined named character entities in HTML 4.01 and XHTML.
	 */
	private static $character_entities = array(
		'nbsp', 'iexcl', 'cent', 'pound', 'curren', 'yen', 'brvbar', 'sect', 'uml',
		'copy', 'ordf', 'laquo', 'not', 'shy', 'reg', 'macr', 'deg', 'plusmn',
		'sup2', 'sup3', 'acute', 'micro', 'para', 'middot', 'cedil', 'sup1', 'ordm',
		'raquo', 'frac14', 'frac12', 'frac34', 'iquest', 'Agrave', 'Aacute', 'Acirc',
		'Atilde', 'Auml', 'Aring', 'AElig', 'Ccedil', 'Egrave', 'Eacute', 'Ecirc',
		'Euml', 'Igrave', 'Iacute', 'Icirc', 'Iuml', 'ETH', 'Ntilde', 'Ograve',
		'Oacute', 'Ocirc', 'Otilde', 'Ouml', 'times', 'Oslash', 'Ugrave', 'Uacute',
		'Ucirc', 'Uuml', 'Yacute', 'THORN', 'szlig', 'agrave', 'aacute', 'acirc',
		'atilde', 'auml', 'aring', 'aelig', 'ccedil', 'egrave', 'eacute', 'ecirc',
		'euml', 'igrave', 'iacute', 'icirc', 'iuml', 'eth', 'ntilde', 'ograve',
		'oacute', 'ocirc', 'otilde', 'ouml', 'divide', 'oslash', 'ugrave', 'uacute',
		'ucirc', 'uuml', 'yacute', 'thorn', 'yuml', 'fnof', 'Alpha', 'Beta', 'Gamma',
		'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa', 'Lambda', 'Mu',
		'Nu', 'Xi', 'Omicron', 'Pi', 'Rho', 'Sigma', 'Tau', 'Upsilon', 'Phi', 'Chi',
		'Psi', 'Omega', 'alpha', 'beta', 'gamma', 'delta', 'epsilon', 'zeta', 'eta',
		'theta', 'iota', 'kappa', 'lambda', 'mu', 'nu', 'xi', 'omicron', 'pi', 'rho',
		'sigmaf', 'sigma', 'tau', 'upsilon', 'phi', 'chi', 'psi', 'omega',
		'thetasym', 'upsih', 'piv', 'bull', 'hellip', 'prime', 'Prime', 'oline',
		'frasl', 'weierp', 'image', 'real', 'trade', 'alefsym', 'larr', 'uarr',
		'rarr', 'darr', 'harr', 'crarr', 'lArr', 'uArr', 'rArr', 'dArr', 'hArr',
		'forall', 'part', 'exist', 'empty', 'nabla', 'isin', 'notin', 'ni', 'prod',
		'sum', 'minus', 'lowast', 'radic', 'prop', 'infin', 'ang', 'and', 'or',
		'cap', 'cup', 'int', 'there4', 'sim', 'cong', 'asymp', 'ne', 'equiv', 'le',
		'ge', 'sub', 'sup', 'nsub', 'sube', 'supe', 'oplus', 'otimes', 'perp',
		'sdot', 'lceil', 'rceil', 'lfloor', 'rfloor', 'lang', 'rang', 'loz',
		'spades', 'clubs', 'hearts', 'diams', 'quot', 'amp', 'lt', 'gt', 'OElig',
		'oelig', 'Scaron', 'scaron', 'Yuml', 'circ', 'tilde', 'ensp', 'emsp',
		'thinsp', 'zwnj', 'zwj', 'lrm', 'rlm', 'ndash', 'mdash', 'lsquo', 'rsquo',
		'sbquo', 'ldquo', 'rdquo', 'bdquo', 'dagger', 'Dagger', 'permil', 'lsaquo',
		'rsaquo', 'euro',
	);

	private static $character_entities_re = '';

	public static function __static ( ) {
		self::$whitelist_elements = Plugins::filter( 'inputfilter_whitelist_elements', self::$whitelist_elements );
		self::$whitelist_attributes = Plugins::filter( 'inputfilter_whitelist_attributes', self::$whitelist_attributes );
		self::$elements_empty = Plugins::filter( 'inputfilter_elements_empty', self::$elements_empty );
		self::$whitelist_protocols = Plugins::filter( 'inputfilter_whitelist_protocols', self::$whitelist_protocols );
		self::$character_entities = Plugins::filter( 'inputfilter_character_entities', self::$character_entities );
		self::$character_entities_re = Plugins::filter( 'inputfilter_character_entities_re', self::$character_entities_re );
	}

	/**
	 * Perform all filtering, return new string.
	 * @param string $str Input string.
	 * @return string Filtered output string.
	 */
	public static function filter( $str )
	{
		if ( !MultiByte::valid_data( $str ) ) {
			return '';
		}
		else {
			do {
				$_str = $str;
				$str = self::strip_nulls( $str );
				$str = self::strip_illegal_entities( $str );
				$str = self::filter_html_elements( $str );
			} while ( $str != $_str );

			return $str;
		}
	}

	/**
	 * Remove nulls, return new string.
	 * @param string $str Input string.
	 * @return string Filtered output string.
	 */
	public static function strip_nulls( $str )
	{
		$str = str_replace( '\0', '', $str );

		return $str;
	}

	/**
	 * Callback function for strip_illegal_entities, do not use.
	 * @access private
	 * @param array $m matches
	 */
	public static function _validate_entity( $m )
	{
		$is_valid = false;

		/**
		 * valid entity references have the form
		 *   /&named([;<\n\r])/
		 * for named entities, or
		 *   /&#(\d{1,5}|[xX][0-9a-fA-F]{1,4})([;<\n\r])/
		 * for numeric character references
		 */

		$e = trim( $m[1] );
		$r = $m[2];
		if ( $r == ';' ) {
			$r = '';
		}

		if ( $e{0} == '#' ) {
			$e = strtolower( $e );
			if ( $e{1} == 'x' ) {
				$e = hexdec( substr( $e, 2 ) );
			}
			else {
				$e = substr( $e, 1 );
			}

			// numeric character references may only have values in the range 0-65535 (16 bit)
			// we strip null, though, just for kicks
			$is_valid = ( intval( $e ) > 0 && intval( $e ) <= 65535 );

			if ( $is_valid ) {
				// normalize to decimal form
				$e = '#' . intval( $e ) . ';';
			}
		}
		else {
			if ( self::$character_entities_re == '' ) {
				self::$character_entities_re = ';(' . implode( '|', self::$character_entities ) . ');';
			}

			// named entities must be known
			$is_valid = preg_match( self::$character_entities_re, $e, $matches );

			// XXX should we map named entities to their numeric equivalents?

			if ( $is_valid ) {
				// normalize to name and nothing but the name... eh.
				$e = $matches[1] . ';';
			}
		}

		return $is_valid ? '&' . $e . $r : '';
	}

	/**
	 * Remove illegal entities, return new string.
	 * @param string $str Input string.
	 * @return string Filtered output string.
	 */
	public static function strip_illegal_entities( $str )
	{
		$str = preg_replace_callback( "/&([^;<\n\r]+)([;<\n\r])/", array( __CLASS__, '_validate_entity' ), $str );

		return $str;
	}

	/**
	 * This really doesn't belong here. It should also be done much better. This is a nasty, NASTY kludge.
	 */
	public static function parse_url( $url )
	{
		// result array
		$r = array(
			'scheme' => '',
			'host' => '',
			'port' => '',
			'user' => '',
			'pass' => '',
			'path' => '',
			'query' => '',
			'fragment' => '',
			//
			'is_relative' => false,
			'is_pseudo' => false,
			'is_error' => false,
			//
			'pseudo_args' => '',
		);

		// sanitize the url
		$sanitized = html_entity_decode( $url, null, 'UTF-8' );		// make double-sure we've converted all entities
		$sanitized = filter_var( $sanitized, FILTER_SANITIZE_URL );		// strip everything but ascii, essentially

		$sanitized_scheme = parse_url( $sanitized, PHP_URL_SCHEME );

		// Use PHP's parse_url to get the basics
		$parsed = parse_url( $url );
		if ( $parsed == false ) {
			$r['is_error'] = true;
			return $r;
		}
		$r = array_merge( $r, $parsed );

		// replace the scheme with the one we got from the fully-sanitized string
		$r['scheme'] = $sanitized_scheme;

		$r['is_pseudo'] = !in_array( $r['scheme'], array( 'http', 'https', '' ) );
		$r['is_relative'] = ( $r['host'] == '' && !$r['is_pseudo'] );

		if ( $r['is_pseudo'] ) {
			$r['pseudo_args'] = $r['path'];
			$r['path'] = '';
		}

		return $r;
	}

	/**
	 * Restore a URL separated by a parse_url() call.
	 * @param $parsed_url array An array as returned by parse_url()
	 */
	public static function glue_url( $parsed_url )
	{
		if ( ! is_array( $parsed_url ) ) {
			return false;
		}

		$res = '';
		$res .= $parsed_url['scheme'];
		if ( $parsed_url['is_pseudo'] || in_array( strtolower( $parsed_url['scheme'] ), array( 'mailto', 'callto' ) ) ) {
			$res .= ':';
		}
		else {
			if ( ! $parsed_url['is_relative'] ) {
				$res .= '://';
			}
		}
		if ( $parsed_url['is_pseudo'] ) {
			$res .= $parsed_url['pseudo_args'];
		}
		else {
			// user[:pass]@
			if ( $parsed_url['user'] ) {
				$res .= $parsed_url['user'];
				if ( $parsed_url['pass'] ) {
					$res .= ':' . $parsed_url['pass'];
				}
				$res .= '@';
			}
			$res .= $parsed_url['host'];
			if ( !empty( $parsed_url['port'] ) ) {
				if ( array_key_exists( $parsed_url['scheme'], Utils::scheme_ports() ) && Utils::scheme_ports( $parsed_url['scheme'] ) == $parsed_url['port'] ) {
					// default port for this scheme, do nothing
				}
				else {
					$res .= ':' . $parsed_url['port'];
				}
			}
			if ( !empty( $parsed_url['path'] ) ) {
				$res .= $parsed_url['path'];
			}
			else {
				$res .= '/';
			}
			if ( $parsed_url['query'] ) {
				$res .= '?' . $parsed_url['query'];
			}
			if ( $parsed_url['fragment'] ) {
				$res .= '#' . $parsed_url['fragment'];
			}
		}

		return $res;
	}

	private static function check_attr_value( $k, $v, $type )
	{
		if ( is_array( $type ) ) {
			// array of allowed values, exact matches only
			return in_array( $v, $type, true );
		}
		else {
			// data type
			switch ( $type ) {
				case 'uri':
					// RfC 2396 <http://www.ietf.org/rfc/rfc2396.txt>
					$bits = self::parse_url( $v );
					return $bits['is_relative'] || in_array( $bits['scheme'], self::$whitelist_protocols );
					break;
				case 'language-code':
					// RfC 1766 <http://www.ietf.org/rfc/rfc1766.txt>
					//    Language-Tag = Primary-tag *( "-" Subtag )
					//    Primary-tag = 1*8ALPHA
					//    Subtag = 1*8ALPHA
					return preg_match( '/^[a-zA-Z]{1,8}(?:-[a-zA-Z]{1,8})*$/i', $v );
					break;
				case 'text':
					// XXX is this sufficient?
					return is_string( $v );
					break;
				case 'datetime':
					// <http://www.w3.org/TR/1998/NOTE-datetime-19980827>
					// <http://www.w3.org/TR/html4/types.html#h-6.11>
					//    YYYY-MM-DDThh:mm:ssTZD
					return preg_match( '/^[0-9]{4}-[0-1][0-9]-[0-3][0-9]T[0-2][0-9]:[0-5][0-9]:[0-5][0-9](?:Z|[\+-][0-2][0-9]:[0-5][0-9])$/', $v );
					break;
				default:
					Error::raise( _t( 'Unknown attribute type "%s" in %s', array( $type, __CLASS__ ) ) );
					return false;
			}
		}
	}

	/**
	 * @todo TODO must build DOM to really properly remove offending elements
	 * @todo TODO properly filter URLs
	 */
	public static function filter_html_elements( $str )
	{
		$tokenizer = new HTMLTokenizer( $str );

		// tokenize, baby
		$tokens = $tokenizer->parse();

		// filter token stream
		$filtered = new HTMLTokenSet;
		$stack = array();
		foreach ( $tokens as $node ) {
			switch ( $node['type'] ) {
				case HTMLTokenizer::NODE_TYPE_TEXT:
					$node['value'] = html_entity_decode( $node['value'], ENT_QUOTES, MultiByte::hab_encoding() );
					break;
				case HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN:
				case HTMLTokenizer::NODE_TYPE_ELEMENT_EMPTY:
					// is this element allowed at all?
					if ( ! in_array( strtolower( $node['name'] ), self::$whitelist_elements ) ) {
						if ( ! in_array( strtolower( $node['name'] ), self::$elements_empty ) ) {
							array_push( $stack, $node['name'] );
						}
						//$node = null; //remove the node completely
						// convert the node to text
						$node = array(
							'type' => HTMLTokenizer::NODE_TYPE_TEXT,
							'name' => '#text',
							'value' => HTMLTokenSet::token_to_string( $node ),
							'attrs' => array(),
						);
					}
					else {
						// check attributes
						foreach ( $node['attrs'] as $k => $v ) {

							$attr_ok = false;

							// if the attribute is in the global whitelist and validates
							if ( array_key_exists( strtolower( $k ), self::$whitelist_attributes['*'] ) && self::check_attr_value( strtolower( $k ), $v, self::$whitelist_attributes['*'][ strtolower( $k ) ] ) ) {
								$attr_ok = true;
							}

							// if there is a whitelist for this node and this attribute is in that list and it validates
							if ( array_key_exists( strtolower( $node['name'] ), self::$whitelist_attributes ) && array_key_exists( strtolower( $k ), self::$whitelist_attributes[ strtolower( $node['name'] ) ] ) && self::check_attr_value( strtolower( $k ), $v, self::$whitelist_attributes[ strtolower( $node['name'] ) ][ strtolower( $k ) ] ) ) {
								$attr_ok = true;
							}

							// if it wasn't in one of the whitelists or failed its check, remove it
							if ( $attr_ok != true ) {
								unset( $node['attrs'][$k] );
							}
						}
					}
					break;
				case HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE:
					if ( ! in_array( strtolower( $node['name'] ), self::$whitelist_elements ) ) {
						if ( strtolower( $temp = array_pop( $stack ) ) !== strtolower( $node['name'] ) ) {
							// something weird happened (Luke, use the DOM!)
							array_push( $stack, $temp );
						}
						//$node = null;
						//convert the node to text
						$node = array(
							'type' => HTMLTokenizer::NODE_TYPE_TEXT,
							'name' => '#text',
							'value' => HTMLTokenSet::token_to_string( $node ),
							'attrs' => array(),
						);
					}
					break;
				case HTMLTokenizer::NODE_TYPE_PI:
				case HTMLTokenizer::NODE_TYPE_COMMENT:
				case HTMLTokenizer::NODE_TYPE_CDATA_SECTION:
				case HTMLTokenizer::NODE_TYPE_STATEMENT:
				default:
					$node = null;
					break;
			}

			if ( $node != null ) {
				$filtered[] = $node;
			}
		}

		// rebuild our output string
		return preg_replace( '#<([^>\s]+)(?:\s+[^>]+)?></\1>#u', '', (string) $filtered );
	}
}

?>
