<div<?php echo ($class) ? ' class="slider ' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field; ?>"><?php echo $caption; ?></label></span>
	<span class="pct50 slider"><span class="hidden data min"><?php echo $min; ?></span><span class="hidden data max"><?php echo $max; ?></span><span class="hidden data step"><?php echo $step; ?></span></span>
	<span class="pct50 text"><input type="<?php echo (isset($this->type)) ? $type : 'text'?>" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="<?php echo $value; ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>></span>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>