<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
<p><?php echo $this->caption; ?></p>
<?php
if (!is_array($value)) {
	$value = array($value);
}
$i = 0;
foreach($value as $value_1) :
$i++;
?>
	<input type="text" name="<?php echo $field; ?>[]" id="<?php echo $field . '_' . $i; ?>" value="<?php echo Utils::htmlspecialchars($value_1); ?>"> <label for="<?php echo $field . '_' . $i; ?>"><a href="#" onclick="return controls.textmulti.remove(this);">[<?php _e('remove'); ?>]</a></label>
<?php
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?php echo $field; ?>');">[<?php _e('add'); ?>]</a>
</div>
