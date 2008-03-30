<div class="<?php echo $class; ?>">
<label><?php echo $this->caption; ?>
<input type="checkbox" name="<?php echo $field; ?>" value="1" <?php echo $value ? 'checked' : ''; ?>></label>
<input type="hidden" name="<?php echo $field; ?>_submitted" value="1" >
<?php if($message != '') : ?>
<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
