<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php'); ?>

<div class="create">

	<?php $form->out(); ?>

</div>

<script type="text/javascript">
$(document).ready(function(){
	$('.container').addClass('transparent');
	// If this post has been saved, add a delete button and a nonce for authorising deletes
	<?php if ( isset( $post->id ) && ( $post->id != '' ) && ACL::access_check( $post->get_access(), 'delete' ) ) : ?>
	$('.container.buttons').prepend($('<input type="button" id="delete" class="button delete" tabindex="6" value="<?php _e('Delete'); ?>">'));
	$('#delete').click(function(){
		$('#create-content')
			.append($('<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"><input type="hidden" name="digest" value="<?php echo $wsse['digest']; ?>">'))
			.attr('action', '<?php URL::out( 'admin', array('page' => 'delete_post', 'id' => $post->id )); ?>')
			.submit();
	});
	<?php endif; ?>

	// If the post hasn't been published, add a publish button
	<?php
		$show_publish = ( $post->id == 0 && User::identify()->can_any( array( 'own_posts' => 'create', 'post_any' => 'create', 'post_' . Post::type_name( $post->content_type ) => 'create' ) ) ) || ( $post->id != 0 && ACL::access_check( $post->get_access(), 'edit' ) );
		if ( isset( $statuses['published'] ) && $post->status != $statuses['published'] && $show_publish ) :
	?>
	$('.container.buttons').prepend($('<input type="button" id="publish" class="button publish" tabindex="5" value="<?php _e('Publish'); ?>">'));
	$('#publish').click( function() {
		$('#status').val(<?php echo $statuses['published']; ?>);
	});
	<?php endif; ?>

	// Submit when the publish button is clicked.
	$('#publish').click( function() {
		$('#create-content').submit();
	});

	$('#create-content').submit(function(){
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
