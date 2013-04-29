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
			$out .= '
				<script type="text/javascript">
				controls.textmulti = {
					add: function(e, field){
						$(e).before(" <span class=\"textmulti_item\"><input type=\"text\" name=\"" + field + "[]\"> <a href=\"#\" onclick=\"return controls.textmulti.remove(this);\" title=\"'. _t( 'Remove item' ).'\" class=\"textmulti_remove opa50\">[' . _t( 'remove' ) . ']</a></span>");
						return false;
					},
					remove: function(e){
						if (confirm("' . _t( 'Remove this item?' ) . '")) {
							if ( $(e).parent().parent().find("input").length == 1) {
								field = $(e).prev().attr("name");
								$(e).parent().prev().before("<input type=\"hidden\" name=\"" + field + "\" value=\"\">");
							}
							$(e).parent().prev("input").remove();
							$(e).parent().remove();
						}
						return false;
					}
				}
				</script>
			';
		}
		return $this->controls_js($out);
	}

}

?>