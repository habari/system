<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="container">
	<fieldset<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
		<legend><?php echo $caption; ?></legend>
		<?php echo $contents; ?>
	</fieldset>
</div>