<?php include('header.php'); ?>

<div class="create">

	<?php $form->out(); ?>

</div>

<script type="text/javascript">
$(document).ready(function(){
	$('.container').addClass('transparent');
	<?php if(isset($post->id) && ($post->id != '')) : ?>
	$('.container.buttons').prepend($('<input type="submit" name="submit" id="delete" class="button delete" tabindex="6" value="<?php _e('Delete'); ?>">'));
	$('#delete').click(function(){
		$('#create-content')
			.append($('<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"><input type="hidden" name="digest" value="<?php echo $wsse['digest']; ?>">'))
			.attr('action', '<?php URL::out( 'admin', array('page' => 'delete_post', 'id' => $post->id )); ?>');
	});
	<?php endif; ?>

	<?php if(isset($statuses['published']) && $post->status != $statuses['published']) : ?>
	$('.container.buttons').prepend($('<input type="submit" name="submit" id="publish" class="button publish" tabindex="5" value="<?php _e('Publish'); ?>">'));
	$('#publish').click( function() {
		$('#status').val(<?php echo $statuses['published']; ?>);
	});
	<?php endif; ?>
});
</script>

<?php include('footer.php'); ?>
