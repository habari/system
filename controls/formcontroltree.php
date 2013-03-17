<?php

namespace Habari;

/**
 * A set of checkbox controls based on FormControl for output via a FormUI.
 */
class FormControlTree extends FormControlSelect
{
	public $options = array();
	public static $outpre = false;

	/**
	 * Override the FormControl constructor to support more parameters
	 *
	 * @param string $name
	 * @param string $caption
	 * @param array $options
	 * @param string $template
	 * @param array $config
	 */
	public function __construct()
	{
		$args = func_get_args();
		list( $name, $storage, $caption, $template, $config ) = array_merge( $args, array_fill( 0, 5, null ) );

		$this->name = $name;
		$this->storage = $storage;
		$this->caption = $caption;
		$this->template = $template;
		$this->config = empty($config) ? array() : $config;
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
	 * Produce HTML output for this text control.
	 *
	 * @param boolean $forvalidation True if this control should render error information based on validation.
	 * @return string HTML that will render this control in the form
	 */
	public function get( $forvalidation = true )
	{
		$theme = $this->get_theme( $forvalidation );
		$theme->options = $this->value;
		$theme->id = $this->name;
		$theme->control = $this;

		return $theme->fetch( $this->get_template(), true );
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