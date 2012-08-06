<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<fieldset<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<legend><?php echo $caption; ?></legend>
	<?php echo $contents; ?>
</fieldset>
