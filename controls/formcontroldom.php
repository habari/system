<?php

namespace Habari;

/**
 * This control represents a DOM-based input field, and is not rendered to the page,
 * but retains data from when it is defined to when the form is processed for success
 */
class FormControlDom extends FormControl
{
	/** @var HTMLNode $node */
	public $node = null;

	public function __construct($name, $storage = 'null:null', array $properties = array(), array $settings = array())
	{
		$this->settings['error_wrap'] = function($output, $errors) {
			return sprintf('<ol class="_control_error_list"><li>%2$s</li></ol>', $output, implode('</li><li>', $errors));
		};
		parent::__construct($name, $storage, $properties, $settings);
	}


	/**
	 * This control shouldn't output anything
	 * @param Theme $theme
	 * @return string
	 */
	public function get(Theme $theme)
	{
		$form = $this->get_form();
		if($this->has_errors) {
			$this->node->add_class('_has_error');
			$error_output = $this->error_wrap('', $this->errors);
			$form->dom->find('[data-control-errors="' . $this->name . '"]')->append_html($error_output);
			$form->dom->find('[data-form-success]')->remove();
		}
		else {
			$form->dom->find('[data-control-errors="' . $this->name . '"]')->remove();
			$form->dom->find('[data-show-on-error="' . $this->name . '"]')->promote_children();
			$form->dom->find('[data-show-on-error="' . $this->name . '"]')->remove();
		}
		if(!$form->submitted) {
			$form->dom->find('[data-form-success]')->remove();
		}
		return '';
	}

	/**
	 * Set the HTMLNode that this control associates with
	 * @param HTMLNode $node The node that this control is associated with
	 * @return FormControlDom $this
	 */
	public function set_node($node)
	{
		$this->node = $node;
		return $this;
	}

	/**
	 * Set the value of this control
	 * @param mixed $value The value to set
	 * @param bool $manually True if this value is set internally rather than being POSTed in the form
	 * @return FormControl $this
	 */
	public function set_value($value, $manually = true)
	{
		$this->node->value = $value;
		return parent::set_value($value, $manually);
	}


}

?>