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

	public function __construct($html)
	{
		$this->dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$this->dom->loadHTML($html);
		$this->xp = new \DOMXPath($this->dom);
	}

	public static function create($html)
	{
		return new HTMLDoc($html);
	}

	public function find($find)
	{
		$expression = new HTMLSelector($find);

		return $this->query($expression->toXPath());
	}

	public function find_one($find)
	{
		$expression = new HTMLSelector($find);

		$array = $this->query($expression->toXPath());
		return reset($array);
	}

	/**
	 * Pass a query on to the XPath query method
	 * @param string $expression An XPath expression
	 * @param DomNode $contextnode The context of the query, by default, the root node
	 * @param bool $registerNodeNS true by default, false to disable the automatic registration of the context node
	 * @return \DOMNodeList A list of qualifying nodes
	 */
	public function query($expression, DomNode $contextnode = null, $registerNodeNS = true)
	{
		return new HTMLNodes($this->xp->query($expression, $contextnode, $registerNodeNS));
	}

	public function get()
	{
		$body_content = $this->query('//body/*');
		$output = '';
		foreach($body_content as $node) {
			$output .= $this->dom->saveHTML($node->node);
		}
		return $output;
	}
}

class HTMLNodes extends \ArrayObject
{
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

	public function __call($method, $args)
	{
		foreach($this as $htmlnode) {
			call_user_func_array(array($htmlnode, $method), $args);
		}
	}

	public function __set($name, $value)
	{
		foreach($this as $htmlnode) {
			$htmlnode->$name = $value;
		}
	}
}

class HTMLNode
{
	/** @var \DomNode $node */
	public $node;

	function __construct($node)
	{
		$this->node = $node;
	}

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

	function add_class($newclass)
	{
		$class = $this->class;
		$classes = preg_split('#\s+#', $class);
		$newclass = is_array($newclass) ? $newclass : preg_split('#\s+#', $newclass);
		$classes = array_merge($classes, $newclass);
		$classes = array_unique($classes);
		$this->class = trim(implode(' ', $classes));
	}

	function remove_class($removeclass)
	{
		$class = $this->class;
		$classes = preg_split('#\s+#', $class);
		$removeclass = is_array($removeclass) ? $removeclass : preg_split('#\s+#', $removeclass);
		$classes = array_diff($classes, $removeclass);
		$classes = array_unique($classes);
		$this->class = trim(implode(' ', $classes));
	}

	function remove()
	{
		$this->node->parentNode->removeChild($this->node);
	}

	function append_html($html)
	{
		$frag = $this->node->ownerDocument->createDocumentFragment();
		$frag->appendXML($html);
		$this->node->appendChild($frag);
	}

	function promote_children()
	{
		while($this->node->hasChildNodes()) {
			$child = $this->node->firstChild;
			$this->node->removeChild($child);
			$this->node->parentNode->insertBefore($child, $this->node);
		}
	}

}

class HTMLSelector
{
	public $selector;

	public function __construct($selector)
	{
		$this->selector = $selector;
	}

	public function toXPath()
	{
		$parts = preg_split('#\s+#', $this->selector);
		$xpath = '';
		$rooting = '//'; // This is XPath for "any descndant of"
		foreach($parts as $part) {
			switch($part) {
				case '>': // Direct descendant of
					$rooting = '/';
					break;
				case '+': // Sibling of, not sure how to handle that yet
					break;
				default:
					$xpath .= $rooting . $this->get_part_xpath($part);
					$rooting = '//';
					break;
			}
		}
		return $xpath;
	}

	private function get_part_xpath($part)
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

/*
$h = HTMLDoc::create('<input type="text" name="foo"><div data-control-error="foo">Remove Me</div>');

$h->find('[name="foo"]')->value = 7;
foreach($h->find('div[data-control-error]') as $err) {
	$control = $err->data_control_error;
	if($h->find_one('[name="' . $control . '"]')->value == 8) {
		$err->remove();
	}
}

echo $h->get();
//*/

?>