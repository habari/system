
	<div class="container">
		<p>
			<label for="<?php echo $id; ?>" class="incontent <?php echo $class; ?>"><?php echo $caption; ?></label>
			<input type="text" name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement text <?php echo $class; ?>" size="100%" value="<?php echo htmlspecialchars($value); ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
		</p>
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
	</div>
