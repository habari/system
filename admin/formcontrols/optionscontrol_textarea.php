<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field; ?>"><?php echo $caption; ?></label></span>
	<span class="pct50"><textarea name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="resizable"<?php 
		echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>><?php echo Utils::htmlspecialchars( $value ); ?></textarea></span>
	<?php if ($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
