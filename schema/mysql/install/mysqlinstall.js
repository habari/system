habari.installer.registerSchema('mysql');
habari.installer.mysql = {
	checkDBCredentials: function() {
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
					default:
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
						$('#sitename').focus();
						break;
					}
				},
				error: handleAjaxError
			});
		}
		else {
			$('#databasesetup').removeClass('done');
			$('#siteconfiguration').children('.options').fadeOut().removeClass('ready').removeClass('done');
			$('#install').children('.options').fadeOut().removeClass('ready').removeClass('done');
		}
	}
};