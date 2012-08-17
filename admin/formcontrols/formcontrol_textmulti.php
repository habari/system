<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
<p><?php echo $this->caption; ?></p>
<?php
if (!is_array($value)) {
	$value = array($value);
}
$i = 0;
foreach($value as $value_1) :
$i++;
	if ( $value_1 ) :
 ?>
	<span class="textmulti_item"><input type="text" name="<?php echo $field; ?>[]" id="<?php echo $field . '_' . $i; ?>" value="<?php echo Utils::htmlspecialchars($value_1); ?>"> <a href="#" onclick="return controls.textmulti.remove(this);" title="<?php _e( 'remove' ); ?>" class="textmulti_remove opa50">[<?php _e( 'remove' ); ?>]</a></span>
<?php
	endif;
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?php echo $field; ?>');" class="textmulti_add opa50" title="<?php _e( 'add' ); ?>">[<?php _e( 'add' ); ?>]</a>
<?php 

	if ( isset( $helptext ) && !empty( $helptext ) ) {
		?>
			<span class="helptext"><?php echo $helptext; ?></span>
		<?php
	}

?>
</div>
