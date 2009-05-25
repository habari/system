<div<?php echo ($class) ? ' class="image ' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field; ?>"><?php echo $caption; ?></label></span>
	<?php Utils::debug($value); ?>
	<span class="pct50 image"><a href="#" title="<?php _t("Change or reset image"); ?>"><img src="<?php echo $value; ?>" alt="<?php echo $caption; ?>" height="50"></a></span>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>