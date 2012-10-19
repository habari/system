<?php
/**
 * @package Habari
 *
 */

/**
 * Tokenizer for HTML.
 * For use by HTMLParser.
 */
class HTMLTokenizer
{
	const NODE_TYPE_TEXT = 1;
	const NODE_TYPE_ELEMENT_OPEN = 2;
	const NODE_TYPE_ELEMENT_CLOSE = 3;
	const NODE_TYPE_PI = 4;
	const NODE_TYPE_COMMENT = 5;
	const NODE_TYPE_CDATA_SECTION = 6;
	const NODE_TYPE_STATEMENT = 7;
	const NODE_TYPE_ELEMENT_EMPTY = 8;

	/* States of the Machine ;p */
	private static $STATE_FINISHED = -1;
	private static $STATE_START = 0;
	private static $STATE_TAG = 1;
	private static $STATE_ELEMENT_OPEN = 2;
	private static $STATE_ELEMENT_CLOSE = 3;
	private static $STATE_STATEMENT = 4;
	private static $STATE_PI = 5;

	/* Character Ranges */
	private static $CHR_TAG_BEGIN = '<';
	private static $CHR_TAG_END = '>';
	private static $CHR_TAG_END_TRIM = '/';
	private static $CHR_ATTRNAME_END = '=';
	private static $CHR_WHITESPACE = " \t\r\n"; // SP, TAB, CR, LF

	private $html;
	private $pos;
	private $len;

	private $state;

	private $nodes;
	private static $empty_elements = array( 'img', 'br', 'hr', 'input', 'area', 'base', 'col', 'link', 'meta', 'param', 'command', 'keygen', 'source' );

	public function __construct( $html, $escape = true )
	{
		$this->html = $html;
		$this->len = strlen( $html );
		$this->pos = 0;
		$this->nodes = new HTMLTokenSet($escape);

		$this->state = self::$STATE_START;
	}

	public function parse()
	{
		while ( $this->has_more() && $this->state != self::$STATE_FINISHED ) {
			switch ( $this->state ) {
				case self::$STATE_START:
					$this->state = $this->parse_start();
					break;
				case self::$STATE_TAG:
					$this->state = $this->parse_tag();
					break;
				case self::$STATE_ELEMENT_OPEN:
					$this->state = $this->parse_element_open();
					break;
				case self::$STATE_ELEMENT_CLOSE:
					$this->state = $this->parse_element_close();
					break;
				case self::$STATE_STATEMENT:
					$this->state = $this->parse_statement();
					break;
				case self::$STATE_PI:
					$this->state = $this->parse_pi();
					break;
				default:
					Error::raise( _t( 'Invalid state %d in %s->parse()', array( $this->state, __CLASS__ ) ) );
					$this->state = self::$STATE_FINISHED;
					break;
			}
		}

		return $this->nodes;
	}

	public function has_more()
	{
		return ( $this->pos < $this->len );
	}

	private function node( $type, $name, $value, $attrs )
	{
		$this->nodes[] = array(
			'type' => $type,
			'name' => $name,
			'value' => $value,
			'attrs' => $attrs,
		);
	}

	private function dec( $n = 1 )
	{
		$this->pos -= $n;
	}

	private function inc( $n = 1 )
	{
		$this->pos += $n;
	}

	private function get()
	{
		if ( $this->has_more() ) {
			return $this->html{ $this->pos++ };
		}

		return null;
	}

	private function peek()
	{
		return $this->html{ $this->pos };
	}

	private function up_to_str( $str )
	{
		$pos = $this->pos;
		$this->pos = strpos( $this->html, $str, $pos );
		if ( $this->pos === false ) {
			// finish
			$this->pos = $this->len;
		}

		return substr( $this->html, $pos, $this->pos - $pos );
	}

	private function up_to_chr( $chr )
	{
		$pos = $this->pos;
		$seg_len = strcspn( $this->html, $chr, $pos );
		$this->pos += $seg_len;

		return substr( $this->html, $pos, $seg_len );
	}

	private function skip_whitespace()
	{
		$this->pos += strspn( $this->html, self::$CHR_WHITESPACE, $this->pos );
	}

	private function parse_start()
	{
		$data = $this->up_to_str( self::$CHR_TAG_BEGIN );
		$this->inc();
		if ( $data != '' ) {
			$this->node( self::NODE_TYPE_TEXT, '#text', $data, null );
		}

		return self::$STATE_TAG;
	}

	private function parse_attributes()
	{
		$attr = array();
		$name = '';

		$this->skip_whitespace();

		// read attribute name
		while ( $name = $this->up_to_chr( self::$CHR_ATTRNAME_END . self::$CHR_TAG_END . self::$CHR_WHITESPACE ) ) {
			$name = strtolower( rtrim( $name, self::$CHR_TAG_END_TRIM ) );
			// skip any whitespace
			$this->skip_whitespace();
			// first non-ws char
			$char = $this->get();
			if ( $char == '=' ) {
				// attribute value follows
				$this->skip_whitespace();
				$char = $this->get();
				if ( $char == '"' ) {
					// double-quoted
					$value = $this->up_to_str( '"' );
					$this->inc();
				}
				elseif ( $char == '\'' ) {
					// single-quoted
					$value = $this->up_to_str( '\'' );
					$this->inc();
				}
				else {
					// bad, bad, bad
					$this->dec();
					$value = $this->up_to_chr( self::$CHR_WHITESPACE . '>' );
				}
			}
			elseif ( $char !== null ) {
				// TODO HTMLParser should handle #IMPLIED attrs
				$value = null;
				$this->dec();
			}
			else {
				// default
				$value = null;
			}
			// store that attribute only if it's not empty
			if ( $name ) {
				$attr[$name] = $value;
			}
			$this->skip_whitespace();
		}

		return $attr;
	}

	private function parse_tag()
	{
		switch ( $this->get() ) {
			case '!':
				return self::$STATE_STATEMENT;
				break;
			case '?':
				// mmmh, PI
				return self::$STATE_PI;
				break;
			case '/':
				return self::$STATE_ELEMENT_CLOSE;
				break;
			default:
				// we just ate the first char of the tagName, oops
				$this->dec();
				return self::$STATE_ELEMENT_OPEN;
		}
	}

	private function parse_element_open()
	{
		$tag = rtrim( $this->up_to_chr( self::$CHR_TAG_END . self::$CHR_WHITESPACE ), self::$CHR_TAG_END_TRIM );
		if ( $tag != '' ) {
			$attr = $this->parse_attributes();
			$char = $this->get();
			if ( ( $char == '/' && $this->peek() == '>' ) || in_array( $tag, self::$empty_elements ) ) {
				// empty element
				if ( $char == '/' && $this->peek() == '>' ) {
					// empty element in collapsed form
					$this->inc(); // skip peeked '>'
				}
				$this->node( self::NODE_TYPE_ELEMENT_EMPTY, $tag, null, $attr );
			}
			else {
				$this->node( self::NODE_TYPE_ELEMENT_OPEN, $tag, null, $attr );
			}
		}

		return self::$STATE_START;
	}

	private function parse_element_close()
	{
		$tag = $this->up_to_chr( self::$CHR_TAG_END );

		if ( $tag != '' ) {
			$char = $this->get();
			if ( $char == '/' && $this->peek() == '>' ) {
				$this->inc();
			}

			$this->node( self::NODE_TYPE_ELEMENT_CLOSE, $tag, null, null );
		}

		return self::$STATE_START;
	}

	private function parse_statement()
	{
		// everything starting with <!
		$nodeName = '#statement';
		$nodeType = self::NODE_TYPE_STATEMENT;

		$char = $this->get();
		if ( $char == '[' ) {
			// CDATA
			// <http://www.w3.org/TR/DOM-Level-2-Core/core.html>
			$nodeName = '#cdata-section';
			$nodeType = self::NODE_TYPE_CDATA_SECTION;

			$this->inc( 6 ); // strlen( 'CDATA[' )
			$data = $this->up_to_str( ']]>' );
			$this->inc( 2 ); // strlen( ']]' )
		}
		elseif ( $char == '-' && $this->peek() == '-' ) {
			// comment
			$nodeName = '#comment';
			$nodeType = self::NODE_TYPE_COMMENT;

			// skip peeked -
			$this->inc();
			// consume text
			$data = $this->up_to_str( '-->' );
			$data = $data; // should trim() upstream
			// skip over final --
			$this->inc( 2 );
		}
		else {
			// some other kind of statement
			$this->dec();
		}

		if ( $nodeType == self::NODE_TYPE_STATEMENT ) {
			$data = '';
			$nodeName = $this->up_to_chr( self::$CHR_TAG_END . self::$CHR_TAG_END_TRIM . self::$CHR_WHITESPACE );
			if ( $this->peek() != '>' ) {
				// there be data or something
				$this->skip_whitespace();
				$data .= $this->up_to_chr( '[>' );
				if ( $this->peek() == '[' ) {
					// internal subset
					$data .= $this->get() . $this->up_to_str( ']' ) . $this->get();
				}
			}
			$data .= $this->up_to_str( '>' );
			// not like anyone uses them, eh?
		}

		// skip over final '>'
		$this->inc();

		if ( $data != '' ) {
			$this->node( $nodeType, $nodeName, $data, null );
		}

		return self::$STATE_START;
	}

	private function parse_pi()
	{
		$target = $this->up_to_chr( self::$CHR_TAG_END . self::$CHR_WHITESPACE );
		$data = $this->up_to_chr( self::$CHR_TAG_END );
		// skip over closing tag
		$this->inc( 1 );

		$this->node( self::NODE_TYPE_PI, $target, $data, array() );

		return self::$STATE_START;
	}

}

?>
