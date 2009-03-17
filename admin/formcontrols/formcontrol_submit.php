<div<?php echo ($class) ? ' class="' . $class . '"' : ''?><?php echo ($id) ? ' id="' . $id . '"' : ''?>><input type="submit" <?php if(isset($disabled) && $disabled) { ?>disabled <?php } ?>name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($caption); ?>">
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
