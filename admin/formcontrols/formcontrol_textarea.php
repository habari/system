<div class="<?php echo $class ?>"><label><?php echo $this->caption; ?><textarea name="<?php echo $field; ?>"><?php echo htmlspecialchars($value); ?></textarea></label>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
