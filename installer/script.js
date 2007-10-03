function setDatabaseType(el)
{
	$('.forsqlite').hide();
	$('.formysql').hide();
	
	switch($(el).val()) {
		case 'mysql':
			$('.formysql').show();
			break;
		case 'sqlite':
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
				switch($('status',xml).text()) {
				case '0': // Show warning, fade the borders and hide the next step
					$('id',xml).each(function(id) {
					ido= $('id',xml).get(id);
					warningtext= $('message',xml).text();
					$('#siteconfiguration').children('.options').fadeOut();
					$('#siteconfiguration').removeClass('ready').removeClass('done');
					$('#install').children('.options').fadeOut();
					$('#install').removeClass('ready').removeClass('done');
					$($(ido).text()).parents('.installstep').removeClass('done');
					$($(ido).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid');
					$($(ido).text()).parents('.inputfield').find('.warning:hidden').html(warningtext).fadeIn();
					});
					break;
				case '1': // Hide the warnings, highlight the borders and show the next step
					ida= new Array( '#databasename', '#databasehost', '#databasepass', '#databaseuser' );
					$(ida).each(function(id) {
					ido= $(ida).get(id);
					$(ido).parents('.inputfield').removeClass('invalid').addClass('valid');
					$(ido).parents('.inputfield').find('.warning:visible').fadeOut();
					$(ido).parents('.installstep').addClass('done')
					});
					$('#siteconfiguration').children('.options').fadeIn();
					$('#siteconfiguration').addClass('ready');
					break;
				}
			},
			error: function(msg, status, err) {
				alert("ERROR:\n"+
				"\nStatus: "+status+
				"\nError: "+err+
				"\n\n"+msg.responseText);
				},
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
				switch($('status',xml).text()) {
				case '0': // Show warning, fade the borders and hide the next step
					$('id',xml).each(function(id) {
					ido= $('id',xml).get(id);
					warningtext= $('message',xml).text();
					$('#siteconfiguration').children('.options').fadeOut();
					$('#siteconfiguration').removeClass('ready').removeClass('done');
					$('#install').children('.options').fadeOut();
					$('#install').removeClass('ready').removeClass('done');
					$($(ido).text()).parents('.installstep').removeClass('done');
					$($(ido).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid');
					$($(ido).text()).parents('.inputfield').find('.warning:hidden').html(warningtext).fadeIn();
					});
					break;
				case '1': // Hide the warnings, highlight the borders and show the next step
					ida= new Array( '#databasefile' );
					$(ida).each(function(id) {
					ido= $(ida).get(id);
						$(ido).parents('.inputfield').removeClass('invalid').addClass('valid');
						$(ido).parents('.inputfield').find('.warning:visible').fadeOut();
						$(ido).parents('.installstep').addClass('done')
					});
					$('#siteconfiguration').children('.options').fadeIn();
					$('#siteconfiguration').addClass('ready');
					break;
				}
			},
			error: function(msg, status, err) {
				alert("ERROR:\n"+
				"\nStatus: "+status+
				"\nError: "+err+
				"\n\n"+msg.responseText);
				},
		});
	}
	else {
		$('.installstep:first').removeClass('done');
		$('#siteconfiguration').children('.options').fadeOut();
		$('#siteconfiguration').removeClass('ready').removeClass('done');
		$('#install').children('.options').fadeOut();
		$('#install').removeClass('ready').removeClass('done');
	}
}

function checkSiteConfigurationCredentials() {
	if ( ( $('#sitename').val() != '' ) && ( $('#adminuser').val() != '' ) && ( $('#adminpass1').val() != '' ) && ( $('#adminpass2').val() != '' ) && ( $('#adminemail').val() != '' ) ) {
		if ( $('#adminpass1').val() != $('#adminpass2').val() ) {
			warningtext= 'The passwords do not match, try typing them again.';
			$('#install').children('.options').fadeOut();
			$('#install').removeClass('ready');
			$('#adminpass1').parents('.installstep').removeClass('done');
			$('#adminpass1').parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid');
			$('#adminpass1').parents('.inputfield').find('.warning:hidden').html(warningtext).fadeIn();
			$('#install').children('.options').fadeOut();
			$('#install').removeClass('ready').removeClass('done');
		}
		else {
			ida= new Array( '#sitename', '#adminuser', '#adminpass1', '#adminpass2', '#adminemail' );
			$(ida).each(function(id) {
				ido= $(ida).get(id);
				$(ido).parents('.inputfield').removeClass('invalid').addClass('valid');
				$(ido).parents('.inputfield').find('.warning:visible').fadeOut();
				$(ido).parents('.installstep').addClass('done')
				});
			$('#install').children('.options').fadeIn();
			$('#install').addClass('ready');
			$('#install').addClass('done');
			$('#submitinstall').removeAttr( 'disabled' );
		}
	}
	else {
		$('#install').children('.options').fadeOut();
		$('#install').removeClass('ready').removeClass('done');
	}
}

$(document).ready(function() {
	$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
	$('.help').hide();
	$('.installstep').removeClass('ready');
	$('.installstep:first').addClass('ready');
	setDatabaseType($('#databasetype'));
	$('.javascript-disabled').hide();
	$('form').attr('autocomplete', 'off');
	checkDBCredentials();
	checkSiteConfigurationCredentials();
	$('#databasehost').blur(checkDBCredentials);
	$('#databaseuser').blur(checkDBCredentials);
	$('#databasepass').blur(checkDBCredentials);
	$('#databasename').blur(checkDBCredentials);
	$('#databasefile').blur(checkDBCredentials);
	$('#sitename').blur(checkSiteConfigurationCredentials);
	$('#adminuser').blur(checkSiteConfigurationCredentials);
	$('#adminpass1').blur(checkSiteConfigurationCredentials);
	$('#adminpass2').blur(checkSiteConfigurationCredentials);
	$('#adminemail').blur(checkSiteConfigurationCredentials);
	});