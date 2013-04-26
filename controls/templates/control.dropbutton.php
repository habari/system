<ul <?= $_template_attributes['ul'] ?> >
	<?php
	$first = true;
	if(count($actions) > 0):
		foreach($actions as $action_id => $action): ?>
			<li>
			<?php if($first): $first = false; ?>
				<input <?= $_attributes ?>>
			<?php endif; ?>
		<a href="<?= $action['href'] ?>"><?= $action['caption'] ?></a></li>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>