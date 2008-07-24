<?php include('header.php'); ?>


<form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">

<div class="create">


	<?php $form->out(); ?>

</div>

</form>

<script type="text/javascript">
$(document).ready(function(){
	<?php if(isset($post->slug) && ($post->slug != '')) : ?>
	$('.container.buttons').prepend($('<input type="submit" name="submit" id="delete" class="button delete" value="<?php _e('Delete'); ?>">'));
	$('#delete').click(function(){
		$('#create-content')
			.append($('<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"><input type="hidden" name="digest" value="<?php echo $wsse['digest']; ?>">'))
			.attr('action', '<?php URL::out( 'admin', array('page' => 'delete_post', 'slug' => $post->slug )); ?>');
	});
	<?php endif; ?>
	<?php if(isset($statuses['published']) && $post->status != $statuses['published']) : ?>
	$('.container.buttons').prepend($('<input type="submit" name="submit" id="publish" class="button publish" value="<?php _e('Publish'); ?>">'));
	$('#publish').click(function(){
		$('#status').val(<?php echo $statuses['published']; ?>);
	});
	<?php endif; ?>
});
</script>

<?php include('footer.php'); ?>
