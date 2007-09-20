function setDatabaseType(el)
{
	$('.forsqlite').hide();
	$('.formysql').hide();
	
	switch($(el).fieldValue()) {
		case 'mysql':
			$('.formysql').show();
			break;
		case 'sqlite':
			$('.forsqlite').show();
			break;
	}
}

function checkField() 
{
	if ($(this).val() == '') {
		showwarning = false;
		fieldclass = 'normal';
	}
	else {
		showwarning = false;
		fieldclass = 'valid';
		
		// These checks should be done via an ajax call
		switch($(this).attr('id')) {
			case 'databasename':
				/*
				if($(this).val() != 'habari') {
				showwarning = true;
				warningtext = 'Habari could not find a database with that name on the server. <br />Please specify the name of an existing database. <a href="#" taborder="0">Learn More...</a>';
				}
				*/
				break;
			case 'databasehost':
				/*
				if($(this).val() != 'localhost') {
				showwarning = true;
				warningtext = 'Habari could not find a MySQL server at the specified address. <br />Please provide a correct host name or address. <a href="#" taborder="0">Learn More...</a>';
				}
				*/
				break;
		}
		fieldclass = showwarning ? 'invalid' : 'valid';
	}
	
	
	if(showwarning == false) {
		$(this).parents('.inputfield').find('.warning:visible').fadeOut();
	}
	if(showwarning == true) {
		$(this).parents('.inputfield').find('.warning:hidden').html(warningtext).fadeIn();
	}
	$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass(fieldclass);
}

$(document).ready(function() {
	$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
	$('.help').hide();
	//$('.ready').removeClass('ready');
	$('.installstep:first').addClass('ready');
	setDatabaseType($('#databasetype'));
	$('.javascript-disabled').hide();
	
	$('#databasehost').blur(checkField);
	$('#databasename').blur(checkField);
	});