<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php
		echo $control->parameter_map(
			array(
				'class', 'id' => 'name'
			)
		); ?>>
<p><?php echo $control->caption; ?></p>
<?php
if ( !is_array( $control->value ) ) {
	$value = array( $control->value );
}
$i = 0;
foreach( $value as $value_1 ) :
$i++;
	if ( $value_1 ) :
 ?>
	<span class="textmulti_item">
		<input <?php
		echo $control->parameter_map(
			array(
				'tabindex', 'size', 'maxlength', 'autocomplete', 'disabled', 'readonly',
			),
			array(
				'name' => $control->field . '[]',
				'id' => $control->field . '_' . $i,
				'value' => Utils::htmlspecialchars( $value_1 ),
			)
		);
		?>>	<a href="#" onclick="return controls.textmulti.remove( this );" title="<?php _e( 'remove' ); ?>" class="textmulti_remove opa50">[<?php _e( 'remove' ); ?>]</a></span>
<?php
	endif;
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?php echo $field; ?>');" class="textmulti_add opa50" title="<?php _e( 'add' ); ?>">[<?php _e( 'add' ); ?>]</a>
<?php

	if ( isset( $control->helptext ) && !empty( $control->helptext ) ) {
		?>
			<span class="helptext"><?php echo $control->helptext; ?></span>
		<?php
	}

?>
	<?php $control->errors_out( '<li>%s</li>', '<ul class="error">%s</ul>' ); ?>
</div>