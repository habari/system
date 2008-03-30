<div class="<?php echo $class ?>">
<?php foreach($options as $key => $text) : ?>
	<label><input type="radio" name="<?php echo $field; ?>" value="<?php echo $key; ?>"<?php echo ( ( $value == $key ) ? ' checked' : '' ); ?>><?php echo htmlspecialchars($text); ?></label>
<?php endforeach; ?>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
