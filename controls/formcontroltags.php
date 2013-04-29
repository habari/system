<?php

namespace Habari;

/**
 * A password control based on FormControlText for output via a FormUI.
 */
class FormControlTags extends FormControlText
{
	public static $outpre = false;

	public function pre_out()
	{
		$out = '';
		if ( !FormControlTextMulti::$outpre ) {
			FormControlTextMulti::$outpre = true;
			$out = <<< TAGS_PRE_OUT
<script type="text/javascript">
$(function(){
	$('input.tags_control').each(function(){

		for(var z in tc_tags=$(this).val().split(/\s*,\s*/)) {
			tc_tags[z]=tc_tags[z].replace(/^(["'])(.*)\1$/, '$2');
		}
		console.log(tc_tags);

		\$this = $(this);
		ajax_url = $(this).data('ajax_url');
		console.log(ajax_url);
		\$this.select2({
			tags: tc_tags,
			placeholder: "Tags",
			minimumInputLength: 1,
			ajax: {
				url: ajax_url,
				dataType: 'json',
				quietMillis: 100,
				data: function (term, page) {
					return { q: term };
				},
				results: function (data, page) {
					var results = {};
					for(var z in data.data) {
						results[parseInt(z)] = {id: parseInt(z), text: data.data[z]};
					}
					return {results: results, more: false};
				},
				formatSelection: function(item) {
					return item.text;
				},
				formatResult: function(item) {
					return item.text;
				}
			}
		});

	});
});
</script>
TAGS_PRE_OUT;
		}
		return $this->controls_js($out);
	}
}

?>