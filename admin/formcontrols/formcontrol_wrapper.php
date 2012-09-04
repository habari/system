<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
	echo $control->parameter_map(
		array(
			'class', 'id' => 'name'
		)
	); ?>>
	<?php echo $contents; ?>
</div>
