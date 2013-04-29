<?php
/**
 * @var array $_template_attributes
 * @var array $_attributes
 * @var array $actions
 */
?>
<div <?= $_template_attributes['div'] ?>>
	<input <?= $_attributes ?>>
	<?php $first = reset($actions); array_shift($actions); ?>
	<a href="<?= $first['href'] ?>" class="primary dropbutton_action"><?= $first['caption'] ?></a><?php if(count($actions) > 0): ?><a href="#" class="dropdown"><span class="arrow icon-circle-arrow-down"></span></a>
	<ul <?= $_template_attributes['ul'] ?> >
		<?php foreach($actions as $action_id => $action): ?>
			<li class="<?= $action_id ?>"><a href="<?= $action['href'] ?>" class="dropbutton_action"><?= $action['caption'] ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</div>
