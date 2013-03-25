<?php

namespace Habari;

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlTree extends FormControlSelect
{
	public static $outpre = false;

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
controls.init(function(){
	$('ol.tree').nestedSortable({
		disableNesting: 'no-nest',
		forcePlaceholderSize: true,
		handle: 'div',
		items: 'li.treeitem',
		opacity: .6,
		placeholder: 'placeholder',
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div'
	});

	$('.tree_submitted').closest('form').submit(function(){
		var tree_input = $('.tree_submitted', this);
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
				</script>
CUSTOM_TREE_JS;
		}
		return $out;
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
		$this->get_id();
		return parent::get($theme);
	}

	public function process()
	{
		$values = json_decode($_POST->raw( $this->field . '_submitted'));
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


		$this->set_value($_POST[$this->input_name()], false);
		parent::process();
	}


	/**
	 * Magic __get method for returning property values
	 * Override the handling of the value property to properly return the setting of the checkbox.
	 *
	 * @param string $name The name of the property
	 * @return mixed The value of the requested property
	 */
	public function __get( $name )
	{
		static $posted = null;
		switch ( $name ) {
			case 'value':
				if ( isset( $_POST[$this->field . '_submitted'] ) ) {
					if(!isset($posted)) {
						$valuesj = $_POST->raw( $this->field . '_submitted');
						$values = json_decode($valuesj);
						$terms = array();
						foreach($this->get_default() as $term) {
							$terms[$term->id] = $term;
						}
						foreach($values as $value) {
							$terms[$value->id]->mptt_left = $value->left;
							$terms[$value->id]->mptt_right = $value->right;
						}
						$terms = new Terms($terms);
						$posted = $terms->tree_sort();
					}
					return $posted;
				}
				else {
					return $this->get_default();
				}
		}
		return parent::__get( $name );
	}
}

?>