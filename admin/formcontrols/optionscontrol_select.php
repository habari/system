<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
	<span class="pct25"><label for="<?php echo $field ?>"><?php echo $this->caption; ?></label></span>
	<span class="pct25"><select name="<?php echo $field . ( $multiple ? '[]' : '' ); ?>"<?php echo ( $multiple ? ' multiple="multiple" size="' . intval($size) . '"' : '' ) ?> <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
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
	</select></span>
	<span class="pct50 helptext"><?php echo $helptext; ?></span>
	<?php if($message != '') : ?>
	<p class="error"><?php echo $message; ?></p>
	<?php endif; ?>
</div>
