<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>

	<div class="container transparent">
<?php /*		<label for="<?php echo $id; ?>" class="incontent <?php echo $class; ?>"><?php echo $caption; ?></label>
		<input type="text" name="<?php echo $field; ?>" id="<?php echo $id; ?>" class="styledformelement text <?php echo $class; ?>" value="<?php echo Utils::htmlspecialchars($value); ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
*/?>	<label <?php
		echo $control->parameter_map(
			array(
				'title' => array('label_title', 'title'),
				'for' => 'field',
			)
		); ?>><?php echo $this->caption; ?></label>
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
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
	</div>
