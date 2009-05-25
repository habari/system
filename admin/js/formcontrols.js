// Code to create the slider formcontrols
var sliders = {
	init: function() {
		sliders.formcontrols = $('.slider.formcontrol');

		if(sliders.formcontrols.length < 1) {
			return;
		}
		
		sliders.formcontrols.addClass('loaded');
		
		sliders.formcontrols.each(function() {
			slider = $('.slider', this);
			input = $('.text input', this);
			value = input.val();
			min = parseInt($('.data.min', this).text());
			max = parseInt($('.data.max', this).text());
			step = parseInt($('.data.step', this).text());
			
			slider.slider({
				value: value,
				min: min,
				max: max,
				step: step,
				slide: function(event, ui) {
					$('.text input', $(this).parent()).val(ui.value);
				}
			});
			
			input.keyup(function() {
				$('.slider', $(this).parent().parent()).slider('value', $(this).val());
			});
		});
	}	
}

// Code to create image formcontrols
var imagecontrols = {
	init: function() {
		imagecontrols.formcontrols = $('.image.formcontrol');
		imagecontrols.triggers = $('.image.formcontrol > .image a');
		imagecontrols.pickers = $('.image.formcontrol > .picker');

		if(colorpicker.formcontrols.length < 1) {
			return;
		}
		
		imagecontrols.triggers.click(function() {
			imagecontrols.open($(this).parents('.image.formcontrol'));
			return false;
		});
		
		imagecontrols.formcontrols.each(function() {
			formcontrol = $(this);
			picker = $('.picker', formcontrol);
			label = $('.label label', formcontrol);
			
			picker.dialog({
				autoOpen: false,
				height: 300,
				width: 750,
				modal: true,
				title: label.text(),
				buttons: {
					'<?php echo _t('Save'); ?>': function() {
						$(this).dialog('close');
					},
					'<?php echo _t('Reset'); ?>': function() {
						$(this).dialog('close');
					},
					'<?php echo _t('Cancel'); ?>': function() {
						$(this).dialog('close');
					}
				},
				close: function() {
				}
			}).dialog('open');
		})
		
		
		
	},
	open: function(formcontrol) {
		picker = imagecontrols.pickers.eq(imagecontrols.formcontrols.index(formcontrol));
		picker.dialog('open');
		// console.log($('.picker', formcontrol), formcontrol);
		return;
	}
}

// Code to create the color picker formcontrols
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
	sliders.init();
	imagecontrols.init();
});