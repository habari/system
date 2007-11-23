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
		if($(this).val() == '') {
			$(this).val($('label.incontent[for=' + $(this).attr('id') + ']').html().trim())
				.addClass('islabeled');
		}
	});

	$('.islabeled').parents('form').submit(function(){
		$('.islabeled').val('');
	});

	// Convert these links into buttons
	$('a.link_as_button').each(function(){
		$(this).after('<button onclick="location.href=\'' + $(this).attr('href') + '\'">' + $(this).html() + '</button>').hide();
	});
});