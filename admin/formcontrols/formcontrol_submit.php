<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="submit"<?php if(isset($disabled) && $disabled) { ?>disabled <?php } if ( isset( $tabindex ) ) { ?> tabindex="<?php echo $tabindex; ?>"<?php } ?> name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($caption, ENT_COMPAT, 'UTF-8'); ?>">
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
