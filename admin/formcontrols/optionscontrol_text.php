<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<span class="pct25"><label <?php
		echo $control->parameter_map(
			array(
				'title' => array('label_title', 'title'),
				'for' => 'field',
			)
		); ?>><?php echo $this->caption; ?></label></span>
	<span class="pct25"><input <?php
		echo $control->parameter_map(
			array(
				'title' => array('control_title', 'title'),
				'tabindex', 'size', 'maxlength', 'type', 'placeholder', 'autocomplete', 'disabled', 'readonly',
				'id' => 'field',
				'name' => 'field',
			),
			array(
				'value' => Utils::htmlspecialchars( $value ),
			)
		);
		?>></span>
	<?php if ( ! empty( $helptext ) ) : ?>
	<span class="pct40 helptext"><?php echo $helptext; ?></span>
	<?php endif; ?>
	<?php $control->errors_out('<p class="error">%s</p>'); ?>
</div>