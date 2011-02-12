<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="submit"<?php if (isset($disabled) && $disabled) { ?>disabled <?php } if ( isset( $tabindex ) ) { ?> tabindex="<?php echo $tabindex; ?>"<?php } ?> name="<?php echo $field; ?>" value="<?php echo Utils::htmlspecialchars($caption); ?>">
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
