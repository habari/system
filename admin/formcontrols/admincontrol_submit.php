<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="submit" name="<?php echo $field; ?>" class="button" value="<?php echo htmlspecialchars($caption); ?>">
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
