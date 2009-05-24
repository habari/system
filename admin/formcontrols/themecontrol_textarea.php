<div<?php echo ($class) ? ' class="textarea ' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field; ?>"><?php echo $caption; ?></label></span>
	<span class="pct50"><textarea name="<?php echo $field; ?>"<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : 
	''?><?php echo isset( $rows ) ? " rows=\"$rows\"" : ''; ?><?php echo isset( $cols ) ? " cols=\"$cols\"" : ''; ?>><?php echo htmlspecialchars($value); ?></textarea></span>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>