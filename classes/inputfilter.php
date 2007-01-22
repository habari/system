<?php

/**
 * Input filtering functions
 */
class InputFilter
{
	/**
	 * Legal elements.
	 */
	private $whitelist_elements= array(
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
	 * Legal attributes for elements.
	 */
	private $whitelist_attributes= array(
		// attributes that are valid for ALL elements (a subset of coreattrs)
		// elements that only take coreattrs don't need to be listed separately
		'*' => array(
			'lang' => 'language-code',
			'xml:lang' => 'language-code', // this is our xhtml support
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
	 * Protocols that are ok for use in URIs.
	 */
	private $whitelist_protocols= array(
		'http', 'https', 'ftp', 'mailto', 'irc', 'news', 'nntp', 'callto',
	);
	
	/**
	 * List of all defined named character entities in HTML 4.01 and XHTML.
	 */
	// for some reason, this doesn't work?
	static $character_entitites= array(
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
	// However, this *does* work.
	static $character_entities_re= ';(nbsp|iexcl|cent|pound|curren|yen|brvbar|sect|uml|copy|ordf|laquo|not|shy|reg|macr|deg|plusmn|sup2|sup3|acute|micro|para|middot|cedil|sup1|ordm|raquo|frac14|frac12|frac34|iquest|Agrave|Aacute|Acirc|Atilde|Auml|Aring|AElig|Ccedil|Egrave|Eacute|Ecirc|Euml|Igrave|Iacute|Icirc|Iuml|ETH|Ntilde|Ograve|Oacute|Ocirc|Otilde|Ouml|times|Oslash|Ugrave|Uacute|Ucirc|Uuml|Yacute|THORN|szlig|agrave|aacute|acirc|atilde|auml|aring|aelig|ccedil|egrave|eacute|ecirc|euml|igrave|iacute|icirc|iuml|eth|ntilde|ograve|oacute|ocirc|otilde|ouml|divide|oslash|ugrave|uacute|ucirc|uuml|yacute|thorn|yuml|fnof|Alpha|Beta|Gamma|Delta|Epsilon|Zeta|Eta|Theta|Iota|Kappa|Lambda|Mu|Nu|Xi|Omicron|Pi|Rho|Sigma|Tau|Upsilon|Phi|Chi|Psi|Omega|alpha|beta|gamma|delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|omicron|pi|rho|sigmaf|sigma|tau|upsilon|phi|chi|psi|omega|thetasym|upsih|piv|bull|hellip|prime|Prime|oline|frasl|weierp|image|real|trade|alefsym|larr|uarr|rarr|darr|harr|crarr|lArr|uArr|rArr|dArr|hArr|forall|part|exist|empty|nabla|isin|notin|ni|prod|sum|minus|lowast|radic|prop|infin|ang|and|or|cap|cup|int|there4|sim|cong|asymp|ne|equiv|le|ge|sub|sup|nsub|sube|supe|oplus|otimes|perp|sdot|lceil|rceil|lfloor|rfloor|lang|rang|loz|spades|clubs|hearts|diams|quot|amp|lt|gt|OElig|oelig|Scaron|scaron|Yuml|circ|tilde|ensp|emsp|thinsp|zwnj|zwj|lrm|rlm|ndash|mdash|lsquo|rsquo|sbquo|ldquo|rdquo|bdquo|dagger|Dagger|permil|lsaquo|rsaquo|euro);';
	
	/**
	 * Perform all filtering, return new string.
	 * @param $str string Input string.
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
	 * @param $m array matches
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
			// numeric character references may only have values in the range 0-65535 (16 bit)
			// we strip null, though, just for kicks
			$e= strtolower( $e );
			if ( $e{1} == 'x' ) {
				$e= hexdec( substr( $e, 2 ) );
			}
			else {
				$e= substr( $e, 1 );
			}
			
			$is_valid= ( intval( $e ) > 0 && intval( $e ) <= 65535 );
			
			if ( $is_valid ) {
				// normalize to decimal form
				$e= '#' . intval( $e ) . ';';
			}
		}
		else {
			// named entities must be known
			
			//
			if ( self::$character_entities_re == '' ) {
				self::$character_entities_re= ';(' . implode( '|', self::$character_entities ) . ');';
			}
			
			$is_valid= preg_match( self::$character_entities_re, $e, $matches );
			
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
	
	public static function filter_html_elements( $str )
	{
		/*
		 * TODO: Implement a HTML tokenizer / parser.
		 * It must make absolutely sure to catch anything a browser may accept as valid HTML.
		 */
		
		return $str;
	}
}

?>
