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
	}

	/**
	 * Set the actions of this dropbutton, the first action is the default action
	 * @param array $actions The actions to set, captions as keys, on_success methods as values
	 * @param bool $override Defaults to false. If true, override existing actions.  If false, merge with existing actions
	 * @return FormControlSubmit $this
	 */
	public function set_actions($new_actions, $override = false)
	{
		$actions = array();
		foreach($new_actions as $caption => $fn) {
			$actions[Utils::slugify($caption)] = array(
				'caption' => $caption,
				'fn' => $fn,
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
	$('.dropbutton_control').each(function(){
		var self = $(this);
		$(self).on('click', 'a', function(){
			var a = $(this);
			self.find('input').val(a.attr('href').replace(/^.*#/, ''));
			self.closest('form').submit();
		});
	});
});
				</script>
CUSTOM_DROPBUTTON_JS;
		}
		return $out;
	}


	/**
	 * This control only executes its on_success callbacks when it was clicked
	 * @return bool|string A string to replace the rendering of the form with, or false
	 */
	public function do_success($form)
	{
		$actions = $this->get_setting('actions', array());
		if(isset($actions[$this->value])) {
			$actions[$this->value]['fn']($form);
		}
		return parent::do_success($form);
	}

	public function get(Theme $theme)
	{
		$this->vars['actions'] = $this->get_setting('actions', array());
		return parent::get($theme);
	}


}


?>