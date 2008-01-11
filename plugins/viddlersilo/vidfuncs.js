$(document).ready(function() {
	$('#vupload').click(function() {
		$('.media').hide();
		$('#viddlerrecord').hide();
		$('#viddlerform').animate({ "opacity": "toggle" }, "slow");
		return false;
	});
	
	$('#vrecord').click(function() {
		$('.media').hide();
		$('#viddlerform').hide();
		$('#viddlerrecord').animate({ "opacity": "toggle" }, "slow");
		return false;
	});
	
	$('#vstream').click(function() {
		$('#viddlerform').hide();
		$('#viddlerrecord').hide();
		$('.media').show();
		return false;
	});
});