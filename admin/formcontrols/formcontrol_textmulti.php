<div class="<?php echo $class ?>">
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
<p><?php echo $this->caption; ?></p>
<?php
if(!is_array($value)) {
	$value = array($value);
}
foreach($value as $value_1) :
?>
	<label><input type="text" name="<?php echo $field; ?>[]" value="<?php echo htmlspecialchars($value_1); ?>"> <a href="#" onclick="return controls.textmulti.remove(this);">[<?php _e('remove'); ?>]</a></label>
<?php
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?php echo $field; ?>');">[<?php _e('add'); ?>]</a>
</div>
