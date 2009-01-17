<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label><?php echo $this->caption; ?></label>
	<input type="checkbox" name="<?php echo $field; ?>" value="1" <?php echo $value ? 'checked' : ''; ?>>
	<input type="hidden" name="<?php echo $field; ?>_submitted" value="1" >
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>