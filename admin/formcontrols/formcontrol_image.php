<div<?php echo ($class) ? ' class="image ' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25 label"><label for="<?php echo $field; ?>"><?php echo $caption; ?></label></span>
	<span class="pct50 image"><a href="#" title="<?php _t("Change or reset image"); ?>"><img src="<?php echo $value; ?>" alt="<?php echo $caption; ?>" height="50"></a></span>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
	<div class="picker">
		<p>HI!</p>
	</div>
</div>