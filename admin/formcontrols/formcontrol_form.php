<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<form <?php
	$fixed = array(
		'method' => 'POST',
	);
	if($form->disabled) {
		$fixed['disabled'] = 'disabled';
	}
	echo $form->parameter_map(
		array(
			'class',
			'id',
			'action',
			'enctype',
			'accept-charset' => 'accept_charset',
			'onsubmit',
		),
		$fixed
	); ?>>
<?php if (isset($message) && $message != ''): ?>
<div class="form_message"><?php echo $message; ?></div>
<?php endif; ?>
<input type="hidden" name="FormUI" value="<?php echo $salted_name; ?>">
<?php echo $pre_out; ?>
<?php echo $controls; ?>
</form>
