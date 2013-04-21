<form <?= $_attributes ?>>
	<?= \Habari\Utils::setup_wsse() ?>
	<input type="hidden" name="_form_id" value="<?= $_control_id ?>">
	<?= $content ?>
</form>