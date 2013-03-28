<select <?= $_attributes ?>>
	<?php foreach( $options as $opts_key => $opts_val ) : ?>
		<?php if ( is_array( $opts_val ) ) : ?>
			<optgroup label="<?php echo $opts_key; ?>">
				<?php foreach( $opts_val as $opt_key => $opt_val ) : ?>
					<option value="<?php echo $opt_key; ?>"<?php echo ( in_array( $opt_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo \Habari\Utils::htmlspecialchars( $opt_val ); ?></option>
				<?php endforeach; ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo $opts_key; ?>"<?php echo ( in_array( $opts_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo \Habari\Utils::htmlspecialchars( $opts_val ); ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
</select>