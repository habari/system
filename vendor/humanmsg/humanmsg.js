/*
	HUMANIZED MESSAGES 1.0
	idea - http://www.humanized.com/weblog/2006/09/11/monolog_boxes_and_transparent_messages
	home - http://humanmsg.googlecode.com
*/

var humanMsg = {
	msgcount: 0,
	setup: function(appendTo, logName, msgOpacity) {
		humanMsg.msgID = 'humanMsg';
		humanMsg.logID = 'humanMsgLog';

		// appendTo is the element the msg is appended to
		if (appendTo == undefined)
			appendTo = 'body';

		// The text on the Log tab
		if (logName == undefined)
			logName = 'Message Log';

		// Opacity of the message
		humanMsg.msgOpacity = .8;

		if (msgOpacity != undefined)
			humanMsg.msgOpacity = parseFloat(msgOpacity);

		// Inject the message structure
		jQuery(appendTo).append('<div id="'+humanMsg.msgID+'" class="humanMsg"><div class="imsgs"></div></div><div id="'+humanMsg.logID+'"><p>'+logName+'</p><ul></ul></div>')

		jQuery('#'+humanMsg.logID+' p').click(
			function() { jQuery('ul', '#'+humanMsg.logID).slideToggle(); jQuery('#humanMsgLog').addClass('logisopen') }	
		)

		jQuery('#'+humanMsg.logID+' ul').click(
			function() { jQuery(this).slideToggle(); jQuery('#humanMsgLog').removeClass('logisopen') }
		)
	},

	displayMsg: function(msg) {
		if (msg == '')
			return;

		clearTimeout(humanMsg.t2);
		humanMsg.msgcount++;

		// Inject message
		$('#'+humanMsg.msgID).show();
		$('<div class="msg" id="msgid_' + humanMsg.msgcount + '"><p>' + msg + '</p></div>')
		.appendTo('#'+humanMsg.msgID+' .imsgs')
		.show().animate({ opacity: humanMsg.msgOpacity}, 200, function() {
			jQuery('#'+humanMsg.logID)
				.show().children('ul').prepend('<li>'+msg+'</li>')	// Prepend message to log
				.children('li:first').slideDown(200)				// Slide it down

			if ( jQuery('#'+humanMsg.logID+' ul').css('display') == 'none') {
				jQuery('#'+humanMsg.logID+' p').animate({ bottom: 40 }, 200, function() {
					jQuery(this).animate({ bottom: 0 }, 300, function() { jQuery(this).css({ bottom: 0 }) })
				})
			}

		})

		// Watch for mouse & keyboard in .5s
		//humanMsg.t1 = setTimeout(humanMsg.bindEvents, 500)
		$('#msgid_'+humanMsg.msgcount).click(humanMsg.removeMsg);
		// Remove message after 5s
		humanMsg.t2 = setTimeout(humanMsg.removeMsg, 5000)
	},

	bindEvents: function() {
	// Remove message if mouse is moved or key is pressed
		jQuery(window)
			.mousemove(humanMsg.removeMsg)
			.click(humanMsg.removeMsg)
			.keypress(humanMsg.removeMsg)
	},

	removeMsg: function() {
		// Unbind mouse & keyboard
		jQuery(window)
			.unbind('mousemove', humanMsg.removeMsg)
			.unbind('click', humanMsg.removeMsg)
			.unbind('keypress', humanMsg.removeMsg)

		// If message is fully transparent, fade it out
		jQuery('#'+humanMsg.msgID+ ' .imsgs .msg').each(function(){
			if (jQuery(this).css('opacity') == humanMsg.msgOpacity)
				jQuery(this).animate({ opacity: 0 }, 500, function() { jQuery(this).remove() })
		});
	}
};

jQuery(document).ready(function(){
	humanMsg.setup();
})