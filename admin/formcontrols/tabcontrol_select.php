<div class="container<?php echo ($class) ? ' ' . $class : ''?>">
	<p class="pct25"><label for="<?php echo $id; ?>"><?php echo $this->caption; ?></label></p>
	<p class="pct75">
	 	<select class="longselect" id="<?php echo $id; ?>" name="<?php echo $field . ( $multiple ? '[]' : '' ); ?>"<?php echo ( $multiple ? ' multiple="multiple" size="' . intval($size) . '"' : '' ) ?>>
<?php foreach($options as $opts_key => $opts_val) : ?>
	<?php if (is_array($opts_val)) : ?>
		<optgroup label="<?php echo $opts_key; ?>">
		<?php foreach($opts_val as $opt_key => $opt_val) : ?>
			<option value="<?php echo $opt_key; ?>"<?php echo ( in_array( $opt_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($opt_val); ?></option>
		<?php endforeach; ?>
		</optgroup>
	<?php else : ?>
		<option value="<?php echo $opts_key; ?>"<?php echo ( in_array( $opts_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($opts_val); ?></option>
	<?php endif; ?>
<?php endforeach; ?>
</select>
	</p>
</div>
