<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label for="<?php echo $id; ?>"><?php echo $this->caption; ?></label><input type="password" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($outvalue); ?>">
	<?php if($message != '') : ?>
		<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
