<?php

namespace Habari;

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlTree extends FormControlSelect
{
	public static $outpre = false;

	/**
	 * Add some default properties to this control
	 */
	public function _extend()
	{
		$this->set_properties(array('style' => 'clear: both;'));
	}

	/**
	 * Return the HTML/script required for this control.  Do it only once.
	 * @return string The HTML/javascript required for this control.
	 */
	public function pre_out()
	{
		$out = '';
		if ( !FormControlTree::$outpre ) {
			FormControlTree::$outpre = true;
			$out = <<<  CUSTOM_TREE_JS
				<script type="text/javascript">
$(function(){
	$('ol.tree').nestedSortable({
		disableNesting: 'no-nest',
		forcePlaceholderSize: true,
		handle: '.handle',
		items: 'li.treeitem',
		opacity: .6,
		placeholder: 'placeholder',
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div'
	});

	$('.tree_control').closest('form').submit(function(){
		var tree_input = $('.tree_control input[type=hidden]', this);
		tree_input.each(function(){
			var tree_input = $(this);
			var data = tree_input.siblings().nestedSortable('toArray', {startDepthCount: 1});
			var comma = '';
			var v = '';
			for(var i in data) {
				if(data[i].item_id != 'root') {
					v += comma + '{"id":"' + parseInt(data[i].item_id) + '","left":"' + (parseInt(data[i].left)-1) + '","right":"' + (parseInt(data[i].right)-1) + '"}';
					comma = ',';
				}
			}
			v = '[' + v + ']';
			tree_input.val(v);
		});
	});
});
				</script>
CUSTOM_TREE_JS;
		}
		return $this->controls_js($out);
	}

	/**
	 * Produce the control for display
	 * @param Theme $theme The theme that will be used to render the template
	 * @return string The output of the template
	 */
	public function get(Theme $theme)
	{
		$this->vars['terms'] = $this->value;
		$this->settings['ignore_name'] = true;
		$this->settings['internal_value'] = true;
		$this->add_class('tree_control');
		$this->get_id();
		return parent::get($theme);
	}

	public function process()
	{
		$values = json_decode($_POST->raw( $this->input_name() ));
		$terms = array();
		foreach($this->value as $term) {
			$terms[$term->id] = $term;
		}
		foreach($values as $value) {
			$terms[$value->id]->mptt_left = $value->left;
			$terms[$value->id]->mptt_right = $value->right;
		}
		$terms = new Terms($terms);
		$posted = $terms->tree_sort();

		//$this->set_value($_POST[$this->input_name()], false);
		$this->set_value($posted, false);
	}
}

?>