<label><?php echo $this->caption; ?><textarea name="<?php echo $field; ?>"<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : 
''?><?php echo isset( $rows ) ? " rows=\"$rows\"" : ''; ?><?php echo isset( $cols ) ? " cols=\"$cols\"" : ''; ?>><?php echo htmlspecialchars($value); ?></textarea></label>
<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
