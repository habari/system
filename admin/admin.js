$.fn.hoverClass = function(c) {
	return this.each(function(){
		$(this).hover(
			function() { $(this).addClass(c);  },
			function() { $(this).removeClass(c); }
		);
	});
};

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

$(document).ready(function(){
	// Alternate the rows' styling.
    $("table").each(function(){
	  $("tr:odd", this).not(".even").addClass("odd");
	  $("tr:even", this).not(".odd").addClass("even");
	});

	$("#menu .menu-item").hover(
		function(){ $("ul", this).fadeIn("fast"); },
		function() { }
	);

	if (document.all) {
		$("#menu .menu-item").hoverClass("sfHover");
	}

	// Prevent all checkboxes to be unchecked.
	$(".search_field").click(function(){
		if($(".search_field:checked").size() == 0 && !$(this).attr('checked')) {
			return false;
		}
	});

	// Toggle default text of inputs on the publish page.
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
		if ($(this).val() == '') {
			$(this)
				.addClass('islabeled')
				.val($('label.incontent[for=' + $(this).attr('id') + ']').html().trim())
		}
	});

	$('.islabeled').parents('form').submit(function(){
		$('.islabeled').val('');
	});

	// Convert these links into buttons
	$('a.link_as_button').each(function(){
		$(this).after('<button onclick="location.href=\'' + $(this).attr('href') + '\';return false;">' + $(this).html() + '</button>').hide();
	});
	
	/* Resizable Textareas */
	$('textarea.resizable').each(function() {
		var textarea = $(this);
		var offset = null;
		var grip = $('<div class="grip"></div>').mousedown(function(ev){
			offset = textarea.height() - (ev.clientY + document.documentElement.scrollTop)
			$(document).mousemove(doDrag).mouseup(endDrag);
		}).mouseup(endDrag);
		var resizer = $('<div class="resizer"></div>').css('margin-bottom',$(this).css('margin-bottom'));
		$(this).css('margin-bottom', '0px').wrap(resizer).parent().append(grip);

		function doDrag(ev){
			textarea.height(Math.max(offset + ev.clientY + document.documentElement.scrollTop, 60) + 'px');
			return false;
		}

		function endDrag(ev){
			$(document).unbind('mousemove', doDrag).unbind('mouseup', endDrag);
			textarea.css('opacity', 1.0);
		}

	});
	
	/* Tabs, using jQuery UI Tabs */
	$('.tabs').tabs({ fxShow: { height: 'show', opacity: 'show' }, fxHide: { height: 'hide', opacity: 'hide' }, unselected: true })
});




