<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field ?>"><?php echo $this->caption; ?></label></span>
	<span class="pct25"><input type="checkbox" name="<?php echo $field; ?>" value="1" <?php echo $value ? 'checked' : ''; ?>></span>
	<input type="hidden" name="<?php echo $field; ?>_submitted" value="1" >
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
