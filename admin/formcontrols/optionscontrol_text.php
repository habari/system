<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php
	if ( isset( $autocomplete ) ) {
		$autocomplete = 'autocomplete="' . $autocomplete . '"';
	}
	else {
		$autocomplete = '';
	}
?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field; ?>"><?php echo $caption; ?></label></span>
	<span class="pct25"><input type="<?php echo (isset($this->type)) ? $type : 'text'?>" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo Utils::htmlspecialchars( $value ); ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?> <?php echo $autocomplete; ?>></span>
	<?php if (!empty($helptext)) : ?>
	<span class="pct40 helptext"><?php echo $helptext; ?></span>
	<?php endif; ?>
	<?php if ($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>

