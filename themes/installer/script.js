var habari = {};
habari.installer = {
	schemas : new Array(),
	
	registerSchema: function(schema) {
		var i = habari.installer.schemas.length + 1;
		habari.installer.schemas[i] = schema;
	},
	
	setDatabaseType: function () {
		for ( var i in habari.installer.schemas ) {
			// May use some function per schema (in their class) to show/hide themselves
			if (habari.installer.schemas[i] != $('#db_type').val()) {
				$("fieldset[id*='" + habari.installer.schemas[i] + "settings']").hide();
			}
			else {
				$("fieldset[id*='" + habari.installer.schemas[i] + "settings']").show();
			}
		}
	},
	
	checkDBCredentials: function() {
		var toCall = 'habari.installer.' + $('#db_type').val() + '.checkDBCredentials()';
		if (eval(toCall)) {
			$('.installstep:first').removeClass('done');
			$('#siteconfiguration').children('.options').fadeOut().removeClass('ready').removeClass('done');
			$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
		}
	},
	
	checkSiteConfiguration: function () {
		var warned = false;
		var installok = true;
		if ( ( $('#blog_title').val() != '' ) && ( $('#admin_username').val() != '' ) && ( $('#admin_pass1').val() != '' ) && ( $('#admin_pass2').val() != '' ) ) {
			//Checking fields is ok
			if ( $('#admin_pass1').val() != $('#admin_pass2').val() ) {
				warningtext= 'The passwords do not match, try typing them again.';
				$('#install').children('.options').fadeOut().removeClass('ready');
				$('#admin_pass1').parents('.installstep').removeClass('done');
				$('#admin_pass1').parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning:hidden').html(warningtext).fadeIn();
				warned = true;
				installok = false;
			}
		}
		if($('#admin_email').val() == '' ) {
			installok = false;
		}
		if(!warned) {
			ida= new Array( '#blog_title', '#admin_username', '#admin_pass1', '#admin_pass2', '#admin_email' );
			$(ida).each(function(id) {
				ido= $(ida).get(id);
				$(ido).parents('.inputfield').removeClass('invalid').find('.warning:visible').fadeOut();
				if($(ido).val() != '') {
					$(ido).addClass('valid');
				}
			});
		}
		if(installok) {
			$('#siteconfiguration').addClass('done');
			$('#install').children('.options').fadeIn().addClass('ready').addClass('done');
			$('#pluginactivation').children('.options').fadeIn().addClass('ready').addClass('done');
			$('#pluginactivation').children('.help-me').show();
			$('#submitinstall').removeAttr( 'disabled' );
		}
		else {
			$('#siteconfiguration').removeClass('done');
			$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
		}
	}
};

function handleAjaxError(msg, status, err)
{
	error_msg= '';
	if (msg.responseText != '') {
		error_msg= msg.responseText;
	}
	$('#installerror').html(
		'<strong>Installation Issue</strong>'+
		'<p>The installer couldn\'t verify your settings, possibly because your server is not correctly configured.  See <a href="doc/manual/index.html#Installation" onclick="$(this).attr(\'target\',\'_blank\');">the manual</a> for information on how to correct this problem, or <a href="#" onclick="noVerify();">continue without verification</a>.</p>' +
//		'<p>You might want to make sure <code>mod_rewrite</code> is enabled and that <code>AllowOverride</code> is at least set to <code>FileInfo</code> for the directory where <code>.htaccess</code> resides.</p>'+
		'<strong>Server Response</strong>'+
		'<p>'+error_msg.replace(/(<([^>]+)>)/ig,"")+'</p>'
	).fadeIn();
}

var checktimer = null;
function queueTimer(timer){
	if(checktimer != null) {
		clearTimeout(checktimer);
	}
	checktimer = setTimeout(timer, 500);
}

function noVerify() {
	$('#databasesetup input').unbind();
	$('#siteconfiguration input').unbind();

	ida= new Array( '#databasefile' );
	$(ida).each(function(id) {
	ido= $(ida).get(id);
		$(ido).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
		$(ido).parents('.installstep').addClass('done')
	});
	$('#siteconfiguration').children('.options').fadeIn().addClass('ready');

	ida= new Array( '#blog_title', '#admin_username', '#admin_pass1', '#admin_pass2', '#admin_email' );
	$(ida).each(function(id) {
		ido= $(ida).get(id);
		$(ido).parents('.inputfield').removeClass('invalid').find('.warning:visible').fadeOut();
		if($(ido).val() != '') {
			$(ido).addClass('valid');
		}
	});
	$('#siteconfiguration').addClass('done');
	$('#install').children('.options').fadeIn().addClass('ready').addClass('done');
	$('#submitinstall').removeAttr( 'disabled' );

	$('#installerror').html('<strong>Verification Disabled</strong><p>The installer will no longer attempt to verify your installation details.</p>' + $('#installerror').html());
}

var itemManage = {
	init: function() {
		if(!$('.item.controls input[type=checkbox]')) return;
		
		$('.item.controls input[type=checkbox]').change(function () {
			if($('.item.controls label').hasClass('all')) {
				itemManage.uncheckAll();
			} else {
				itemManage.checkAll();
			}
		});
		
		$('.item:not(.ignore):not(.controls) .checkbox input[type=checkbox]').change(function () {
			itemManage.changeItem();
		});
		
		itemManage.changeItem();
	},
	changeItem: function() {
		var selected = {};

		count = $('.item:not(.hidden):not(.ignore):not(.controls) .checkbox input[type=checkbox]:checked').length;

		if(count == 0) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls label').addClass('none').removeClass('all').text('None selected');
		} else if(count == $('.item:not(.hidden):not(.ignore):not(.controls) .checkbox input[type=checkbox]').length) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 1;
			});
			$('.item.controls label').removeClass('none').addClass('all').text('All selected');
		} else {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls label').removeClass('none').removeClass('all').text(count + ' selected');
		}
	},
	uncheckAll: function() {
		$('.item:not(.hidden):not(.ignore):not(.controls) .checkbox input[type=checkbox]').each(function() {
			this.checked = 0;
		});
		itemManage.changeItem();
	},
	checkAll: function() {
		$('.item:not(.hidden):not(.ignore):not(.controls) .checkbox input[type=checkbox]').each(function() {
			this.checked = 1;
		});
		itemManage.changeItem();
	}
}

$(document).ready(function() {
	$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;});
	$('.help').hide();
	$('.installstep').removeClass('ready');
	$('.installstep:eq(0), .installstep:eq(1)').addClass('ready');
	$('.javascript-disabled').hide();
	$('#installform').before('<div class="installerror error" id="installerror"></div>');
	$('#installerror').hide();
	$('form').attr('autocomplete', 'off');
	itemManage.init();
	habari.installer.setDatabaseType();
	habari.installer.checkDBCredentials();
	habari.installer.checkSiteConfiguration();
	$('#db_type').change(function(){habari.installer.setDatabaseType()});
	$('#databasesetup input').keyup(function(){queueTimer(habari.installer.checkDBCredentials)});
	$('#siteconfiguration input').keyup(function(){queueTimer(habari.installer.checkSiteConfiguration)});
	$('#locale').focus().change(function(){$('#locale-form').submit()});
});
