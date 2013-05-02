<?php

namespace Habari;

/**
 * A submit control based on FormControl for output via FormUI
 */
class FormControlDropbutton extends FormContainer
{
	static $outpre = false;

	public function _extend() {
		$this->properties['type'] = 'hidden';
		$this->add_template_class('div', 'dropbutton dropbutton_control');
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
			$out = <<<  CUSTOM_DROPBUTTON_JS
				<script type="text/javascript">
controls.init(function(){
	$('body').on('click', function(e){
		$('.dropbutton').removeClass('dropped');
	});
	$('.dropbutton_control').each(function(){
		var self = $(this);
		var needWidth = self.find('.primary').outerWidth()+self.find('.dropdown').outerWidth();
		var menu = self.find('.dropdown-menu');
		menu.css('margin-left', -200)
		toWidth = Math.max(needWidth, menu.width());
		marginleft = Math.min(0, needWidth - menu.width());
		if(marginleft < -2) {
			$('li:first-child input', menu).css('border-top-left-radius', '3px');
		}
		menu.width(toWidth).css('margin-left', marginleft);
		self.find('.dropdown').on('click', function(event){
			$('.dropbutton_control').not(self).removeClass('dropped');
			self.toggleClass('dropped');
			event.preventDefault();
			return false;
		});
	});
});
				</script>
CUSTOM_DROPBUTTON_JS;
		}
		return $this->controls_js($out);
	}


	/**
	 * This control only executes its on_success callbacks when it was clicked
	 * @return bool|string A string to replace the rendering of the form with, or false
	 */
	public function do_success($form)
	{
		$actions = $this->get_setting('actions', array());
		if(isset($actions[$this->value])) {
			if(isset($actions[$this->value]['fn']) && is_callable($actions[$this->value]['fn'])) {
				$fn = $actions[$this->value]['fn'];
				call_user_func($fn, $form);
			}
			elseif(isset($actions[$this->value]['href']) && is_string(isset($actions[$this->value]['href']))) {
				Utils::redirect($actions[$this->value]['href'], true);
			}
		}
		return parent::do_success($form);
	}

	public function get(Theme $theme)
	{
		$primary = true;
		$controls = array();
		/** @var FormControlSubmit $control */
		foreach ( $this->controls as $index => $control ) {
			if($control->is_enabled()) {
				$control->add_class('dropbutton_action');
				$control->add_class(Utils::slugify($control->input_name()));
				if($primary) {
					$control->add_class('primary');
					$primary = false;
				}
				$controls[$index] = $control;
			}
		}
		if(count($controls) == 0) {
			return '';
		}
		$this->vars['first'] = array_shift($controls);
		$this->vars['actions'] = $controls;
		$this->set_template_properties('div', array('id' => $this->get_visualizer()));
		$this->add_template_class('ul', 'dropdown-menu');
		if(count($controls) > 0) {  // Remember, these are in the dropmenu, doesn't include the first
			$this->add_template_class('div', 'has-drop');
		}
		else {
			$this->add_template_class('div', 'no-drop');
		}
		return parent::get($theme);
	}

	/**
	 * Returns the HTML id of the element that the control exposes as a target, for example, for labels
	 */
	public function get_visualizer()
	{
		return $this->get_id() . '_visualizer';
	}


}


?>
