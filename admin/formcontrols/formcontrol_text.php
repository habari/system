<div class="<?php echo $class ?>"><label><?php echo $this->caption; ?><input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>"></label>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
