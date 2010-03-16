<div class="container transparent <?php echo ($class) ? $class : ''?>" <?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="submit" name="<?php echo $field; ?>" class="button" value="<?php echo htmlspecialchars($caption, ENT_COMPAT, 'UTF-8'); ?>" <?php echo isset($tabindex) ? ' tabindex="' . $tabindex . '"' : ''?>>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
