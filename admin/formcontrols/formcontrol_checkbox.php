<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<label for="<?php echo $field; ?>"><?php echo $this->caption; ?></label>
	<input type="checkbox" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="1" <?php echo $value ? 'checked' : ''; ?>>
	<input type="hidden" name="<?php echo $field; ?>_submitted" value="1" >
	<?php 
	
		if ( isset( $helptext ) && !empty( $helptext ) ) {
			?>
				<span class="helptext"><?php echo $helptext; ?></span>
			<?php
		}
	
	?>
	<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
