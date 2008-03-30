<div class="<?php echo $class ?>">
<label><?php echo $this->caption; ?><select name="<?php echo $field ?>">
<?php foreach($options as $key => $text) : ?>
	<option value="<?php echo $key; ?>" <?php echo ( ( $value == $key ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($text); ?></option>
<?php endforeach; ?>
</select></label>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
