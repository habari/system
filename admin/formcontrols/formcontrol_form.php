<form
	id="<?php echo $id; ?>"
	method="post"
	action="<?php echo $action; ?>"
	class="<?php echo $class; ?>"
	enctype="<?php echo $enctype; ?>"
	<?php echo $onsubmit; ?>
>
<?php if(isset($message) && $message != ''): ?>
<p><?php echo $message; ?></p>
<?php endif; ?>
<div><input type="hidden" name="FormUI" value="<?php echo $salted_name; ?>"></div>
<?php echo $pre_out; ?>
<?php echo $controls; ?>
</form>
