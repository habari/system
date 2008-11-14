<div class="inputfield<?php echo ($class) ? ' ' . $class : ''; ?>">
	<label for="<?php echo $id; ?>"><?php echo $this->caption; ?><?php echo (isset($required) && $required) ? ' <strong>*</strong>' : ''?></label>
	<input type="text" id="<?php echo $id; ?>" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>">
	<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/themes/installer/images/ready.png" alt="">
	<div class="warning"><?php if($message != '') : ?><?php echo $message; ?><?php endif; ?></div>
	<div class="help">
		<?php echo $help; ?>
	</div>
</div>