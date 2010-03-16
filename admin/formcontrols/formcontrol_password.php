<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label for="<?php echo $field; ?>"><?php echo $this->caption; ?></label><input type="password" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo htmlspecialchars($outvalue, ENT_COMPAT, 'UTF-8'); ?>">
	<?php if($message != '') : ?>
		<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
