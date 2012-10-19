<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
<?php foreach( $control->options as $key => $text ) : ?>
	<input type="radio" <?php
		echo $control->parameter_map(
			array(
				'title' => array( 'control_title', 'title' ),
				'tabindex', 'disabled', 'readonly',
				'name' => 'field',
			),
			array(
				'value' => $key,
				'id' => Utils::slugify( $key, '_' )
			)
		);
		?><?php echo ( ( $control->value == $key ) ? ' checked' : '' ); ?>>
	<label for="<?php echo Utils::slugify( $key ); ?>"><?php echo Utils::htmlspecialchars( $text ); ?></label>
<?php endforeach; ?>
<?php 

	if ( isset( $control->helptext ) && !empty( $control->helptext ) ) {
		?>
			<span class="helptext"><?php echo $control->helptext; ?></span>
		<?php
	}

		?>
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
</div>
