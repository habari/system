<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label <?php echo isset( $title ) ? " title=\"$title\"" : ''; ?>><?php echo $this->caption; ?></label>
	<input <?php echo isset( $title ) ? " title=\"$title\"" : ''; ?> type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>" >
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>