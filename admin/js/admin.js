// DASHBOARD
var dashboard = {
	init: function() {
		$('.modules').sortable({
			accept: '.module',
			handle: 'div.handle',
			opacity: .9,
			onStop : function(){ /* AJAX call goes here. Maybe disable and enable dragging while it's being made? */ }
		})

		$('.options').toggle(function() {
				$(this).parents('li').addClass('viewingoptions')
			}, function() {
				$(this).parents('li').removeClass('viewingoptions')
			})
	}
}

// TIMELINE
var timeline = {
	init: function() {
		// No Timeline? No runny-runny.
		if (!$('.timeline').length) return;

		var timelineWidth = $('.years').width();
		var steps = $('.years').width()/$('.handle').width();

		$('.track')
		.width($('.years').width())
		.slider({
			handle: '.handle',
			maxValue: timelineWidth-20,
			stop: function(event, ui) {
				timelineHandle.updateLoupeInfo();

				console.log('Loupe Moved To: '+ui.value)
			}
		})
		.unbind('click')
		.bind('dblclick', function(e) { // Double-clicking on either side of the handle moves the handle to the clicked position.
			// Dismiss clicks on handle
			if ($(e.target).is('.handle')) return false;

			timeline.noJump = true;
			clearTimeout(timeline.t1);
			$('.track').slider('moveTo', e.layerX)
		})
		.bind('click', function(e) { // Clicking either side of the handle moves the handle its own length to that side.

			// Dismiss clicks on handle
			if ($(e.target).is('.handle')) return false;

			// Click to left or right of handle?
			if (e.layerX < $('.track').slider("value") )
				timeline.t1 = setTimeout('timeline.skipLoupeLeft()', 300);
			else
				timeline.t1 = setTimeout('timeline.skipLoupeRight()', 300);
		})
		.slider('moveTo', timelineWidth)

		// Spool the timline handle
		timelineHandle.init();
	},
	skipLoupeLeft: function(e) {
		console.log('Jump Left')
		if (timeline.noJump == true) {
			timeline.noJump = null;
			return false;
		}

		$('.handle').css( 'left', Math.max(parseInt($('.handle').css('left')) - $('.handle').width(), 0) )

		timelineHandle.updateLoupeInfo();

	},
	skipLoupeRight: function(e) {
		console.log('Jump Right')
		if (timeline.noJump == true) {
			timeline.noJump = null;
			return false;
		}

		$('.handle').css( 'left', Math.min(parseInt($('.handle').css('left')) + $('.handle').width(), parseInt($('.track').width()) - $('.handle').width() ))

		timelineHandle.updateLoupeInfo();
	}
}


// TIMELINE HANDLE
var timelineHandle = {
	init: function() {
		// Resize Handle Left
		$('.resizehandleleft')
			.mousedown(function(e) {
				timelineHandle.firstMousePos = $('.handle').offset().left - $('.track').offset().left;
				timelineHandle.initialSize = $('.handle').width();

				$(document).mousemove(timelineHandle.doDragLeft).mouseup(timelineHandle.endDrag);
				return false;
			})
			.mouseup(timelineHandle.endDrag);

		$('.resizehandleright')
			.mousedown(function(e) {
				timelineHandle.firstMousePos = e.clientX;
				timelineHandle.initialSize = $('.handle').width();

				$(document).mousemove(timelineHandle.doDragRight).mouseup(timelineHandle.endDrag);
				return false;
			})
			.mouseup(timelineHandle.endDrag);
	},
	doDragLeft: function(e) {
		$('.handle').css({
			'left': 	'auto',
			'right': 	$('.handle').parents('.track').width() - (parseInt($('.handle').css('left')) + $('.handle').width())
		})

		Math.min()

		// Set Loupe Width. Min 20, Max 200, no spilling to the left
		$('.handle').css('width', Math.min(Math.max(timelineHandle.initialSize + (timelineHandle.firstMousePos - (e.clientX - $('.track').offset().left)), 20), Math.min($('.track').width() - parseInt($('.handle').css('right')), 200)))

		return false;
	},
	doDragRight: function(e) {
		$('.handle').css({
			'left': 	$('.handle').offset().left - $('.track').offset().left,
			'right': 	'auto'
		})

		// Set Loupe Width. Min 20, Max 200, no spilling to the right
		$('.handle').css( 'width', Math.min(Math.max(timelineHandle.initialSize + (e.clientX - timelineHandle.firstMousePos), 20), Math.min($('.track').width() - parseInt($('.handle').css('left')), 200)) )

		return false;
	},
	updateLoupeInfo: function() {
		timelineWidth = $('.track').width();
		loupePosition = parseInt($('.handle').css('left'));
		loupeWidth = loupePosition + $('.handle').width();

		$('.currentposition').text( loupePosition +'-'+ loupeWidth +' of '+ timelineWidth )

		/* AJAX call to fetch needed info goes here. */
		if(jQuery.isFunction(this.loupeUpdate)) {
			this.loupeUpdate(loupePosition, loupeWidth, timelineWidth);
		}
	},
	endDrag: function(e) {
		timeline.noJump = true;

		// Reset to using 'left'.
		$('.handle').css({
			'left': 	$('.handle').offset().left - $('.track').offset().left,
			'right': 	'auto'
		})

		timelineHandle.updateLoupeInfo();

		$(document).unbind('mousemove', timelineHandle.doDrag).unbind('mouseup', timelineHandle.endDrag);

		return false;
	},
	// This function is assigned on the page so that the loupe can be bound to disctinct ajax calls
	loupeUpdate: null
}



/* TIMELINE TODO:
	- Consider step'd resize
		- Problem: timeline length might not be suitable for steps, so at either end, the loupe might not fit.
	- Draggable timeline
	- Float month name if outside view
	- Display and float years
	- Reinit slider (or do away with slider alltogether and gather the info manually)
	-
*/


// SPINNER
var spinner = {
	start: function() {
		$('#spinner').spinner({ height: 32, width: 32, speed: 50, image: '../system/admin/images/spinnersmalldark.png' }); $('#spinner').show();
	},
	stop: function () {
		$('#spinner').spinner('stop');$('#spinner').hide();
	}
}


// DROPBUTTON
var dropButton = {
	init: function() {
		var currentDropButton = '';
		$('.dropbutton').hover( function(e) {
			dropButton.currentDropButton = $(e.currentTarget);

			// Clear any timers, let the button know it's being hovered
			clearTimeout(dropButton.t1);
			dropButton.showMenu();
		}, function(e) {
			// After mouse out, wait, then close
			dropButton.t1 = setTimeout('dropButton.hideMenu()', 500);
		})
	},

	showMenu: function(element) {
		// Close all open dropbuttons
		$('.dropbutton').removeClass('hovering')

		// Open this dropbutton
		$(dropButton.currentDropButton).addClass('hovering')
	},

	hideMenu: function(element) {
		// Fade out and close dropbutton
		$(dropButton.currentDropButton).removeClass('hovering')

		$('.carrot').removeClass('carrot')
	}
};



// THE MENU
var theMenu = {
	init: function() {
		// Carrot functionality
		$('#menulist li').hover(function() {
			$('#menulist li').removeClass('carrot')
			$(this).addClass('carrot')
		}, function() {
			$('#menulist li').removeClass('carrot')
		})

		// Adhoc hotkey support
		$('html').keydown(function (e) {
			var keyCode = e.keyCode;

			// Disable hotkeys in relevant form elements
			if (e.target.tagName == 'INPUT' || e.target.tagName == 'TEXTAREA')
				return;

			// Temp keydown-checker
			// Hide menu on ESC
			if (keyCode == 27) {
				$('.carrot').removeClass('carrot')
				dropButton.hideMenu();

			// Catch 'Q'
			} else if (keyCode == 81 && ($('#menu #menulist').css('display') != 'block')) {
				dropButton.currentDropButton = $('#menu');
				dropButton.showMenu();
			} else if (keyCode == 81 && ($('#menu #menulist').css('display') == 'block')) {
				dropButton.hideMenu();

			// Down Arrow & Menu Open?
			} else if (($('#menu').hasClass('hovering') == true) && keyCode == 40) {
				// If carrot doesn't exist, select first item
				if (!$('#menulist li').hasClass('carrot'))
					$('#menulist li:first').addClass('carrot')
				// If carrot is at bottom, move it to top
				else if ($('#menulist li:last').hasClass('carrot')) {
					$('#menulist li:last').removeClass('carrot')
					$('#menulist li:first').addClass('carrot')
				// If carrot exists, move it down
				} else
					$('.carrot').removeClass('carrot').next().addClass('carrot')

			// Up Arrow & Menu Open?
			} else if (($('#menu').hasClass('hovering') == true) && keyCode == 38) {

				// If carrot doesn't exist, select last item
				if (!$('#menulist li').hasClass('carrot'))
					$('#menulist li:last').addClass('carrot')
				// If carrot is at top, move it to bottom
				else if ($('#menulist li:first').hasClass('carrot')) {
					$('#menulist li:first').removeClass('carrot')
					$('#menulist li:last').addClass('carrot')
				// If carrot exists, move it up
				} else
					$('.carrot').removeClass('carrot').prev().addClass('carrot')

			// Enter & Carrot
			} else if ($('#menu').hasClass('hovering') == true && $('.carrot') && keyCode == 13) {
				location = $('.carrot a').attr('href')


			// Pass it along if it isn't for us
			} else
				return;

			// Don't pass key along
			return false;
		})
	}
}


// RESIZABLE TEXTAREAS
$.fn.resizeable = function(){

	this.each(function() {
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
}



/* ON PAGE STARTUP */
String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

var tagskeyup;

$(document).ready(function(){
	// Ready all dropbuttons and The Menu
	dropButton.init();
	theMenu.init();
	dashboard.init();
	timeline.init();

	// Damn the lack of proper support for pseudo-classes!
	$('.modulecore .item:first-child, ul li:first-child').addClass('first-child')
	$('.modulecore .item:last-child, ul li:last-child').addClass('last-child')

	// Alternate the rows' styling.
    $("table").each(function(){
	  $("tr:odd", this).not(".even").addClass("odd");
	  $("tr:even", this).not(".odd").addClass("even");
	});

	$("#oldmenu .menu-item").hover(
		function(){ $("ul", this).fadeIn("fast"); },
		function() { }
	);

	// Prevent all checkboxes to be unchecked.
	$(".search_field").click(function(){
		if($(".search_field:checked").size() == 0 && !$(this).attr('checked')) {
			return false;
		}
	});

	// Move labels into elements. Use with usability-driven care.
	$('label.incontent').each(function(){
		if ($('#habari_password').length > 0)
			document.getElementById('habari_password').type = 'text';

		var ctl = '#' + $(this).attr('for');
		if($(ctl).val() == '') {
			$(ctl).val($(this).html().trim())
				.addClass('islabeled');
		}

		$(this).hide()
	});

	$('.islabeled').focus(function(){
		$('label[for='+$(this).attr('id')+']').show()

		if ($(this).attr('id') == 'habari_password')
			document.getElementById('habari_password').type = 'password';

		$(this).filter('.islabeled')
			.val('')
			.removeClass('islabeled');
	}).blur(function(){
		$('label[for='+$(this).attr('id')+']').hide()

		if ($(this).val() == '') {
			$('label[for='+$(this).attr('id')+']').removeClass('popup')


			if ($(this).attr('id') == 'habari_password')
				document.getElementById('habari_password').type = 'text';

			$(this)
				.addClass('islabeled')
				.val($('label.incontent[for=' + $(this).attr('id') + ']').html().trim())
		} else {
			$('label[for='+$(this).attr('id')+']').addClass('popup')
		}
	});

	$('.islabeled').parents('form').submit(function(){
		$('.islabeled').val('');
	});


	// Convert these links into buttons
	$('a.link_as_button').each(function(){
		$(this).after('<button onclick="location.href=\'' + $(this).attr('href') + '\';return false;">' + $(this).html() + '</button>').hide();
	});


	/* Make Textareas Resizable */
	$('.resizable').resizeable();


	/* Init Tabs, using jQuery UI Tabs */
	$('.tabcontrol').tabs({ fxShow: { height: 'show', opacity: 'show' }, fxHide: { height: 'hide', opacity: 'hide' }, unselected: true })


	// Tag Drawer: Add tag via click
	$('#tag-list li').click(function() {
		// here we set the current text of #tags to current for later examination
		var current = $('#tags').val();

		// create a regex that finds the clicked tag in the input field
		var replstr = new RegExp('\\s*"?' + $( this ).text() + '"?\\s*', "gi");

		// check to see if the tag item we clicked has been clicked before...
		if( $( this ).attr( 'class' )== 'clicked' ) {
			// remove that tag from the input field
			$( '#tags' ).val( current.replace(replstr, '') );
			// unhighlight that tag
			$(this).removeClass( 'clicked' );
		}
		else {
			// if it hasn't been clicked, go ahead and add the clicked class
			$(this).addClass( 'clicked' );
			// be sure that the option wasn't already in the input field
			if(!current.match(replstr) || $( '#tags.islabeled' ).size() > 0) {
				// check to see if current is the default text
				if( $( '#tags.islabeled' ).size() > 0 ) {
					// and if it is, replace it with whatever we clicked
					$( '#tags' ).removeClass('islabeled').val( $( this ).text() );
				} else {
					// else if we already have tag content, just append the new tag
					if( $('#tags' ).val() != '' ) {
						$( '#tags' ).val( current + "," + $( this ).text() );
					} else {
						$( '#tags' ).val( $( this ).text() );
					}
				}
			}
		}

		// replace unneccessary commas
		$( '#tags' ).val( $( '#tags' ).val().replace(new RegExp('^\\s*,\\s*|\\s*,\\s*$', "gi"), ''));
		$( '#tags' ).val( $( '#tags' ).val().replace(new RegExp('\\s*,(\\s*,)+\\s*', "gi"), ','));

	});

	$( '#tags' ).keyup(function(){
		clearTimeout(tagskeyup);
		tagskeyup = setTimeout(resetTags, 500);
	});

	// Tag Drawer: Remove all tags.
	$( '#clear' ).click( function() {
		// so we nuke all the tags in the tag text field
		$( '#tags' ).val( 'Tags, separated by, commas' ).addClass('islabeled');
		// and remove the clicked class from the tags in the manager
		$( '#tag-list li' ).removeClass( 'clicked' );
	});


	// LOGIN: Obscure password field if browser has loaded in cookie'd info
	if ($('body.login #habari_password').length > 0 && $('body.login #habari_password').val() != 'Password')
		document.getElementById('habari_password').type = 'password';

	// LOGIN: Focus cursor on 'Name'.
	$('body.login #habari_username').focus()


});

function resetTags() {
	var current = $('#tags').val();

	$('#tag-list li').each(function(){
		replstr = new RegExp('\\s*"?' + $( this ).text() + '"?\\s*', "gi");
		if(current.match(replstr)) {
			$(this).addClass('clicked');
		}
		else {
			$(this).removeClass('clicked');
		}
	});

}

// EDITOR INTERACTION
habari.editor = {
	insertSelection: function(value) {
		if($('#content').filter('.islabeled').size() > 0) {
			$('#content').filter('.islabeled')
				.removeClass('islabeled')
				.val(value);
		}
		else {
			var contentel = $('#content')[0];
			if('selectionStart' in contentel) {
				var content = $('#content').val();
				$('#content').val(content.substr(0, contentel.selectionStart) + value + contentel.value.substr(contentel.selectionEnd, content.length));
			}
			else if(document.selection) {
				contentel.focus();
				document.selection.createRange().text = value;
			}
			else {
				$('#content').filter('.islabeled')
					.removeClass('islabeled')
					.val(value);
			}
		}
	},
	getContents: function() {
		return $('#content').val();
	},
	setContents: function(contents) {
		$('#content').filter('.islabeled')
			.val('')
			.removeClass('islabeled');
		$('#content').val(contents)
	},
	getSelection: function(contents) {
		if($('#content').filter('.islabeled').size() > 0) {
			return '';
		}
		else {
			var contentel = $('#content')[0];
			if('selectionStart' in contentel) {
				return $('#content').val().substr(contentel.selectionStart, contentel.selectionEnd - contentel.selectionStart);
			}
			else if(document.selection) {
				contentel.focus();
				var range = document.selection.createRange();
				if (range == null) {
					return '';
				}
				return range.text;
			}
			else {
				return $("#content").val();
			}
		}
	}
};