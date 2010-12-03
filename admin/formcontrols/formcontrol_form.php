<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<form
	id="<?php echo $id; ?>"
	method="post"
	action="<?php echo $action; ?>"
	class="<?php echo $class; ?>"
	enctype="<?php echo $enctype; ?>"
	accept-charset="<?php echo $accept_charset; ?>"
	<?php echo $onsubmit; ?>
>
<?php if (isset($message) && $message != ''): ?>
<div class="form_message"><?php echo $message; ?></div>
<?php endif; ?>
<input type="hidden" name="FormUI" value="<?php echo $salted_name; ?>">
<?php echo $pre_out; ?>
<?php echo $controls; ?>
</form>
