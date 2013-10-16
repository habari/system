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

// DASHBOARD
var dashboard = {
	init: function() {
		$('.modules').sortable({
			items: '.module:not(.add-item-module)',
			handle: 'div.handle',
			opacity: 0.9,
			stop: function() {
				dashboard.update();
			}
		});

		$('.options').live('click', function(){
			var li = $(this).closest('li');
			if(li.hasClass('viewingoptions')) {
				li.toggleClass('viewingoptions');
			}
			else {
				spinner.start();
				$('.optionswindow .optionsform', li).load(
					habari.url.ajaxDashboard,
					{'action': 'configModule', 'moduleid': li.data('module-id')},
					function(){
						li.toggleClass('viewingoptions');
						spinner.stop();
					}
				);
			}
		});

		$('.close', '.modules').live('click', function() {
			// grab the module ID from the parent DIV data attribute.
			dashboard.remove( $(this).parents('.module').data('module-id') );
		});

		$('.optionsform form').live('submit', function(){
			return dashboard.post(this);
		});
		findChildren();
	},
	update: function() {
		spinner.start();
		// disable dragging and dropping while we update
		$('.modules').sortable('disable');
		var query = [];
		$('.module', '.modules').not('.ui-sortable-helper').each( function(i) {
			query.push($(this).data('module-id'));
		} );
		query.action = 'updateModules';
		habari_ajax.post(
			habari.url.ajaxDashboard,
			{'moduleOrder': query, 'action': 'updateModules'},
			function() {
				$('.modules').sortable('enable');
			}
		);
	},
	updateModule: function() {
		//spinner.start();
		// disable dragging and dropping while we update
		// here we would update the modules options then
		// reload the modules
	},
	add: function() {
		spinner.start();
		// disable dragging and dropping while we update
		$('.modules').sortable('disable');
		var query = {};
		query.action = 'addModule';
		query.module_name = $('#dash_additem option:selected').val();
		habari_ajax.post(
			habari.url.ajaxDashboard,
			query,
			{modules: '.modules'},
			function(){
				$('.modules').sortable('refresh');
				$('.modules').sortable('enable');
			}
		);
	},
	remove: function( id ) {
		spinner.start();
		// disable dragging and dropping while we update
		$('.modules').sortable('destroy');
		var query = {};
		query.action = 'removeModule';
		query.moduleid = id;
		habari_ajax.post(
			habari.url.ajaxDashboard,
			query,
			{modules: '.modules'},
			dashboard.init
		);
	},
	post: function(blockform) {
		var form = $(blockform);
		$.ajax({
			success: function(data){
				form.parents('.optionsform').html(data);
			},
			error: function(data, err) {
				console.log(data, err);
			},
			type: 'POST',
			url: habari.url.ajaxConfigModule,
			data: form.serialize(),
			dataType: 'html'
		});
		return false;
	}
};

// Item Management
var itemManage = {
	init: function() {
		if ($('.page-users, .page-options, .page-user, .page-tags, .page-plugins, .page-groups').length !== 0) {
			$("input#search").keyup(function (e) {
				var str = $('input#search').val();
				itemManage.simpleFilter(str);
			});
		}

		if (!$('.item.controls input[type=checkbox]')) {return;}

		itemManage.initItems();

		$('.item.controls input[type=checkbox]').change(function () {
			if ($('.item.controls label.selectedtext').hasClass('all')) {
				itemManage.uncheckAll();
			} else {
				itemManage.checkAll();
			}
		});

		/* for all manage pages except for comments, add an ajax call to the
		 * delete button
		 */
		if ( $('.manage.comments').length === 0 ) {
			$('.item.controls input.button.delete').click(function () {
				itemManage.update( 'delete' );
				return false;
			});
		}

		$('.item.controls input.rename.button').click(function() {
			itemManage.rename();
		});
	},
	initItems: function() {
		$('.item:not(.ignore) .checkbox input[type=checkbox]').change(function () {
			itemManage.changeItem();
		});
		$('.item:not(.ignore) .checkbox input[type=checkbox]').each(function() {
			id = $(this).attr('id');
			id = id.replace(/.*\[(.*)\]/, "$1" ); // checkbox ids have the form name[id]
			if (itemManage.selected['p' + id] == 1) {
				this.checked = 1;
			}
		});
		$('.item .less').click(function() {
			itemManage.expand($(this).parent());
		});
		itemManage.changeItem();
	},
	expand: function(item) {
		$('.item').removeClass('expanded');

		item.addClass('expanded');

		$('.more', item).click(function() {
			itemManage.contract($(this).parent());
		});
	},
	contract: function(item) {
		item.removeClass('expanded');
	},
	selected: [],
	searchCache: [],
	searchRows: [],
	simpleFilter: function( search ) {
		search = $.trim( search.toLowerCase() );

		// cache search items on first call
		if ( itemManage.searchCache.length === 0 ) {
			itemManage.searchRows = $('li.item, .item.plugin, .item.tag, div.settings, .container.plugins, .item.group');
			itemManage.searchCache = itemManage.searchRows.map(function() {
				return $(this).text().toLowerCase();
			});
		}

		itemManage.searchCache.each(function(i) {
			if ( this.search( search ) == -1 ) {
				$(itemManage.searchRows[i]).addClass('hidden');
			} else {
				$(itemManage.searchRows[i]).removeClass('hidden');
			}
		});

		if ($('div.settings').length !== 0 || $('.container.plugins:visible').length > 1) {
			$('select[name=navigationdropdown]').val('all');
		}

		/*
		if ($('li.item').length != 0) {
			itemManage.changeItem();
		}*/
	},
	changeItem: function() {
		var selected = {};

		if (itemManage.selected.length !== 0) {
			selected = itemManage.selected;
		}

		$('.item:not(.ignore) .checkbox input[type=checkbox]:checked').each(function() {
			check = $(this);
			id = check.attr('id');
			id = id.replace(/.*\[(.*)\]/, "$1" );
			selected['p' + id] = 1;
			check.parent().parent().filter('.item').addClass('selected');
			check.parent().parent().parent().filter('.item').addClass('selected');
			
		});
		$('.item:not(.ignore) .checkbox input[type=checkbox]:not(:checked)').each(function() {
			check = $(this);
			id = check.attr('id');
			id = id.replace(/.*\[(.*)\]/, "$1" );
			selected['p' + id] = 0;
			check.parent().parent().filter('.item').removeClass('selected');
			check.parent().parent().parent().filter('.item').removeClass('selected');
			
		});

		itemManage.selected = selected;

		visible = $('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]:checked').length;

		total = $('.currentposition .total').text();

		count = 0;
		for (var id in itemManage.selected)	{
			if (itemManage.selected[id] == 1) {
				count = count + 1;
			}
		}

		if (count === 0) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls label.selectedtext').addClass('none').removeClass('all').text(_t('None selected'));
		} else if (visible == $('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]').length) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 1;
			});
			$('.item.controls label.selectedtext').removeClass('none').addClass('all').html(_t('All %1$s visible selected (<a href="#all" class="everything">Select all %2$s</a>)', count, total));

			$('.item.controls label.selectedtext .everything').click(function() {
				itemManage.checkEverything();
				return false;
			});

			if (visible != count) {
				$('.item.controls label.selectedtext').text('All visible selected (' + count + ' total)');
			}

			if ((total == count) || $('.currentposition .total').length === 0) {
				$('.item.controls label.selectedtext').removeClass('none').addClass('all').addClass('total').html(_t('All %s selected', total));
			}
		} else {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls label.selectedtext').removeClass('none').removeClass('all').text(_t('%s selected', count));

			if (visible != count) {
				$('.item.controls label.selectedtext').text(_t('%1$s selected (%2$s visible)', count, visible));
			}
		}
	},
	checkEverything: function() {
		itemManage.fetch(0, $('.currentposition .total').text(), false, true);
	},
	uncheckAll: function() {
		$('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]').each(function() {
			this.checked = 0;
		});
		itemManage.selected = [];
		itemManage.changeItem();
	},
	checkAll: function() {
		$('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]').each(function() {
			this.checked = 1;
		});
		itemManage.changeItem();
	},
	purge: function () {
		itemManage.update( 'purge' );
	},
	update: function( action, id ) {
		spinner.start();
		var query = {};
		if ( id === undefined ) {
			query = itemManage.selected;
		}
		else {
			query['p' + id]= 1;
		}

		query.action = action;
		query.timestamp = $('input#timestamp').attr('value');
		query.nonce = $('input#nonce').attr('value');
		query.digest = $('input#password_digest').attr('value');
		if ( $('.manage.users').length !== 0 ) {
			query.reassign = $('select#reassign').attr('value');
		}

		elItem = $('#item-' + id);

		if (elItem.length > 0 || action == 'delete') {
			elItem.fadeOut();
		}

		habari_ajax.post(
			itemManage.updateURL,
			query,
			function( result ) {
				if ( $('.timeline').length ) {
					/* TODO: calculate new offset and limit based on filtering
					 * and the current action
					 */
					loupeInfo = timeline.getLoupeInfo();
					itemManage.fetch( 0, loupeInfo.limit, true );
					timeline.updateLoupeInfo();
				}
				else {
					itemManage.fetch( 0, 20, false );
				}

				// if we deleted stuff, scroll to the top of the new page
				if ( action == 'delete' ) {
					window.scroll(0,0);
				}

				itemManage.selected = [];
			}
		);
	},
	rename: null,
	remove: function( id ) {
		itemManage.update( 'delete', id );
	},
	fetch: function( offset, limit, resetTimeline, silent ) {
		offset = ( offset === undefined ) ? 0 : offset;
		limit = ( limit === undefined ) ? 20: limit;
		silent = ( silent === undefined ) ? false: silent;
		spinner.start();

		habari_ajax.get(
			itemManage.fetchURL,
			'&search=' + liveSearch.getSearchText() + '&offset=' + offset + '&limit=' + limit,
			function(json) {
				if (silent) {
					itemManage.selected = json.item_ids;
					itemManage.initItems();
				} else {
					itemManage.fetchReplace.html(json.items);
					// if we have a timeline, replace its content
					if ( resetTimeline && $('.timeline').length !== 0 ) {
						// we hide and show the timeline to fix a firefox display bug
						$('.years').html(json.timeline).hide();
						itemManage.initItems();
						setTimeout( function() {
							$('.years').show();
							timeline.reset();
						}, 100 );
						$('input.checkbox').rangeSelect();
					}
					else {
						itemManage.initItems();
						$('input.checkbox').rangeSelect();
					}
					findChildren();
				}
			}
		);
	}
};

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

// Plugin Management
var pluginManage = {
	init: function() {
		// Return if we're not on the plugins page
		if (!$('.page-plugins').length) {return;}

		$('.plugins .item').hover( function() {
			$(this).find('#pluginconfigure:visible').parent().css('background', '#FAFAFA');
		}, function() {
			$(this).find('#pluginconfigure:visible').parent().css('background', '');
	  	});
	
		helpToggler.init();
	}
};

// Theme Management
var themeManage = {
	area_drop_options: {
		placeholder: 'block_drop',
		forcePlaceholderSize: true,
		connectWith: '.area_drop',
		containment: $('#block_add').parents('.item'),
		axis: 'y'
	},
	init: function() {
		// Return if we're not on the themes page
		if (!$('.page-themes').length) {return;}
		
		helpToggler.init();

		// Adding available blocks
		$('#block_instance_add').live('click', function() {
			themeManage.add_block($('#block_instance_title').val(), $('#block_instance_type').val());
		});

		// Add a block to an area
		$('.area_available').live('click', function() {
			// Clone the block, it has the right name and id
			var block = $(this).closest('.block_drag').clone();
			// Change the clone to have the controls we need
			block.find('.instance_controls,small').remove();
			block.append('<div class="close">&nbsp;</div><div class="handle">&nbsp;</div>');
			// Add the block to the target area
			var target = $('#'+($(this).attr('class').match(/target_([\w-]+)/)[1]));
			target.append(block);
			themeManage.refresh_areas();
			return false;
		});

		// Remove a block from an area
		$('.close').live('click', function() {
			$(this).parent().remove();
			themeManage.refresh_areas();
		});

		// Calculate a hash of the initial state so we can tell if save is required
		themeManage.initial_data_hash = themeManage.data_hash();

		// Sort blocks in areas
		// @todo Move the options to a property, so they're not repeated in save_areas.
		$('.area_drop').sortable({
			items: '.block_drag',
			placeholder: 'block_drop',
			forcePlaceholderSize: true,
			connectWith: '.area_drop',
			containment: $('#block_add').parents('.item'),
			update: themeManage.refresh_areas,
			remove: themeManage.refresh_areas,
			axis: 'y'
		});
		themeManage.refresh_areas();

		// Save areas
		$('#save_areas').click(function() {
			themeManage.save_areas();
		});

		// Warn user about unsaved changes
		window.onbeforeunload = function() {
			if (themeManage.changed()) {
				spinner.start(); spinner.stop();
				return _t('You did not save the changes you made. \nLeaving this page will result in the lose of data.');
			}
		};
	},
	refresh_areas: function() {
		$('.area_drop').sortable('refresh');
		$('.area_drop').each(function() {
			var area = $(this);
			if (area.find('.block_drag').length == 0) {
				area.find('.no_blocks').show();
			} else {
				area.find('.no_blocks').hide();
			}
		});
		if (themeManage.changed()) {
			$('#save_areas').removeAttr('disabled');
		} else {
			$('#save_areas').attr('disabled', 'disabled');
		}
	},
	add_block: function (title, type) {
		spinner.start();
		$('#block_add').load(
			habari.url.ajaxAddBlock,
			{title:title, type:type}
		);
		spinner.stop();
	},
	delete_block: function (id) {
		spinner.start();
		$('#block_add').load(
			habari.url.ajaxDeleteBlock,
			{block_id:id}
		);
		spinner.stop();
	},
	save_areas: function() {
		spinner.start();
		var output = {};
		$('.area_drop_outer').each(function() {
			var area = $('h2', this).data('areaname');
			output[area] = [];
			$('.block_drag', this).each(function(){
				m = $(this).attr('class').match(/block_instance_(\d+)/)
				output[area].push(m[1]);
			});
		});
		habari_ajax.post(
			habari.url.ajaxSaveAreas,
			{area_blocks:output, scope:$('#scope_id').val()},
			{'block_areas': '#scope_container'},
			// Can't simply refresh the sortable because we've reloaded the element
			function(data) {
				$('.area_drop').sortable({
					placeholder: 'block_drop',
					forcePlaceholderSize: true,
					connectWith: '.area_drop',
					containment: $('#block_add').parents('.item'),
					update: themeManage.refresh_areas,
					remove: themeManage.refresh_areas,
					axis: 'y'
				});
				// We've saved, reset the hash
				themeManage.initial_data_hash = themeManage.data_hash();
				themeManage.refresh_areas();
			}
		);
	},
	change_scope: function() {
		spinner.start();
		var output = {};
		habari_ajax.post(
			habari.url.ajaxSaveAreas,
			{scope:$('#scope_id').val()},
			{'block_areas': '#scope_container'},
			// Can't simply refresh the sortable because we've reloaded the element
			function(data) {
				$('.area_drop').sortable({
					placeholder: 'block_drop',
					forcePlaceholderSize: true,
					connectWith: '.area_drop',
					containment: $('#block_add').parents('.item'),
					update: themeManage.refresh_areas,
					remove: themeManage.refresh_areas,
					axis: 'y'
				});
				// We've saved, reset the hash
				themeManage.initial_data_hash = themeManage.data_hash();
				themeManage.refresh_areas();
			}
		);
	},
	changed: function() {
		return themeManage.initial_data_hash != themeManage.data_hash();
	},
	data_hash: function() {
		var output = '';
		$('.area_drop_outer').each(function() {
			var area = $('h2', this).text();
			output += area;
			$('.block_drag', this).each(function(){
				m = $(this).attr('class').match(/block_instance_(\d+)/)
				output += m[1];
			});
		});
		return crc32(output);
	}

};

// Group Management
var groupManage = {
	init: function(users) {
		this.users = users;

		for(var z in this.users) {
			if (users.hasOwnProperty(z)) {
				$('#assign_user').append($('<option value="' + this.users[z].id + '">' + this.users[z].username + '</option>'));
				if (this.users[z].member) {
					this.addMember(this.users[z].id);
				}
			}
		}

		this.userscanAll();

		$('#add_user').click(function() {
			groupManage.addMember($('#assign_user').val());
		});

		// Apply permission deny/allow toggle rules
		$('.bool-permissions input[type=checkbox],.crud-permissions input[type=checkbox]').change(function(){
			if ($(this).attr('checked')) {
				if ($(this).hasClass('bitflag-deny')) {
					$('input[type=checkbox]', $(this).parents('tr')).filter(function(){return !$(this).hasClass('bitflag-deny');}).attr('checked', false);
				}
				else {
					$('input[type=checkbox].bitflag-deny', $(this).parents('tr')).attr('checked', false);
				}
			}
		});

	},
	removeMember: function(member, id) {
		name = this.users[id].username;

		if (this.users[id].member) {
			if ($('#user_' + id).val() === 0) {
				$('#user_' + id).val('1');
				$('#currentusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
			}
			else {
				$('#removedusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
				$('#user_' + id).val('0');
			}
		}
		else {
			$('#assign_user').append($('<option value="' + id + '">' + name + '</option>'));
			$('#add_users').show();
			$('#user_' + id).val('0');
		}

		$(member).remove();

		this.userscanAll();
		return false;
	},
	addMember: function(id) {
		$('#assign_user option[value=' + id + ']').remove();

		$('#user_' + id).val('1');

		if (this.users[id].member) {
			$('#currentusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
		}
		else {
			$('#newusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
		}
		this.userscanAll();
	},
	userscanAll: function() {
		this.userscan('#currentusers');
		this.userscan('#removedusers');
		this.userscan('#newusers');
		if ($('#add_users option').length > 0) {
			$('#add_users').show();
		}
		else {
			$('#add_users').hide();
		}
	},
	userscan: function(div) {
		if ($(div + ' .user').length > 0) {
			$(div).show();
		}
		else {
			$(div).hide();
		}
	}
};

// TIMELINE
var timeline = {
	init: function() {
		// No Timeline? No runny-runny.
		if (!$('.timeline').length) {return;}
		var self = this; // keep context in closures

		// Set up pointers to elements for speed
		timeline.view = $('.timeline');

		// Fix width of years, so they don't spill into the next year
		$('.year > span').each( function() {
			$(this).width( $(this).parents('.year').width() - 4 );
		});

		// Get an array of posts per month
		timeline.monthData = [0];
		timeline.monthWidths = [0];
		timeline.totalCount = 0;
		$('.years .months span').each(function(i) {
			timeline.monthData[i] = $(this).width();
			timeline.monthWidths[i] = $(this).parent().width() + 1; // 1px border
			timeline.totalCount += timeline.monthData[i];
		});

		// manually set the timelineWidth to contain its children for IE7
		var timelineWidth = 0;
		if ( $.browser.msie ) {
			jQuery(timeline.monthWidths).each(function() { timelineWidth += this; } );
			$('.years').width(timelineWidth);
		} else {
			timelineWidth = $('.years').width();
		}

		// check for a timeline larger than its view
		// timeline.overhang = ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;
		var viewWidth = $('.timeline').width();
		timeline.overhang = ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;

		// Find the width which makes the loupe select 20 items
		var handleWidth = timelineWidth - timeline.positionFromIndex( timeline.totalCount - 20 );

		// Make the slider bounded by the view
		var maxSliderValue = Math.min( viewWidth, timelineWidth ) - handleWidth;

		// Initialize the timeline handle
		timeline.handle = new timelineHandle( '.handle', handleWidth, maxSliderValue );

		$('.track')
			.width( $('.years').width() - timeline.overhang )
			.bind('dblclick', function(e) { // Double-clicking on either side of the handle moves the handle to the clicked position.
				// Dismiss clicks on handle
				if ($(e.target).add($(e.target).parents()).is('.handle')) {return false;}

				timeline.noJump = true;
				clearTimeout(timeline.t1);
				timeline.handle.value( e.pageX - $('.track').offset().left );
				timeline.change();
			})
			.bind('click', function(e) { // Clicking either side of the handle moves the handle its own length to that side.

				// Dismiss clicks on handle
				if ( $(e.target).add($(e.target).parents()).is('.handle') ) {return false;}

				// Click to left or right of handle?
				if ( e.pageX - $('.track').offset().left < timeline.handle.value() ) {
					timeline.t1 = setTimeout(timeline.skipLoupeLeft, 300);
				} else {
					timeline.t1 = setTimeout(timeline.skipLoupeRight, 300);
				}
			});

		timeline.updateLoupeInfo();
	},
	change: function() {
		var loupeInfo = timeline.getLoupeInfo();
		itemManage.fetch( loupeInfo.offset, loupeInfo.limit, false );
		timeline.updateLoupeInfo();
	},
	skipLoupeLeft: function(e) {
		timeline.updateView();
		timeline.handle.value( timeline.handle.value() - timeline.handle.width() )
		timeline.change();
	},
	skipLoupeRight: function(e) {
		timeline.updateView();
		timeline.handle.value( timeline.handle.value() + timeline.handle.width() );
		timeline.change();
	},
	updateView: function() {
		if ( ! timeline.overhang ) { return; }
		if ( timeline.handle.offset().left <= timeline.view.offset().left + 5) {
			// timeline needs to slide right if we are within 5px of edge
			$('.years').css( 'right', Math.max( parseInt($('.years').css('right'),10) - timeline.handle.width(), 0 - timeline.overhang ) );
		}
		else if ( timeline.handle.offset().left + timeline.handle.width() + 5 >= timeline.view.offset().left + timeline.view.width() ) {
			// slide the timeline to the left
			$('.years').css( 'right', Math.min( parseInt($('.years').css('right'),10) + timeline.handle.width(), 0 ) );
		}
	},
	indexFromPosition: function(pos) {
		var monthBoundary = 0;
		var monthIndex = 1;
		var month = 0;
		var i;

		// get the index of the first post in the month that the handle is over

		for ( i = 0; i < timeline.monthWidths.length && monthBoundary + timeline.monthWidths[i] < pos; i++ ) {
			monthBoundary += timeline.monthWidths[i];
			monthIndex += timeline.monthData[i];
			month = i + 1;
		}

		// the index is the offset from this boundary, but it cannot be greater than
		// the number of posts in the month (the month has some extra padding which
		// increases its width).
		var padding = parseInt( $('.years span').css('margin-left'),10);
		padding = padding ? padding : 0;
		return monthIndex + Math.min(
						Math.max( pos - ( monthBoundary + padding ), 0 ),
						timeline.monthData[month] - 1 );
	},
	/* the inverse of the above function */
	positionFromIndex: function(index) {
		var month = 0;
		var position = 0;
		var positionIndex = 1;

		if ( index < 1 ) {return 0;}

		for ( i = 0; i < timeline.monthWidths.length && positionIndex + timeline.monthData[i] < index; i++ ) {
			position+= timeline.monthWidths[i];
			positionIndex+= timeline.monthData[i];
			month = i + 1;
		}

		var padding = parseInt( $('.years .months span').css('margin-left'), 10 );
		padding = padding ? padding : 0;
		return position + padding + ( index - positionIndex );
	},
	getLoupeInfo: function() {
		var cur_overhang = $('.track').offset().left - $('.years').offset().left;
		var loupeStartPosition = timeline.indexFromPosition( timeline.handle.value() + cur_overhang);
		var loupeEndPosition = timeline.indexFromPosition( timeline.handle.value() + timeline.handle.width() + cur_overhang );

		var loupeInfo = {
			start: loupeStartPosition,
			end: loupeEndPosition,
			offset: parseInt(timeline.totalCount, 10) - parseInt(loupeEndPosition, 10),
			limit: 1 + parseInt(loupeEndPosition, 10) - parseInt(loupeStartPosition, 10)
			};
		return loupeInfo;
	},
	updateLoupeInfo: function() {
		var loupeInfo = timeline.getLoupeInfo();
		$('.currentposition').html( _t('%1$s-%2$s of <span class="total inline">%3$s</span>', loupeInfo.start, loupeInfo.end, timeline.totalCount));

		// Hide 'newer' and 'older' links as necessary
		if (loupeInfo.start == 1) {$('.navigator .older').animate({opacity: '0'}, 200);} 
		else {$('.navigator .older').animate({opacity: '1'}, 200);}
		if (loupeInfo.end == timeline.totalCount) {$('.navigator .newer').animate({opacity: '0'}, 200); }
		else {$('.navigator .newer').animate({opacity: '1'}, 200);}
	},
	reset: function () {
		// update the arrays of posts per month
		timeline.monthData = [0];
		timeline.monthWidths = [0];
		timeline.totalCount = 0;
		$('.years .months span').each( function(i) {
			timeline.monthData[i] = $(this).width();
			timeline.monthWidths[i] = $(this).parent().width() + 1; // 1px border
			timeline.totalCount += timeline.monthData[i];
		});

		// manually set the timelineWidth to contain its children for IE7
		var timelineWidth = 0;
		if ( $.browser.msie ) {
			jQuery(timeline.monthWidths).each(function() { timelineWidth += this; } );
			$('.years').width( timelineWidth );
		}
		else {
			timelineWidth = $('.years').width();
		}

		// check for a timeline larger than its view
		// timeline.overhang = ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;
		var viewWidth = $('.timeline').width();
		timeline.overhang = ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;

		// find the width which makes the loupe select 20 items
		var handleWidth = timelineWidth - timeline.positionFromIndex( timeline.totalCount - 20 );
		// make the slider bounded by the view
		timeline.handle.max = Math.min( viewWidth, timelineWidth ) - handleWidth;

		// reset the widths
		$('.track').width( $('.years').width() - timeline.overhang );
		$('.handle').width( handleWidth + 'px' );

		// Fix width of years, so they don't spill into the next year
		$('.year > span').each( function() {
			$(this).width( $(this).parents('.year').width() - 4 );
		});

		// move the handle to the max value
		timeline.handle.value( timeline.handle.max );
		timeline.updateLoupeInfo();
	}
};


// TIMELINE SLIDER
function timelineHandle( id, width, maxvalue ) {
	this.handle = $(id, timeline.view);
	this.max = maxvalue;
	this.value( maxvalue );

	this.handle.css( 'width', width + 'px');
	/* force 'right' property to 'auto' so we can check in doDragLeft if we have fixed the
	 * right side of the handle */
	this.handle.css( 'right', 'auto' );

	// this is required to keep context
	var self = this;
	
	this.handle.mousedown(function(e) {
		return self.mouseDown(e);
	});

	// Resize handles
	$('.resizehandleleft').mousedown(function(e) {
		return self.resize(e, 'left');
	});

	$('.resizehandleright').mousedown(function(e) {
		return self.resize(e, 'right');
	});
}

timelineHandle.prototype = {
	mouseDown: function(e) {
		this.initialpos = e.pageX;
		
		// keep context in closures
		var self = this;
		$(document)
			.bind( 'mousemove.timeline', function(e) {
				return self.mouseMove.call( self, e );
			})
			.bind( 'mouseup.timeline', function(e) {
				return self.mouseUp.call( self, e );
			});
		return false;
	},
	mouseMove: function(e) {
		var new_value = this.value() + (e.pageX - this.initialpos);
		new_value = ( new_value < 0 ) ? 0 : new_value;
		new_value = ( new_value > this.max ) ? this.max : new_value;
		if ( new_value != this.value() ) {
			this.initialpos = e.pageX;
			this.value( new_value );
		}
		timeline.updateView();
		return false;
	},
	mouseUp: function(e) {
		$(document).unbind('mousemove.timeline').unbind('mouseup.timeline');
		timeline.change();
		return false;
	},
	value: function(value) {
			if ( arguments.length ) {
				 value = ( value < 0 ) ? 0 : value;
				 value = ( value > this.max ) ? this.max : value;
				 this.val = parseInt( value, 10 );
				 this.handle.css( 'left', this.val + 'px' );
			}
			return this.val;
	},
	width: function() {
		return this.handle.width();
	},
	resize: function(e, direction) {
		this.initialSize = this.handle.width();
		this.firstMousePos = e.clientX;

		// setup functions to keep context
		var self = this;
		if ( direction == 'left' ) {
			this.dragDelegate = function(e) {
				return self.doDragLeft(e);
			};
		}
		else {
			this.dragDelegate = function(e) {
				return self.doDragRight(e);
			};
		}
		this.endDragDelegate = function(e) {
			return self.endDrag(e);
		};
		$(document).bind('mousemove.timeline', this.dragDelegate)
			.bind('mouseup.timeline', this.endDragDelegate);
		return false;	
	},
	doDragLeft: function(e) {
		var h = this.handle;
		var track = h.parents('.track');
		// fix the right side
		h.css({
			'left':	'auto',
			'right': track.width() - ( parseInt(h.css('left'), 10) + h.width() )
		});

		// Set Loupe Width. Min 20, Max 200, no spilling to the left
		h.css( 'width',
			Math.min(
				Math.max( this.initialSize - (e.clientX - this.firstMousePos), 20 ),
				Math.min( track.width() - parseInt(h.css('right'),10 ), 200 )
			)
		);

		return false;
	},
	doDragRight: function(e) {
		var h = this.handle;
		var track = h.parents('.track');
		// fix the left side
		h.css({
			'left': h.offset().left - track.offset().left,
			'right': 'auto'
		});

		// Set Loupe Width. Min 20, Max 200, no spilling to the right
		h.css( 'width',
			Math.min(
				Math.max( this.initialSize + (e.clientX - this.firstMousePos), 20),
				Math.min( track.width() - parseInt(h.css('left'), 10), 200 )
			)
		);

		return false;
	},
	endDrag: function(e) {
		// Reset to using 'left'.
		this.handle.css({
			'left':	this.handle.offset().left - $('.track').offset().left,
			'right': 'auto'
		});

		// update slider max value for the new handle width
		this.max = Math.min( $('.timeline').width(), $('.years').width() ) - this.handle.width();

		// update slider value
		this.value( parseInt( this.handle.css('left'), 10 ) );
		timeline.change();

		$(document).unbind('mousemove.timeline').unbind('mouseup.timeline');

		return false;
	},
	offset: function(){
		return this.handle.offset();
	}
};

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


// NAVIGATION DROPDOWNS
var navigationDropdown = {
	init: function() {
		if ($('.page-user').length === 0 && $('.page-options').length === 0) {
			return;
		}

		$('.container.settings, .optiongroup').each(function() {
			$('<option></option>').attr('value', $(this).attr('id')).text($('h2', this).text()).appendTo($('select[name=navigationdropdown]'));
		});
	},
	changePage: function(location) {
		if ( location === undefined ) {
			nextPage = $('select[name=navigationdropdown]').val();
		} else {
			nextPage = location.options[location.selectedIndex].value;
		}

		if (nextPage !== "" && nextPage != document.location.href) {
			document.location.href = nextPage;
		}
	},
	filter: function() {
		var selected = $('select[name=navigationdropdown]').val();

		if ( selected == 'all' ) {
			$('.settings, .container.plugins, .optiongroup').removeClass('hidden');
		}
		else {
			$('.settings:not(#' + selected + '), .container.plugins:not(#' + selected + '), .optiongroup:not(#' + selected + ')').addClass('hidden');
			$('.settings#' + selected + ', .container.plugins#' + selected + ', .optiongroup#' + selected ).removeClass('hidden');
		}
	}
};


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
			dropButton.t1 = setTimeout(dropButton.hideMenu, 500);
		});
	},

	showMenu: function(element) {
		// Close all open dropbuttons
		$('.dropbutton').removeClass('hovering');

		// Open this dropbutton
		$(dropButton.currentDropButton).addClass('hovering');
	},

	hideMenu: function(element) {
		// Fade out and close dropbutton
		$(dropButton.currentDropButton).removeClass('hovering');

		$('.carrot').removeClass('carrot');
	}
};



// THE MENU
var theMenu = {
	init: function() {
		// Carrot functionality
		$('#menulist li').hover(function() {
			$('#menulist li').removeClass('carrot');
			$(this).addClass('carrot');
		}, function() {
			$('#menulist li').removeClass('carrot');
		});

		// Open menu on Q
		$.hotkeys.add('q', {propagate:true, disableInInput: true}, function(){
			if ($('#menu #menulist > ul').css('display') != 'block') {
				dropButton.currentDropButton = $('#menu');
				dropButton.showMenu();
			} else if ($('#menu #menulist > ul').css('display') == 'block') {
				dropButton.hideMenu();
			} else {
				return false;
			}
		});

		// Close menu on ESC
		$.hotkeys.add('esc', {propagate:true, disableInInput: false}, function(){
			$('.carrot').removeClass('carrot');
			dropButton.hideMenu();
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
					if ($('#menu').hasClass('hovering') === true) {
						if (owner.hasClass('submenu')) {
							$('.carrot').removeClass('carrot');
							owner.addClass('carrot');
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
			if ($('#menu #menulist').css('display') == 'block') {
				dropButton.hideMenu();
			}
		});
	},
	blinkCarrot: function(owner) {
		var blinkSpeed = 100;
		$(owner).addClass('carrot').addClass('blinking').fadeOut(blinkSpeed).fadeIn(blinkSpeed).fadeOut(blinkSpeed).fadeIn(blinkSpeed, function() {
			dropButton.hideMenu();
		});
	}
};

// LIVESEARCH
var liveSearch = {
	init: function() {
		liveSearch.input = $('.search input');
		liveSearch.searchPrompt = liveSearch.input.attr('placeholder');
		liveSearch.prevSearch = liveSearch.getSearchText();

		liveSearch.input
			.focus( function() {
				if ( $.trim( liveSearch.input.val() ) == liveSearch.searchPrompt ) {
					liveSearch.input.val('');
				}
			})
			.blur( function () {
				if ( $.trim( liveSearch.input.val() ) === '' ) {
					liveSearch.input.val( liveSearch.searchPrompt );
				}
			})
			.keyup( function( event ) {
				var code = event.keyCode;

				if ( code == 27 ) { // ESC key
					liveSearch.input.val('');
					$('.special_search a').removeClass('active');
				}

				if ( code != 13 ) { // anything but enter
					if (liveSearch.timer) {
						clearTimeout(liveSearch.timer);
					}
					liveSearch.timer = setTimeout( liveSearch.doSearch, 500);
				}
			})
			.submit( liveSearch.doSearch );

	},
	searchprompt: '',
	timer: null,
	prevSearch: '',
	input: null,
	doSearch: function() {
		if ( liveSearch.getSearchText() == liveSearch.prevSearch ) {return;}

		spinner.start();

		liveSearch.prevSearch = liveSearch.getSearchText();
		itemManage.fetch( 0, 20, true );
	},
	getSearchText: function() {
		var search_txt = $.trim( liveSearch.input.val() );
		if ( search_txt == liveSearch.searchPrompt ) {
			return '';
		}
		return search_txt;
	}
};


// SEARCH CRITERIA TOGGLE
function toggleSearch() {
	var re = new RegExp('\\s*' + $(this).attr('href').substr(1), 'gi');
	if ($('#search').val().match(re)) {
		$('#search').val(liveSearch.getSearchText().replace(re, ''));
		$(this).removeClass('active');
	}
	else {
		$('#search').val(liveSearch.getSearchText() + ' ' + $(this).attr('href').substr(1));
		$(this).addClass('active');
	}
	liveSearch.doSearch();
	return false;
}


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
	// initialize the timeline after window load to make sure CSS has been applied to the DOM
	timeline.init();

	// Icons only for thin-width clients -- Must be run here to work properly in Safari
	if ($('#title').width() < ($('#mediatabs li').length * $('#mediatabs li').width())) {
		$('#mediatabs').addClass('iconify');
	}
});

$(document).ready(function(){
	// Initialize all sub-systems
	dropButton.init();
	theMenu.init();
	dashboard.init();
	itemManage.init();
	pluginManage.init();
	themeManage.init();
	liveSearch.init();
	findChildren();
	navigationDropdown.init();
	labeler.init();
	
	// fix autofilled passwords overlapping labels
	$(window).load(function(){
		window.setTimeout(function(){
			labeler.check($('label[for=habari_password]'));
		}, 200);
	});

	// Alternate the rows' styling.
	$("table").each( function() {
		$("tr:odd", this).not(".even").addClass("odd");
		$("tr:even", this).not(".odd").addClass("even");
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

	// SEARCH: Set default special search terms and assign click handler
	$('.special_search a')
		.click(toggleSearch)
		.each(function(){
			var re = new RegExp($(this).attr('href').substr(1));
			if ($('#search').val().match(re)) {
				$(this).addClass('active');
			}
		});

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

function resetTags() {
	var current = $('#tags').val();

	$('#tag-list li').each(function(){
		replstr = new RegExp('\\s*"?' + $( this ).text() + '"?\\s*', "gi");
		if (current.match(replstr)) {
			$(this).addClass('clicked');
		}
		else {
			$(this).removeClass('clicked');
		}
	});

	if (current.length === 0 && !$('#tags').hasClass('focus')) {
		$('label[for=tags]').addClass('overcontent').removeClass('abovecontent').show();
	}

}
