<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $id; ?>"><?php echo $caption; ?></label></span>
	<span class="pct25"><input type="text" name="<?php echo $field; ?>" value="<?php echo $value; ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>></span>
	<span class="pct50 helptext"><?php echo $helptext; ?></span>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>

