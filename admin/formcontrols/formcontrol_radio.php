<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<?php foreach($options as $key => $text) : ?>
	<input type="radio" name="<?php echo $field; ?>" id="<?php echo Utils::slugify($key, '_'); ?>" value="<?php echo $key; ?>"<?php echo ( ( $value == $key ) ? ' checked' : '' ); ?>><label for="<?php echo Utils::slugify($key); ?>"><?php echo Utils::htmlspecialchars($text); ?></label>
<?php endforeach; ?>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
