<?php

namespace Habari;

/**
 * A hidden field control based on FormControl for output via a FormUI.
 */
class FormControlAutocomplete extends FormControl
{
	static $outpre;

	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'text';
		$this->properties['class'] = 'autocomplete_control';


		$config = new \stdClass();
		$config->minimumInputLength = 2;
		$config->tags = array();
		$this->properties['data-autocomplete-config'] = $config;
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
	$('.autocomplete_control').each(function(){
		var self = $(this);
		var autocomplete_config = self.data('autocomplete-config');
		if(autocomplete_config.ajax_url) {
			$.extend(autocomplete_config, {
				ajax: {
					url: autocomplete_config.ajax_url,
					dataType: 'json',
					data: function (term, page) { return {q: term} },
					results: function (data, page) { return data.data; }
				}
			});
		}
		if(autocomplete_config.ajax_ishtml) {
			$.extend(autocomplete_config, {
				escapeMarkup: function(m) { return m; }
			});
		}
		if(autocomplete_config.allow_new) {
			$.extend(autocomplete_config, {
				createSearchChoice: function(term) {
					return {id:term, text:term};
				}
			});
		}
		if(autocomplete_config.init_selection) {
			$.extend(autocomplete_config, {
				initSelection: function(element, callback) {
					var data = [];
					$(element.val().split(',')).each(function () {
						data.push({id: this, text: this});
					});
					callback(data);
				}
			});
		}
		$.extend(autocomplete_config, {
			width: 'resolve'
		});
		console.log(autocomplete_config);
		self.select2(autocomplete_config);
	});

	$('.autocomplete_control').closest('form').submit(function(){
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
		if(isset($this->settings['ajax_url'])) {
			$this->properties['data-autocomplete-config']->ajax_url = $this->settings['ajax_url'];
		}
		if(isset($this->settings['ajax_ishtml'])) {
			$this->properties['data-autocomplete-config']->ajax_ishtml = $this->settings['ajax_ishtml'];
		}
		if(isset($this->settings['allow_new'])) {
			$this->properties['data-autocomplete-config']->tokenSeparators = array(',');
			$this->properties['data-autocomplete-config']->allow_new = true;
		}
		if(isset($this->settings['init_selection'])) {
			$this->properties['data-autocomplete-config']->init_selection = true;
		}
		$this->properties['data-autocomplete-config'] = json_encode($this->properties['data-autocomplete-config'] );
/*
		Stack::add('template_header_javascript', 'select2' );
		Stack::add('template_stylesheet', 'select2-css');
*/
		return parent::get($theme);
	}


}

?>
