<?php 
namespace Habari;
if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); }
?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<label <?php
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
				'tabindex', 'size', 'maxlength', 'type',
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