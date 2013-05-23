<?php

namespace Habari;

/**
 * A *very* simple DOMDocument wrapper, used to more easily query HTML values and append/remove elements
 * @package Habari
 */

class HTMLDoc
{
	/** @var \DOMXPath $xp */
	public $xp;
	/** @var \DomDocument $dom */
	public $dom;

	/**
	 * Create a HTMLDoc object
	 * @param string $html The HTML to parse
	 */
	public function __construct($html)
	{
		$this->dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$this->dom->loadHTML($html);
		$this->xp = new \DOMXPath($this->dom);
	}

	/**
	 * Fluent constructor for HTMLDoc objects
	 * @param string $html The HTML to parse
	 * @return HTMLDoc An instance of the HTMLDoc object created
	 */
	public static function create($html)
	{
		return new HTMLDoc($html);
	}

	/**
	 * Find elements in the DOM based on CSS selector
	 * @param string $find A CSS selector
	 * @return HTMLNodes A list of qualifying nodes
	 */
	public function find($find)
	{
		$expression = new HTMLSelector($find);

		return $this->query($expression->toXPath());
	}

	/**
	 * Find the first element in the DOM based on a CSS selector
	 * @param string $find A CSS selector
	 * @return HTMLNode A qualifying node
	 */
	public function find_one($find)
	{
		$expression = new HTMLSelector($find);

		$array = $this->query($expression->toXPath());
		return reset($array);
	}

	/**
	 * Pass a query on to the XPath query method
	 * @param string $expression An XPath expression
	 * @param \DomNode $contextnode The context of the query, by default, the root node
	 * @param bool $registerNodeNS true by default, false to disable the automatic registration of the context node
	 * @return HTMLNodes A list of qualifying nodes
	 */
	public function query($expression, \DomNode $contextnode = null, $registerNodeNS = true)
	{
		return new HTMLNodes($this->xp->query($expression, $contextnode, $registerNodeNS));
	}

	/**
	 * Return the HTML represented by the DOM
	 * @return string The requested HTML
	 */
	public function get()
	{
		$body_content = $this->query('//body/*');
		$output = '';
		foreach($body_content as $node) {
			$output .= $this->dom->saveXML($node->node);
		}
		return $output;
	}

	/**
	 * Render this DOM as a string
	 * @return string the string representation of the DOM
	 */
	function __toString()
	{
		return $this->get();
	}

}

/**
 * Contain a list of DOM nodes and provide access to them
 * @package Habari
 */
class HTMLNodes extends \ArrayObject
{

	/**
	 * Overridden constructor for \ArrayObject
	 * Converts regular \DomNodes in this array to HTMLNodes so that they have new methods
	 * @param null|array|DomNodeList $input A list of objects to initialize this \ArrayObject with
	 * @param int $flags
	 * @param string $iterator_class
	 */
	public function __construct($input = null, $flags = 0, $iterator_class = "ArrayIterator")
	{
		$altered_input = array();
		if($input instanceof \DOMNodeList) {
			foreach($input as $i) {
				if($i instanceof \DOMNode) {
					$altered_input[] = new HTMLNode($i);
				}
				else {
					$altered_input[] = $i;
				}
			}
		}
		parent::__construct($altered_input, $flags, $iterator_class);
	}

	/**
	 * Make calls against this list to execute that method on all of the items within it
	 * @param string $method The method called on this list
	 * @param array $args Arguments to this call
	 * @return HTMLNodes $this
	 */
	public function __call($method, $args)
	{
		foreach($this as $htmlnode) {
			call_user_func_array(array($htmlnode, $method), $args);
		}
		return $this;
	}

	/**
	 * Set the value of a parameter on every item of this array
	 * @param string $name The name of the parameters
	 * @param mixed $value The value to assign to that parameter
	 */
	public function __set($name, $value)
	{
		foreach($this as $htmlnode) {
			$htmlnode->$name = $value;
		}
	}
}

/**
 * A representation of the node on which we can call custom methods
 * @package Habari
 */
class HTMLNode
{
	/** @var \DomNode $node */
	public $node;

	/**
	 * Constructor for this node
	 * @param \DOMNode $node The actual node we're trying to access
	 */
	function __construct($node)
	{
		$this->node = $node;
	}

	/**
	 * Get the value of an attribute of this node
	 * @param string $name The name of the attribute value to obtain
	 * @return mixed The value of the attribute
	 */
	function __get($name)
	{
		switch($name) {
			default:
				if($attribute = $this->node->attributes->getNamedItem($name)) {
					return $attribute->nodeValue;
				}
				if($attribute = $this->node->attributes->getNamedItem(str_replace('_', '-', $name))) {
					return $attribute->nodeValue;
				}
				return null;
		}
	}

	/**
	 * Set the value of an attribute on this node
	 * @param string $name The name of the attribute to set
	 * @param mixed $value The value of the parameter
	 */
	function __set($name, $value)
	{
		switch($name) {
			default:
				if(!$attribute = $this->node->attributes->getNamedItem($name)) {
					$attribute = $this->node->ownerDocument->createAttribute($name);
					$this->node->appendChild($attribute);
				}
				$attribute->nodeValue = $value;

				break;
		}
	}

	/**
	 * Add a class to the class attribute of this node
	 * @param string|array $newclass The class or classes to add to this node
	 */
	function add_class($newclass)
	{
		$class = $this->class;
		$classes = preg_split('#\s+#', $class);
		$newclass = is_array($newclass) ? $newclass : preg_split('#\s+#', $newclass);
		$classes = array_merge($classes, $newclass);
		$classes = array_unique($classes);
		$this->class = trim(implode(' ', $classes));
	}

	/**
	 * Remove a class from this node
	 * @param string|array $removeclass The class or classes to remove from this node
	 */
	function remove_class($removeclass)
	{
		$class = $this->class;
		$classes = preg_split('#\s+#', $class);
		$removeclass = is_array($removeclass) ? $removeclass : preg_split('#\s+#', $removeclass);
		$classes = array_diff($classes, $removeclass);
		$classes = array_unique($classes);
		$this->class = trim(implode(' ', $classes));
	}

	/**
	 * Remove this node from the DOM
	 */
	function remove()
	{
		$this->node->parentNode->removeChild($this->node);
	}

	/**
	 * Append HTML as a child of this node
	 * @param string $html The HTML to add, which is subsequently parsed into DOMNodes
	 */
	function append_html($html)
	{
		$frag = $this->node->ownerDocument->createDocumentFragment();
		$frag->appendXML($html);
		$this->node->appendChild($frag);
	}

	/**
	 * Move the children of this node into this node's parent, just before this node in the DOM tree
	 */
	function promote_children()
	{
		while($this->node->hasChildNodes()) {
			$child = $this->node->firstChild;
			$this->node->removeChild($child);
			$this->node->parentNode->insertBefore($child, $this->node);
		}
	}

	/**
	 * Get this node's string representation
	 * @return string The node's string representation
	 */
	function get()
	{
		return $this->node->ownerDocument->saveXML($this->node);
	}

	/**
	 * Get the HTML of all child elements of this node
	 * @return string The requested HTML
	 */
	function inner_html()
	{
		$inner_html = '';
		foreach($this->node->childNodes as $child) {
			$tmp_dom = new \DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$inner_html .= trim($tmp_dom->saveXML());
		}
		// Kludgey hack to remove doctype spec
		$inner_html = preg_replace('#^\s*<\?xml(\s.*)?\?>\s*#', '', $inner_html);
		return $inner_html;
	}

}

/**
 * A representation of a CSS selector, with methods to convert CSS to XPath
 * @package Habari
 */
class HTMLSelector
{
	/** @var string $selector the CSS selector */
	public $selector;

	/**
	 * Constructor for setting the CSS selector in this class
	 * @param string $selector A CSS selector
	 */
	public function __construct($selector)
	{
		$this->selector = $selector;
	}

	/**
	 * Convert the CSS selector to an XPath selector
	 * @return string
	 */
	public function toXPath()
	{
		preg_match_all('/[:\.\w#]+|>|\+|,/sim', $this->selector, $parts);
		$xpath = '';
		$rooting = '//'; // This is XPath for "any descndant of"
		$stack = array();
		foreach($parts[0] as $part) {
			switch($part) {
				case '>': // Direct descendant of
					$rooting = '/';
					break;
				case '+': // Sibling of, not sure how to handle that yet
					break;
				case ',': // OR...
					$stack[] = '|';
					$rooting = '//';
					break;
				default:
					$xpath_part = $this->get_part_xpath($part, $stack);
					$stack[] = $rooting;
					$stack[] = $xpath_part;
					$rooting = '//';
					break;
			}
		}
		$xpath = implode('', $stack);
		return $xpath;
	}

	/**
	 * Interal method for parsing the CSS parts into XPath parts
	 * @param string $part Some atomic part of a CSS selector
	 * @param array $stack An array of previous xpath parts
	 * @return string The equivalent XPath part
	 */
	private function get_part_xpath($part, $stack)
	{
		// For "[name=value]" $2 = "name", $3 = "=", $5 = "value"
		preg_match_all('/\[(([^\]]+?)(([~\-!]?=)([^\]]+))?)\]|\W?\w+/', $part, $matches, PREG_SET_ORDER);
		$props = array();
		$tag = '*';
		foreach($matches as $match) {
			if($match[0][0] == '#') {  // it's an ID
				$props[] = '[@id = "' . substr($match[0], 1) . '"]';
			}
			elseif($match[0][0] == '.') {  // it's a class
				$props[] = '[contains(@class, "' . substr($match[0], 1) . '")]';
			}
			elseif($match[0][0] == ':') { // it's a pseudo-selector, oh noes!
				// @todo Ack!  Do something!
				$last = end($stack);

			}
			elseif($match[0][0] == '[') { // it's a property-based selector
				if(empty($match[5])) { // Just checking if the element has the name
					$props[] = '[@' . $match[1] . ']';
				}
				else {
					$props[] = '[@' . $match[1] . ']';
				}
			}
			else { // it's a tag
				$tag = $match[0];
			}
		}
		return $tag . implode('', $props);
	}
}

?>