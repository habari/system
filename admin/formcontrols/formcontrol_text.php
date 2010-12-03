<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label><?php echo $this->caption; ?></label>
	<input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>">
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>