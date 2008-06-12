<?php include('header.php'); ?>


<form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">

<div class="create">

	<?php if(!$newpost): ?>
	<div class="container">
		<a href="<?php echo $post->permalink; ?>" class="viewpost"><?php _e('View Post'); ?></a>
	</div>
	<?php endif; ?>

	<?php $form->out(); ?>

</div>

</form>

<script type="text/javascript">
$(document).ready(function(){
	<?php if(isset($statuses['published']) && $post->status != $statuses['published']) : ?>
	$('#right_control_set').append($('<input type="submit" name="submit" id="publish" class="publish" value="<?php _e('Publish'); ?>">'));
	$('#publish').click(function(){
		$('#status').val(<?php echo $statuses['published']; ?>);
	});
	<?php endif; ?>
	<?php if(isset($post->slug) && ($post->slug != '')) : ?>
	$('#left_control_set').append($('<input type="submit" name="submit" id="delete" class="delete" value="<?php _e('Delete'); ?>">'));
	$('#delete').click(function(){
		$('#create-content')
			.append($('<input type="hidden" name="nonce" value="<?php echo $wsse['nonce']; ?>"><input type="hidden" name="timestamp" value="<?php echo $wsse['timestamp']; ?>"><input type="hidden" name="digest" value="<?php echo $wsse['digest']; ?>">'))
			.attr('action', '<?php URL::out( 'admin', array('page' => 'delete_post', 'slug' => $post->slug )); ?>');
	});
	<?php endif; ?>
});
</script>

<?php include('footer.php'); ?>
