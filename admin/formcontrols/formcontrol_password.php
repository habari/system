<div class="<?php echo $class ?>"><label><?php echo $this->caption; ?><input type="password" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($outvalue); ?>"></label>
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
