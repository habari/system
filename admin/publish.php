<?php
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<?php include('header.php'); ?>

	<?php $form->out(); ?>


<script type="text/javascript">
$(document).ready(function(){


	// Submit when the publish button is clicked.
	$('#publish').click( function() {
		$('#create_content').submit();
	});

	$('#create_content').submit(function(){
		$('.check-change').each(function() {
			$(this).data('checksum', crc32($(this).val()));
		});
	});

	$('.check-change').each(function() {
		$(this).data('checksum', crc32($(this).val()));
		$(this).data('oldvalue', $(this).val());
	});

	window.onbeforeunload = function(){
		changed = false;
		$('.check-change').each(function() {
			if ($(this).data('checksum') != crc32($(this).val())) {
				console.log([$(this).data('oldvalue'), $(this).val()]);
				changed = true;
			}
		});
		if (changed) {
			spinner.start(); spinner.stop();
			return '<?php
				// Note to translators: the 'new-line character' is an actual "\n" not a new-line character
				_e('You did not save the changes you made. \nLeaving this page will result in the loss of data.');
				?>';
		}
	};

});
</script>

<?php include('footer.php'); ?>
