<div <?= $_attributes ?>>
	<ul class="tabcontrol tabs">
		<?php $ct =0;foreach($controls as $title => $tabcontent):$ct++;?><li><a href="#tab_<?= $_control->get_id() ?>_<?= $ct; ?>"><?= $title; ?></a></li><?php endforeach; ?>
	</ul>

	<?php $ct =0;foreach($controls as $tabcontent):$ct++;?>
		<div id="tab_<?= $_control->get_id() ?>_<?= $ct ?>" <?= $_template_attributes['tab_div'] ?>>
			<div <?= $_template_attributes['tab_div_inside'] ?>><?= $tabcontent ?></div>
		</div>
	<?php endforeach; ?>
</div>