<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<?php echo Format::term_tree( $control->options, $control->name, $control->config ); ?>
	<input type="hidden" name="<?php echo $field; ?>_submitted" class="tree_submitted" value="1">
	<?php 
	
		if ( isset( $control->helptext ) && !empty( $control->helptext ) ) {
			?>
				<span class="helptext"><?php echo $control->helptext; ?></span>
			<?php
		}
	
	?>
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
