<?php

namespace Habari;

/**
 * A faceted search control based on FormControl for output via a FormUI.
 */
class FormControlFacet extends FormControl
{
	static $outpre;

	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		Stack::add('template_header_javascript', 'visualsearch' );
		Stack::add('template_stylesheet', 'visualsearch-css');
		Stack::add('template_stylesheet', 'visualsearch-datauri-css');


		$config = new \stdClass();
		$config->facets = array('type', 'status', 'author', 'from', 'to', 'tag');
		$config->values = array(
			'type' => array(
				'entry',
				'page',
			),
			'status' => array(
				'draft',
				'published',
				'scheduled',
			),
			'author' => array(
				'admin'
			),
			'tag' => array(
				'habari',
				'exploding',
				'sausages'
			));
		$this->properties['data-facet-config'] = $config;
		$this->add_template_class('div', 'facet_ui');
	}

	/**
	 * Return the HTML/script required for this control.  Do it only once.
	 * @return string The HTML/javascript required for this control.
	 */
	public function pre_out()
	{
		$out = '';
		if ( !self::$outpre ) {
			self::$outpre = true;
			$out = <<<  CUSTOM_AUTOCOMPLETE_JS
				<script type="text/javascript">
controls.init(function(){
	$('.facet_ui').each(function(){
		var self = $(this);
		var target = $('#' + self.data('target'));
		var facet_config = target.data('facet-config');
		self.data('visualsearch', VS.init({
			container: self,
			query: '',
			showFacets: false,
			callbacks: {
				search: function(query, searchCollection) {
					console.log(query, searchCollection);
				},
				facetMatches: function(callback) {
					if(facet_config.facetsURL != undefined) {
						$.post(
							facet_config.facetsURL,
							{},
							function(response) {
								callback(response);
							}
						)
					}
					else {
						callback(facet_config.facets)
					}
				},
				valueMatches: function(facet, searchTerm, callback) {
					if(facet_config.valuesURL != undefined) {
						$.post(
							facet_config.valuesURL,
							{
								facet: facet,
								q: searchTerm
							},
							function(response) {
								callback(response);
							}
						)
					}
					else {
						if(facet_config.values[facet]!=undefined){
							callback(facet_config.values[facet]);
						}
					}
				}
			}
		}));
		self.closest('form').on('submit', function(){
			target.val(self.data('visualsearch').searchBox.value());
		});
	});

});
				</script>
CUSTOM_AUTOCOMPLETE_JS;
		}
		return $this->controls_js($out);
	}

	/**
	 * Set the URL to use for ajax callbacks.
	 * The callback needs to accept at least the search term as the POSTed parameter "q"
	 * @param string $url The URL to submit the AJAX request to
	 * @param bool $ishtml If true, the display response returned via AJAX is to be rendered as HTML (unescaped)
	 * @return FormControlAutocomplete $this
	 */
	public function set_ajax($url, $ishtml = false) {
		$this->set_settings(
			array(
				'ajax_url' => $url,
				'ajax_ishtml' => $ishtml,
			),
			false
		);
		return $this;
	}


	public function get(Theme $theme)
	{
		$this->properties['type'] = 'hidden';
		$this->properties['data-facet-config'] = json_encode($this->properties['data-facet-config'] );
		$this->set_template_properties('div', array(
			'id' => $this->get_visualizer(),
			'data-target' => $this->get_id(),
		));
		return parent::get($theme);
	}

	/**
	 * Provide the HTML id of the visualizer element, which is different from the input element that provides a value
	 */
	public function get_visualizer() {
		return $this->get_id() . '_visualizer';
	}

	/**
	 * Parse the values of this field and return an array of key/value components.
	 * Example:
	 *   $f->value = 'tag: "llamas" tag: feline alpaca';
	 *   var_dump($f->parsed());  // ['tag' => ['llamas', 'feline'], 'text' => ['alpaca']];
	 * @return array The array of parsed values
	 */
	public function parse() {
		preg_match_all('/(\w+):\s*"([^"]+)"|(\w+):\s*([\S]+)|"([\w\s]+(?!:))"|([\S]+(?!:))/im', $this->value, $matches, PREG_SET_ORDER);
		$results = array();
		foreach($matches as $match) {
			if(!empty($match[1])) {
				$results[$match[1]][] = trim($match[2], '"');
			}
			if(!empty($match[3])) {
				$results[$match[3]][] = trim($match[4], '"');
			}
			if(!empty($match[5])) {
				$results['text'][] = trim($match[5], '"');
			}
			if(!empty($match[6])) {
				$results['text'][] = trim($match[6], '"');
			}
		}
		return $results;
	}
}

?>
