	<div class="container">
		<p>
			<label for="<?php echo $id; ?>" class="incontent"><?php echo $caption; ?></label>
			<textarea name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement <?php echo $class; ?>" rows="20" cols="114" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>'><?php echo $value; ?></textarea>
		</p>
		<?php if($message != '') : ?>
		<p class="error"><?php echo $message; ?></p>
		<?php endif; ?>
	</div>
