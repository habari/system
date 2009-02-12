	<div class="container">
		<p>
			<label for="<?php echo $id; ?>" class="incontent textarea"><?php echo $caption; ?></label>
			<textarea name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement <?php echo $class; ?>" rows="20" cols="114" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>><?php echo htmlspecialchars($value); ?></textarea>
		</p>
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
	</div>