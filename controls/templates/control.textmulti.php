<div <?= $_attributes ?>>
<?php
foreach( $value as $v ) :
	?>
		<div>
			<input type="text" name="<?= $_control->name . '[]' ?>" value="<?= Habari\Utils::htmlspecialchars( $v ) ?>" class="textmulti_input">
			<a href="#" onclick="return controls.textmulti.remove(this);" title="<?php _e( 'remove' ); ?>" class="textmulti_remove">[<?php _e( 'remove' ); ?>]</a></span>
		</div>
	<?php
endforeach;
?>
<a href="#" onclick="return controls.textmulti.add(this, '<?= $_control->name; ?>');" class="textmulti_add" title="<?php _e( 'add' ); ?>"><?php _e( '[add]' ); ?></a>
</div>