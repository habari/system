<form
	id="<?php echo $id; ?>"
	method="post"
	action="<?php echo $action; ?>"
	class="<?php echo $class; ?>"
	<?php echo $onsubmit; ?>
>
<input type="hidden" name="FormUI" value="<?php echo $salted_name; ?>">
<?php echo $pre_out; ?>
<?php echo $controls; ?>
</form>
