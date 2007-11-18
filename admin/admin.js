String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

$(document).ready(function(){
	$(".search_field").click(function(){
		if($(".search_field:checked").size() == 0 && !$(this).attr('checked')) {
			return false;
		}
	});

	$('label.incontent').each(function(){
		var ctl = '#' + $(this).attr('for');
		if($(ctl).val() == '') {
			$(ctl).val($(this).html().trim())
				.addClass('islabeled');
		}
		$(this).hide();
	});
	$('.islabeled').focus(function(){
		$(this).filter('.islabeled')
			.val('')
			.removeClass('islabeled');
	}).blur(function(){
		if($(this).val() == '') {
			$(this).val($('label.incontent[for=' + $(this).attr('id') + ']').html().trim())
				.addClass('islabeled');
		}
	});

	$('.islabeled').parents('form').submit(function(){
		$('.islabeled').val('');
	});
});