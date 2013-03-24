<div <?= $_attributes ?>>
	<?php foreach($checkboxes as $checkbox_value => $checkbox_data): ?>
		<label for="<?= $checkbox_data['id'] ?>"><input name="<?= $_control->name; ?>[]" type="checkbox" value="<?= $checkbox_value ?>" <?= $checkbox_data['checked'] ?>><?= $checkbox_data['label'] ?></label>
	<?php endforeach; ?>
</div>