<?php

namespace Habari;

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlAggregate extends FormControlCheckbox
{
	static $outpre = false;

	/**
	 * Called upon construct.  Sets control properties
	 */
	public function _extend()
	{
		$this->properties['type'] = 'hidden';
		$this->properties['class'] = 'aggregate_control';
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
	$('.aggregate_control').each(function(){
		var self = $(this);
		$('body').on('change', self.data('selector'), function(){
			var results = [];
			var checkboxes = $(self.data('selector'));
			checkboxes.each(function(){
				var checkbox = $(this);
				if(checkbox.prop('checked')) {
					results.push(checkbox.val());
				}
			});
			self.val(JSON.stringify(results));
			var visualizer = $('.aggregate_ui[data-target=' + self.attr('id') + ']');
			visualizer.prop('indeterminate', results.length > 0 && results.length < checkboxes.length);
			visualizer.prop('checked', results.length == checkboxes.length);
		});
	});
});
				</script>
CUSTOM_AUTOCOMPLETE_JS;
		}
		return $out;
	}


	public function set_selector($class) {
		$this->properties['data-selector'] = $class;
		return $this;
	}

	public function get(Theme $theme)
	{
		$this->get_id(true);
		return parent::get($theme);
	}


}

?>