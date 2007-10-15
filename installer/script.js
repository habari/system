function handleAjaxError(msg, status, err)
{
	error_msg= '';
	if (msg.responseText != '') {
		error_msg= msg.responseText;
	}
	$('#installerror').html(
		'<strong>AJAX Error</strong>'+
		'<p>You might want to make sure <code>mod_rewrite</code> is enabled and that <code>AllowOverride</code> is at least set to <code>FileInfo</code> for the directory where <code>.htaccess</code> resides.</p>'+
		'<strong>Server Response</strong>'+
		'<p>'+error_msg.replace(/(<([^>]+)>)/ig,"")+'</p>'
	).fadeIn();
}
				
function setDatabaseType()
{
	switch($('#databasetype').val()) {
		case 'mysql':
			$('.forsqlite').hide();
			$('.formysql').show();
			break;
		case 'sqlite':
			$('.formysql').hide();
			$('.forsqlite').show();
			break;
	}
}

function checkDBCredentials()
{
	if ( ( $('#databasetype').val() == 'mysql' ) && ( $('#databasehost').val() != '' ) && ( $('#databaseuser').val() != '' ) && ( $('#databasename').val() != '' ) ) {
		$.ajax({ 
			type: 'POST',
			url: 'ajax/check_mysql_credentials',
			data: { // Ask InstallHandler::ajax_check_mysql_credentials to check the credentials
				ajax_action: 'check_mysql_credentials',
				host: $('#databasehost').val(),
				database: $('#databasename').val(),
				user: $('#databaseuser').val(),
				pass: $('#databasepass').val()
			},
			success: function(xml) {
				$('#installerror').fadeOut();
				switch($('status',xml).text()) {
				case '0': // Show warning, fade the borders and hide the next step
					$('id',xml).each(function(id) {
					ido= $('id',xml).get(id);
					warningtext= $('message',xml).text();
					$('#siteconfiguration').children('.options').fadeOut().removeClass('ready').removeClass('done');
					$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
					$($(ido).text()).parents('.installstep').removeClass('done');
					$($(ido).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
					});
					break;
				case '1': // Hide the warnings, highlight the borders and show the next step
					ida= new Array( '#databasename', '#databasehost', '#databasepass', '#databaseuser' );
					$(ida).each(function(id) {
					ido= $(ida).get(id);
					$(ido).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
					$(ido).parents('.installstep').addClass('done')
					});
					$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
					break;
				}
			},
			error: handleAjaxError,
		});
	}
	else if ( ( $('#databasetype').val() == 'sqlite' ) && ( $('#databasefile').val() != '' ) ) {
		$.ajax({
			type: 'POST',
			url: 'ajax/check_sqlite_credentials',
			data: { // Ask InstallHandler::ajax_check_mysql_credentials to check the credentials
				ajax_action: 'check_sqlite_credentials',
				file: $('#databasefile').val(),
			},
			success: function(xml) {
				$('#installerror').fadeOut();
				switch($('status',xml).text()) {
				case '0': // Show warning, fade the borders and hide the next step
					$('id',xml).each(function(id) {
					ido= $('id',xml).get(id);
					warningtext= $('message',xml).text();
					$('#siteconfiguration').children('.options').fadeOut().removeClass('ready').removeClass('done');
					$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
					$($(ido).text()).parents('.installstep').removeClass('done');
					$($(ido).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
					});
					break;
				case '1': // Hide the warnings, highlight the borders and show the next step
					ida= new Array( '#databasefile' );
					$(ida).each(function(id) {
					ido= $(ida).get(id);
						$(ido).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
						$(ido).parents('.installstep').addClass('done')
					});
					$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
					break;
				}
			},
			error: handleAjaxError,
		});
	}
	else {
		$('.installstep:first').removeClass('done');
		$('#siteconfiguration').children('.options').fadeOut().removeClass('ready').removeClass('done');
		$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
	}
}

function checkSiteConfigurationCredentials() {
	if ( ( $('#sitename').val() != '' ) && ( $('#adminuser').val() != '' ) && ( $('#adminpass1').val() != '' ) && ( $('#adminpass2').val() != '' ) && ( $('#adminemail').val() != '' ) ) {
		if ( $('#adminpass1').val() != $('#adminpass2').val() ) {
			warningtext= 'The passwords do not match, try typing them again.';
			$('#install').children('.options').fadeOut().removeClass('ready');
			$('#adminpass1').parents('.installstep').removeClass('done');
			$('#adminpass1').parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning:hidden').html(warningtext).fadeIn();
			$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
		}
		else {
			ida= new Array( '#sitename', '#adminuser', '#adminpass1', '#adminpass2', '#adminemail' );
			$(ida).each(function(id) {
				ido= $(ida).get(id);
				$(ido).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
				$(ido).parents('.installstep').addClass('done')
				});
			$('#install').children('.options').fadeIn().addClass('ready').addClass('done');
			$('#submitinstall').removeAttr( 'disabled' );
		}
	}
	else {
		$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
	}
}

$(document).ready(function() {
	$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
	$('.help').hide();
	$('.installstep').removeClass('ready');
	$('.installstep:first').addClass('ready');
	$('.javascript-disabled').hide();
	$('#installform').before('<div class="installerror error" id="installerror"></div>');
	$('#installerror').hide();
	$('form').attr('autocomplete', 'off');
	setDatabaseType();
	checkDBCredentials();
	checkSiteConfigurationCredentials();
	$('#databasetype').change(setDatabaseType);
	$('#databasehost,#databaseuser,#databasepass,#databasename,#databasefile').blur(checkDBCredentials);
	$('#sitename,#adminuser,#adminpass1,#adminpass2,#adminemail').blur(checkSiteConfigurationCredentials);
	});
