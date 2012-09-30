<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
	<div class="container">
		<label for="<?php echo $id; ?>" class="incontent textarea"><?php echo $caption; ?></label>
		<textarea name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement <?php echo $class; ?>" rows="<?php echo isset( $rows ) ? $rows : 20; ?>" cols="<?php echo isset( $cols ) ? $cols : 114; ?>" 
<?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>><?php echo $control->raw ? Utils::htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false ): Utils::htmlspecialchars($value); ?></textarea>
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
	</div>
