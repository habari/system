<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<label for="<?php echo $field; ?>"><?php echo $this->caption; ?></label><input type="password" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo Utils::htmlspecialchars($outvalue); ?>">
	<?php if ($message != '') : ?>
		<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
