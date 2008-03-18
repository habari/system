<?php

/**
 * Input filtering functions.
 * 
 * @package Habari
 */
class InputFilter
{
	/**
	 * Allowed elements.
	 */
	private static $whitelist_elements= array(
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
	);
	
	/**
	 * Allowed attributes and values.
	 */
	private static $whitelist_attributes= array(
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
	);
	
	/**
	 * #EMPTY elements.
	 */
	private static $elements_empty= array(
		'img',
	);
	
	/**
	 * Protocols that are ok for use in URIs.
	 */
	private static $whitelist_protocols= array(
		'http', 'https', 'ftp', 'mailto', 'irc', 'news', 'nntp', 'callto',
	);
	
	/**
	 * List of all defined named character entities in HTML 4.01 and XHTML.
	 */
	private static $character_entities= array(
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
	private static $character_entities_re= '';
	
	private static $scheme_ports= array(
		'ftp' => 21,
		'ssh' => 22,
		'telnet' => 23,
		'http' => 80,
		'pop3' => 110,
		'nntp' => 119,
		'news' => 119,
		'irc' => 194,
		'imap3' => 220,
		'https' => 443,
		'nntps' => 563,
		'imaps' => 993,
		'pop3s' => 995,
	); 
	
	/**
	 * Perform all filtering, return new string.
	 * @param string $str Input string.
	 * @return string Filtered output string.
	 */
	public static function filter( $str )
	{
		$str= self::strip_nulls( $str );
		$str= self::strip_illegal_entities( $str );
		$str= self::filter_html_elements( $str );
		
		return $str;
	}
	
	public static function strip_nulls( $str )
	{
		$str= preg_replace( '/\0+/', '', $str );
		
		return $str;
	}

	/**
	 * Callback function for strip_illegal_entities, do not use.
	 * @access private
	 * @param array $m matches
	 */	
	public static function _validate_entity( $m )
	{
		$is_valid= FALSE;
		
		// valid entity references have the form
		//   /&named([;<\n\r])/
		// for named entities, or
		//   /&#(\d{1,5}|[xX][0-9a-fA-F]{1,4})([;<\n\r])/
		// for numeric character references
		
		$e= trim( $m[1] );
		$r= $m[2];
		if ( $r == ';' ) {
			$r= '';
		}
		
		if ( $e{0} == '#' ) {
			$e= strtolower( $e );
			if ( $e{1} == 'x' ) {
				$e= hexdec( substr( $e, 2 ) );
			}
			else {
				$e= substr( $e, 1 );
			}
			
			// numeric character references may only have values in the range 0-65535 (16 bit)
			// we strip null, though, just for kicks
			$is_valid= ( intval( $e ) > 0 && intval( $e ) <= 65535 );
			
			if ( $is_valid ) {
				// normalize to decimal form
				$e= '#' . intval( $e ) . ';';
			}
		}
		else {
			if ( self::$character_entities_re == '' ) {
				self::$character_entities_re= ';(' . implode( '|', self::$character_entities ) . ');';
			}
			
			// named entities must be known
			$is_valid= preg_match( self::$character_entities_re, $e, $matches );
			
			// XXX should we map named entities to their numeric equivalents?
			
			if ( $is_valid ) {
				// normalize to name and nothing but the name... eh.
				$e= $matches[1] . ';';
			}
		}
		
		return $is_valid ? '&' . $e . $r : '';
	}
	
	public static function strip_illegal_entities( $str )
	{
		$str= preg_replace_callback( "/&([^;<\n\r]+)([;<\n\r])/", array( __CLASS__, '_validate_entity' ), $str );
		
		return $str;
	}
	
	/**
	 * This really doesn't belong here. It should also be done much better. This is a nasty, NASTY kludge.
	 */
	public static function parse_url( $url )
	{
		// result array
		$r= array(
			'scheme' => '',
			'host' => '',
			'port' => '',
			'user' => '',
			'pass' => '',
			'path' => '',
			'query' => '',
			'fragment' => '',
			//
			'is_relative' => FALSE,
			'is_pseudo' => FALSE,
			'is_error' => TRUE,
			//
			'pseudo_args' => '',
		);
		
		// TODO normalize etc., make re tighter (ips)
		$re= '@^' // delimiter + anchor
			// scheme, address, port are optional for relative urls ...
			. '(?:'
				// scheme
				. '(?P<scheme>[a-zA-Z][^:]*):(?://)?'
				// real protocols
				. '(?P<full_address>(?:'
					// optional userinfo
					. '(?:'
						// username
						. '(?P<user>(?:[a-zA-Z0-9_.!~*\'()-]|(?:%[0-9a-fA-F]{2})|[;&=+$,])+)'
						// password
						. ':(?P<pass>(?:[a-zA-Z0-9_.!~*\'()-]|(?:%[0-9a-fA-F]{2})|[;:&=+$,])+)?\@)?'
					// address:
					. '(?P<host>'
					//   ip
					  . '(?:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})|'
					//   or hostname
					  . '(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]+[a-zA-Z0-9])?\.)*(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]+[a-zA-Z0-9])?)*\.[a-zA-Z](?:[a-zA-Z0-9-]+[a-zA-Z0-9])?'
					. ')'
					// optional port (:0-65535)
					. '(?::(?P<port>[0-5]?[0-9]{1,4}|6[0-4][0-9]{3}|65[0-4][0-9]{2}|655[0-2][0-9]|6553[0-5]))?'
				// pseudo-protocols
				. ')|.+)'
			// /optional for relative
			. ')?'
			// path
			. '(?P<path>/?[^?#]+)?'
			// querystring
			. '(?:\?(?P<query>[^#]+))?'
			// fragment (hash)
			. '(?:#(?P<fragment>.*))?'
			// delimiter
			. '@'
			;
		
		$t= preg_match_all( $re, $url, $matches, PREG_SET_ORDER );
		if ( ! $t ) // TODO better error handling
			return $r;
		
		$matches= $matches[0];
		if ( ! isset( $matches['full_address'] ) )
			$matches['full_address']= '';
		
		$r['is_error']= FALSE;
		$r['is_relative']= empty( $matches['full_address'] );
		$r['is_pseudo']= ! array_key_exists( 'host', $matches );
		$r['pseudo_args']= $r['is_pseudo'] ? $matches['full_address'] : '';
		
		foreach ( array( 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment' ) as $k ) {
			if ( array_key_exists( $k, $matches ) ) {
				$r[$k]= $matches[$k];
			}
		}
		
		return $r;
	}
	
	public static function glue_url( $parsed_url )
	{
		if ( ! is_array( $parsed_url ) ) {
			return FALSE;
		}
		
		$res = '';
		$res .= $parsed_url['scheme'];
		if ( $parsed_url['is_pseudo'] || $parsed_url['scheme'] == 'mailto' ) {
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
				if ( array_key_exists( $parsed_url['scheme'], self::$scheme_ports ) && self::$scheme_ports[ $parsed_url['scheme'] ] == $parsed_url['port'] ) {
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
			return in_array( $v, $type, TRUE );
		}
		else {
			// data type 
			switch ( $type ) {
				case 'uri':
					// RfC 2396 <http://www.ietf.org/rfc/rfc2396.txt>
					$bits= self::parse_url( $v );
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
					Error::raise( sprintf( 'Unkown attribute type "%s" in %s', $type, __CLASS__ ) );
					return FALSE;
			}
		}
	}
	
	/**
	 * @todo TODO must build DOM to really properly remove offending elements
	 * @todo TODO properly filter URLs
	 */
	public static function filter_html_elements( $str )
	{
		$tokenizer= new HTMLTokenizer( $str );
		
		// tokenize, baby
		$tokens= $tokenizer->parse();
		
		// filter token stream
		$filtered= array();
		$stack= array();
		foreach ( $tokens as $node ) {
			switch ( $node['type'] ) {
				case HTMLTokenizer::NODE_TYPE_TEXT:
					if ( sizeof( $stack ) > 0 && ! in_array( strtolower( $stack[sizeof( $stack )-1] ), self::$whitelist_elements ) ) {
						// skip node if filtered element is still open
						$node= NULL;
					}
					else {
						// XXX use blog charset setting
						$node['value']= html_entity_decode( $node['value'], ENT_QUOTES, 'utf-8' );
					}
					break;
				case HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN:
					// is this element allowed at all?
					if ( ! in_array( strtolower( $node['name'] ), self::$whitelist_elements ) ) {
						if ( ! in_array( strtolower( $node['name'] ), self::$elements_empty ) ) {
							array_push( $stack, $node['name'] );
						}
						$node= NULL;
					}
					else {
						// check attributes
						foreach ( $node['attrs'] as $k => $v ) {
							$attr_ok= (
								( 
									   in_array( strtolower( $k ), self::$whitelist_attributes['*'] )
									|| ( array_key_exists( strtolower( $node['name'] ), self::$whitelist_attributes ) &&
									     array_key_exists( strtolower( $k ), self::$whitelist_attributes[strtolower( $node['name'] )] ) )
								)
								&& self::check_attr_value( strtolower( $k ), $v, self::$whitelist_attributes[strtolower( $node['name'] )][strtolower( $k )] )
							);
							if ( ! $attr_ok ) {
								unset( $node['attrs'][$k] );
							}
						}
					}
					break; 
				case HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE:
					if ( ! in_array( strtolower( $node['name'] ), self::$whitelist_elements ) ) {
						if ( strtolower( $temp= array_pop( $stack ) ) !== strtolower( $node['name'] ) ) {
							// something weird happened (Luke, use the DOM!)
							array_push( $stack, $temp );
						}
						$node= NULL;
					} 
					break;
				case HTMLTokenizer::NODE_TYPE_PI:
				case HTMLTokenizer::NODE_TYPE_COMMENT:
				case HTMLTokenizer::NODE_TYPE_CDATA_SECTION:
				case HTMLTokenizer::NODE_TYPE_STATEMENT:
					$node= NULL;
					break;
				default:
			}
			
			if ( $node != NULL ) {
				$filtered[]= $node;
			}
		}
		
		// rebuild our output string
		$str= '';
		foreach ( $filtered as $node ) {
			switch ( $node['type'] ) {
				case HTMLTokenizer::NODE_TYPE_TEXT:
					$str.= $node['value'];
					break;
				case HTMLTokenizer::NODE_TYPE_ELEMENT_OPEN:
					$str.= '<';
					$str.= $node['name'];
					if ( $node['attrs'] ) {
						foreach ( $node['attrs'] as $k => $v ) {
							$str.= ' ';
							$str.= $k;
							$str.= '="';
							$str.= htmlspecialchars( html_entity_decode( $v, ENT_QUOTES, 'utf-8' ), ENT_COMPAT, 'utf-8' );
							$str.= '"';
						}
					}
					$str.= '>';
					break;
				case HTMLTokenizer::NODE_TYPE_ELEMENT_CLOSE:
					$str.= '</';
					$str.= $node['name'];
					$str.= '>';
					break;
				case HTMLTokenizer::NODE_TYPE_PI:
				case HTMLTokenizer::NODE_TYPE_COMMENT:
				case HTMLTokenizer::NODE_TYPE_CDATA_SECTION:
				case HTMLTokenizer::NODE_TYPE_STATEMENT:
					Error::raise( sprintf( 'Undead token "%s" (%d) in %s', $node['name'], $node['type'], __CLASS__ ) ); 
					break;
				default:
			}
		}
		// $document->toString() is so much easier :~
		
		$str= preg_replace( '@<([^>\s]+)(?:\s+[^>]+)?></\1>@', '', $str ); 
		
		return $str;
	}
}

?>