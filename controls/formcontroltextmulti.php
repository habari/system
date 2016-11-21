<?php

namespace Habari;

/**
 * A multiple-slot text control based on FormControl for output via a FormUI.
 * @todo Make DHTML fallback for non-js browsers
 */
class FormControlTextMulti extends FormControl
{
	public static $outpre = false;

	/**
	 * Return the HTML/script required for this control.  Do it only once.
	 * @return string The HTML/javascript required for this control.
	 */
	public function pre_out()
	{
		$out = '';
		if ( !FormControlTextMulti::$outpre ) {
			FormControlTextMulti::$outpre = true;
			if(is_array($this->value)) {
				$fieldcount = count($this->value);
			}
			else {
				$fieldcount = 0;
			}
			// translatable strings
			// very bad practice but the below code is just horrible to read already and heredoc does not support method calls
			$removeitem = _t( 'Remove item' );
			$remove = _t( '[remove]' );
			$removethisitem = _t( 'Remove this item?' );
			$out .= <<< JSCODE
				<script type="text/javascript">
				controls.textmulti = {
					add: function(e, controlname){
						$(e).before('<div><input type="text" name="' + controlname + '[]"> <a href="#" onclick="return controls.textmulti.remove(this);" title="{$removeitem}" class="textmulti_remove">{$remove}</a></div>');
						return false;
					},
					remove: function(e) {
						var item = $(e).prev();
						if (confirm("{$removethisitem} " + item.val())) {
							item.parent().remove();
						}
						return false;
					},
				}
				</script>
JSCODE;
		}
		return $this->controls_js($out);
	}
}

?>