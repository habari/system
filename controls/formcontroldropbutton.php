<?php

namespace Habari;

/**
 * A submit control based on FormControl for output via FormUI
 */
class FormControlDropbutton extends FormControl
{
	static $outpre = false;
	public $on_success = array();

	public function _extend() {
		$this->properties['type'] = 'hidden';
		$this->add_template_class('div', 'dropbutton dropbutton_control');
	}

	/**
	 * Set the actions of this dropbutton, the first action is the default action
	 * @param array $new_actions An array of actions to apply to the control
	 * @param bool $override Defaults to false. If true, override existing actions.  If false, merge with existing actions
	 * @return FormControlDropbutton $this
	 */
	public function set_actions($new_actions, $override = false)
	{
		$actions = array();
		foreach($new_actions as $caption => $fn) {
			$key = Utils::slugify($caption);
			if(is_callable($fn)) { // Passed in a callback
				$href = '#' . Utils::slugify($caption);
			}
			elseif(is_array($fn)) { // Passed in a plugin array actionlist?
				$actions[$key] = $fn;
				continue;
			}
			elseif(is_string($fn)) { // Passed in a URL
				$href = $fn;
			}
			else { // Don't know what this is!
				// Aaaiieeeeee!!!!
				$href = 'Aaaiieeeeee!!!!';
			}
			$actions[$key] = array(
				'class' => $key,
				'caption' => $caption,
				'fn' => $fn,
				'href' => $href,
			);
		}
		if(!$override) {
			$actions = array_merge($this->get_setting('actions', array()), $actions);
		}
		$this->set_settings(array('actions' => $actions));
		return $this;
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
		self.find('.dropdown-menu').width(self.find('.primary').outerWidth()+self.find('.dropdown').outerWidth());
		$('.dropbutton_action', self).on('click', function(){
			var a = $(this);
			self.find('input').val(a.attr('href').replace(/^.*#/, ''));
			self.closest('form').submit();
		});
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
				$actions[$this->value]['fn']($form);
			}
			elseif(isset($actions[$this->value]['href']) && is_string(isset($actions[$this->value]['href']))) {
				Utils::redirect($actions[$this->value]['href'], true);
			}
		}
		return parent::do_success($form);
	}

	public function get(Theme $theme)
	{
		$actions = $this->get_setting('actions', array());
		$primary = true;
		foreach($actions as $key => &$action) {
			$caption = $action['caption'];
			unset($action['caption']);
			$class = array($key, 'dropbutton_action');
			if(isset($action['class'])) {
				$class[] = $action['class'];
			}
			if($primary) {
				$class[] = 'primary';
				$primary = false;
			}
			$action['class'] = implode(' ', $class);
			$attributes = $action;
			unset($attributes['fn']);
			$action = array(
				'attributes' => Utils::html_attr($attributes, ENT_COMPAT, 'UTF-8', false, false),
				'caption' => $caption,
			);
		}
		$this->vars['actions'] = $actions;
		$this->set_template_properties('div', array('id' => $this->get_visualizer()));
		$this->add_template_class('ul', 'dropdown-menu');
		if(count($this->settings['actions']) > 1) {
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