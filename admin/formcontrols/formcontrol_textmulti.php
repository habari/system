<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
<p><?php echo $this->caption; ?></p>
<?php
if(!is_array($value)) {
	$value = array($value);
}
$i = 0;
foreach($value as $value_1) :
$i++;
?>
	<input type="text" name="<?php echo $field; ?>[]" id="<?php echo $field . '_' . $i; ?>" value="<?php echo htmlspecialchars($value_1, ENT_COMPAT, 'UTF-8'); ?>"> <label for="<?php echo $field . '_' . $i; ?>"><a href="#" onclick="return controls.textmulti.remove(this);">[<?php _e('remove'); ?>]</a></label>
<?php
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?php echo $field; ?>');">[<?php _e('add'); ?>]</a>
</div>
