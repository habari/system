
	<div class="container">
		<p>
			<label for="<?php echo $id; ?>" class="incontent"><?php echo $caption; ?></label>
			<input type="text" name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement text <?php echo $class; ?>" size="100%" value="<?php echo $value; ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
		</p>
		<?php if($message != '') : ?>
		<p class="error"><?php echo $message; ?></p>
		<?php endif; ?>
	</div>

