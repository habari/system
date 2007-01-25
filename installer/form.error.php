<?php
if (
  isset($form_errors) && 
  count($form_errors) > 0 && 
  isset($form_errors[$error_id])) {
?>
 <div class="error"><?php echo $form_errors[$error_id];?></div>
<?php }?>
