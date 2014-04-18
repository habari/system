var installer = {
	verifyDB: true,
	
	handleAjaxError: function(msg, status, err) {
		error_msg= '';
		if (msg.responseText != '') {
			error_msg= msg.responseText;
		}
		installer.showError(
			'<strong>Installation Issue</strong>'+
			'<p>The installer couldn\'t verify your settings, possibly because your server is not correctly configured.  See <a href="doc/manual/installation.html#common_issues" onclick="$(this).attr(\'target\',\'_blank\');">the manual</a> for information on how to correct this problem, or <a href="#" onclick="installer.noVerify();return false;">continue without database verification</a>.</p>' +
//			'<p>You might want to make sure <code>mod_rewrite</code> is enabled and that <code>AllowOverride</code> is at least set to <code>FileInfo</code> for the directory where <code>.htaccess</code> resides.</p>'+
			'<strong>Server Response</strong>'+
			'<p>'+error_msg.replace(/(<([^>]+)>)/ig,"")+'</p>'
		);
	},
	
	showError: function(error) {
		$('#installerror').html(error).fadeIn();
		$('html,body').animate({scrollTop: $('#installerror').offset().top},500);
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
		installer.checkDBFields();
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
	
	checkDBFields: function() {
		switch ($('#db_type').val()) {
			case 'mysql':
				installer.mysql.checkDBFields();
				break;
			case 'pgsql':
				installer.pgsql.checkDBFields();
				break;
			case 'sqlite':
				installer.sqlite.checkDBFields();
				break;
		}
	},

	checkSiteConfigurationCredentials: function() {
		var installok = true;

		// If admin passwords have been entered, check if they're the same
		var pass1 = $('#adminpass1');
		var pass2 = $('#adminpass2');

		if ( pass1.val().length > 0 && pass2.val().length > 0 && pass1.val() == pass2.val() ) {
			pass1.parents('.inputfield').removeClass('invalid').addClass('valid');
			installok = true;
		}
		else {
			pass1.parents('.inputfield').removeClass('valid').addClass('invalid');
			installok = false;
		}

		// Check other details have been entered
		$('#sitename, #adminuser').each(function(){
			if ($(this).val() != '') {
				$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			}
			else {
				$(this).parents('.inputfield').removeClass('valid').addClass('invalid');
				installok = false;
			}
		});

		// Very loosely check if the email is valid (don't be tempted to check this more strictly, you'll go mad, annoy people, or both).
		var regexemail = /.+@.+/;
		if (regexemail.test($('#adminemail').val())) {
			$('#adminemail').parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
		}
		else {
			$("#adminemail").parents('.inputfield').removeClass('valid').addClass('invalid');
			installok = false;
		}

		if (installok) {
//			installer.checkDBCredentials();
			$('#siteconfiguration, #themeselection').addClass('ready').addClass('done').children('.options').fadeIn().children('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$('#themeselection').children('.help-me').show();
		}
		else {
			$('#siteconfiguration').removeClass('done');
			$('#themeselection, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#themeselection').children('.help-me').hide();
		}
	},

	checkThemeConfiguration: function() {
		$('#siteconfiguration, #pluginactivation, #install').addClass('ready').addClass('done').children('.options').fadeIn().children('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
		$('#pluginactivation').children('.help-me').show();
		$('#submitinstall').removeAttr( 'disabled' );

		installer.resolveFeatures();
	},

	resolveFeatures: function() {
		$('#pluginactivation .item').removeClass('provider').removeClass('conflict');
		$('.feature_note').html('');

		theme_requires = $('.theme_selection:checked').data('requires').split(',').filter(function(e){return e != ''});
		theme_conflicts = $('.theme_selection:checked').data('conflicts').split(',').filter(function(e){return e != ''});
		theme_provides = $('.theme_selection:checked').data('provides').split(',').filter(function(e){return e != ''});
		requires = theme_requires;
		provides = theme_provides;

		$('.plugin_selection').each(function(){
			var p = $(this);
			plugin_requires = p.data('requires').split(',').filter(function(e){return e != ''});
			plugin_provides = p.data('provides').split(',').filter(function(e){return e != ''});
			plugin_conflicts = p.data('conflicts').split(',').filter(function(e){return e != ''});
			p.attr('disabled', false);

			if(p.attr('checked')) {
				requires = requires.concat(plugin_requires);
				provides = provides.concat(plugin_provides);

				conflicting = intersect(theme_requires, plugin_conflicts);
				providing = intersect(theme_requires, plugin_provides);

				if(conflicting.length > 0) {
					p.attr('checked', false).attr('disabled', true);
					p.parents('.item')
						.addClass('conflict')
						.find('.feature_note').html(_t('This plugin conflics with a requirement of the theme: %s', conflicting.join(',')));
				}
				else if(providing.length > 0) {
					p.attr('disabled', false);
					p.parents('.item')
						.addClass('provider')
						.find('.feature_note').html(_t('This plugin provides a requirement of the theme: %s', providing.join(',')));
					for(i in providing) {
						if(installer.getProviders(providing[i]).length == 1) {
							break;
						}
					}
				}
				else {
					p.attr('disabled', false);
				}

				plugin_name = p.parents('.head').find('label .name').text();
				for(i in plugin_provides) {
					conflicters = installer.getConflicters(plugin_provides[i]);
					for(u in conflicters) {
						conflicters[u].attr('checked', false).attr('disabled', true);
						conflicters[u].parents('.item')
							.addClass('conflict')
							.find('.feature_note').html(_t('This plugin conflicts with the enabled plugin %1$s over this feature: %2$s', plugin_name, plugin_provides[i]));
					}
				}

				for(i in plugin_conflicts) {
					conflicters = installer.getProviders(plugin_conflicts[i]);
					for(u in conflicters) {
						conflicters[u].attr('checked', false).attr('disabled', true);
						conflicters[u].parents('.item')
							.addClass('conflict')
							.find('.feature_note').html(_t('This plugin provides a feature that the enabled plugin %1$s conflicts with: %2$s', plugin_name, plugin_conflicts[i]));
					}
				}
			}

			for(i in plugin_requires) {
				providers = installer.getProviders(plugin_requires[i]);
				if(providers == 0 && theme_provides.indexOf(plugin_requires[i]) < 0) {
					p.attr('checked', false).attr('disabled', true);
					p.parents('.item')
						.addClass('conflict')
						.find('.feature_note').html(_t('This plugin requires a feature that is not provided by the current theme or any available plugin: %s', plugin_requires[i]));
				}
			}

		});

		requires = requires.filter(function(e,i,a){return e != '' && i == a.indexOf(e);});
		provides = provides.filter(function(e,i,a){return e != '' && i == a.indexOf(e);});

		unfulfilled = requires.filter(function(e,i,a){return provides.indexOf(e) < 0;});
		missing_features = [];
		if(unfulfilled.length > 0) {
			for(i in unfulfilled) {
				providers = installer.getProviders(unfulfilled[i]);
				for(u in providers) {
					if(!providers[u].parents('.item').hasClass('provider')) {
						providers[u].parents('.item')
							.addClass('provider')
							.find('.feature_note').html(_t('This plugin provides a requirement of an active plugin: %s', unfulfilled[i]));
					}
				}
				if(installer.getProviders(unfulfilled[i], true).length == 0) {
					missing_features.push(unfulfilled[i]);
				}
			}
		}
		if(missing_features.length > 0) {
			$('#unfulfilled_feature_list').html(missing_features.join(', '));
			$('#feature_error').show();
			$('#submitinstall').attr('disabled', true);
		}
		else {
			$('#feature_error').hide();
			$('#submitinstall').attr('disabled', false);
		}

	},

	getProviders: function(feature, active) {
		var result = [];
		selector = active ? '.plugin_selection:checked' : '.plugin_selection';
		$(selector).each(function(){
			provides = $(this).data('provides').split(',').filter(function(e){return e != ''});
			if(provides.indexOf(feature) >= 0 ) {
				result.push($(this));
			}
		});
		return result;
	},

	getConflicters: function(feature) {
		var result = [];
		$('.plugin_selection').each(function(){
			provides = $(this).data('conflicts').split(',').filter(function(e){return e != ''});
			if(provides.indexOf(feature) >= 0 ) {
				result.push($(this));
			}
		});
		return result;
	},

	noVerify: function() {
		installer.verifyDB = false;
		installer.showError('<strong>Verification Disabled</strong><p>The installer will no longer attempt to verify the database settings.</p>' + $('#installerror').html());
	}
}

installer.mysql = {
	checkDBFields : function() {
		if ( ( $('#mysqldatabasehost').val() == '' ) || ( $('#mysqldatabaseuser').val() == '' ) || ( $('#mysqldatabasename').val() == '' ) ) {
			$('#check_db_connection').attr('disabled', true);
			$('#mysqldatabasename, #mysqldatabasehost, #mysqldatabasepass, #mysqldatabaseuser, #tableprefix').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
		else {
			$('#check_db_connection').attr('disabled', false);
		}
	},

	checkDBCredentials : function() {
		if ( ( $('#mysqldatabasehost').val() != '' ) && ( $('#mysqldatabaseuser').val() != '' ) && ( $('#mysqldatabasename').val() != '' ) ) {
			if (installer.verifyDB) {
				$.ajax({
					type: 'POST',
					url: habari.url.check_mysql_credentials,
					data: { // Ask InstallHandler::ajax_check_mysql_credentials to check the credentials
						ajax_action: 'check_mysql_credentials',
						host: $('#mysqldatabasehost').val(),
						database: $('#mysqldatabasename').val(),
						user: $('#mysqldatabaseuser').val(),
						pass: $('#mysqldatabasepass').val(),
						table_prefix: $('#tableprefix').val()
					},
					success: function(xml) {
						$('#installerror').fadeOut();
						switch($('status',xml).text()) {
						case '0': // Show warning, fade the borders and hide the next step
							$('#mysqldatabasename, #mysqldatabasehost, #mysqldatabasepass, #mysqldatabaseuser, #tableprefix').each(function() {
								$(this).parents('.inputfield').addClass('valid').removeClass('invalid').find('.warning:visible').hide();
							});
							warningtext= $('message',xml).text();
							$('id',xml).each(function() {
								$($(this).text()).parents('.inputfield').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
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
			$('#mysqldatabasename, #mysqldatabasehost, #mysqldatabasepass, #mysqldatabaseuser, #tableprefix').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
	},
	
	validDBCredentials: function() {
		$('#mysqldatabasename, #mysqldatabasehost, #mysqldatabasepass, #mysqldatabaseuser, #tableprefix').each(function() {
			$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$(this).parents('.installstep').addClass('done');
		});
		$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
		$('#siteconfiguration').children('.help-me').show();
		installer.checkSiteConfigurationCredentials();
	}
}

installer.pgsql = {
	checkDBFields : function() {
		if ( ( $('#pgsqldatabasehost').val() == '' ) || ( $('#pgsqldatabaseuser').val() == '' ) || ( $('#pgsqldatabasename').val() == '' ) ) {
			$('#check_db_connection').attr('disabled', true);
			$('#pgsqldatabasename, #pgsqldatabasehost, #pgsqldatabasepass, #pgsqldatabaseuser, #tableprefix').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('.installstep:eq(1)').removeClass('done');
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
		else {
			$('#check_db_connection').attr('disabled', false);
		}
	},

	checkDBCredentials : function() {
		if ( ( $('#pgsqldatabasehost').val() != '' ) && ( $('#pgsqldatabaseuser').val() != '' ) && ( $('#pgsqldatabasename').val() != '' ) ) {
			if (installer.verifyDB) {
				$.ajax({
					type: 'POST',
					url: habari.url.check_pgsql_credentials,
					data: { // Ask InstallHandler::ajax_check_pgsql_credentials to check the credentials
						ajax_action: 'check_pgsql_credentials',
						host: $('#pgsqldatabasehost').val(),
						database: $('#pgsqldatabasename').val(),
						user: $('#pgsqldatabaseuser').val(),
						pass: $('#pgsqldatabasepass').val(),
						table_prefix: $('#tableprefix').val()
					},
					success: function(xml) {
						$('#installerror').fadeOut();
						switch($('status',xml).text()) {
						case '0': // Show warning, fade the borders and hide the next step
							$('#pgsqldatabasename, #pgsqldatabasehost, #pgsqldatabasepass, #pgsqldatabaseuser, #tableprefix').each(function() {
								$(this).parents('.inputfield').addClass('valid').removeClass('invalid').find('.warning:visible').hide();
							});
							warningtext= $('message',xml).text();
							$('id',xml).each(function() {
								$($(this).text()).parents('.inputfield').addClass('invalid').find('.warning').html(warningtext).fadeIn();
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
			$('#pgsqldatabasename, #pgsqldatabasehost, #pgsqldatabasepass, #pgsqldatabaseuser, #tableprefix').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('.installstep:eq(1)').removeClass('done');
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
	},
	
	validDBCredentials: function() {
		$('#pgsqldatabasename, #pgsqldatabasehost, #pgsqldatabasepass, #pgsqldatabaseuser, #tableprefix').each(function() {
			$(this).parents('.inputfield').removeClass('invalid').addClass('valid').find('.warning:visible').fadeOut();
			$(this).parents('.installstep').addClass('done')
		});
		$('#siteconfiguration').children('.options').fadeIn().addClass('ready');
		$('#siteconfiguration').children('.help-me').show();
		installer.checkSiteConfigurationCredentials();
	}
}

installer.sqlite = {
	checkDBFields : function() {
		if ( ( $('#databasefile').val() == '' ) ) {
			$('#check_db_connection').attr('disabled', true);
			$('#databasefile, #tableprefix').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('.installstep:eq(1)').removeClass('done');
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
		else {
			$('#check_db_connection').attr('disabled', false)
		}
	},

	checkDBCredentials : function() {
		if ( $('#databasefile').val() != '' ) {
			if (installer.verifyDB) {
				$.ajax({
					type: 'POST',
					url: habari.url.check_sqlite_credentials,
					data: { // Ask InstallHandler::ajax_check_sqlite_credentials to check the credentials
						ajax_action: 'check_sqlite_credentials',
						file: $('#databasefile').val()
					},
					success: function(xml) {
						$('#installerror').fadeOut();
						switch($('status',xml).text()) {
						case '0': // Show warning, fade the borders and hide the next step
							$('#databasefile, #tableprefix').each(function() {
								$(this).parents('.inputfield').addClass('valid').removeClass('invalid').find('.warning:visible').hide();
							});
							warningtext= $('message',xml).text();
							$('id',xml).each(function() {
								$($(this).text()).parents('.inputfield').removeClass('valid').addClass('invalid').find('.warning').html(warningtext).fadeIn();
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
			$('#databasefile, #tableprefix').each(function() {
				$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').find('.warning:visible').fadeOut();
			});
			$('.installstep:eq(1)').removeClass('done');
			$('#siteconfiguration, #pluginactivation, #install').removeClass('ready').removeClass('done').children('.options').fadeOut();
			$('#siteconfiguration, #pluginactivation').children('.help-me').hide();
		}
	},

	validDBCredentials: function() {
		$('#databasefile, #tableprefix').each(function() {
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

/* compute the intersection of two arrays */
function intersect(a, b) {
	var ai=0, bi=0, result = new Array();

	while( ai < a.length && bi < b.length ) {
		if(a[ai] < b[bi] ) ai++;
		else if (a[ai] > b[bi] ) bi++;
		else {
			if(a[ai] != '') {
				result.push(a[ai]);
			}
			ai++; bi++;
		}
	}

	return result;
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
			$('.item.controls label').addClass('none').removeClass('all').text(_t('None selected'));
		} else if(count == $('.item:not(.hidden):not(.ignore):not(.controls) .checkbox input[type=checkbox]').length) {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 1;
			});
			$('.item.controls label').removeClass('none').addClass('all').text(_t('All selected'));
		} else {
			$('.item.controls input[type=checkbox]').each(function() {
				this.checked = 0;
			});
			$('.item.controls label').removeClass('none').removeClass('all').text(_t('%s selected', count));
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
	$('.help-items').click(function(){$(this).parents('.installstep').find('.items').fadeOut(function(){$(this).toggleClass('show-help').fadeIn()});$('html, body').animate({scrollTop: $("#themeselection").offset().top - 20}, 500);return false;});
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
//	installer.checkDBFields();
	$('#db_type').change(installer.setDatabaseType);
	$('#databasesetup input').keyup(function(){queueTimer(installer.checkDBFields)});
	$('#check_db_connection').click(function(){installer.checkDBCredentials()});
	$('#siteconfiguration input').keyup(function(){queueTimer(installer.checkSiteConfigurationCredentials)});
	$('#themeselection input').click(function(){queueTimer(installer.checkThemeConfiguration)});
	$('.plugin_selection').click(function(){queueTimer(installer.resolveFeatures)});
	$('#locale').focus().change(function() { $('#locale-form').submit();	});
});
