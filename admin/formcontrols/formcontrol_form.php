<form
	id="<?php echo $id; ?>"
	method="post"
	action="<?php echo $action; ?>"
	class="<?php echo $class; ?>"
	enctype="<?php echo $enctype; ?>"
	accept-charset="<?php echo $accept_charset; ?>"
	<?php echo $onsubmit; ?>
>
<?php if(isset($message) && $message != ''): ?>
<p><?php echo $message; ?></p>
<?php endif; ?>
<input type="hidden" name="FormUI" value="<?php echo $salted_name; ?>">
<?php echo $pre_out; ?>
<?php echo $controls; ?>
</form>
