<div <?= $_attributes ?>>
	<ul class="tabcontrol tabs">
		<?php $ct =0;foreach($controls as $title => $tabcontent):$ct++;?><li><a href="#tab_<?= $_control->get_id() ?>_<?= $ct; ?>"><?= $title; ?></a></li><?php endforeach; ?>
	</ul>

	<?php $ct =0;foreach($controls as $tabcontent):$ct++;?>
		<div id="tab_<?= $_control->get_id() ?>_<?= $ct ?>" class="splitter">
			<div class="splitterinside"><?= $tabcontent ?></div>
		</div>
	<?php endforeach; ?>
</div>