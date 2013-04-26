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
		$this->properties['class'] = 'facet_control';

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
	$('.facet_control').each(function(){
		var self = $(this);
		var facet_config = self.data('facet-config');
		var visualsearch = VS.init({
			container: self,
			query: '',
			showFacets: false,
			callbacks: {
				search: function(query, searchCollection) {},
				facetMatches: function(callback) {callback(facet_config.facets)},
				valueMatches: function(facet, searchTerm, callback) {if(facet_config.values[facet]!=undefined)callback(facet_config.values[facet]);}
			}
		});
	});

	$('.facet_control').closest('form').submit(function(){
	});
});
				</script>
CUSTOM_AUTOCOMPLETE_JS;
		}
		return $out;
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
		$this->properties['data-facet-config'] = json_encode($this->properties['data-facet-config'] );
		return parent::get($theme);
	}


}

?>
