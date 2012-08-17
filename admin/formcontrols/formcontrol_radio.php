<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
<?php foreach($options as $key => $text) : ?>
	<input type="radio" name="<?php echo $field; ?>" id="<?php echo Utils::slugify($key, '_'); ?>" value="<?php echo $key; ?>"<?php echo ( ( $value == $key ) ? ' checked' : '' ); ?>><label for="<?php echo Utils::slugify($key); ?>"><?php echo Utils::htmlspecialchars($text); ?></label>
<?php endforeach; ?>
<?php 

	if ( isset( $helptext ) && !empty( $helptext ) ) {
		?>
			<span class="helptext"><?php echo $helptext; ?></span>
		<?php
	}

?>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
