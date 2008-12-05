<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<ul>
<?php foreach($options as $key => $text) : ?>
	<li>
		<input type="checkbox" name="<?php echo $field; ?>[]" value="<?php echo $key; ?>"<?php echo ( in_array( $key, (array) $value ) ? ' checked' : '' ); ?>><label><?php echo htmlspecialchars($text); ?></label>
	</li>
<?php endforeach; ?>
</ul>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
