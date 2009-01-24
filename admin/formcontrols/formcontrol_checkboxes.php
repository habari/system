<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<ul>
<?php foreach($options as $key => $text) : ?>
	<li>
		<label><input type="checkbox" name="<?php echo $field; ?>[]" value="<?php echo $key; ?>"<?php echo ( in_array( $key, (array) $value ) ? ' checked' : '' ); ?>><?php echo htmlspecialchars($text); ?></label>
	</li>
<?php endforeach; ?>
</ul>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>