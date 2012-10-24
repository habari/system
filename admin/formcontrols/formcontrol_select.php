<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
	<label <?php
		echo $control->parameter_map(
			array(
				'title' => array( 'label_title', 'title' ),
				'for' => 'field',
			)
		); ?>><?php echo $control->caption; ?></label>
	<select <?php
		echo $control->parameter_map(
			array(
				'tabindex', 'disabled', 'readonly', 'multiple',
				'id' => 'field',
			),
			array(
				'name' => ( $control->multiple ) ? $control->field . '[]' : $control->field,
				'size' => ( $control->multiple ) ? $control->size : '1',
			)
		);
		?>>
	<?php foreach( $control->options as $opts_key => $opts_val ) : ?>
		<?php if ( is_array( $opts_val ) ) : ?>
			<optgroup label="<?php echo $opts_key; ?>">
			<?php foreach( $opts_val as $opt_key => $opt_val ) : ?>
				<option value="<?php echo $opt_key; ?>"<?php echo ( in_array( $opt_key, (array) $control->value ) ? ' selected' : '' ); ?>><?php echo Utils::htmlspecialchars( $opt_val ); ?></option>
			<?php endforeach; ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo $opts_key; ?>"<?php echo ( in_array( $opts_key, (array) $control->value ) ? ' selected' : '' ); ?>><?php echo Utils::htmlspecialchars( $opts_val ); ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
	</select>
	<?php 

		if ( isset( $control->helptext ) && !empty( $control->helptext ) ) {
			?>
				<span class="helptext"><?php echo $control->helptext; ?></span>
			<?php
		}

		?>
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
</div>