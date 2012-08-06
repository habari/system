<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="container ' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<label for="<?php echo $field; ?>" class="incontent formcontrol abovecontent"><?php echo $this->caption; ?></label>
<select
	id="<?php echo $field; ?>"
	name="<?php echo $field . ( $multiple ? '[]' : '' ); ?>"
	<?php echo ( $multiple ? ' multiple="multiple" size="' . intval($size) . '"' : '' ); ?>
	<?php echo ( isset($disabled) && $disabled ? ' disabled="disabled"' : ''); ?>
>
<?php foreach($options as $opts_key => $opts_val) : ?>
	<?php if (is_array($opts_val)) : ?>
		<optgroup label="<?php echo $opts_key; ?>">
		<?php foreach($opts_val as $opt_key => $opt_val) : ?>
			<option value="<?php echo $opt_key; ?>"<?php echo ( in_array( $opt_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo Utils::htmlspecialchars($opt_val); ?></option>
		<?php endforeach; ?>
		</optgroup>
	<?php else : ?>
		<option value="<?php echo $opts_key; ?>"<?php echo ( in_array( $opts_key, (array) $value ) ? ' selected' : '' ); ?>><?php echo Utils::htmlspecialchars($opts_val); ?></option>
	<?php endif; ?>
<?php endforeach; ?>
</select>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
