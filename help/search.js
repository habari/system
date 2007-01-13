/* search help */

function dosearch(e)
{
	var search = $('#search').val();
	if(e.keyCode == 13) {
	
		$.ajaxSetup({type: 'GET'});	
		$('content').load("keywords.js", "foo");
	
		$.ajax({ 
			type: "GET", 
			url: "keywords.js",
			global: false,
			datatype: 'json', 
			success: function(msg){ 
				alert( "Data Saved: " + msg ); 
			} 
		});
	/*
		$.getJSON('keywords.js', {}, function(json) {
			var results = new Array();
			for(var z in json.terms) {
				if(json.terms[z][0] == search) {
					results.push(json.terms[z][1]);
				}
			}	
			alert(results[0]);		
		});
		*/
	}
}


$('document').ready(function() {
	$('#search').keypress(dosearch);
	$('#search_help').show();
} );
