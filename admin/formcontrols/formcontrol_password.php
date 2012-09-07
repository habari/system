<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<label <?php
		echo $control->parameter_map(
			array(
				'title' => array( 'label_title', 'title' ),
				'for' => 'field',
			)
		); ?>><?php echo $control->caption; ?></label>
	<input type="password" <?php
		echo $control->parameter_map(
			array(
				'title' => array( 'control_title', 'title' ),
				'tabindex', 'size', 'maxlength', 'placeholder', 'autocomplete', 'disabled', 'readonly',
				'id' => 'field',
				'name' => 'field',
			),
			array(
				'value' => Utils::htmlspecialchars( $control->outvalue ),
			)
		);
		?>>
		<?php
		
			if ( isset( $control->helptext ) && !empty( $control->helptext ) ) {
				?>
					<span class="helptext"><?php echo $control->helptext; ?></span>
				<?php
			}
		
		?>
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
</div>