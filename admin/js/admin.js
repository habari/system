// Habari ajax. All Habari Ajax calls should go through here. It allows us to use uniform humanmsg stuff,
// as well as uniform error handling
var habari_ajax = {
	post: function(post_url, post_data, ahah_target, local_cb) {
		habari_ajax.ajax('POST', post_url, post_data, ahah_target, local_cb);
	},
	
	get: function(get_url, get_data, ahah_target, local_cb) {
		habari_ajax.ajax('GET', get_url, get_data, ahah_target, local_cb);
	},
	
	ajax: function(type, url, data, ahah_target, local_cb) {
		$.ajax({
			url: url,
			data: data,
			success: function(json_data) {
				if($.isPlainObject(ahah_target)) {
					for(var i in ahah_target) {
						if(json_data.html && json_data.html[i]) {
							$(ahah_target[i]).html(json_data.html[i]);
						}
					}
				}
				var cb = ($.isFunction(ahah_target) && local_cb == undefined) ? ahah_target : local_cb;
				habari_ajax.success(json_data, cb);
			},
		error: habari_ajax.error,
		  dataType: 'json',
			type: type
		});
	},
	
	success: function(json_data, local_cb)
	{
		spinner.stop();
		
		if ( json_data.response_code == 200 && json_data.message != null && json_data.message != '' ) {
			human_msg.display_msg( json_data.message );	
		}
		if ( json_data.response_code >= 400 ) {
			habari_ajax.error(null, json_data.message, json_data.response_code)
		}
		if(json_data.habari_callback != null && json_data.habari_callback != '') {
			json_data.habari_callback = eval(json_data.habari_callback);
			if($.isFunction(json_data.habari_callback)) {
				json_data.habari_callback(json_data);
			}
		}
		
		local_cb(json_data.data);
	},
	
	error: function(XMLHttpRequest, textStatus, errorThrown) {
		var msg;
		if ( XMLHttpRequest == null ) {
			switch( errorThrown ) {
				case 408:
					msg = textStatus;
					break;
				default:
					msg = _t('Uh Oh. An error has occurred. Please try again later.');
			}
		}
		else {
			msg = _t('Uh Oh. An error has occurred. Please try again later.');
		}
		spinner.stop();
		human_msg.display_msg(msg);
	}
}

// Help Toggler
var helpToggler = {
	init: function() {
		$('.plugins .item a.help, .currenttheme a.help').click(function() {
			var help = $('.pluginhelp', $(this).parents('.item')).add( $('.currenttheme #themehelp') );
									
			if( help.hasClass('active') ) {
				help.slideUp();
				help.add(this).removeClass('active');
			}
			else {
				help.slideDown();
				help.add(this).addClass('active');
			}
			
			return false;
			
		});
	}
}

// SPINNER
var spinner = {
	start: function() {
		$('#spinner')
			.css({ height: 32, width: 32 })
			.css('background-image', 'url(' + habari.url.habari + '/system/admin/images/spin.gif)')
			.show();
	},
	stop: function () {
		$('#spinner').hide();
	}
};

// THE MENU
var theMenu = {
	init: function() {
		// Open menu on Q
		$.hotkeys.add('q', {propagate:true, disableInInput: true}, function(){
			if (!$('#menulist').hasClass('hovering')) {
				$('#menulist').addClass('hovering');
			} else {
				$('#menulist').removeClass('hovering');
			}
		});

		// Close menu on ESC
		$.hotkeys.add('esc', {propagate:true, disableInInput: false}, function(){
			$('#menulist').removeClass('hovering');
		});

		// Down arrow
		$.hotkeys.add('down', {propagate:true, disableInInput: true}, function(evt) {
			if ($('#menulist .carrot ul li.carrot').length !== 0) {
				if ($('#menulist .carrot ul li:last').hasClass('carrot')) {
					// Move to top if at bottom
					$('#menulist .carrot ul li:last').removeClass('carrot');
					$('#menulist .carrot ul li:first').addClass('carrot');
				} else {
					$('#menulist .carrot ul li.carrot').removeClass('carrot').next().addClass('carrot');
				}
				// stop propagation
				evt.preventDefault();
			} else if (($('#menu').hasClass('hovering') === true)) {
				// If carrot doesn't exist, select first item
				if (!$('#menulist li').hasClass('carrot')) {
					$('#menulist li:first').addClass('carrot');
				}
				// If carrot is at bottom, move it to top
				else if ($('#menulist li:last').hasClass('carrot')) {
					$('#menulist li:last').removeClass('carrot');
					$('#menulist li:first').addClass('carrot');
				// If carrot exists, move it down
				} else {
					$('.carrot').removeClass('carrot').next().addClass('carrot');
				}
				// stop propagation
				evt.preventDefault();
			}
			return false;
		});

		// Left arrow
		$.hotkeys.add('left', {propagate:true, disableInInput: true}, function(){
			$('.carrot ul li.carrot').removeClass('carrot');
		});

		// Up arrow
		$.hotkeys.add('up', {propagate:true, disableInInput: true}, function(){
			if ($('#menulist .carrot ul li.carrot').length !== 0) {
				if ($('#menulist .carrot ul li:first').hasClass('carrot')) {
					$('#menulist .carrot ul li:first').removeClass('carrot');
					$('#menulist .carrot ul li:last').addClass('carrot');
				// If carrot exists, move it up
				} else {
					$('#menulist .carrot ul li.carrot').removeClass('carrot').prev().addClass('carrot');
				}
			} else if ($('#menu').hasClass('hovering') === true) {
				// If carrot doesn't exist, select last item
				if (!$('#menulist li').hasClass('carrot')) {
					$('#menulist li:last').addClass('carrot');
				}
				// If carrot is at top, move it to bottom
				else if ($('#menulist li:first').hasClass('carrot')) {
					$('#menulist li:first').removeClass('carrot');
					$('#menulist li:last').addClass('carrot');
				// If carrot exists, move it up
				} else {
					$('.carrot').removeClass('carrot').prev().addClass('carrot');
				}
			} else {
				return false;
			}
		});

		// Right arrow
		$.hotkeys.add('right', {propagate:true, disableInInput: true}, function(){
			if ($('.carrot').hasClass('submenu') === true) {
				$('.carrot ul li:first').addClass('carrot');
			} else {
				return false;
			}
		});

		// Left arrow
		$.hotkeys.add('left', {propagate:true, disableInInput: true}, function(){
			$('.carrot ul li.carrot').removeClass('carrot');
		});

		// Enter & Carrot
		$.hotkeys.add('return', { propagate:true, disableInInput: true }, function() {
			if ($('#menu').hasClass('hovering') === true && $('.carrot')) {
				if($('.carrot .carrot').length > 0) {
					carrot= $('.carrot .carrot a').eq(0); 
				} else {
					carrot= $('.carrot a').eq(0);
				}
				theMenu.blinkCarrot(carrot.parent());
				location = carrot.attr('href');
			} else {
				return false;
			}
		});

		// Page hotkeys
		$('#menu ul li').each(function() {
			var hotkey = $('a span.hotkey', this).eq(0).text();
			var href = $('a', this).attr('href');
			var owner = $(this);
			var blinkSpeed = 100;

			if (hotkey) {
				$.hotkeys.add(hotkey, { propagate: true, disableInInput: true }, function() {
					if ($('#menulist').hasClass('hovering') === true) {
						if (owner.hasClass('submenu')) {
							if(!owner.hasClass('hovering')) {
								owner.addClass('hovering');
							} else {
								owner.removeClass('hovering');
							}
						} else if (owner.hasClass('sub')) {
							// Exists in a submenu
							if ($('#menu li.carrot li.hotkey-' + hotkey).length !== 0) {
								// Hotkey exists in an active menu, use that
								location = $('#menu li.carrot li.hotkey-' + hotkey + ' a').attr('href');
								theMenu.blinkCarrot($('#menu li.carrot li.hotkey-' + hotkey));
							} else {
								// Use the first occurance of hotkey, but expand the parent first
								user = $('#menu li li.hotkey-' + hotkey).eq(0);
								user.parent().parent().addClass('carrot');
								location = $('a', user).attr('href');
								theMenu.blinkCarrot(user);
							}
						} else {
							location = href;
							theMenu.blinkCarrot(owner);
						}

					} else {
						return false;
					}
				});
			}
		});

		// View blog hotkey
		$.hotkeys.add('v', { propagate: true, disableInInput: true }, function() {
			location = $('#site').attr('href');
		});

		// Display hotkeys
		$('#menu a .hotkey').addClass('enabled');

		$('#menu ul li a').click( function() {
			theMenu.blinkCarrot(this);
		});

		// If menu is open and mouse is clicked outside menu, close menu.
		$('html').click(function() {
			if ($('#menulist').hasClass('hovering')) {
				$('#menulist').removeClass('hovering');
			}
		});
	},
	blinkCarrot: function(owner) {
		var blinkSpeed = 100;
		$(owner).addClass('carrot').addClass('blinking').fadeOut(blinkSpeed).fadeIn(blinkSpeed).fadeOut(blinkSpeed).fadeIn(blinkSpeed);
	}
};

// RESIZABLE TEXTAREAS
$.fn.resizeable = function(){

	this.each(function() {
		function doDrag(ev){
			textarea.height(Math.max(offset + ev.clientY + document.documentElement.scrollTop, 60) + 'px');
			return false;
		}

		function endDrag(ev){
			$(document).unbind('mousemove', doDrag).unbind('mouseup', endDrag);
			textarea.css('opacity', 1.0);
		}

		var textarea = $(this);
		var offset = null;
		var grip = $('<div class="grip"></div>').mousedown(function(ev){
			offset = textarea.height() - (ev.clientY + document.documentElement.scrollTop);
			$(document).mousemove(doDrag).mouseup(endDrag);
		}).mouseup(endDrag);
		var resizer = $('<div class="resizer"></div>').css('margin-bottom',$(this).css('margin-bottom'));
		$(this).css('margin-bottom', '0px').wrap(resizer).parent().append(grip);
	});
};


// RANGE SELECT - Courtesy of Barney Boisvert at http://www.barneyb.com/barneyblog/projects/jquery-checkbox-range-selection/
$.fn.rangeSelect = function() {
	var lastCheckbox = null;
	var $spec = this;

	$spec.bind("click", function(e) {
		if (lastCheckbox !== null && e.shiftKey) {
			$spec.slice(
				Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
				Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1)
			.attr({checked: e.target.checked ? "checked" : ""}).change();
		}
		lastCheckbox = e.target;
	});
	return $spec;
};

// Home-made pseudo-classes
function findChildren() {
	$('div > .item:first-child, .modulecore .item:first-child, ul li:first-child').addClass('first-child');
	$('div > .item:last-child, .modulecore .item:last-child, ul li:last-child').addClass('last-child');
}

// code for making inline labels which then move above form inputs when the inputs have content
var labeler = {
	focus: null,
	init: function() {
		$('label.incontent').each( function() {
			labeler.check(this);

			// focus on the input when clicking on the label
			$(this).click(function() {
				$('#' + $(this).attr('for')).focus();
			});
		});

		$('.islabeled').focus( function() {
			labeler.focus = $(this);
			labeler.aboveLabel($(this));
		}).blur(function(){
			labeler.focus = null;
			labeler.check($('label[for='+$(this).attr('id')+']'));
		});
	},
	check: function(label) {
		var target = $('#' + $(label).attr('for'));

		if ( !target ) {return;}

		if ( labeler.focus !== null && labeler.focus.attr('id') == target.attr('id') ) {
			labeler.aboveLabel(target);
		}
		else if ( target.val() === '' ) {
			labeler.overLabel(target);
		}
		else {
			labeler.aboveLabel(target);
		}
	},
	aboveLabel: function(el) {
		$(el).addClass('islabeled');
		$('label[for=' + $(el).attr('id') + ']').removeClass('overcontent').removeClass('hidden').addClass('abovecontent');
	},
	overLabel: function(el) {
		$(el).addClass('islabeled');
		// If the placeholder attribute is supported, we can simply hide labels when we have provided a
		// placeholder attribute
		if ("placeholder" in $(el)[0] && $(el).attr('placeholder') ) {
			$('label[for=' + $(el).attr('id') + ']').addClass('hidden');
		}
		else {
			$('label[for=' + $(el).attr('id') + ']').addClass('overcontent').removeClass('abovecontent');
		}
	}
};


// EDITOR INTERACTION
habari.editor = {
	insertSelection: function(value) {
		var contentel = $('#content')[0];
		if ('selectionStart' in contentel) {
			var content = $('#content').val();
			$('#content').val(content.substr(0, contentel.selectionStart) + value + contentel.value.substr(contentel.selectionEnd, content.length));
		}
		else if (document.selection) {
			contentel.focus();
			document.selection.createRange().text = value;
		}
		else {
			$('#content').filter('.islabeled')
				.val(value);
		}
		$('label[for=content].overcontent').addClass('abovecontent').removeClass('overcontent').hide();
	},
	getContents: function() {
		return $('#content').val();
	},
	setContents: function(contents) {
		$('#content').filter('.islabeled')
			.val('')
			.removeClass('islabeled');
		$('#content').val(contents);
	},
	getSelection: function(contents) {
		if ($('#content').val() === '') {
			return '';
		}
		else {
			var contentel = $('#content')[0];
			if ('selectionStart' in contentel) {
				return $('#content').val().substr(contentel.selectionStart, contentel.selectionEnd - contentel.selectionStart);
			}
			else if (document.selection) {
				contentel.focus();
				var range = document.selection.createRange();
				if ( range === undefined ) {
					return '';
				}
				return range.text;
			}
			else {
				return $('#content').val();
			}
		}
	}
};


// ON PAGE STARTUP
var tagskeyup;

$(window).load( function() {
	// Icons only for thin-width clients -- Must be run here to work properly in Safari
	if ($('#title').width() < ($('#mediatabs li').length * $('#mediatabs li').width())) {
		//$('#mediatabs').addClass('iconify');
	}
});

$(document).ready(function(){
	theMenu.init();
	helpToggler.init();
	findChildren();
	labeler.init();
	
	// fix autofilled passwords overlapping labels
	$(window).load(function(){
		window.setTimeout(function(){
			labeler.check($('label[for=habari_password]'));
		}, 200);
	});

	// Prevent all checkboxes to be unchecked.
	$(".search_field").click(function(){
		if ($(".search_field:checked").size() === 0 && !$(this).attr('checked')) {
			return false;
		}
	});

	// Convert these links into buttons
	$('a.link_as_button').each(function(){
		$(this).after('<button onclick="location.href=\'' + $(this).attr('href') + '\';return false;">' + $(this).html() + '</button>').hide();
	});

	/* Make Textareas Resizable */
	$('.resizable').resizeable();

	/* Init Tabs, using jQuery UI Tabs */
	$('.tabcontrol').parent().tabs({ 
		fx: { height: 'toggle', opacity: 'toggle' }, 
		selected: -1, 
		collapsible: true,
		select: function(event, ui) {
			$(ui.panel).removeClass('ui-tabs-panel ui-widget-content ui-corner-bottom');
		},
		create: function() {
			$(this).removeClass('ui-tabs ui-widget-content');
			$('.tabs').removeClass('ui-widget-header');
		},
		show: function(event, ui) {
			var panelHeight = $(ui.panel).height();
			$('html, body').animate({
				scrollTop: panelHeight
				},
				400
			);
		}
	});

	// LOGIN: Focus cursor on 'Name'.
	$('body.login #habari_username').focus();

	// Take care of AJAX calls
	$('body').bind('ajaxSuccess', function(event, req, opts){
		if (opts.dataType == 'json') {
			eval('var cc=' + req.responseText);
			if (cc.callback) {
				cc.callback();
			}
		}
	});

	// Init shift-click for range select on checkboxes
	$('input.checkbox').rangeSelect();
	
	// theme popups
	$('.themethumb').click(function(e){
		var themeinfo = $(this).siblings('.themeinfo')
		if(e.clientX > $(window).width()/2) themeinfo.addClass('right'); else themeinfo.removeClass('right');
		$('.themeinfo').not(themeinfo).hide();
		themeinfo.toggle();
	});
});
