<div class="inputfield formcontrol<?php echo ($class) ? ' ' . $class : ''; ?>">
	<?php echo $contents; ?>
	<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
	<div class="warning"><?php if($message != '') : ?><?php echo $message; ?><?php endif; ?></div>
	<div class="help">
		<?php echo $help; ?>
	</div>
</div>