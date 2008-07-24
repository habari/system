// DASHBOARD
var dashboard = {
	init: function() {
		$('.modules').sortable({
			items: '.module:not(.add-item-module)',
			handle: 'div.handle',
			opacity: .9,
			stop: function() {
				dashboard.update();
			}
		});

		$('.options').toggle(function() {
				$(this).parents('li').addClass('viewingoptions')
			}, function() {
				$(this).parents('li').removeClass('viewingoptions')
			});

		$('.close', '.modules').click( function() { 
		    // grab the module ID from the parent DIV id attribute. 
		    matches = $(this).parents('.module').attr('id').split( ':', 2 ); 
		    dashboard.remove( matches[0] ); 
		}); 
	},
	update: function() {
		spinner.start();
		// disable dragging and dropping while we update
		$('.modules').sortable('disable');
		var query = {};
		$('.module', '.modules').not('.ui-sortable-helper').each( function(i) {
			query['module' + i] = this.getAttribute('id');
		} );	
		query['action'] = 'updateModules';
		$.post(
			habari.url.ajaxDashboard,
			query,
			function() {
		     	spinner.stop();
				$('.modules').sortable('enable');
			});
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
		query['action'] = 'addModule';
		query['module_name'] = $('#dash_additem option:selected').val();
		$.post(
			habari.url.ajaxDashboard,
			query,
			function( json ) {
		     	spinner.stop();
				$('.modules').html( json.modules );
				dashboard.init();
				//$('.modules').sortable('enable');
				humanMsg.displayMsg( json.message );
			},
			'json'
			);
	},
	remove: function( id ) {
		spinner.start();
		// disable dragging and dropping while we update
		$('.modules').sortable('disable');
		var query = {};
		query['action'] = 'removeModule';
		query['moduleid'] = id;
		$.post(
			habari.url.ajaxDashboard,
			query,
			function( json ) {
		     	spinner.stop();
				$('.modules').html( json.modules );
				dashboard.init();
				//$('.modules').sortable('enable');
				humanMsg.displayMsg( json.message );
			},
			'json'
			);
	}
}

// Inline edit
var inEdit = {
	init: function() {
		inEdit.editables= '.date a.edit-date, .title a.author.edit-author, .authorinfo a.edit-url, .authorinfo a.edit-email, .time span.edit-time, .content.edit-content';
		
		if($('#comments').length == 0) { // Only works for comments, presently
			return;
		}
		
		$(inEdit.editables, $('.item')).filter(':not(a)').each(function() {
			$(this).addClass('editable');
			$(this).click(function() {
				if(inEdit.activated != $(this).parents('.item').attr('id').substring(8)) {
					inEdit.deactivate();
					inEdit.activate($(this).parents('.item'));
				}
				return false;
			});
		})
	},
	activated: false,
	editables: null,
	getDestination: function( classes ) {
		classes= classes.split(" ");
		
		var clas= null;
		
		var key = 0;
		for (var key in classes) {
			clas= classes[key];
			if(clas.search('edit-') != -1) {
				destination= clas.substring(clas.search('edit-') + 5);
				return destination;
			}
		}
		
		return false;
				
	},
	activate: function( parent ) {		
		$(parent).hide().addClass('ignore');
		
		parent= $(parent).clone().addClass('clone').removeClass('ignore').show().insertAfter(parent);
		
		editables = $(inEdit.editables, parent);
				
		inEdit.activated= $(parent).attr('id').substring(8);
		var form = $('<form action="#"></form>').addClass('inEdit');
		parent.wrap(form);
		
		editables.each(function() {
			var classes= $(this).attr('class');
			destination= inEdit.getDestination(classes);
			var val= $(this).text();
			var width= $(this).width();
			
			$(this).hide();
			
			if($(this).hasClass('area')) {
				var field= $('<textarea></textarea>');
				field.height(100)
					.attr('class', classes)
					.removeClass('pct75')
					.width(width - 5);
			} else {
				var field= $('<input></input>');
				field.attr('class', classes)
					.width(width + 5);
			}
			field.addClass('editor').removeClass('editable')
				.val(val)
				.insertAfter($(this));
		});
		
		$('ul.dropbutton li:not(.cancel):not(.submit)', parent).remove();
		$('ul.dropbutton li.cancel, ul.dropbutton li.submit', parent).removeClass('nodisplay');
		$('ul.dropbutton li.submit', parent).addClass('first-child');
		$('ul.dropbutton li.cancel', parent).addClass('last-child');
		dropButton.init();
		$('ul.dropbutton a', parent).css('color', 'inherit');
		$('ul.dropbutton', parent).animate({
			backgroundColor: '#FFFFCC',
			color: '#333333'
		}, 100, 'linear', function() {
			$('ul.dropbutton', parent).animate({
				backgroundColor: '#333333',
				color: 'white'
			},10000);
		});
				
		var submit= $('<input type="submit"></input>')
			.addClass('inEditSubmit')
			.val('Update')
			.hide()
			.appendTo(parent);
		
		$("form").submit(function() {
			inEdit.update();
			return false;
		});
		
		itemManage.initItems();
		itemManage.changeItem();
		
	},
	update: function() {
		spinner.start();
		
		query= {};
		
		$('.editor').each(function() {
			query[inEdit.getDestination($(this).attr('class'))]= $(this).val();
		});
		
		query['id']= inEdit.activated;
		query['timestamp']= $('input#timestamp').attr('value');
		query['nonce']= $('input#nonce').attr('value');
		query['digest']= $('input#PasswordDigest').attr('value');
		
		$.ajax({
			type: 'POST',
				url: habari.url.ajaxInEdit,
				data: query,
				dataType: 'json',
				success: function( result ){
					spinner.stop();
					jQuery.each( result, function( index, value) {
						humanMsg.displayMsg( value );
					} );
					inEdit.deactivate();

					loupeInfo = timelineHandle.getLoupeInfo();
					itemManage.fetch( loupeInfo.offset, loupeInfo.limit, false );
				}
		});
		
	},
	deactivate: function() {
		inEdit.activated = false;
		
		$('.item').show().removeClass('ignore');
		$('form.inEdit').remove();
		
		itemManage.changeItem();
		
	}
}

// Item Management
var itemManage = {
	init: function() {
		if($('.manage.users, .page-options, .page-user').length != 0) {
			$("input#search").keyup(function (e) {
				var str= $('input#search').val();
				itemManage.simpleFilter(str);
			});
		}
		
		if(!$('.item.controls input[type=checkbox]')) return;
		
		itemManage.initItems();

		$('.item.controls input[type=checkbox]').change(function () {
			if($('.item.controls span.selectedtext').hasClass('all')) {
				itemManage.uncheckAll();
			} else {
				itemManage.checkAll();
			}
		});
		
		/* for all manage pages except for comments, add an ajax call to the
		 * delete button
		 */
		if( $('.manage.comments').length == 0 ) {
			$('.item.controls input.button.delete').click(function () {
				itemManage.update( 'delete' );
				return false;
			});
		}
	},
	initItems: function() {
		$('.item:not(.ignore) .checkbox input[type=checkbox]').change(function () {
			itemManage.changeItem();
		});
		$('.item:not(.ignore) .checkbox input[type=checkbox]').each(function() {
			id = $(this).attr('id');
			id = id.replace(/.*\[(.*)\]/, "$1" ); // checkbox ids have the form name[id]
			if(itemManage.selected['p' + id] == 1) {
				this.checked = 1;
			}
		});
		itemManage.changeItem();
	},
	selected: [],
	simpleFilter: function( search ) {
		search= search.toLowerCase();
		
		var count= 0;
		$('li.item, a.tag, div.settings div.item').each(function() {
			var labels= $('.user, span', this);
			var text= $(this).text().toLowerCase();
			if((text.search( search ) == -1) && (text != search)) {
				$(this).hide();
				$(this).addClass('hidden');
			} else {
				count= count + 1;
				$(this).show();
				$(this).removeClass('hidden');
			}
		});
		
		if($('div.settings').length != 0) {
			$('select[name=navigationdropdown]').val('all');
			
			$('div.settings.container:not(.addnewuser)').each(function() { 
				if(($('div.item:not(.hidden)', this).length == 0) && ($('h2', this).text().toLowerCase().search( search ) == -1)) {
					$(this).hide().addClass('hidden');
				} else {
					$(this).show().removeClass('hidden');
				}
				
				if($('h2', this).text().toLowerCase().search( search ) != -1) {
					$('div.item', this).each(function() {
						$(this).show().removeClass('hidden');
					});
				}
			});
		}
		
		if($('li.item').length != 0) {
			itemManage.changeItem();
		}
	},
	changeItem: function() {
		var selected = {};

		if(itemManage.selected.length != 0) {
			selected = itemManage.selected;
		}

		$('.item:not(.ignore) .checkbox input[type=checkbox]:checked').each(function() {
			id = $(this).attr('id');
			id = id.replace(/.*\[(.*)\]/, "$1" );
			selected['p' + id] = 1;
		});
		$('.item:not(.ignore) .checkbox input[type=checkbox]:not(:checked)').each(function() {
			id = $(this).attr('id');
			id = id.replace(/.*\[(.*)\]/, "$1" );
			selected['p' + id] = 0;
		});

		itemManage.selected = selected;

		visible = $('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]:checked').length;
		
		count = 0;
		for (var id in itemManage.selected)	{
			if(itemManage.selected[id] == 1) {
				count = count + 1;
			}
		}

		if(count == 0) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls span.selectedtext').addClass('none').removeClass('all').text('None selected');
		} else if(visible == $('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]').length) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 1;
			});
			$('.item.controls span.selectedtext').removeClass('none').addClass('all').text('All selected');
			if(visible != count) {
				$('.item.controls span.selectedtext').text('All visible selected (' + count + ' total)');
			}
		} else {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls span.selectedtext').removeClass('none').removeClass('all').text(count + ' selected');
			if(visible != count) {
				$('.item.controls span.selectedtext').text(count + ' selected (' + visible + ' visible)');
			}
		}
	},
	uncheckAll: function() {
		$('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]').each(function() {
			this.checked = 0;
		});
		itemManage.changeItem();
	},
	checkAll: function() {
		$('.item:not(.hidden):not(.ignore) .checkbox input[type=checkbox]').each(function() {
			this.checked = 1;
		});
		itemManage.changeItem();
	},
	update: function( action, id ) {
		spinner.start();
		var query= {};
		if ( id == null ) {
			query = itemManage.selected;
		}
		else {
			query['p' + id]= 1;
		}

		query['action'] = action;
		query['timestamp']= $('input#timestamp').attr('value');
		query['nonce']= $('input#nonce').attr('value');
		query['digest']= $('input#PasswordDigest').attr('value');
		if ( $('.manage.users').length != 0 ) {
			query['reassign'] = $('select#reassign').attr('value');
		}

		$.post(
			itemManage.updateURL,
			query,
			function( result ) {
				spinner.stop();
				jQuery.each( result, function( index, value ) {
					humanMsg.displayMsg( value );
				});
				if ( $('.timeline').length ) {
					/* TODO: calculate new offset and limit based on filtering
					 * and the current action
					 */
					loupeInfo = timelineHandle.getLoupeInfo();
					itemManage.fetch( 0, loupeInfo.limit, true );
					timelineHandle.updateLoupeInfo();
				}
				else {
					itemManage.fetch( 0, 20, false );
				}
				
				itemManage.selected = [];
			},
			'json'
			);
	},
	remove: function( id ) {
		itemManage.update( 'delete', id );
	},
	fetch: function( offset, limit, resetTimeline ) {
		offset = ( offset == null ) ? 0 : offset;
		limit = ( limit == null ) ? 20: limit;
		spinner.start();

		$.ajax({
			type: 'POST',
			url: itemManage.fetchURL,
			data: '&search=' + liveSearch.input.val() + '&offset=' + offset + '&limit=' + limit,
			dataType: 'json',
			success: function(json) {
				itemManage.fetchReplace.html(json.items);
				// if we have a timeline, replace its content
				if ( resetTimeline && $('.timeline').length !=0 ) {
					// we hide and show the timeline to fix a firefox display bug
					$('.years').html(json.timeline).hide();
					spinner.stop();
					itemManage.initItems();
					$('.years').show();
					timeline.reset();
				}
				else {
					spinner.stop();
					itemManage.initItems();
				}
				if ( itemManage.inEdit == true ) {
					inEdit.init();
					inEdit.deactivate();
				}
				findChildren();
			}
		});
	}
}

// Tag Management
var tagManage = {
	init: function() {
		// Return if we're not on the tags page
		if(!$('.page-tags').length) return;

		$('.tags .tag').click(function() {
				$(this).toggleClass('selected');
				tagManage.changeTag();
				return false;
			}
		);

		$('.tags.controls input.delete.button').click(function () {
			tagManage.remove();
		});
		$('.tags.controls input.rename.button').click(function () {
			tagManage.rename();
		});

		$("input#search").keyup(function (e) {
			var str= $('input#search').val();
			itemManage.simpleFilter(str);
			tagManage.changeTag();
		});
	},
	changeTag: function() {
		count = $('.tags .tag.selected').length;

		visible = $('.tags .tag.selected:not(.hidden)').length;

		if(count == 0) {
			$('.tags.controls span.selectedtext').addClass('none').removeClass('all').text('None selected');
		} else if(visible == $('.tags .tag:not(.hidden)').length) {
			$('.tags.controls span.selectedtext').removeClass('none').addClass('all').text('All selected');
			if(visible != count) {
				$('.tags.controls span.selectedtext').text('All visible selected (' + count + ' total)');
			}
		} else {
			$('.tags.controls span.selectedtext').removeClass('none').removeClass('all').text(count + ' selected');
			if(visible != count) {
				$('.tags.controls span.selectedtext').text(count + ' selected (' + visible + ' visible)');
			}
		}
	}
}

// Plugin Management
var pluginManage = {
	init: function() {
		// Return if we're not on the tags page
		if(!$('.page-plugins').length) return;

		$('.plugins .item').hover( function() {
			$(this).find('#pluginconfigure:visible').parent().css('background', '#FAFAFA');
			}, function() {
			$(this).find('#pluginconfigure:visible').parent().css('background', '');
      }
		);
	}
}

// TIMELINE
var timeline = {
	init: function() {
		// No Timeline? No runny-runny.
		if (!$('.timeline').length) return;

		// Set up pointers to elements for speed
		timeline.view= $('.timeline');
		timeline.handle= $('.handle', timeline.view);

		// Get an array of posts per month
		timeline.monthData= [0];
		timeline.monthWidths= [0];
		timeline.totalCount= 0;
		$('.years .months span').each(function(i) {
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
		timeline.overhang= ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;
		var viewWidth= $('.timeline').width();
		timeline.overhang= ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;

		// Find the width which makes the loupe select 20 items
		var handleWidth= timelineWidth - timeline.positionFromIndex( timeline.totalCount - 20 );

		// Make the slider bounded by the view
		var maxSliderValue= Math.min( viewWidth, timelineWidth ) - handleWidth;

		/* Initialize the timeline handle. We need to do this before we create the slider because
		 * at the end of the slider initializer, it calls slider('moveTo', startValue) which will
		 * trigger the 'stop' event. We also don't need to do a search on initial page load, so
		 * set do_search to false until after slider initialization */
		timelineHandle.init( handleWidth );
		timeline.do_search= false;

		$('.track')
		.width( $('.years').width() - timeline.overhang )
		.slider({
			handle: '.handle',
			max: Math.max( 1, maxSliderValue ),
			startValue: maxSliderValue,
			axis: 'horizontal',
			stop: function(event, ui) {
				timeline.updateView();
				if ( timeline.do_search ) {
					var loupeInfo = timelineHandle.getLoupeInfo();
					itemManage.fetch( loupeInfo.offset, loupeInfo.limit, false );
				}
				timelineHandle.updateLoupeInfo();
			},
			slide: function( event, ui) {
				timeline.updateView();
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
			if (e.layerX < $('.track').slider('value') )
				timeline.t1 = setTimeout('timeline.skipLoupeLeft()', 300);
			else
				timeline.t1 = setTimeout('timeline.skipLoupeRight()', 300);
		})
		.slider( 'moveTo', timelineWidth - handleWidth ); // a bug in the jQuery code requires us to explicitly do this in the case that startValue == 0

		// Fix width of years, so they don't spill into the next year
		$('.year > span').each( function() {
			$(this).width( $(this).parents('.year').width() - 4 )
		})

		// update the do_search state variable
		timeline.do_search= true;
	},
	skipLoupeLeft: function(e) {
		if (timeline.noJump == true) {
			timeline.noJump = null;
			return false;
		}

		$('.handle').css( 'left', Math.max(parseInt($('.handle').css('left')) - $('.handle').width(), 0) );
		timeline.updateView();
		var loupeInfo = timelineHandle.getLoupeInfo();
		itemManage.fetch( loupeInfo.offset, loupeInfo.limit, false );
		timelineHandle.updateLoupeInfo();

	},
	skipLoupeRight: function(e) {
		if (timeline.noJump == true) {
			timeline.noJump = null;
			return false;
		}

		$('.handle').css( 'left', Math.min(parseInt($('.handle').css('left')) + $('.handle').width(), parseInt($('.track').width()) - $('.handle').width() ));
		timeline.updateView();
		var loupeInfo = timelineHandle.getLoupeInfo();
		itemManage.fetch( loupeInfo.offset, loupeInfo.limit, false );
		timelineHandle.updateLoupeInfo();
	},
	updateView: function() {
		if ( ! timeline.overhang )
			return;
		if ( timeline.handle.offset().left <= timeline.view.offset().left + 5) {
			// timeline needs to slide right if we are within 5px of edge
			$('.years').css( 'right', Math.max( parseInt($('.years').css('right')) - timeline.handle.width(), 0 - timeline.overhang ) );
			/*$('.years').stop().animate( {
				right: Math.max( parseInt($('.years').css('right')) - 2*timeline.handle.width(), 0 - timeline.overhang )
				}, function() { timeline.sliding= false; } );*/
		}
		else if ( timeline.handle.offset().left + timeline.handle.width() + 5 >= timeline.view.offset().left + timeline.view.width() ) {
			// slide the timeline to the left
			$('.years').css( 'right', Math.min( parseInt($('.years').css('right')) + timeline.handle.width(), 0 ) );
			/*$('.years').stop().animate( {
				right: Math.min( parseInt($('.years').css('right')) + 2*timeline.handle.width(), 0 )
				}, function() { timeline.sliding= false; } );*/
		}
	},
	indexFromPosition: function(pos) {
		var monthBoundary= 0;
		var monthIndex= 1;
		var month= 0;
		var i;

		// get the index of the first post in the month that the handle is over

		for ( i = 0; i < timeline.monthWidths.length && monthBoundary + timeline.monthWidths[i] < pos; i++ ) {
			monthBoundary += timeline.monthWidths[i];
			monthIndex += timeline.monthData[i];
			month= i + 1;
		}

		// the index is the offset from this boundary, but it cannot be greater than
		// the number of posts in the month (the month has some extra padding which
		// increases its width).
		var padding= parseInt( $('.years span').css('margin-left') );
		padding= padding ? padding : 0;
		return monthIndex + Math.min(
						Math.max( pos - ( monthBoundary + padding ), 0 ),
						timeline.monthData[month] - 1 );
	},
	/* the reverse of the above function */
	positionFromIndex: function(index) {
		var month= 0;
		var position= 0;
		var positionIndex= 1;

		if ( index < 1 ) return 0;

		for ( i = 0; i < timeline.monthWidths.length && positionIndex + timeline.monthData[i] < index; i++ ) {
			position+= timeline.monthWidths[i];
			positionIndex+= timeline.monthData[i];
			month= i + 1;
		}

		var padding= parseInt( $('.years .months span').css('margin-left') );
		padding= padding ? padding : 0;
		return position + padding + ( index - positionIndex );
	},
	reset: function () {
		// update the arrays of posts per month
		timeline.monthData= [0];
		timeline.monthWidths= [0];
		timeline.totalCount= 0;
		$('.years .months span').each(function(i) {
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
		timeline.overhang= ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0;
		var viewWidth= $('.timeline').width();
		timeline.overhang= ( timelineWidth > viewWidth ) ? timelineWidth - viewWidth : 0

		// find the width which makes the loupe select 20 items
		var handleWidth= timelineWidth - timeline.positionFromIndex( timeline.totalCount - 20 );
		// make the slider bounded by the view
		var maxSliderValue= Math.min( viewWidth, timelineWidth ) - handleWidth;

		// reset the widths
		$('.track').width( $('.years').width() - timeline.overhang );
		$('.handle').width( handleWidth + 'px' );

		// reset the slider maxValue
		$('.track').slider( 'setData', 'max', Math.max( 1, maxSliderValue ) );

		// move the handle without triggering a search
		timeline.do_search= false;
		$('.track').slider( 'moveTo', maxSliderValue );
		timeline.do_search= true;
	}
}


// TIMELINE HANDLE
var timelineHandle = {
	init: function( handleWidth ) {
		timeline.handle.css('width', handleWidth + 'px');

		/* force 'right' property to 'auto' so we can check in doDragLeft if we have fixed the 
		 * right side of the handle */
		timeline.handle.css('right', 'auto');
		// Resize Handle Left
		$('.resizehandleleft')
			.mousedown(function(e) {
				timelineHandle.firstMousePos = timeline.handle.offset().left - $('.track').offset().left;
				timelineHandle.initialSize = timeline.handle.width();

				$(document).mousemove(timelineHandle.doDragLeft).mouseup(timelineHandle.endDrag);
				return false;
			})
			.mouseup(timelineHandle.endDrag);

		$('.resizehandleright')
			.mousedown(function(e) {
				timelineHandle.firstMousePos = e.clientX;
				timelineHandle.initialSize = timeline.handle.width();

				$(document).mousemove(timelineHandle.doDragRight).mouseup(timelineHandle.endDrag);
				return false;
			})
			.mouseup(timelineHandle.endDrag);
	},
	doDragLeft: function(e) {
		var h = timeline.handle;
		var track = h.parents('.track');
		// fix the right side (only do this if we haven't already done it)
		if ( h.css('right') == 'auto' ) {
			h.css({
				'left':	'auto',
				'right': track.width() - ( parseInt(h.css('left')) + h.width() )
			});
		}

		// Set Loupe Width. Min 20, Max 200, no spilling to the left
		h.css('width', Math.min(Math.max(timelineHandle.initialSize + (timelineHandle.firstMousePos - (e.clientX - track.offset().left)), 20), Math.min(track.width() - parseInt(h.css('right')), 200)));

		return false;
	},
	doDragRight: function(e) {
		var h = timeline.handle;
		var track = h.parents('.track');
		// fix the left side
		h.css({
			'left': h.offset().left - track.offset().left,
			'right': 'auto'
		});

		// Set Loupe Width. Min 20, Max 200, no spilling to the right
		h.css( 'width', Math.min(Math.max(timelineHandle.initialSize + (e.clientX - timelineHandle.firstMousePos), 20), Math.min(track.width() - parseInt(h.css('left')), 200)) );

		return false;
	},
	getLoupeInfo: function() {
		var cur_overhang= $('.track').offset().left - $('.years').offset().left;
		var loupeStartPosition = timeline.indexFromPosition( parseInt($('.handle').css('left')) + cur_overhang);
		var loupeWidth= $('.handle').width();
		var loupeEndPosition= timeline.indexFromPosition( parseInt($('.handle').css('left')) + loupeWidth + cur_overhang );
		
		var loupeInfo = {
			start: loupeStartPosition,
			end: loupeEndPosition,
			offset: parseInt(timeline.totalCount) - parseInt(loupeEndPosition),
			limit: 1 + parseInt(loupeEndPosition) - parseInt(loupeStartPosition)
			};
		return loupeInfo;
	},
	updateLoupeInfo: function() {
		var loupeInfo = timelineHandle.getLoupeInfo();

		$('.currentposition').text( loupeInfo.start +'-'+ loupeInfo.end +' of '+ timeline.totalCount );
	},
	endDrag: function(e) {
		timeline.noJump = true;

		// Reset to using 'left'.
		$('.handle').css({
			'left': 	$('.handle').offset().left - $('.track').offset().left,
			'right': 	'auto'
		});

		var loupeInfo = timelineHandle.getLoupeInfo();
		itemManage.fetch( loupeInfo.offset, loupeInfo.limit, false );
		timelineHandle.updateLoupeInfo();

		$(document).unbind('mousemove', timelineHandle.doDrag).unbind('mouseup', timelineHandle.endDrag);

		return false;
	}
}



/* TIMELINE TODO:
	- Consider step'd resize
		- Problem: timeline length might not be suitable for steps, so at either end, the loupe might not fit.
	- Draggable timeline
	- Float month name if outside view
	- Reinit slider (or do away with slider alltogether and gather the info manually)
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


// NAVIGATION DROPDOWNS
var navigationDropdown = {
	init: function() {
		if($('.page-user').length == 0) {
			return;
		}
		
		$('.container.settings').each(function() {
			$('<option></option>').attr('value', $(this).attr('id')).text($('h2', this).text()).appendTo($('select[name=navigationdropdown]'));
		});
	},
	changePage: function(location) {
		nextPage = location.options[location.selectedIndex].value

		if (nextPage != "")
			document.location.href = nextPage
	},
	filter: function() {
		var selected = $('select[name=navigationdropdown]').val();
		
		if ( selected == 'all' ) {
			$('.settings').show();
		}
		else {
			$('.settings:not(#' + selected + ')').addClass('hidden').hide();
			$('.settings#' + selected).removeClass('hidden').show();
		}
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
			$('#menulist li').removeClass('carrot')
			$(this).addClass('carrot')
		}, function() {
			$('#menulist li').removeClass('carrot')
		})

		// Open menu on Q
		$.hotkeys.add('q', {propagate:true, disableInInput: true}, function(){
			if ($('#menu #menulist').css('display') != 'block') {
				dropButton.currentDropButton = $('#menu');
				dropButton.showMenu();
			} else if ($('#menu #menulist').css('display') == 'block') {
				dropButton.hideMenu();
			} else {
				return false;
			}
		});

		// Close menu on ESC
		$.hotkeys.add('esc', {propagate:true, disableInInput: false}, function(){
			$('.carrot').removeClass('carrot')
			dropButton.hideMenu();
		});

		// Down arrow
		$.hotkeys.add('down', {propagate:false, disableInInput: true}, function() {
			if(($('#menu').hasClass('hovering') == true)) {
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
			} else {
				return false;
			}
			return false;
		});

		// Up arrow
		$.hotkeys.add('up', {propagate:true, disableInInput: true}, function(){
			if ($('#menu').hasClass('hovering') == true) {
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
			} else {
				return false;
			}
		});

		// Enter & Carrot
		$.hotkeys.add('return', {propagate:true, disableInInput: true}, function(){
			if ($('#menu').hasClass('hovering') == true && $('.carrot')) {
				location = $('.carrot a').attr('href')
			} else {
				return false;
			}
		});

		// Page hotkeys
		$('#menu ul li').each(function() {
			var hotkey = $('a span.hotkey', this).text();
			var href = $('a', this).attr('href');
			if(hotkey) {
				$.hotkeys.add(hotkey, {propagate:true, disableInInput: true}, function(){
					if ($('#menu').hasClass('hovering') == true) {
						location = href;
					} else {
						return false;
					}
				});
			}
		});

		// Display hotkeys
		$('#menu a .hotkey').addClass('enabled');

		// If menu is open and mouse is clicked outside menu, close menu.
		$('html').click(function() {
			if ($('#menu #menulist').css('display') == 'block') {
				dropButton.hideMenu();
			}
		})
	}
}

// LIVESEARCH
var liveSearch = {
	init: function() {
		liveSearch.input= $('.search input');
		liveSearch.searchPrompt= liveSearch.input.attr('placeholder');

		liveSearch.input
			.focus( function() {
				if ( liveSearch.input.val() == liveSearch.searchPrompt ) {
					liveSearch.input.val('');
				}
			})
			.blur( function () {
				if (liveSearch.input.val() == '') {
					liveSearch.input.val( liveSearch.searchPrompt );
				}
			})
			.keyup( function( event ) {
				var code= event.keyCode;

				if ( liveSearch.input.val() == '') {
					return false;
				} else if ( code == 27 ) { // ESC key
					liveSearch.input.val('');
				} else if ( code != 13 ) { // anything but enter
					if ( liveSearch.timer) {
						clearTimeout( liveSearch.timer);
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
		if ( liveSearch.input.val() == liveSearch.prevSearch ) return;

		liveSearch.prevSearch = liveSearch.input.val();
		itemManage.fetch( 0, 20, true );
	}
}

// SEARCH CRITERIA TOGGLE
function toggleSearch() {
	var re = new RegExp('\\s*' + $(this).attr('href').substr(1), 'gi');
	if($('#search').val().match(re)) {
		$('#search').val($('#search').val().replace(re, ''));
		$(this).removeClass('active');
	}
	else {
		$('#search').val($('#search').val() + ' ' + $(this).attr('href').substr(1));
		$(this).addClass('active');
	}
	liveSearch.doSearch();
	return false;
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


// Damn the lack of proper support for pseudo-classes!
function findChildren() {
	$('div > .item:first-child, .modulecore .item:first-child, ul li:first-child').addClass('first-child')
	$('div > .item:last-child, .modulecore .item:last-child, ul li:last-child').addClass('last-child')
}

/* ON PAGE STARTUP */
String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); }

var tagskeyup;
// initialize the timeline after window load to make sure CSS has been applied to the DOM
$(window).load( function() {
	timeline.init();
});

$(document).ready(function(){
	// Initialize all sub-systems
	dropButton.init();
	theMenu.init();
	dashboard.init();
	inEdit.init();
	itemManage.init();
	tagManage.init();
	pluginManage.init();
	liveSearch.init();
	findChildren();
	navigationDropdown.init();

	// Alternate the rows' styling.
	$("table").each( function() {
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

		var ctl = '#' + $(this).attr('for');
		if($(ctl).val() == '') {
			$(ctl).addClass('islabeled');
			$(this).addClass('overcontent');
		} else {
			$(ctl).addClass('islabeled'); 
			$(this).addClass('abovecontent'); 
		}
		
		$(this).click(function() {
			$(ctl).focus();
		})
	});

	$('.islabeled').focus(function(){
		$('label[for='+$(this).attr('id')+']').removeClass('overcontent').addClass('abovecontent').show(); 
	}).blur(function(){
		if ($(this).val() == '') {
			$('label[for='+$(this).attr('id')+']').addClass('overcontent').removeClass('abovecontent'); 
		} else {
			$('label[for='+$(this).attr('id')+']').removeClass('overcontent').addClass('abovecontent').hide();
		}
	});

	// Convert these links into buttons
	$('a.link_as_button').each(function(){
		$(this).after('<button onclick="location.href=\'' + $(this).attr('href') + '\';return false;">' + $(this).html() + '</button>').hide();
	});


	/* Make Textareas Resizable */
	$('.resizable').resizeable();


	/* Init Tabs, using jQuery UI Tabs */
	$('.tabcontrol').tabs({ fx: { height: 'toggle', opacity: 'toggle' }, selected: null, unselect: true })
	
	/* Icons only for thin-width clients */
	var width = $('#mediatabs li').length * $('#mediatabs li').width();
	
	if($('#title').width() < width) {
		$('#mediatabs').addClass('iconify');
	}
	
	// Tag Drawer: Add tag via click
	$('#tag-list li').click(function() {
		// here we set the current text of #tags to current for later examination
		var current = $('#tags').val();
		
		// create a regex that finds the clicked tag in the input field
		var replstr = new RegExp('\\s*"?' + $( this ).text() + '"?\\s*', "gi");

		// check to see if the tag item we clicked has been clicked before...
		if( $( this ).hasClass('clicked') ) {
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
				if( $( '#tags').val().length == 0 ) {
					// and if it is, replace it with whatever we clicked
					$( '#tags' ).removeClass('islabeled').val( $( this ).text() );
					$('label[for=tags]').removeClass('overcontent').addClass('abovecontent').hide();
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
		
		resetTags();

	});

	$( '#tags' ).keyup(function(){
		clearTimeout(tagskeyup);
		tagskeyup = setTimeout(resetTags, 500);
	});
	
	$('#tags').focus(function() {
		$('#tags').addClass('focus');
		}).blur(function() {
			$('#tags').removeClass('focus');
		});

	// Tag Drawer: Remove all tags.
	$( '#clear' ).click( function() {
		// so we nuke all the tags in the tag text field
		$(' #tags ').val( '' );
		$('label[for=tags]').removeClass('abovecontent').addClass('overcontent').show();
		// and remove the clicked class from the tags in the manager
		$( '#tag-list li' ).removeClass( 'clicked' );
	});

	// LOGIN: Focus cursor on 'Name'.
	$('body.login #habari_username').focus();

	// SEARCH: Set default special search terms and assign click handler
	$('.special_search a')
		.click(toggleSearch)
		.each(function(){
			var re = new RegExp($(this).attr('href').substr(1));
			if($('#search').val().match(re)) {
				$(this).addClass('active');
			}
		});

	$('body').bind('ajaxSuccess', function(event, req, opts){
		if(opts.dataType == 'json') {
			eval('var cc=' + req.responseText);
			if(cc.callback) {
				cc.callback();
			}
		}
	});

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
	
	if(current.length == 0 && !$('#tags').hasClass('focus')) {
		$('label[for=tags]').addClass('overcontent').removeClass('abovecontent').show();
	}

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
