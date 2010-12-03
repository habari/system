<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="button" <?php if (isset($disabled) && $disabled) { ?>disabled <?php } 	if ( isset( $tabindex ) ) { ?> tabindex="<?php echo $tabindex; ?>"<?php } ?>	name="<?php echo $field; ?>" value="<?php echo Utils::htmlspecialchars($caption); ?>"
<?php if (isset($onclick) && $onclick != ''): ?>
<script type="text/javascript">function onclick_<?php echo Utils::slugify($id, '_') ?>() {
<?php echo $onclick; ?>
}</script>
<?php endif; ?>
>
<?php if ($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
