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
	<input type="file" <?php
		echo $control->parameter_map(
			array(
				'title' => array( 'control_title', 'title' ),
				'tabindex', 'disabled', 'readonly',
				'id' => 'field',
				'name' => 'field',
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