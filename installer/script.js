var installer = {
	verifyDB: true,
	
	handleAjaxError: function(msg, status, err) {
		error_msg= '';
		if (msg.responseText != '') {
			error_msg= msg.responseText;
		}
		$('#installerror').html(
			'<strong>Installation Issue</strong>'+
			'<p>The installer couldn\'t verify your settings, possibly because your server is not correctly configured.  See <a href="doc/manual/index.html#Installation" onclick="$(this).attr(\'target\',\'_blank\');">the manual</a> for information on how to correct this problem, or <a href="#" onclick="installer.noVerify();">continue without database verification</a>.</p>' +
//			'<p>You might want to make sure <code>mod_rewrite</code> is enabled and that <code>AllowOverride</code> is at least set to <code>FileInfo</code> for the directory where <code>.htaccess</code> resides.</p>'+
			'<strong>Server Response</strong>'+
			'<p>'+error_msg.replace(/(<([^>]+)>)/ig,"")+'</p>'
		).fadeIn();
	},
	
	setDatabaseType: function() {
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
				break;
		}
		installer.checkDBCredentials();
	},
	
	checkDBCredentials: function() {
		switch ($('#db_type').val()) {
			case 'mysql':
				installer.mysql.checkDBCredentials();
				break;
			case 'pgsql':
				installer.pgsql.checkDBCredentials();
				break;
			case 'sqlite':
				installer.sqlite.checkDBCredentials();
				break;
		}
	},
	
	checkSiteConfigurationCredentials: function() {
		var warned = false;
		var emptyfields = false;
		var installok = true;
		// Do not change the order, else installok var will be incorrect
		$('#sitename, #adminuser, #adminpass1, #adminpass2, #adminemail').each(function(){
			if ($(this).val() != '') {
				$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			}
			else {
				$(this).parents('.inputfield').removeClass('valid');
				emptyfields = true;
			}
		});
		if(!emptyfields) {
			if ( $('#adminpass1').val() != $('#adminpass2').val() ) {
				warningtext= 'The passwords do not match, try typing them again.';
				$('#adminpass1').parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning:hidden').html(warningtext).fadeIn();
				warned = true;
				installok = false;
				$('#siteconfiguration').removeClass('done');
				$('#pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
				$('#pluginactivation').children('.help-me').hide();
			}
		}
		else {
			installok = false;
		}
		if (installok) {
			$('#adminpass1').parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			if(!warned) {
				$('#sitename', '#adminuser', '#adminpass1', '#adminpass2', '#adminemail').each(function() {
					$(this).parents('.inputfield').removeClass('invalid').find('.warning:visible').fadeOut();
					if($(this).val() != '') {
						$(this).addClass('valid');
					}
				});
			}
			$('#siteconfiguration, #pluginactivation, #install').addClass('ready').addClass('done').children('.options').fadeIn().children('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$('#pluginactivation').children('.help-me').show();
			$('#submitinstall').removeAttr( 'disabled' );
		}
		else {
			$('#siteconfiguration').removeClass('done');
			$('#pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#pluginactivation').children('.help-me').hide();
		}
	},
	
	noVerify: function() {
		installer.verifyDB = false;
		$('#installerror').html('<strong>Verification Disabled</strong><p>The installer will no longer attempt to verify the database settings.</p>' + $('#installerror').html());
	}
	
}

installer.mysql = {
	checkDBCredentials : function() {
		if ( ( $('#mysqldatabasehost').val() != '' ) && ( $('#mysqldatabaseuser').val() != '' ) && ( $('#mysqldatabasename').val() != '' ) ) {
			if (installer.verifyDB) {
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
							warningtext= $('message',xml).text();
							$('id',xml).each(function() {
								$($(this).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
								$($(this).text()).parents('.installstep').removeClass('done')
							});
							$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
							$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
							break;
						case '1': // Hide the warnings, highlight the borders and show the next step
							installer.mysql.validDBCredentials();
							break;
						}
					},
					error: installer.handleAjaxError
				});
			} else {
				installer.mysql.validDBCredentials();
			}
		} else {
			$('#mysqldatabasename, #mysqldatabasehost, #mysqldatabasepass, #mysqldatabaseuser').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
	},
	
	validDBCredentials: function() {
		$('#mysqldatabasename, #mysqldatabasehost, #mysqldatabasepass, #mysqldatabaseuser').each(function() {
			$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$(this).parents('.installstep').addClass('done')
		});
		$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
		$('#siteconfiguration').children('.help-me').show();
		installer.checkSiteConfigurationCredentials();
	}
}

installer.pgsql = {
	checkDBCredentials : function() {
		if ( ( $('#pgsqldatabasehost').val() != '' ) && ( $('#pgsqldatabaseuser').val() != '' ) && ( $('#pgsqldatabasename').val() != '' ) ) {
			if (installer.verifyDB) {
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
							warningtext= $('message',xml).text();
							$('id',xml).each(function() {
								$($(this).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
								$($(this).text()).parents('.installstep').removeClass('done')
							});
							$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
							$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
							break;
						case '1': // Hide the warnings, highlight the borders and show the next step
							installer.pgsql.validDBCredentials();
							break;
						}
					},
					error: installer.handleAjaxError
				});
			} else {
				installer.pgsql.validDBCredentials();
			}
		} else {
			$('#pgsqldatabasename, #pgsqldatabasehost, #pgsqldatabasepass, #pgsqldatabaseuser').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('.installstep:eq(1)').removeClass('done');
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
	},
	
	validDBCredentials: function() {
		$('#pgsqldatabasename, #pgsqldatabasehost, #pgsqldatabasepass, #pgsqldatabaseuser').each(function() {
			$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$(this).parents('.installstep').addClass('done')
		});
		$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
		$('#siteconfiguration').children('.help-me').show();
		installer.checkSiteConfigurationCredentials();
	}
}

installer.sqlite = {
	checkDBCredentials : function() {
		if ( $('#databasefile').val() != '' ) {
			if (installer.verifyDB) {
				$.ajax({
					type: 'POST',
					url: 'ajax/check_sqlite_credentials',
					data: { // Ask InstallHandler::ajax_check_sqlite_credentials to check the credentials
						ajax_action: 'check_sqlite_credentials',
						file: $('#databasefile').val()
					},
					success: function(xml) {
						$('#installerror').fadeOut();
						switch($('status',xml).text()) {
						case '0': // Show warning, fade the borders and hide the next step
							warningtext= $('message',xml).text();
							$('id',xml).each(function() {
								$($(this).text()).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
								$($(this).text()).parents('.installstep').removeClass('done')
							});
							$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
							$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
							break;
						case '1': // Hide the warnings, highlight the borders and show the next step
							installer.sqlite.validDBCredentials();
							break;
						}
					},
					error: installer.handleAjaxError
				});
			} else {
				installer.sqlite.validDBCredentials();
			}
		} else {
			$('#databasefile').parents('.inputfield').removeClass('valid');
			$('.installstep:eq(1)').removeClass('done');
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
	},
	
	validDBCredentials: function() {
		$('#databasefile').each(function() {
			$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$(this).parents('.installstep').addClass('done')
		});
		$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
		$('#siteconfiguration').children('.help-me').show();
		installer.checkSiteConfigurationCredentials();
	}
}

/* Handles the timers for verification */
var checktimer = null;
function queueTimer(timer){
	if(checktimer != null) {
		clearTimeout(checktimer);
	}
	checktimer = setTimeout(timer, 500);
}

/* Manages Plugin Activation Items */
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
	// Table Prefix is optional, it is always OK
	$('#tableprefix').parents('.inputfield').addClass('valid');
	$('.javascript-disabled').hide();
	$('#installform').before('<div class="installerror error" id="installerror"></div>');
	$('#installerror').hide();
	$('form').attr('autocomplete', 'off');
	itemManage.init();
	installer.setDatabaseType();
	installer.checkDBCredentials();
	installer.checkSiteConfigurationCredentials();
	$('#db_type').change(installer.setDatabaseType);
	$('#databasesetup input').keyup(function(){queueTimer(installer.checkDBCredentials)});
	$('#siteconfiguration input').keyup(function(){queueTimer(installer.checkSiteConfigurationCredentials)});
	$('#locale').focus().change(function() {
		$('#locale-form').submit();
	});
});
