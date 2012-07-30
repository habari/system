<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
	echo $control->parameter_map(
		array(
			'class', 'id' => 'name'
		)
	); ?>>
	<input <?php
		echo $control->parameter_map(
			array(
				'title' => array('control_title', 'title'),
				'tabindex', 'size', 'maxlength', 'type', 'placeholder',
				'id' => 'field',
				'name' => 'field',
			),
			array(
				'value' => Utils::htmlspecialchars( $value ),
			)
		);
		?>>
	<label <?php
		echo $control->parameter_map(
			array(
				'title' => array('label_title', 'title'),
				'for' => 'field',
			)
		); ?>><?php echo $this->caption; ?></label>
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
</div>
