<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<?php echo Format::term_tree( $options, $control->name, $control->config ); ?>
	<input type="hidden" name="<?php echo $field; ?>_submitted" class="tree_submitted" value="1">
	<?php 
	
		if ( isset( $helptext ) && !empty( $helptext ) ) {
			?>
				<span class="helptext"><?php echo $helptext; ?></span>
			<?php
		}
	
	?>
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
