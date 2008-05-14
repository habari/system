function handleAjaxError(msg, status, err)
{
	error_msg= '';
	if (msg.responseText != '') {
		error_msg= msg.responseText;
	}
	$('#installerror').html(
		'<strong>Installation Issue</strong>'+
		'<p>The installer couldn\'t verify your settings, possibly because your server is not correctly configured.  See <a href="manual/index.html#Installation" onclick="$(this).attr(\'target\',\'_blank\');">the manual</a> for information on how to correct this problem, or <a href="#" onclick="noVerify();">continue without verification</a>.</p>' +
//		'<p>You might want to make sure <code>mod_rewrite</code> is enabled and that <code>AllowOverride</code> is at least set to <code>FileInfo</code> for the directory where <code>.htaccess</code> resides.</p>'+
		'<strong>Server Response</strong>'+
		'<p>'+error_msg.replace(/(<([^>]+)>)/ig,"")+'</p>'
	).fadeIn();
}

function setDatabaseType()
{
	switch($('#db_type').val()) {
		case 'mysql':
			$('.forpgsql').hide();
			$('.forsqlite').hide();
			$('.formysql').show();
			break;
		case 'pgsql':
			$('.formysql').hide();
			$('.forsqlite').hide();
			$('.forpgsql').show();
			break;
		case 'sqlite':
			$('.formysql').hide();
			$('.forpgsql').hide();
			$('.forsqlite').show();
			checkDBCredentials();
			break;
	}
}

function checkDBCredentials()
{
	if ( ( $('#db_type').val() == 'mysql' ) && ( $('#mysqldatabasehost').val() != '' ) && ( $('#mysqldatabaseuser').val() != '' ) && ( $('#mysqldatabasename').val() != '' ) ) {
		$.ajax({
			type: 'POST',
			url: 'ajax/check_mysql_credentials',
			data: { // Ask InstallHandler::ajax_check_mysql_credentials to check the credentials
				ajax_action: 'check_mysql_credentials',
				host: $('#mysqldatabasehost').val(),
				database: $('#mysqldatabasename').val(),
				user: $('#mysqldatabaseuser').val(),
				pass: $('#mysqldatabasepass').val()
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
					ida= new Array( '#mysqldatabasename', '#mysqldatabasehost', '#mysqldatabasepass', '#mysqldatabaseuser' );
					$(ida).each(function(id) {
					ido= $(ida).get(id);
					$(ido).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
					$(ido).parents('.installstep').addClass('done')
					});
					$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
					$('#sitename').focus()
					break;
				}
			},
			error: handleAjaxError,
		});
	}
	else if ( ( $('#db_type').val() == 'pgsql' ) && ( $('#pgsqldatabasehost').val() != '' ) && ( $('#pgsqldatabaseuser').val() != '' ) && ( $('#pgsqldatabasename').val() != '' ) ) {
		$.ajax({
			type: 'POST',
			url: 'ajax/check_pgsql_credentials',
			data: { // Ask InstallHandler::ajax_check_pgsql_credentials to check the credentials
				ajax_action: 'check_pgsql_credentials',
				host: $('#pgsqldatabasehost').val(),
				database: $('#pgsqldatabasename').val(),
				user: $('#pgsqldatabaseuser').val(),
				pass: $('#pgsqldatabasepass').val()
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
					ida= new Array( '#pgsqldatabasename', '#pgsqldatabasehost', '#pgsqldatabasepass', '#pgsqldatabaseuser' );
					$(ida).each(function(id) {
					ido= $(ida).get(id);
					$(ido).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
					$(ido).parents('.installstep').addClass('done')
					});
					$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
					$('#sitename').focus()
					break;
				}
			},
			error: handleAjaxError,
		});
	}
	else if ( ( $('#db_type').val() == 'sqlite' ) && ( $('#databasefile').val() != '' ) ) {
		$.ajax({
			type: 'POST',
			url: 'ajax/check_sqlite_credentials',
			data: { // Ask InstallHandler::ajax_check_sqlite_credentials to check the credentials
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
	var warned = false;
	var installok = true;
	if ( ( $('#sitename').val() != '' ) && ( $('#adminuser').val() != '' ) && ( $('#adminpass1').val() != '' ) && ( $('#adminpass2').val() != '' ) ) {
		//Checking fields is ok
		if ( $('#adminpass1').val() != $('#adminpass2').val() ) {
			warningtext= 'The passwords do not match, try typing them again.';
			$('#install').children('.options').fadeOut().removeClass('ready');
			$('#adminpass1').parents('.installstep').removeClass('done');
			$('#adminpass1').parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning:hidden').html(warningtext).fadeIn();
			warned = true;
			installok = false;
		}
	}
	if($('#adminemail').val() == '' ) {
		installok = false;
	}
	if(!warned) {
		ida= new Array( '#sitename', '#adminuser', '#adminpass1', '#adminpass2', '#adminemail' );
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
		$('#submitinstall').removeAttr( 'disabled' );
	}
	else {
		$('#siteconfiguration').removeClass('done');
		$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
	}
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

	ida= new Array( '#sitename', '#adminuser', '#adminpass1', '#adminpass2', '#adminemail' );
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
	$('#db_type').change(setDatabaseType);
	$('#databasesetup input').keyup(function(){queueTimer(checkDBCredentials)});
	$('#siteconfiguration input').keyup(function(){queueTimer(checkSiteConfigurationCredentials)});
	$('#databaseuser').focus()
});
