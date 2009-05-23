// Code to create the color pickers for themes
var colorpicker = {
	init: function() {
		colorpicker.formcontrols = $('.color.formcontrol');
		colorpicker.boxes = $('.color.formcontrol .colorbox');

		if(colorpicker.formcontrols.length < 1) {
			return;
		}

		colorpicker.formcontrols.addClass('loaded');

		colorpicker.formcontrols.each(function() {
			$('.colorbox', this).css({'backgroundColor': $('input', this).val()});
		});

		$(colorpicker.boxes).parent().ColorPicker({
			flat: true,
			color: '#FFFFFF',
			onBeforeShow: function(colpkr) {
				parent = $(colpkr).parents('.formcontrol.color');
				input = $('.text input', parent);
				box = $('.colorbox', parent);
				$(colpkr).ColorPickerSetColor(input.val());
				return false;
			},
			onSubmit: function(cal, hsb, hex, rgb) {
				parent = cal.parents('.formcontrol.color');
				input = $('.text input', parent);
				input.attr('value', '#' + hex);
				$('.colorbox', parent).css('backgroundColor', '#' + hex);
				
				$(cal).fadeOut(500);
				
				return true;
			},
			onShow: function (colpkr) {
				$(colpkr).fadeIn(500);
				return false;
			},
			onHide: function (colpkr) {
				$(colpkr).fadeOut(500);
				return false;
			},
			onChange: function (hsb, hex, rgb) {
			}
		}).ColorPickerHide();

		colorpicker.boxes.click(function() {
			parent = $(this).parent();
			if($('.colorpicker', parent).css('display') == 'none') {
				parent.ColorPickerShow();
			} else{
				parent.ColorPickerHide();
			}
		});
	}
}

// Start it all up
$(document).ready(function() {
	colorpicker.init();
});