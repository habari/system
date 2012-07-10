<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
<ul>
<?php foreach($options as $key => $text) : ?>
	<li>
		<label for="<?php echo Utils::slugify($key); ?>"><input type="checkbox" name="<?php echo $field; ?>[]" id="<?php echo Utils::slugify($key); ?>" value="<?php echo $key; ?>"<?php echo ( in_array( $key, (array) $value ) ? ' checked' : '' ); ?>><?php echo Utils::htmlspecialchars($text); ?></label>
	</li>
<?php endforeach; ?>
</ul>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
<?php $control->errors_out('<li>%s</li>', '<ul class="error">%s</ul>'); ?>
</div>
