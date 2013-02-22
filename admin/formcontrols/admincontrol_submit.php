<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="transparent <?php echo ($class) ? $class : ''?>" <?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="submit" name="<?php echo $field; ?>" class="button" value="<?php echo Utils::htmlspecialchars($caption); ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
