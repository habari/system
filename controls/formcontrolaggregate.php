<?php

namespace Habari;

/**
 * A checkbox control based on FormControl for output via a FormUI.
 */
class FormControlAggregate extends FormControl
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
		var visualizer = $('.aggregate_ui[data-target=' + self.attr('id') + ']');
		var checkboxes = $(self.data('selector'));
		var do_update = function(){
			var results = [];
			checkboxes.filter(':checked').each(function(){
				var checkbox = $(this);
				if(checkbox.prop('checked')) {
					results.push(checkbox.val());
				}
			});
			self.val(JSON.stringify(results));
			var checked = checkboxes.filter(':checked');
			visualizer.prop('indeterminate', checked.length > 0 && checked.length < checkboxes.length);
			visualizer.prop('checked', checked.length == checkboxes.length);
			if(visualizer.data('label') != '') {
				label = $('#' + visualizer.data('label'));
				num_checked = checkboxes.filter(':checked').length;
				label.html(_t('%d selected').replace(/%d/, num_checked));
			}
		}
		$('body')
			.on('change', self.data('selector'), do_update)
			.on('change', self, function(){
				if(!visualizer.prop('indeterminate')) checkboxes.prop('checked', visualizer.prop('checked'));
				do_update();
			});
		$.each($.parseJSON(self.val()), function(i, e){
			checkboxes.filter('[value="' + e + '"]').prop('checked', true);
			do_update();
		});

	});
});
				</script>
CUSTOM_AUTOCOMPLETE_JS;
		}
		return $out;
	}

	/**
	 * Set the selector used to find checkboxes whose values should be aggregated
	 * @param string $class The CSS selector to use for aggregation
	 * @return FormControlAggregate $this
	 */
	public function set_selector($class) {
		$this->properties['data-selector'] = $class;
		return $this;
	}

	/**
	 * Render this control
	 * Ensures that this control has an id property
	 * @param Theme $theme The theme used to render the control
	 * @return string The HTML output of the control
	 */
	public function get(Theme $theme)
	{
		$this->get_id(true);
		$this->set_settings(array('html_value' => json_encode($this->value)));
		return parent::get($theme);
	}

	/**
	 * Set the HTML id of the label to change when the aggregation value changes
	 * @param string $id The HTML id of the label to update
	 * @return FormControlAggregate $this
	 */
	public function set_label_id($id)
	{
		$this->set_settings(array('label_id' => $id));
		return $this;
	}

	/**
	 * Returns the ID of any wrapping label
	 * @return null|string
	 */
	public function get_label_id() {
		return $this->get_setting('label_id', function($name, $control) {
			if($control->container instanceof FormControlLabel) {
				return $control->container->get_id();
			}
			return '';
		});
	}

	/**
	 * Shortcut to wrap this control in a label
	 * This version for this control defaults to use the control.label.outsideright template
	 * @param string $label The caption of the label
	 * @return FormControlLabel The label control is returned.  FYI, THIS BREAKS THE FLUENT INTERFACE.
	 */
	public function label($label)
	{
		return FormControlLabel::wrap($label, $this)->set_template('control.label.outsideright');
	}

	/**
	 * Obtain the value of this control as supplied by the incoming $_POST values
	 */
	public function process()
	{
		$value = $_POST->raw($this->input_name());
		if($value != 'all') {
			$value = json_decode($value);
		}
		$this->set_value($value, false);
	}


}

?>