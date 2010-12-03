<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div class="container pagesplitter" id="<?php echo $id; ?>">
	<ul class="tabcontrol tabs">
		<?php $ct =0;foreach($controls as $title => $tabcontent):$ct++;?><li><a href="#tab_<?php echo $id; ?>_<?php echo $ct; ?>"><?php echo $title; ?></a></li><?php endforeach; ?>
	</ul>

	<?php $ct =0;foreach($controls as $title => $tabcontent):$ct++;?>
		<div id="tab_<?php echo $id; ?>_<?php echo $ct; ?>" class="splitter">
			<div class="splitterinside"><?php echo $tabcontent; ?></div>
		</div>
	<?php endforeach; ?>
</div>
