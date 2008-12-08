<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label><?php echo $this->caption; ?></label>
	<input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>">
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
