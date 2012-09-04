<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<input type="submit" <?php
		echo $control->parameter_map(
			array(
				'tabindex', 'disabled', 'readonly',
				'id' => 'field',
				'name' => 'field',
			),
			array(
				'value' => Utils::htmlspecialchars( $control->caption ),
			)
		);
		?>>
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
</div>
