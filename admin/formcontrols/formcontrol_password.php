<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label for="<?php echo $field; ?>"><?php echo $this->caption; ?></label><input type="password" name="<?php echo $field; ?>" id="<?php echo $field; ?>" <?php if ( isset( $size ) ) { ?> size="<?php echo $size; ?>"<?php } ?><?php if ( isset( $tabindex ) ) { ?> tabindex="<?php echo $tabindex; ?>"<?php } ?> value="<?php echo Utils::htmlspecialchars($outvalue); ?>">
	<?php 
	
		if ( isset( $helptext ) && !empty( $helptext ) ) {
			?>
				<span class="helptext"><?php echo $helptext; ?></span>
			<?php
		}
	
	?>
	<?php if ($message != '') : ?>
		<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
