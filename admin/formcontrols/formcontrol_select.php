<div class="<?php echo $class ?>">
<label><?php echo $this->caption; ?><select name="<?php echo $field ?>">
<?php foreach($options as $opts_key => $opts_val) : ?>
	<?php if (is_array($opts_val)) : ?>
		<optgroup label="<?php echo $opts_key; ?>">
		<?php foreach($opts_val as $opt_key => $opt_val) : ?>
			<option value="<?php echo $opt_key; ?>" <?php echo ( ( $value == $opt_key ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($opt_val); ?></option>
		<?php endforeach; ?>
		</optgroup>
	<?php else : ?>
		<option value="<?php echo $opts_key; ?>" <?php echo ( ( $value == $opts_key ) ? ' selected' : '' ); ?>><?php echo htmlspecialchars($opts_val); ?></option>
	<?php endif; ?>
<?php endforeach; ?>
</select></label>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
