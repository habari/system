<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="container<?php echo ($class) ? ' ' . $class : ''?>">
	<p class="pct25"><label for="<?php echo $id; ?>"><?php echo $this->caption; ?></label></p>
	<p class="pct75">
		<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $field; ?>" class="styledformelement" value="1" <?php echo $value ? 'checked' : ''; ?>>
		<input type="hidden" name="<?php echo $field; ?>_submitted" value="1" >
	</p>
</div>
